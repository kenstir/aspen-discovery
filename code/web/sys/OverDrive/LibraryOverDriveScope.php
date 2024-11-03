<?php /** @noinspection PhpMissingFieldTypeInspection */

class LibraryOverDriveScope extends DataObject {
	public $__table = 'library_overdrive_scope';
	public $id;
	public $scopeId;
	public $libraryId;

	public function getNumericColumnNames(): array {
		return [
			'id',
			'libraryId',
			'scopeId',
		];
	}

	/** @noinspection PhpUnusedParameterInspection */
	static function getObjectStructure($context = ''): array {
		require_once ROOT_DIR . '/sys/OverDrive/OverDriveScope.php';
		$overDriveScopes = [];
		$overDriveScopes[-1] = translate([
			'text' => 'Select a value',
			'isPublicFacing' => true,
		]);
		$overDriveScope = new OverDriveScope();
		$overDriveScope->orderBy('name');
		$overDriveScopes = $overDriveScopes + $overDriveScope->fetchAll('id', 'name');

		$libraryList = Library::getLibraryList(!UserAccount::userHasPermission('Administer All Libraries'));

		return [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'libraryId' => [
				'property' => 'libraryId',
				'type' => 'enum',
				'values' => $libraryList,
				'label' => 'Library',
				'description' => 'The id of a library',
			],
			'scopeId' => [
				'property' => 'scopeId',
				'type' => 'enum',
				'values' => $overDriveScopes,
				'label' => 'OverDrive Scope',
				'description' => 'The OverDrive scope to use',
				'default' => -1,
				'forcesReindex' => true,
			],
		];
	}

	/** @noinspection PhpUnusedParameterInspection */
	function getEditLink($context): string {
		if ($context == 'libraries') {
			return '/Admin/Libraries?objectAction=edit&id=' . $this->libraryId . '#propertyRowoverDriveScopes';
		}else{
			return '/OverDrive/Scopes?objectAction=edit&id=' . $this->scopeId;
		}
	}

	private $_overDriveScope = null;
	public function getOverDriveScope() : OverDriveScope {
		if ($this->_overDriveScope == null) {
			$this->_overDriveScope = new OverDriveScope();
			$this->_overDriveScope->id = $this->scopeId;
			if (!$this->_overDriveScope->find(true)){
				$this->_overDriveScope = null;
			}
		}
		return $this->_overDriveScope;
	}
}