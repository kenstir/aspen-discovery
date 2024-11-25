<?php /** @noinspection PhpMissingFieldTypeInspection */

class UserYearInReview extends DataObject {
	public $__table = 'user_year_in_review';
	public $id;
	public $userId;
	public $settingId;
	public $wrappedActive;
	public $wrappedViewed;
	public $wrappedResults;
}