<?php
/**
 * Generates test materials request data for test users.
 * Parameters (positional)
 * 1) server name
 * 2) background process ID
 * 3) generation type (1 = Test users with no materials requests, 2 = All test users, 3 = Specified patron)
 * 4) Number of years to generate
 * 5) Minimum entries per month
 * 6) Maximum entries per month
 * 7) Clear Existing Reading History
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../bootstrap_aspen.php';

set_time_limit(0);

require_once ROOT_DIR . '/sys/Administration/BackgroundProcess.php';
$backgroundProcess = null;
if ($argc > 2) {
	$backgroundProcessId = $argv[2];
	$backgroundProcess = new BackgroundProcess();
	$backgroundProcess->id = $backgroundProcessId;
	if (!$backgroundProcess->find(true)) {
		$backgroundProcess = null;
		echo ("Could not find the specified background process\n");
		die();
	}else{
		if (!$backgroundProcess->isRunning) {
			$backgroundProcess->addNote('Error, attempted to restart previously completed background process');
			die();
		}
	}
}

if ($argc > 3) {
	$generationType = $argv[3];
}else{
	$backgroundProcess->endProcess('Generation Type was not provided');
	die();
}

//Load additional parameters
if ($argc > 4) {
	$numberOfYears = (int)$argv[4];
}else{
	$backgroundProcess->endProcess('Number of years was not provided');
	die();
}
if ($argc > 5) {
	$minEntriesPerMonth = (int)$argv[5];
}else{
	$backgroundProcess->endProcess('Min entries per month was not provided');
	die();
}
if ($argc > 6) {
	$maxEntriesPerMonth = (int)$argv[6];
}else{
	$backgroundProcess->endProcess('Max entries per month was not provided');
	die();
}
if ($argc > 7) {
	$clearExistingMaterialRequests = (bool)$argv[7];
}else{
	$backgroundProcess->endProcess('Whether existing material requests should be cleared was not provided');
	die();
}

$patronBarcode = '';
if ($generationType == 3) {
	if ($argc > 8) {
		$patronBarcode = $argv[8];
	}else{
		$backgroundProcess->endProcess('No patron barcode was supplied');
		die();
	}
}

$userIdsToProcess = [];
if ($generationType == '1') {
	//All test users with no reading history
	$user = new User();
	$user->isLocalTestUser = 1;
	$user->find();
	while ($user->fetch()) {
		require_once ROOT_DIR . '/sys/MaterialsRequests/MaterialsRequest.php';
		$materialsRequestDB = new MaterialsRequest();
		$materialsRequestDB->createdBy = $user->id;
		if ($materialsRequestDB->count() == 0){
			$userIdsToProcess[] = $user->id;
		}
	}
}elseif ($generationType == '2') {
	//All test users
	$user = new User();
	$user->isLocalTestUser = 1;
	$userIdsToProcess = $user->fetchAll('id', 'id');
}else{
	//Specified user
	if (empty($patronBarcode)) {
		if (!is_null($backgroundProcess)) { $backgroundProcess->endProcess('No patron barcode was supplied');}
		die();
	}else{
		$user = new User();
		$user->ils_barcode = $patronBarcode;
		if ($user->find(true)) {
			$userIdsToProcess[] = $user->id;
		}
	}
}

//Load Record Sources
require_once ROOT_DIR . '/sys/Indexing/IndexedRecordSource.php';
$recordSource = new IndexedRecordSource();
$recordSource->find();
$sources = [];
while ($recordSource->fetch()) {
	if (empty($recordSource->subSource)) {
		$sources[$recordSource->id] = $recordSource->source;
	}else{
		$sources[$recordSource->id] = $recordSource->source . ':' . $recordSource->subSource;
	}
}
if (!is_null($backgroundProcess)) { $backgroundProcess->addNote('Loaded indexed record sources');}

require_once ROOT_DIR . '/sys/Indexing/IndexedFormat.php';
$format = new IndexedFormat();
$formats = $format->fetchAll('id', 'format');

require_once ROOT_DIR . '/sys/ReplacementCost.php';
$replacementCosts = ReplacementCost::getReplacementCostsByFormat();

//Start a search for random grouped works
require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
$groupedWork = new GroupedWork();
$groupedWork->orderBy('RAND()');
$groupedWork->find();

$numProcessed = 0;
$numUsersSkipped = 0;
if (!is_null($backgroundProcess)) {
	$backgroundProcess->addNote('Processing ' . count($userIdsToProcess) . ' Users');
}

$defaultStatusesByLibrary = [];
foreach ($userIdsToProcess as $userId) {
	$user = new User();
	$user->id = $userId;
	if ($user->find(true)) {
		require_once ROOT_DIR . '/sys/MaterialsRequests/MaterialsRequest.php';
		global $aspen_db;
		$getGroupedWorkIdStmt = $aspen_db->prepare("SELECT * FROM grouped_work AS t1 JOIN (SELECT id FROM grouped_work ORDER BY RAND() LIMIT 1) as t2 ON t1.id=t2.id", [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]);

		if ($minEntriesPerMonth > $maxEntriesPerMonth) {
			$tmp = $minEntriesPerMonth;
			$minEntriesPerMonth = $maxEntriesPerMonth;
			$maxEntriesPerMonth = $tmp;
		}
		if ($clearExistingMaterialRequests) {
			$materialsRequestDB = new MaterialsRequest();
			$materialsRequestDB->createdBy = $user->id;
			$numDeleted = $materialsRequestDB->delete(true);
			if (!is_null($backgroundProcess)) {
				$backgroundProcess->addNote("Removed $numDeleted Material Requests for {$user->getDisplayName()}.");
			}
		}

		$currentYear = date('Y', time());
		$numEntriesGenerated = 0;

		$homeLibrary = $user->getHomeLibrary();

		if (!array_key_exists($homeLibrary->libraryId, $defaultStatusesByLibrary)){
			require_once ROOT_DIR . '/sys/MaterialsRequests/MaterialsRequestStatus.php';
			$defaultStatus = new MaterialsRequestStatus();
			$defaultStatus->isDefault = 1;
			$defaultStatus->libraryId = $homeLibrary->libraryId;
			if (!$defaultStatus->find(true)) {
				$defaultStatusesByLibrary[$homeLibrary->libraryId] = -1;
			}else{
				$defaultStatusesByLibrary[$homeLibrary->libraryId] = $defaultStatus->id;
			}
		}

		if ($defaultStatusesByLibrary[$homeLibrary->libraryId] == -1) {
			$numUsersSkipped++;
		}else{
			//Loop through the years to generate
			for ($year = $currentYear; $year >= ($currentYear - $numberOfYears + 1); $year--) {
				//Loop through each month
				$maxMonth = 12;
				if ($year == $currentYear) {
					//Restrict the maximum month to this month
					$maxMonth = date('m', time());
				}
				for ($month = 1; $month <= $maxMonth; $month++) {
					$numEntriesToGenerate = rand($minEntriesPerMonth, $maxEntriesPerMonth);
					for ($entryNumber = 0; $entryNumber < $numEntriesToGenerate; $entryNumber++) {
						if ($groupedWork->fetch()){
							$materialRequest = new MaterialsRequest();
							$materialRequest->createdBy = $user->id;
							$materialRequest->libraryId = $homeLibrary->libraryId;
							$materialRequest->title = $groupedWork->full_title;
							$materialRequest->author = $groupedWork->author;
							//Grab a record at random from the grouped work
							require_once ROOT_DIR . '/sys/Grouping/GroupedWorkRecord.php';
							$groupedWorkRecord = new GroupedWorkRecord();
							$groupedWorkRecord->groupedWorkId = $groupedWork->id;
							$groupedWorkRecord->orderBy('RAND()');
							$groupedWorkRecord->limit(0, 1);
							if ($groupedWorkRecord->find(true)) {
								if (array_key_exists($groupedWorkRecord->formatId, $formats)) {
									$materialRequest->format = $formats[$groupedWorkRecord->formatId];
								}else{
									//Hardcode something
									$materialRequest->format = 'Book';
								}
							}else{
								//Hardcode a format
								$materialRequest->format = 'Book';
							}
							$checkoutDay = rand(0, 28);
							$materialRequest->dateCreated = strtotime("$year-$month-$checkoutDay");
							$materialRequest->placeHoldWhenAvailable = true;

							$materialRequest->status = $defaultStatusesByLibrary[$homeLibrary->libraryId];
							$materialRequest->insert();
							$numEntriesGenerated++;
						}
					}
				}
			}
		}
		$getGroupedWorkIdStmt->closeCursor();

		$results['success'] = true;
		if (!is_null($backgroundProcess)) {
			$backgroundProcess->addNote("Successfully generated $numEntriesGenerated material requests for user {$user->getDisplayName()}.");
		}
		$numProcessed++;
	}else{
		if (!is_null($backgroundProcess)) {
			$backgroundProcess->addNote("Could not find user $userId");
		}
	}
}
if (!is_null($backgroundProcess)) {
	$backgroundProcess->endProcess("Processed $numProcessed users, skipped $numUsersSkipped that did not have a valid default status for their library.");
}
