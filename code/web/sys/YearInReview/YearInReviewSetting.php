<?php /** @noinspection PhpMissingFieldTypeInspection */
require_once ROOT_DIR . '/sys/LibraryLocation/LibraryYearInReview.php';

class YearInReviewSetting extends DataObject {
	public $__table = 'year_in_review_settings';
	public $id;
	public $name;
	public $year;
	public $style;
	/** @noinspection PhpUnused */
	public $staffStartDate;
	/** @noinspection PhpUnused */
	public $patronStartDate;
	public $endDate;

	/** @noinspection PhpUnused */
	protected $_promoMessage;
	protected $_libraries;

	public function getNumericColumnNames(): array {
		return [
			'year',
			'staffStartDate',
			'patronStartDate',
		];
	}

	/** @noinspection PhpUnusedParameterInspection */
	static function getObjectStructure($context = ''): array {
		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All System Messages'));
		return [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'name' => [
				'property' => 'name',
				'type' => 'text',
				'label' => 'Name',
				'description' => 'The name of the Year In review Settings',
			],
			'year' => [
				'property' => 'year',
				'type' => 'enum',
				'label' => 'Year',
				'values' => [
					2024 => '2024'
				],
				'description' => 'The year for the Year in review',
			],
			//TODO: Next year, these should load dynamically based on the available styles for the year
			'style' => [
				'property' => 'style',
				'type' => 'enum',
				'label' => 'Style',
				'values' => [
					'0' => 'Modern',
					'1' => 'Festive'
				],
				'description' => 'The style for the Year in review',
			],
			'promoMessage' => [
				'property' => 'promoMessage',
				'type' => 'translatableTextBlock',
				'label' => 'Promo Message To Display to the patron',
				'description' => 'Provide information about the Year In Review so patrons know the functionality exists.',
				'defaultTextFile' => 'YearInReview_promoMessage.MD',
				'hideInLists' => true,
			],
			'staffStartDate' => [
				'property' => 'staffStartDate',
				'type' => 'timestamp',
				'label' => 'Start Date to Show for Staff',
				'description' => 'The first date the year in review should be shown to staff',
				'required' => true,
				'unsetLabel' => 'No start date',
			],
			'patronStartDate' => [
				'property' => 'patronStartDate',
				'type' => 'timestamp',
				'label' => 'Start Date to Show for Patrons',
				'description' => 'The first date the year in review should be shown to patrons',
				'required' => true,
				'unsetLabel' => 'No end date',
			],
			'endDate' => [
				'property' => 'endDate',
				'type' => 'timestamp',
				'label' => 'End Date to Show',
				'description' => 'The last date to show year in review',
				'readOnly' => true,
				'unsetLabel' => 'No end date',
			],
			'libraries' => [
				'property' => 'libraries',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Libraries',
				'description' => 'Define libraries that see this system message',
				'values' => $libraryList,
			],
		];
	}

	public function __get($name) {
		if ($name == "libraries") {
			return $this->getLibraries();
		} else {
			return parent::__get($name);
		}
	}

	public function getLibraries(): ?array {
		if (!isset($this->_libraries) && $this->id) {
			$this->_libraries = [];
			$obj = new LibraryYearInReview();
			$obj->yearInReviewId = $this->id;
			$obj->find();
			while ($obj->fetch()) {
				$this->_libraries[$obj->libraryId] = $obj->libraryId;
			}
		}
		return $this->_libraries;
	}

	public function __set($name, $value) {
		if ($name == "libraries") {
			$this->_libraries = $value;
		} else {
			parent::__set($name, $value);
		}
	}

