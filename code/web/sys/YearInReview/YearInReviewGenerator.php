<?php

function generateYearInReview(User $patron) : void {
	require_once ROOT_DIR . '/sys/YearInReview/UserYearInReview.php';
	require_once ROOT_DIR . '/sys/YearInReview/YearInReviewSetting.php';
	require_once ROOT_DIR . '/sys/LibraryLocation/LibraryYearInReview.php';

	if ($patron->hasIlsConnection()) {
		$userIsStaff = $patron->isStaff();

		global $library;
		$yearInReviewSetting  = new YearInReviewSetting();
		if ($userIsStaff) {
			$yearInReviewSetting->whereAdd('staffStartDate <= ' . time());
		}else{
			$yearInReviewSetting->whereAdd('patronStartDate <= ' . time());
		}
		$yearInReviewSetting->whereAdd('endDate >= ' . time());

		$libraryYearInReview = new LibraryYearInReview();
		$libraryYearInReview->libraryId = $library->libraryId;
		$yearInReviewSetting->joinAdd($libraryYearInReview, 'INNER', 'libraryYearInReview', 'id', 'yearInReviewId');

		if ($yearInReviewSetting->find(true)) {
			//We have valid settings
			$userYearInReview = new UserYearInReview();
			$userYearInReview->userId = $patron->id;
			$userYearInReview->settingId = $yearInReviewSetting->id;
			if (!$userYearInReview->find(true)) {
				//We have not created year in review data for the user
				$readingHistorySize = $patron->getReadingHistorySizeForYear($yearInReviewSetting->year);

				if ($readingHistorySize >= 5) {
					$readingHistorySummary = $patron->getReadingHistorySummaryForYear($yearInReviewSetting->year);

					$userYearInReview->wrappedActive = true;
					$slidesToShow = [];
					//Calculate information for use in the display of Year in Review
					if ($yearInReviewSetting->year == 2024) {
						$yearInReviewData = new stdClass();
						$yearInReviewData->userData = [];

						//Always show the first slide (intro)
						$slidesToShow[] = 1;

						//Borrower Experience
						$yearInReviewData->userData['totalCheckouts'] = $readingHistorySummary->totalYearlyCheckouts;
						if (!empty($readingHistorySummary->yearlyCostSavings)) {
							$yearInReviewData->userData['yearlyCostSavings'] = $readingHistorySummary->yearlyCostSavings;
							$slidesToShow[] = 2;
						}else{
							//Show the version with just the number of checkouts
							$slidesToShow[] = 3;
						}

						//Hot Month / Busy Months
						if ($readingHistorySummary->maxMonthlyCheckouts - $readingHistorySummary->averageCheckouts > 2) {
							$yearInReviewData->userData['topMonth'] = date("F", strtotime($readingHistorySummary->topMonth));
							$slidesToShow[] = 4;
						}else{
							$yearInReviewData->userData['averageCheckouts'] = $readingHistorySummary->averageCheckouts;
							$slidesToShow[] = 5;
						}

						//Top formats
						if (!empty($readingHistorySummary->topFormats)) {
							$yearInReviewData->userData['topFormats'] = join("\n", $readingHistorySummary->topFormats);
							$formatNames = array_values($readingHistorySummary->topFormats);
							$yearInReviewData->userData['topFormat1'] = $formatNames[0];
							$yearInReviewData->userData['topFormat2'] = count($formatNames) > 1 ?  $formatNames[1] : '';
							$yearInReviewData->userData['topFormat3'] = count($formatNames) > 2 ?  $formatNames[2] : '';
							$slidesToShow[] = 6;
						}

						//Top genres
						if (!empty($readingHistorySummary->topGenres)) {
							if (count($readingHistorySummary->topGenres) == 1){
								$yearInReviewData->userData['topGenres'] = $readingHistorySummary->topGenres[0];
							}elseif (count($readingHistorySummary->topGenres) == 2){
								$yearInReviewData->userData['topGenres'] = join("\n", [
									$readingHistorySummary->topGenres[0],
									'and',
									$readingHistorySummary->topGenres[1]
								]);
							}elseif(count($readingHistorySummary->topGenres) == 3){
								$yearInReviewData->userData['topGenres'] = join("\n", [
									$readingHistorySummary->topGenres[0],
									$readingHistorySummary->topGenres[1],
									'and',
									$readingHistorySummary->topGenres[2]
								]);
							}
							$slidesToShow[] = 7;
						}

						//Top author
						if (!empty($readingHistorySummary->topAuthor)) {
							$yearInReviewData->userData['topAuthor'] = $readingHistorySummary->topAuthor;
							$slidesToShow[] = 8;
						}

						//Top series
						if ($readingHistorySummary->topSeries) {
							$yearInReviewData->userData['topSeries'] = join(" and ", $readingHistorySummary->topSeries);
							$slidesToShow[] = 9;
						}

						//Recommendations
						if ($readingHistorySummary->recommendations) {
							$yearInReviewData->userData['recommendations'] = join("\n\n", $readingHistorySummary->recommendations);
							$slidesToShow[] = 10;
						}

						//Always show the last slide
						$slidesToShow[] = 11;

						$yearInReviewData->numSlidesToShow = count($slidesToShow);
						$yearInReviewData->slidesToShow = $slidesToShow;
						$userYearInReview->wrappedResults = json_encode($yearInReviewData);
					}
				}else{
					$userYearInReview->wrappedActive = false;
					$userYearInReview->wrappedResults = '';
				}
				$userYearInReview->wrappedViewed = 0;

				$userYearInReview->insert();

				if ($userYearInReview->wrappedActive) {
					//Create a user message for the user
					require_once ROOT_DIR . '/sys/Account/UserMessage.php';
					//First check for and dismiss existing Year in Review messages
					$userMessage = new UserMessage();
					$userMessage->userId = UserAccount::getActiveUserId();
					$userMessage->messageType = 'yearInReview';
					$userMessage->isDismissed = 0;
					$userMessage->find();
					while ($userMessage->fetch()) {
						$userMessage->isDismissed = 1;
						$userMessage->update();
					}
					$userMessage = new UserMessage();
					$userMessage->userId = UserAccount::getActiveUserId();
					$userMessage->message = $yearInReviewSetting->getTextBlockTranslation('promoMessage', $patron->interfaceLanguage);
					$userMessage->messageType = 'yearInReview';
					$userMessage->relatedObjectId = $userYearInReview->id;
					$userMessage->action1 = "return AspenDiscovery.Account.viewYearInReview(1)";
					$userMessage->action1Title = 'Yes';
					$userMessage->isDismissed = 0;
					$userMessage->insert();
				}
			}
		}
	}
}
