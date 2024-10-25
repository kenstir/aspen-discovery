<?php /** @noinspection PhpMissingFieldTypeInspection */

class LocationOverDriveScope extends DataObject {
	public $__table = 'location_overdrive_scope';
	public $id;
	public $scopeId;
	public $locationId;
	public $weight;

	public function getNumericColumnNames(): array {
		return [
			'locationId',
			'scopeId',
			'weight',
		];
	}

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

		$locationsList = [];
		$location = new Location();
		$location->orderBy('displayName');
		if (!UserAccount::userHasPermission('Administer All Locations')) {
			$homeLibrary = Library::getPatronHomeLibrary();
			$location->libraryId = $homeLibrary->libraryId;
		}
		$location->find();
		while ($location->fetch()) {
			$locationsList[$location->locationId] = $location->displayName;
		}

		return [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'Id',
				'description' => 'The unique id',
			],
			'locationId' => [
				'property' => 'locationId',
				'type' => 'enum',
				'values' => $locationsList,
				'label' => 'Location',
				'description' => 'The Location to associate the scope to',
				'required' => true,
			],
			'scopeId' => [
				'property' => 'scopeId',
				'type' => 'enum',
				'values' => $overDriveScopes,
				'label' => 'OverDrive Scope',
				'description' => 'The OverDrive scope to use',
				'hideInLists' => false,
				'default' => -1,
				'forcesReindex' => true,
			],
		];
	}

	function getEditLink($context): string {
		if ($context == 'locations') {
			return '/Admin/Locations?objectAction=edit&id=' . $this->locationId . '#propertyRowoverDriveScopes';
		}else {
			return '/OverDrive/Scopes?objectAction=edit&id=' . $this->scopeId;
		}
	}
}