	/**
	 * Override the update functionality to save related objects
	 *
	 * @see DB/DB_DataObject::update()
	 */
	public function update($context = '') {
		$this->__set('endDate', strtotime($this->year + 1  . '-02-01'));
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveLibraries();
			$this->saveTextBlockTranslations('promoMessage');
		}
		return $ret;
	}

	public function insert($context = '') {
		$this->__set('endDate', strtotime($this->year + 1  . '-02-01'));
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveLibraries();
			$this->saveTextBlockTranslations('promoMessage');
		}
		return $ret;
	}

	public function delete($useWhere = false) : int {
		$ret = parent::delete($useWhere);
		if ($ret && !empty($this->id)) {
			$libraryYearInReview = new LibraryYearInReview();
			$libraryYearInReview->yearInReviewId = $this->id;
			$libraryYearInReview->delete(true);
		}
		return $ret;
	}

	public function saveLibraries() : void {
		if (isset ($this->_libraries) && is_array($this->_libraries)) {
			$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer Year in Review for All Libraries'));
			foreach ($libraryList as $libraryId => $displayName) {
				$obj = new LibraryYearInReview();
				$obj->yearInReviewId = $this->id;
				$obj->libraryId = $libraryId;
				if (in_array($libraryId, $this->_libraries)) {
					if (!$obj->find(true)) {
						$obj->insert();
					}
				} else {
					if ($obj->find(true)) {
						$obj->delete();
					}
				}
			}
		}
	}

	public function getSlide(User $patron, int|string $slideNumber) : array {
		$result = [
			'success' => false,
			'title' => translate([
				'text' => 'Error',
				'isPublicFacing' => true,
			]),
			'message' => translate([
				'text' => 'Unknown error loading year in review slide.',
				'isPublicFacing' => true,
			]),
		];

		//Load slide configuration for the year
		$userYearInResults = $patron->getYearInReviewResults();
		if ($userYearInResults !== false) {
			$style = (isset($userYearInResults->activeStyle) && is_numeric($userYearInResults->activeStyle)) ? $userYearInResults->activeStyle : $this->style;
			$configurationFile = ROOT_DIR . "/year_in_review/{$this->year}_$style.json";
			if (file_exists($configurationFile)) {
				$slideConfiguration = json_decode(file_get_contents($configurationFile));

				if ($slideNumber > 0 && $slideNumber <= $userYearInResults->numSlidesToShow) {
					$slideIndex = $userYearInResults->slidesToShow[$slideNumber - 1];
					$slideInfo = $slideConfiguration->slides[$slideIndex - 1];
					$result['success'] = true;
					$result['title'] = translate([
						'text' => $slideInfo->title,
						'isPublicFacing' => true,
					]);

					foreach ($slideInfo->overlay_text as $overlayText) {
						foreach ($userYearInResults->userData as $field => $value) {
							$overlayText->text = str_replace("{" . $field . "}", $value, $overlayText->text);
						}
					}

					$result['slideConfiguration'] = $slideInfo;
					$result['numSlidesToShow'] = $userYearInResults->numSlidesToShow;
					$result['modalBody'] = $this->formatSlide($slideInfo, $slideNumber);

					$modalButtons = '';
					if ($slideNumber > 1) {
						$modalButtons .= '<button type="button" class="btn btn-default" onclick="return AspenDiscovery.Account.viewYearInReview(' . $slideNumber - 1 . ')">' . translate([
								'text' => 'Previous',
								'isPublicFacing' => true,
								'inAttribute' => true,
							]) . '</button>';
					}
					if ($slideNumber < $userYearInResults->numSlidesToShow) {
						$modalButtons .= '<button type="button" class="btn btn-primary" onclick="return AspenDiscovery.Account.viewYearInReview(' . $slideNumber + 1 . ')">' . translate([
								'text' => 'Next',
								'isPublicFacing' => true,
								'inAttribute' => true,
							]) . '</button>';
					}
					$result['modalButtons'] = $modalButtons;
				} else {
					$result['message'] = translate([
						'text' => 'Invalid slide number',
						'isPublicFacing' => true,
					]);
				}
			}else{
				$result['message'] = translate([
					'text' => 'Unable to find year in review configuration file',
					'isPublicFacing' => true,
				]);
			}
		}else{
			$result['message'] = translate([
				'text' => 'Unable to find year in review data',
				'isPublicFacing' => true,
			]);
		}

		return $result;
	}

	private function formatSlide(stdClass $slideInfo, int $slideNumber) : string {
		global $interface;
		$interface->assign('slideNumber', $slideNumber);
		$interface->assign('slideInfo', $slideInfo);
		return $interface->fetch('YearInReview/slide.tpl');
	}

	public function getSlideImage(User $patron, int|string $slideNumber) : bool {
		//Load slide configuration for the year
		$gotImage = true;
		$userYearInResults = $patron->getYearInReviewResults();
		if ($userYearInResults !== false) {
			$style = (isset($userYearInResults->activeStyle) && is_numeric($userYearInResults->activeStyle)) ? $userYearInResults->activeStyle : $this->style;
			$configurationFile = ROOT_DIR . "/year_in_review/{$this->year}_$style.json";
			if (file_exists($configurationFile)) {
				$slideConfiguration = json_decode(file_get_contents($configurationFile));

				if ($slideNumber > 0 && $slideNumber <= $userYearInResults->numSlidesToShow) {
					$slideIndex = $userYearInResults->slidesToShow[$slideNumber - 1];
					$slideInfo = $slideConfiguration->slides[$slideIndex - 1];

					foreach ($slideInfo->overlay_text as $overlayText) {
						foreach ($userYearInResults->userData as $field => $value) {
							$overlayText->text = str_replace("{" . $field . "}", $value, $overlayText->text);
						}
					}

					$gotImage = $this->createSlideImage($slideInfo);
				}
			}
		}

		return $gotImage;
	}

	private function createSlideImage(stdClass $slideInfo) : ?string {
		$gotImage = false;
		if (count($slideInfo->overlay_text) == 0) {
			//This slide is not dynamic, we just return the static contents
		}else{
			require_once ROOT_DIR . '/sys/Covers/CoverImageUtils.php';

			//Get the background image for the slide
			$backgroundImageFile = ROOT_DIR . '/year_in_review/images/' . $slideInfo->background;
			$backgroundImageFile = realpath($backgroundImageFile);
			$backgroundImage = imagecreatefrompng($backgroundImageFile);
			$backgroundImageInfo = getimagesize($backgroundImageFile);
			$backgroundWidth = $backgroundImageInfo[0];
			$backgroundHeight = $backgroundImageInfo[1];
			//Create a canvas for the slide
			$slideCanvas = imagecreatetruecolor($backgroundWidth, $backgroundHeight);
			//Display the background to the slide
			imagecopy($slideCanvas, $backgroundImage, 0, 0, 0, 0, $backgroundWidth, $backgroundHeight);

			if (empty($overlayText->font)) {
				$font = ROOT_DIR . '/fonts/JosefinSans-Bold.ttf';
			}else{
				$font = ROOT_DIR . "/fonts/$overlayText->font.ttf";
			}

			$white = imagecolorallocate($slideCanvas, 255, 255, 255);
			$black = imagecolorallocate($slideCanvas, 0, 0, 0);

			//Add overlay text to the image
			foreach ($slideInfo->overlay_text as $overlayText) {
				$overlayWidth = $overlayText->width;
				if (str_ends_with($overlayWidth,'%')) {
					$percent = str_replace('%', '', $overlayWidth) / 100;
					$textWidth = $backgroundWidth * $percent;
				}else{
					$textWidth = $overlayWidth;
				}
				$fontSize = $overlayText->font_size;
				if (str_ends_with($fontSize,'em')) {
					$fontSize = str_replace('em', '', $fontSize) * 16;
				}
				$left = $overlayText->left;
				if (str_ends_with($left,'%')) {
					$percent = str_replace('%', '', $left) / 100;
					$left = $backgroundWidth * $percent;
				}elseif (str_ends_with($left,'px')) {
					$left = str_replace('px', '', $left);
				}
				$top = $overlayText->top;
				if (str_ends_with($top,'%')) {
					$percent = str_replace('%', '', $top) / 100;
					$top = $backgroundWidth * $percent;
				}elseif (str_ends_with($top,'px')) {
					$top = str_replace('px', '', $top);
				}

				if ($overlayText->color == 'white') {
					$color = $white;
				}else{
					$color = $black;
				}

				if (!empty($overlayText->allCaps)) {
					$overlayText->text = strtoupper($overlayText->text);
				}

				[
					,
					$lines,
				] = wrapTextForDisplay($font, $overlayText->text, $fontSize, $fontSize * .2, $textWidth);
				if ($overlayText->align == 'center') {
					addCenteredWrappedTextToImage($slideCanvas, $font, $lines, $fontSize, $fontSize * .2, $left, $top, $textWidth, $color);
				}else{
					addWrappedTextToImage($slideCanvas, $font, $lines, $fontSize, $fontSize * .2, $left, $top, $color);
				}
			}

			//Output the image to the browser
			imagepng($slideCanvas);
			imagedestroy($slideCanvas);
			$gotImage = true;
		}

		return $gotImage;
	}
}
