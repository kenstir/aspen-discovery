<?php /** @noinspection PhpMissingFieldTypeInspection */

class OverDriveAPIProduct extends DataObject {
	public $__table = 'overdrive_api_products';   // table name

	public $id;
	public $overdriveId;
	public $crossRefId;
	/** @noinspection PhpUnused */
	public $mediaType;
	public $title;
	public $subtitle;
	public $series;
	/** @noinspection PhpUnused */
	public $primaryCreatorRole;
	public $primaryCreatorName;
	public $cover;
	public $dateAdded;
	public $dateUpdated;
	/** @noinspection PhpUnused */
	public $lastMetadataCheck;
	/** @noinspection PhpUnused */
	public $lastMetadataChange;
	/** @noinspection PhpUnused */
	public $lastAvailabilityCheck;
	/** @noinspection PhpUnused */
	public $lastAvailabilityChange;
	public $deleted;
	/** @noinspection PhpUnused */
	public $dateDeleted;
}