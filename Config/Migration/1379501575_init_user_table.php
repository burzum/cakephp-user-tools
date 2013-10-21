<?php
class InitUserTable extends CakeMigration {

/**
 * Migration description
 *
 * @var string
 * @access public
 */
	public $description = '';

/**
 * Actions to be performed
 *
 * @var array $migration
 * @access public
 */
	public $migration = array(
		'up' => array(
			'create_table' => array(
				'users' => array(
					'id' => array('type' => 'string', 'null' => false, 'default' => null, 'length' => 36, 'key' => 'primary'),
					'username' => array('type' => 'string', 'null' => false, 'default' => null),
					'email' => array('type' => 'string', 'null' => true, 'default' => null),
					'email_verified' => array('type' => 'boolean', 'null' => true, 'default' => '0'),
					'email_token' => array('type' => 'string', 'null' => true, 'default' => null),
					'email_token_expires' => array('type' => 'datetime', 'null' => true, 'default' => null),
					'password' => array('type' => 'string', 'null' => true, 'default' => null, 'length' => 128),
					'password_token' => array('type' => 'string', 'null' => true, 'default' => null, 'length' => 128),
					'tos' => array('type' => 'boolean', 'null' => true, 'default' => '0'),
					'active' => array('type' => 'boolean', 'null' => true, 'default' => '0'),
					'last_login' => array('type' => 'datetime', 'null' => true, 'default' => null),
					'last_action' => array('type' => 'datetime', 'null' => true, 'default' => null),
					'role' => array('type' => 'string', 'null' => true, 'default' => null),
					'created' => array('type' => 'datetime', 'null' => true, 'default' => null),
					'modified' => array('type' => 'datetime', 'null' => true, 'default' => null),
					'indexes' => array(
						'PRIMARY' => array('column' => 'id', 'unique' => 1),
						'BY_USERNAME' => array('column' => array('username'), 'unique' => 0),
						'BY_EMAIL' => array('column' => array('email'), 'unique' => 0)
					),
				),
			)
		),
		'down' => array(
			'drop_table' => array(
				'users'
			)
		),
	);

/**
 * Before migration callback
 *
 * @param string $direction, up or down direction of migration process
 * @return boolean Should process continue
 * @access public
 */
	public function before($direction) {
		return true;
	}

/**
 * After migration callback
 *
 * @param string $direction, up or down direction of migration process
 * @return boolean Should process continue
 * @access public
 */
	public function after($direction) {
		return true;
	}

}
