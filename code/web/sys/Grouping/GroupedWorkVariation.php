<?php /** @noinspection PhpMissingFieldTypeInspection */

class GroupedWorkVariation extends DataObject {
	public $__table = 'grouped_work_variation';
	public $id;
	public $groupedWorkId;
	public $primaryLanguageId;
	public $eContentSourceId;
	public $formatId;
	public $formatCategoryId;
}