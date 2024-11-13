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
		],

		//katherine

		//kirstien

		//kodi


		//alexander - PTFS-Europe

		//chloe - PTFS-Europe
		'create_libkey_module' => [
			'title' => 'Create LibKey Module',
			'description' => 'Add LibKey to the list of modules',
			'sql' => [
				"INSERT INTO modules (name) VALUES ('LibKey')",
			],
		], // create_libkey_module
		'create_libkey_permissions' => [
			'title' => 'Create LibKey Permissions',
			'description' => 'Add an LibKey permission section containing the Administer LibKey Settings permission',
			'sql' => [
				"INSERT INTO permissions (name, sectionName, requiredModule, weight, description) VALUES ( 'Administer LibKey Settings','LibKey','LibKey', 0, 'Allows the user to administer the integration with LibKey')",
				"INSERT INTO role_permissions(roleId, permissionId) VALUES ((SELECT roleId from roles where name='opacAdmin'), (SELECT id from permissions where name='Administer LibKey Settings'))",
			],
		],// create_libkey_permissions


		//James Staub - Nashville Public Library


		//other

	];
}
