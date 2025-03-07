<?php

function getUpdates24_11_00(): array {
	$curTime = time();
	return [
		/*'name' => [
			 'title' => '',
			 'description' => '',
			 'continueOnError' => false,
			 'sql' => [
				 ''
			 ]
		 ], //name*/

		//mark - Grove
		'library_shareit_settings' => [
			'title' => 'Library SHAREit Settings',
			'description' => 'Add a new library SHAREit settings',
			'continueOnError' => true,
			'sql' => [
				"ALTER TABLE library ADD COLUMN repeatInShareIt TINYINT(1) DEFAULT 0",
				"ALTER TABLE library ADD COLUMN shareItCid TINYTEXT",
				"ALTER TABLE library ADD COLUMN shareItLid TINYTEXT",
			]
		], //library_shareit_settings
		'location_shareit_settings' => [
			'title' => 'Location SHAREit Settings',
			'description' => 'Add a new location SHAREit settings',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE location ADD COLUMN repeatInShareIt TINYINT(1) DEFAULT 0",
			]
		], //location_shareit_settings
		'library_shareit_credentials' => [
			'title' => 'Library SHAREit Credentials',
			'description' => 'Add library SHAREit login credentials',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE library ADD COLUMN shareItUsername TINYTEXT",
				"ALTER TABLE library ADD COLUMN shareItPassword TINYTEXT",
			]
		], //library_shareit_credentials
		'multiple_overdrive_scopes' => [
			'title' => 'Multiple OverDrive Scopes',
			'description' => 'Enable libraries and locations to link to multiple overdrive scopes',
			'continueOnError' => false,
			'sql' => [
				'CREATE TABLE library_overdrive_scope (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					scopeId INT NOT NULL,
					libraryId INT NOT NULL,
					weight INT NOT NULL default 1,
					unique (libraryId, scopeId)
				) ENGINE InnoDB',
				'CREATE TABLE location_overdrive_scope (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					scopeId INT NOT NULL,
					locationId INT NOT NULL,
					weight INT NOT NULL default 1,
					unique (locationId, scopeId)
				) ENGINE InnoDB',
				'INSERT INTO library_overdrive_scope (scopeId, libraryId) SELECT overDriveScopeId, libraryId from library where overDriveScopeId != -1',
				'INSERT INTO location_overdrive_scope (scopeId, locationId) SELECT overDriveScopeId, locationId from location where overDriveScopeId > 0',
				'INSERT INTO location_overdrive_scope (scopeId, locationId) SELECT library.overDriveScopeId, locationId from location inner join library on location.libraryId = library.libraryId where location.overDriveScopeId = -1 and library.overDriveScopeId != -1',
			]
		], //library_multiple_overdrive_collections
		'setup_overdrive_advantage_and_auth_in_link_tables' => [
			'title' => 'Setup Overdrive Advantage and Auth In Library Link Table',
			'description' => 'Setup Overdrive Advantage and Auth In Library Link Table',
			'continueOnError' => true,
			'sql' => [
				'ALTER TABLE library_overdrive_scope ADD COLUMN circulationEnabled TINYINT DEFAULT 1',
				'ALTER TABLE library_overdrive_scope ADD COLUMN authenticationILSName VARCHAR(45) DEFAULT null',
				'ALTER TABLE library_overdrive_scope ADD COLUMN requirePin tinyint(1) DEFAULT 0',
				"ALTER TABLE library_overdrive_scope ADD COLUMN overdriveAdvantageName  varchar(128) DEFAULT ''",
				"ALTER TABLE library_overdrive_scope ADD COLUMN overdriveAdvantageProductsKey varchar(20) DEFAULT ''",
			]
		], //setup_overdrive_advantage_and_auth_in_link_tables
		'move_overdrive_reader_name_to_settings' => [
			'title' => 'Move OverDrive Reader Name to Settings',
			'description' => 'Move overdrive reader name to settings',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE overdrive_settings ADD COLUMN readerName varchar(25) DEFAULT 'Libby'",
				"UPDATE overdrive_settings inner join overdrive_scopes on overdrive_settings.id = overdrive_scopes.settingId set overdrive_settings.readerName = overdrive_scopes.readerName"
			]
		], //move_overdrive_reader_name_to_settings
		'add_overdrive_settings name' => [
			'title' => 'Add OverDrive Settings Name',
			'description' => 'Add OverDrive Settings Name',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE overdrive_settings ADD COLUMN name varchar(125) DEFAULT 'Libby'",
			]
		], //move_overdrive_reader_name_to_settings
		'remove_overdrive_fulfillment_method' => [
			'title' => 'Remove deprecated OverDrive fulfillment method',
			'description' => 'Remove deprecated OverDrive fulfillment method',
			'sql' => [
				'ALTER TABLE overdrive_settings DROP COLUMN useFulfillmentInterface'
			]
		], //remove_overdrive_fulfillment_method
		'fix_duplicate_variations' => [
			'title' => 'Fix Duplicate Variations',
			'description' => 'Fix duplicate variations for grouped works',
			'sql' => [
				'fixDuplicateVariations'
			]
		], //fix_duplicate_variations
		'add_variation_index' => [
			'title' => 'Add variation index',
			'description' => 'Make Grouped Work Variations Unique',
			'sql' => [
				'ALTER TABLE grouped_work_variation ADD UNIQUE INDEX uniqueness(groupedWorkId, primaryLanguageId, eContentSourceId, formatId, formatCategoryId)'
			]
		], //add_variation_index
		'remove_unused_primary_language_from_grouped_work' => [
			'title' => 'Remove unused primary language from grouped work',
			'description' => 'Remove unused primary language from grouped work',
			'sql' => [
				'ALTER TABLE grouped_work DROP COLUMN primary_language'
			]
		],
		'create_overdrive_library_settings' => [
			'title' => 'Create OverDrive Library Settings',
			'description' => 'Create a new table to store library specific settings for an OverDrive collection',
			'sql' => [
				"CREATE TABLE library_overdrive_settings (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					settingId INT NOT NULL,
					weight INT DEFAULT 0,
					libraryId INT NOT NULL,
					clientSecret varchar(256) DEFAULT NULL,
					clientKey varchar(50) DEFAULT NULL,
					circulationEnabled tinyint(4) DEFAULT 1,
					authenticationILSName varchar(45) DEFAULT NULL,
					requirePin tinyint(1) DEFAULT 0,
					overdriveAdvantageName varchar(128) DEFAULT '',
					overdriveAdvantageProductsKey varchar(20) DEFAULT '',
					UNIQUE librarySetting(settingId, libraryId)
				) ENGINE INNODB CHARACTER SET utf8 COLLATE utf8_general_ci",
				'INSERT INTO library_overdrive_settings (settingId, libraryId, clientSecret, clientKey, circulationEnabled, authenticationILSName, requirePin, overdriveAdvantageName, overdriveAdvantageProductsKey) SELECT overdrive_scopes.settingId, library.libraryId, clientSecret, clientKey, circulationEnabled, authenticationILSName, requirePin, overdriveAdvantageName, overdriveAdvantageProductsKey from library INNER JOIN overdrive_scopes ON library.overDriveScopeId = overdrive_scopes.id where overDriveScopeId != -1',
			]
		], //create_overdrive_library_settings
		'overdrive_collection_name_for_circ' => [
			'title' => 'OverDrive Collection Name for Circulation Entries',
			'description' => 'Add Collection Name to holds and checkouts for OverDrive',
			'sql' => [
				'ALTER TABLE user_hold ADD COLUMN collectionName varchar(128) DEFAULT null',
				'ALTER TABLE user_checkout ADD COLUMN collectionName varchar(128) DEFAULT null'
			]
		], //overdrive_collection_name_for_circ
		'add_test_user_flag' => [
			'title' => 'Add Test User Flag',
			'description' => 'Add a test user flag to indicate users that are for testing only',
			'sql' => [
				'ALTER TABLE user ADD COLUMN isLocalTestUser TINYINT(1) DEFAULT 0'
			]
		],
		'background_process' => [
			'title' => 'Background Process',
			'description' => 'Add table to store information about Background Processes run from the Aspen UI',
			'sql' => [
				"CREATE TABLE background_process (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					owningUserId INT NOT NULL,
					name VARCHAR(255) NOT NULL,
					notes MEDIUMTEXT,
					startTime INT NOT NULL,
					endTime INT,
					isRunning TINYINT(1) DEFAULT 1,
					INDEX owningUserId (owningUserId)
				) ENGINE INNODB CHARACTER SET utf8 COLLATE utf8_general_ci",
			],
		],
		'user_message_related_object' => [
			'title' => 'User Message Related Object',
			'description' => 'Add a related object id to user messages to allow for automatic dismissal and other behavior',
			'sql' => [
				'ALTER TABLE user_messages ADD COLUMN relatedObjectId INT',
			]
		],
		'make_cloudsource_baseurl_text' => [
			'title' => 'Update Library cloudSourceBaseUrl From Varchar To Text',
			'description' => 'Update Library cloudSourceBaseUrl From Varchar To Text',
			'continueOnError' => true,
			'sql' => [
				//Some systems had issues with this update in 24.10.10 so, add it again as TINYTEXT
				"ALTER TABLE library ADD COLUMN cloudSourceBaseUrl TINYTEXT",
				"ALTER TABLE library CHANGE COLUMN cloudSourceBaseUrl cloudSourceBaseUrl TINYTEXT",
			]
		],
		'change_share_it_column_types' => [
			'title' => 'Change ShareIt Column Types',
			'description' => 'Change ShareIt Column Types',
			'sql' => [
				"ALTER TABLE library CHANGE COLUMN shareItCid shareItCid TINYTEXT",
				"ALTER TABLE library CHANGE COLUMN shareItLid shareItLid TINYTEXT",
				"ALTER TABLE library CHANGE COLUMN shareItUsername shareItUsername TINYTEXT",
				"ALTER TABLE library CHANGE COLUMN shareItPassword shareItPassword TINYTEXT",
			]
		],
		/*
		//TODO: Remove this when 24.10 is closer to done
		'remove_library_and_location_overdrive_scopes' => [
			'title' => 'Remove Library and Location Overdrive Scopes',
			'description' => 'Remove library and location overdrive scopes - completes multiple overdrive scopes',
			'sql' => [
				'ALTER TABLE library DROP COLUMN overDriveScopeId',
				'ALTER TABLE location DROP COLUMN overDriveScopeId',
				'ALTER TABLE overdrive_settings
			]
		],*/

		//katherine - ByWater

		//kirstien - ByWater

		//kodi - ByWater
		'alternate_card_varchar_limit' => [
			'title' => 'Alternate Card Label Character Limits',
			'description' => 'Increase the character limit for the alternate library car label and alternate library card password label fields',
			'sql' => [
				"ALTER TABLE library MODIFY COLUMN alternateLibraryCardLabel VARCHAR(100) DEFAULT ''",
				"ALTER TABLE library MODIFY COLUMN alternateLibraryCardPasswordLabel VARCHAR(100) DEFAULT ''",
			],
		], //alternate_card_varchar_limit

		//alexander - PTFS-Europe

		//chloe - PTFS-Europe
		'ebsco_passwords_are_stored_as_hash' => [
			'title' => 'EBSCO Passwords Are Stored As Hash',
			'description' => 'allow for longer strings so passwords can be stored as hashed values',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE ebsco_eds_settings MODIFY COLUMN edsApiPassword VARCHAR(255)",
				"ALTER TABLE ebscohost_settings MODIFY COLUMN profilePwd VARCHAR(255)"
			]
		], // ebsco_passwords_are_stored_as_hash

		//pedro - PTFS-Europe

		//James Staub - Nashville Public Library
		'payment_branch_settings' => [
			'title' => 'Payment Branch Settings',
			'description' => 'Add a new Library setting to associate an eCommerce payment with a branch',
			'continueOnError' => false,
			'sql' => [
				"ALTER TABLE library ADD COLUMN paymentBranchSource enum('notApplicable','patronHomeLocation','specified') DEFAULT 'notApplicable'",
				"ALTER TABLE library ADD COLUMN specifiedPaymentBranchCode varchar(6) DEFAULT ''",
			]
		]

		//Jeremy Eden - Howell Carnegie District Library

		//other

	];
}


