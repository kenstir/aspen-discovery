<?php


class CustomFormSubmission extends DataObject
{
	public $__table = 'library_web_builder_custom_from_submission';
	public $id;
	public $formId;
	public $libraryId;
	public $userId;
	public $dateSubmitted;
	public $submission;
}