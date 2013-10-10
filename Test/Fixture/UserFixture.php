<?php
/**
 * UserFixture
 *
 * @author Florian Krämer
 * @copyright 2013 Florian Krämer
 * @license MIT
 */
class UserFixture extends CakeTestFixture {

/**
 * Name
 *
 * @var string $name
 */
	public $name = 'User';

/**
 * Table
 *
 * @var array $table
 */
	public $table = 'users';

/**
 * Fields
 *
 * @var array
 */
	public $fields = array(
			'id' => array('type'=>'string', 'null' => false, 'length' => 36, 'key' => 'primary'),
			'username' => array('type'=>'string', 'null' => false, 'default' => NULL),
			'slug' => array('type'=>'string', 'null' => false, 'default' => NULL),
			'password' => array('type'=>'string', 'null' => true, 'default' => NULL, 'length' => 128),
			'password_token' => array('type'=>'string', 'null' => true, 'default' => NULL, 'length' => 128),
			'email' => array('type'=>'string', 'null' => true, 'default' => NULL),
			'email_verified' => array('type'=>'boolean', 'null' => true, 'default' => '0'),
			'email_token' => array('type'=>'string', 'null' => true, 'default' => NULL),
			'email_token_expires' => array('type'=>'datetime', 'null' => true, 'default' => NULL),
			'tos' => array('type'=>'boolean', 'null' => true, 'default' => '0'),
			'active' => array('type'=>'boolean', 'null' => true, 'default' => '0'),
			'last_action' => array('type'=>'datetime', 'null' => true, 'default' => NULL),
			'last_login' => array('type'=>'datetime', 'null' => true, 'default' => NULL),
			'is_admin' => array('type'=>'boolean', 'null' => true, 'default' => '0'),
			'role' => array('type'=>'string', 'null' => true, 'default' => NULL),
			'created' => array('type'=>'datetime', 'null' => true, 'default' => NULL),
			'modified' => array('type'=>'datetime', 'null' => true, 'default' => NULL),
			'indexes' => array(
				'PRIMARY' => array('column' => 'id', 'unique' => 1))
			);

/**
 * Records
 *
 * @var array
 */
	public $records = array(
		array(
			'id'  => '1',
			'username'  => 'adminuser',
			'slug' => 'adminuser',
			'password'  => 'test', // test
			'password_token'  => 'testtoken',
			'email' => 'adminuser@cakedc.com',
			'email_verified' => 1,
			'email_token' => 'testtoken',
			'email_token_expires' => '2008-03-25 02:45:46',
			'tos' => 1,
			'active' => 1,
			'last_action'  => '2008-03-25 02:45:46',
			'last_login' => '2008-03-25 02:45:46',
			'is_admin' => 1,
			'role' => 'admin',			
			'created'  => '2008-03-25 02:45:46',
			'modified'  => '2008-03-25 02:45:46'
		)
	);

/**
 * Constructor
 *
 *
 */
	public function __construct() {
		parent::__construct();
		$this->User = ClassRegistry::init('Users.User');
		foreach ($this->records as &$record) {
			$record['password'] = $this->User->hash($record['password'], null, true);
		}
	}

}
