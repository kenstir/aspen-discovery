<?php

require_once ROOT_DIR . '/services/Admin/Admin.php';

class Testing_GenerateReadingHistory extends Admin_Admin {
	function launch() : void {
		global $interface;

		if (isset($_REQUEST['generateReadingHistory'])) {
			set_time_limit(0);
			$results = [
				'success' => false,
			];
			$generationType = $_REQUEST['generationType'];
			$userIdsToProcess = [];
			if ($generationType == '1') {
				//All test users with no reading history
				$user = new User();
				$user->isLocalTestUser = 1;
				$user->find();
				while ($user->fetch()) {
					require_once ROOT_DIR . '/sys/ReadingHistoryEntry.php';
					$readingHistoryDB = new ReadingHistoryEntry();
					$readingHistoryDB->userId = $user->id;
					$readingHistoryDB->whereAdd('deleted = 0');
					if ($readingHistoryDB->count() == 0){
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
				$patronBarcode = $_REQUEST['patronBarcode'] ?? '';
				if (empty($patronBarcode)) {
					$updateMessage = 'No patron barcode was supplied';
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
			$updateMessage = '';
			foreach ($userIdsToProcess as $userId) {
				$user = new User();
				$user->id = $userId;
				if ($user->find(true)) {
					if ($user->trackReadingHistory == 0) {
						$user->trackReadingHistory = 1;
					}
					require_once ROOT_DIR . '/sys/ReadingHistoryEntry.php';
					global $aspen_db;
					$getGroupedWorkIdStmt = $aspen_db->prepare("SELECT * FROM grouped_work AS t1 JOIN (SELECT id FROM grouped_work ORDER BY RAND() LIMIT 1) as t2 ON t1.id=t2.id", [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]);

					$numberOfYears = $_REQUEST['numberOfYears'] ?? 1;
					$minEntriesPerMonth = $_REQUEST['minEntriesPerMonth'] ?? 0;
					$maxEntriesPerMonth = $_REQUEST['maxEntriesPerMonth'] ?? 10;
					$clearExistingReadingHistory = isset($_REQUEST['clearExistingReadingHistory']) && $_REQUEST['clearExistingReadingHistory'] == 'on';
					if ($minEntriesPerMonth > $maxEntriesPerMonth) {
						$tmp = $minEntriesPerMonth;
						$minEntriesPerMonth = $maxEntriesPerMonth;
						$maxEntriesPerMonth = $tmp;
					}
					if ($clearExistingReadingHistory) {
						$readingHistoryDB = new ReadingHistoryEntry();
						$readingHistoryDB->userId = $user->id;
						$numDeleted = $readingHistoryDB->delete(true);
						$user->totalCostSavings = 0;
						$updateMessage .= "Removed $numDeleted Reading History Entries for {$user->getDisplayName()}.<br/>";
					}

					$currentYear = date('Y', time());
					$numEntriesGenerated = 0;
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
									$readingHistoryEntry = new ReadingHistoryEntry();
									$readingHistoryEntry->userId = $user->id;
									$readingHistoryEntry->groupedWorkPermanentId =$groupedWork->permanent_id;
									$readingHistoryEntry->title = $groupedWork->full_title;
									$readingHistoryEntry->author = $groupedWork->author;
									//Grab a record at random from the grouped work
									require_once ROOT_DIR . '/sys/Grouping/GroupedWorkRecord.php';
									$groupedWorkRecord = new GroupedWorkRecord();
									$groupedWorkRecord->groupedWorkId = $groupedWork->id;
									$groupedWorkRecord->orderBy('RAND()');
									$groupedWorkRecord->limit(0, 1);
									if ($groupedWorkRecord->find(true)) {
										$readingHistoryEntry->source = $sources[$groupedWorkRecord->sourceId];
										$readingHistoryEntry->sourceId = $groupedWorkRecord->recordIdentifier;
										if (array_key_exists($groupedWorkRecord->formatId, $formats)) {
											$readingHistoryEntry->format = $formats[$groupedWorkRecord->formatId];
										}else{
											//Hardcode something
											$readingHistoryEntry->format = 'Book';
										}
									}else{
										//Hardcode a format
										$readingHistoryEntry->format = 'Book';
									}
									$checkoutDay = rand(0, 28);
									$readingHistoryEntry->checkOutDate = strtotime("$year-$month-$checkoutDay");
									$readingHistoryEntry->checkInDate = $readingHistoryEntry->checkOutDate;
									$formatLower = strtolower($readingHistoryEntry->format);
									if (array_key_exists($formatLower, $replacementCosts)) {
										$readingHistoryEntry->costSavings = $replacementCosts[$formatLower];
										$user->totalCostSavings += $readingHistoryEntry->costSavings;
									}
									$readingHistoryEntry->insert();
									$numEntriesGenerated++;
									$getGroupedWorkIdStmt->closeCursor();
								}
							}
						}
					}
					$user->update();
					$results['success'] = true;
					$updateMessage .= "Successfully generated $numEntriesGenerated reading history entries for user {$user->getDisplayName()}.<br/>";
					$numProcessed++;
				}else{
					$updateMessage = "Could not find user $userId<br/>";
				}
			}
			$updateMessage = "Processed $numProcessed users.<br/>" . $updateMessage;
			$results['message'] = $updateMessage;

			$interface->assign('results', $results);
		}

		$this->display('generateReadingHistory.tpl', 'Generate Reading History', 'Greenhouse/greenhouse-sidebar.tpl');
	}

	function getBreadcrumbs(): array {
		$breadcrumbs = [];
		$breadcrumbs[] = new Breadcrumb('/Greenhouse/Home', 'Greenhouse Home');
		$breadcrumbs[] = new Breadcrumb('/Testing/GenerateReadingHistory', 'Generate Reading History', true);
		return $breadcrumbs;
	}

	function canView() : bool {
		if (UserAccount::isLoggedIn()) {
			if (UserAccount::getActiveUserObj()->isAspenAdminUser()) {
				return true;
			}
		}
		return false;
	}

	function getActiveAdminSection(): string {
		return 'greenhouse';
	}
}