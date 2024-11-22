<?php /** @noinspection PhpMissingFieldTypeInspection */
require_once ROOT_DIR . '/sys/LibraryLocation/LibraryYearInReview.php';

class YearInReviewSetting extends DataObject {
	public $__table = 'year_in_review_settings';
	public $id;
	public $name;
	public $year;
	public $staffStartDate;
	public $patronStartDate;

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
			'libraries' => [
				'property' => 'libraries',
				'type' => 'multiSelect',
				'listStyle' => 'checkboxSimple',
				'label' => 'Libraries',
				'description' => 'Define libraries that see this system message',
				'values' => $libraryList,
				'hideInLists' => true,
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
		$ret = parent::update();
		if ($ret !== FALSE) {
			$this->saveLibraries();
		}
		return $ret;
	}

	public function insert($context = '') {
		$ret = parent::insert();
		if ($ret !== FALSE) {
			$this->saveLibraries();
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
}