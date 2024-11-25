<?php

function getUpdates24_12_00(): array {
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
		'localIllRequestType' => [
			'title' => 'Add localIllRequestType to Library Settings',
			'description' => 'Add localIllRequestType to Library Settings',
			'continueOnError' => false,
			'sql' => [
				'ALTER TABLE library ADD COLUMN localIllRequestType TINYINT DEFAULT 0'
			]
		], //localIllRequestType
		'makeVdxHoldGroupsGeneric' => [
			'title' => 'Make VDX Hold Groups Generic',
			'description' => 'Make VDX Hold Groups more generic so they can be used for Local ILL',
			'continueOnError' => true,
			'sql' => [
				"UPDATE permissions SET name = 'Administer Hold Groups' where name = 'Administer VDX Hold Groups'",
				"RENAME TABLE vdx_hold_groups TO hold_groups",
				"RENAME TABLE vdx_hold_group_location TO hold_group_location",
				"ALTER TABLE hold_group_location CHANGE COLUMN vdxHoldGroupId holdGroupId INT"
			]
		], //makeVdxHoldGroupsGeneric
		'local_ill_forms' => [
			'title' => 'Local ILL Form setup',
			'description' => 'Add the ability to configure Local ILL forms for locations',
			'continueOnError' => true,
			'sql' => [
				'CREATE TABLE IF NOT EXISTS local_ill_form(
							id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
							name VARCHAR(50) NOT NULL UNIQUE,
							introText TEXT,
							showAcceptFee TINYINT(1) DEFAULT 0,
							requireAcceptFee TINYINT(1) DEFAULT 0,
							showMaximumFee TINYINT(1) DEFAULT 0,
							feeInformationText TEXT
						) ENGINE = INNODB;',
				'ALTER TABLE location ADD COLUMN localIllFormId INT DEFAULT -1',
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES
							('ILL Integration', 'Administer All Local ILL Forms', '', 17, 'Allows the user to define administer all Local ILL Forms.'),
							('ILL Integration', 'Administer Library Local ILL Forms', '', 18, 'Allows the user to define administer Local ILL Forms for their library.')",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer All Local ILL Forms'))",
			],
		], //local_ill_forms
		'copyVDXFormsToLocalIllForms' => [
			'title' => 'Copy VDX Forms to Local ILL Forms',
			'description' => 'Copy VDX Forms to Local ILL Forms',
			'continueOnError' => true,
			'sql' => [
				"INSERT INTO local_ill_form(id, name, introText, showAcceptFee, requireAcceptFee, showMaximumFee, feeInformationText) SELECT id, name, introText, showAcceptFee, showAcceptFee, showMaximumFee, feeInformationText FROM vdx_form",
				"UPDATE location set localIllFormId = vdxFormId"
			]
		], //copyVDXFormsToLocalIllForms
		'add_hold_out_of_hold_group_message' => [
			'title' => 'Add hold out of hold group message',
			'description' => 'Add hold out of hold group message',
			'sql' => [
				'ALTER TABLE user_hold ADD COLUMN outOfHoldGroupMessage TINYTEXT'
			]
		], //add_hold_out_of_hold_group_message
		'year_in_review_permissions' => [
			'title' => 'Year In Review Permissions',
			'description' => 'Add new permissions for Year In Review functionality',
			'continueOnError' => true,
			'sql' => [
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Year in Review', 'Administer Year in Review for All Libraries', '', 10, 'Allows Year in Review functionality to be configured for all libraries.')",
				"INSERT INTO permissions (sectionName, name, requiredModule, weight, description) VALUES ('Year in Review', 'Administer Year in Review for Home Library', '', 20, 'Allows Year in Review functionality to be configured for the user\'s home library.')",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer Year in Review for All Libraries'))",
			],
		], //year_in_review_permissions
		'year_in_review_settings' => [
			'title' => 'Year In Review Settings',
			'description' => 'Add new settings for Year In Review functionality',
			'continueOnError' => true,
			'sql' => [
				"CREATE TABLE year_in_review_settings (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					name VARCHAR(50) NOT NULL UNIQUE,
					year int,
					staffStartDate int,
					patronStartDate int
				) ENGINE INNODB CHARACTER SET utf8 COLLATE utf8_general_ci",
				'CREATE TABLE library_year_in_review (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					yearInReviewId INT NOT NULL,
					libraryId INT NOT NULL,
					UNIQUE (yearInReviewId, libraryId)
				) ENGINE INNODB CHARACTER SET utf8 COLLATE utf8_general_ci',
				'DROP TABLE user_year_in_review',
				'CREATE TABLE user_year_in_review (
					id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
					userId INT NOT NULL,
					settingId int NOT NULL,
					wrappedActive TINYINT(1) DEFAULT 0,
					wrappedViewed TINYINT(1) DEFAULT 0,
					wrappedResults TEXT,
					UNIQUE (userId, settingId)
				) ENGINE INNODB CHARACTER SET utf8 COLLATE utf8_general_ci',
				'ALTER TABLE ptype ADD COLUMN enableYearInReview TINYINT DEFAULT 0'
			]
		], //year_in_review_settings

		//katherine

		//kirstien

		//kodi


		//alexander - PTFS-Europe
		'add_regular_expression_for_iTypes_to_treat_as_eContent' => [
			'title' => 'Add Regular Expression For iTypes To Treat As Econtent',
			'description' => 'Add treatItemsAsEcontent to give control over iTypes to be treated as eContent',
			'sql' => [
				"ALTER TABLE indexing_profiles ADD COLUMN treatItemsAsEcontent VARCHAR(512) DEFAULT 'ebook|ebk|eaudio|evideo|online|oneclick|eaudiobook|download|eresource|electronic resource'",
			],
		], //add_treatItemsAsEcontent_field

		//chloe - PTFS-Europe


		//James Staub - Nashville Public Library


		//other

	];
}
