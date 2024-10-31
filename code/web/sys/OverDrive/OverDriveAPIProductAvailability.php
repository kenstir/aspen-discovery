<?php /** @noinspection PhpMissingFieldTypeInspection */

class OverDriveAPIProductAvailability extends DataObject {
	public $__table = 'overdrive_api_product_availability';   // table name

	public $id;
	public $productId;
	public $libraryId;
	public $settingId;
	public $available;
	public $copiesOwned;
	public $copiesAvailable;
	public $numberOfHolds;
	public $shared;

	private $_libraryName;
	private $_settingName;

	/** @noinspection PhpUnused */
	function getLibraryName() : string {
		if ($this->libraryId == -1) {
			return 'Shared Digital Collection';
		} else {
			if (empty($this->_libraryName)) {
				$library = new Library();
				$library->libraryId = $this->libraryId;
				$library->find(true);
				$this->_libraryName = $library->displayName;
			}
			return $this->_libraryName;
		}
	}

	/** @noinspection PhpUnused */
	function getSettingName() : string {
		if (empty($this->_settingName)) {
			require_once ROOT_DIR . '/sys/OverDrive/OverDriveSetting.php';
			$setting = new OverDriveSetting();
			$setting->id = $this->settingId;
			if ($setting->find(true)) {
				$this->_settingName = $setting->name;
			} else {
				$this->_settingName = 'Unknown';
			}
		}
		return $this->_settingName;
	}

	function getSettingDescription() : string {
		if (empty($this->_settingName)) {
			require_once ROOT_DIR . '/sys/OverDrive/OverDriveSetting.php';
			$setting = new OverDriveSetting();
			$setting->id = $this->settingId;
			if ($setting->find(true)) {
				$this->_settingName = $setting->id . ': '  . $setting->__toString();
			} else {
				$this->_settingName = 'Unknown';
			}
		}
		return $this->_settingName;
	}
} 