<?php /** @noinspection PhpMissingFieldTypeInspection */
require_once ROOT_DIR . '/sys/LibraryLocation/LibraryYearInReview.php';

class YearInReviewSetting extends DataObject {
	public $__table = 'year_in_review_settings';
	public $id;
	public $name;
	public $year;
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
		$configurationFile = ROOT_DIR . "/year_in_review/$this->year.json";
		if (file_exists($configurationFile)) {
			$slideConfiguration = json_decode(file_get_contents($configurationFile));
			$userYearInResults = $patron->getYearInReviewResults();
			if ($userYearInResults !== false) {
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
					$result['modalBody'] = $this->formatSlide($slideInfo, $patron);

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
					'text' => 'Unable to find year in review data',
					'isPublicFacing' => true,
				]);
			}
		}else{
			$result['message'] = translate([
				'text' => 'Unable to find year in review configuration file',
				'isPublicFacing' => true,
			]);
		}

		return $result;
	}

	private function formatSlide(stdClass $slideInfo, User $patron) : string {
		global $interface;
		$interface->assign('slideInfo', $slideInfo);
		return $interface->fetch('YearInReview/slide.tpl');
	}
}