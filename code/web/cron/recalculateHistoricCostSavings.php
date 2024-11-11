<?php
/**
 * Recalculates cost savings for all reading history entries from the command line
 * First parameter - server name
 * Second parameter - background process ID (optional)
 * Third parameter - format (optional)
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
			$backgroundProcess->endProcess('Error, attempted to restart previously completed background process');
			die();
		}
	}
}
if ($argc > 3) {
	$format = $argv[3];
}

require_once ROOT_DIR . '/sys/ReplacementCost.php';
$replacementCosts = ReplacementCost::getReplacementCostsByFormat();

require_once ROOT_DIR . '/sys/ReadingHistoryEntry.php';
$readingHistoryEntry = new ReadingHistoryEntry();
if (!empty($format)) {
	$readingHistoryEntry->format = $format;
}
$numEntriesToUpdate = $readingHistoryEntry->count();
if (!is_null($backgroundProcess)) { $backgroundProcess->addNote("Updating $numEntriesToUpdate reading history entries"); }

$readingHistoryEntry->find();
$numUpdated = 0;
$loggedZeroCostFormats = [];

//Recalculate all reading history entries
while ($readingHistoryEntry->fetch()) {
	$lowerFormat = strtolower($readingHistoryEntry->format);
	if (array_key_exists($lowerFormat, $replacementCosts)) {
		if ($replacementCosts[$lowerFormat] > 0) {
			//Update the costSavings for the reading history entry and update the total cost savings for the user
			$readingHistoryEntryToUpdate = new ReadingHistoryEntry();
			$readingHistoryEntryToUpdate->id = $readingHistoryEntry->id;
			if ($readingHistoryEntryToUpdate->find(true)) {
				$readingHistoryEntryToUpdate->costSavings = $replacementCosts[$lowerFormat];
				$readingHistoryEntryToUpdate->update();
				$numUpdated++;
			}
		}
	}else{
		if (!array_key_exists($lowerFormat, $loggedZeroCostFormats)) {
			if (!is_null($backgroundProcess)) { $backgroundProcess->addNote("Skipping $readingHistoryEntry->format because no replacement cost was specified."); }
			$loggedZeroCostFormats[$lowerFormat] = $lowerFormat;
		}
	}
}

//Now get the total cost savings for users that have checked something out in the format
$readingHistoryEntry = new ReadingHistoryEntry();
if (!empty($format)) {
	$readingHistoryEntry->format = $format;
}
$readingHistoryEntry->selectAdd();
$readingHistoryEntry->selectAdd("DISTINCT userId as userId");

$numUsersUpdated = 0;
$readingHistoryEntry->find();
while ($readingHistoryEntry->fetch()) {
	$userToUpdate = new User();
	$userToUpdate->id = $readingHistoryEntry->userId;
	if ($userToUpdate->find(true)) {
		$tmpReadingHistory = new ReadingHistoryEntry();
		$tmpReadingHistory->userId = $userToUpdate->id;
		$tmpReadingHistory->selectAdd();
		$tmpReadingHistory->selectAdd("SUM(costSavings) as costSavings");
		if ($tmpReadingHistory->costSavings != $userToUpdate->totalCostSavings) {
			if ($tmpReadingHistory->find(true)) {
				$userToUpdate->__set('totalCostSavings', $tmpReadingHistory->costSavings);
			} else {
				$userToUpdate->__set('totalCostSavings', 0);
			}
			$userToUpdate->update();
		}
	}
	$numUsersUpdated++;
}

if (!is_null($backgroundProcess)) {
	$backgroundProcess->addNote(translate([
		'text' => 'Updated %1% historic cost savings.',
		1 => $numUpdated,
		'isAdminFacing' => true
	]));
	$endNote = translate([
		'text' => 'Updated total cost savings for %1% users.',
		1 => $numUsersUpdated,
		'isAdminFacing' => true
	]);
	$backgroundProcess->endProcess($endNote);
}