/** @noinspection PhpUnused */
function fixDuplicateVariations(&$update) : void {
	global $aspen_db;
	$numUpdated = 0;
	$results = $aspen_db->query('SELECT MIN(id) as minId, groupedWorkId, primaryLanguageId, eContentSourceId, formatId, formatCategoryId, count(*) as numRecords FROM `grouped_work_variation` group by groupedWorkId, primaryLanguageId, eContentSourceId, formatId, formatCategoryId having numRecords > 1;', PDO::FETCH_ASSOC);
	if ($results) {
		require_once ROOT_DIR . '/sys/Grouping/GroupedWorkVariation.php';
		while ($row = $results->fetch()){
			$minId = $row['minId'];
			$groupedWorkVariation = new GroupedWorkVariation();
			$groupedWorkVariation->groupedWorkId = $row['groupedWorkId'];
			$groupedWorkVariation->primaryLanguageId = $row['primaryLanguageId'];
			$groupedWorkVariation->eContentSourceId = $row['eContentSourceId'];
			$groupedWorkVariation->formatId = $row['formatId'];
			$groupedWorkVariation->formatCategoryId = $row['formatCategoryId'];
			$allVariationIds = $groupedWorkVariation->fetchAll('id', 'id');
			foreach ($allVariationIds as $variationId) {
				if ($variationId != $minId) {
					$aspen_db->query("UPDATE grouped_work_record_items set groupedWorkVariationId = $minId WHERE groupedWorkVariationId = $variationId");
					$groupedWorkVariation = new GroupedWorkVariation();
					$groupedWorkVariation->id = $variationId;
					$groupedWorkVariation->delete();
					$numUpdated++;
				}
			}
		}
		$update['success'] = true;
		$update['status'] = "Updated $numUpdated Variations";

	}
}
