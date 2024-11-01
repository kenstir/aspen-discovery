<?php
/**
 * Recalculates cost savings for any reading history entry with a zero cost from the command line
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
$readingHistoryEntry->costSavings = 0;
if (!empty($format)) {
	$readingHistoryEntry->format = $format;
}
$numEntriesToUpdate = $readingHistoryEntry->count();
if (!is_null($backgroundProcess)) { $backgroundProcess->addNote("Updating $numEntriesToUpdate reading history entries"); }

$readingHistoryEntry->find();
$numUpdated = 0;
$loggedZeroCostFormats = [];

//Recalculate reading history entries
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

				$userToUpdate = new User();
				$userToUpdate->id = $readingHistoryEntryToUpdate->userId;
				if ($userToUpdate->find(true)) {
					$userToUpdate->totalCostSavings += $replacementCosts[$lowerFormat];
					$userToUpdate->update();
				}
				$numUpdated++;
			}
		}else{
			if (!array_key_exists($lowerFormat, $loggedZeroCostFormats)) {
				if (!is_null($backgroundProcess)) { $backgroundProcess->addNote("Skipping $readingHistoryEntry->format because no replacement cost was specified."); }
				$loggedZeroCostFormats[$lowerFormat] = $lowerFormat;
			}
		}
	}
}

if (!is_null($backgroundProcess)) {
	$backgroundProcess->addNote(translate([
		'text' => 'Updated %1% historic cost savings that were previously 0.',
		1 => $numUpdated,
		'isAdminFacing' => true
	]));
	$backgroundProcess->isRunning = false;
	$backgroundProcess->endTime = time();
	$backgroundProcess->update();
}