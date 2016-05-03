<?php
namespace Burzum\UserTools\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * UserFixture
 *
 * @author Florian Krämer
 * ]@copyright 2013 - 2016 Florian Krämer
 * @license MIT
 */
class UserFixture extends TestFixture {

	/**
	 * Fields
	 *
	 * @var array
	 */
	public $fields = [
		'id' => ['type' => 'uuid', 'length' => null, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null],
		'username' => ['type' => 'string', 'null' => false, 'default' => null],
		'slug' => ['type' => 'string', 'null' => true, 'default' => null],
		'password' => ['type' => 'string', 'null' => true, 'default' => null, 'length' => 128],
		'password_token' => ['type' => 'string', 'null' => true, 'default' => null, 'length' => 128],
		'password_token_expires' => ['type' => 'datetime', 'null' => true, 'default' => null],
		'email' => ['type' => 'string', 'null' => true, 'default' => null],
		'email_verified' => ['type' => 'boolean', 'null' => true, 'default' => '0'],
		'email_token' => ['type' => 'string', 'null' => true, 'default' => null],
		'email_token_expires' => ['type' => 'datetime', 'null' => true, 'default' => null],
		'tos' => ['type' => 'boolean', 'null' => true, 'default' => '0'],
		'active' => ['type' => 'boolean', 'null' => true, 'default' => '0'],
		'last_action' => ['type' => 'datetime', 'null' => true, 'default' => null],
		'last_login' => ['type' => 'datetime', 'null' => true, 'default' => null],
		'is_admin' => ['type' => 'boolean', 'null' => true, 'default' => '0'],
		'role' => ['type' => 'string', 'null' => true, 'default' => null],
		'created' => ['type' => 'datetime', 'null' => true, 'default' => null],
		'modified' => ['type' => 'datetime', 'null' => true, 'default' => null],
		'_constraints' => [
			'primary' => ['type' => 'primary', 'columns' => ['id'], 'length' => []],
			'unique_username' => ['type' => 'unique', 'columns' => ['username']],
			'unique_email' => ['type' => 'unique', 'columns' => ['email']]
		],
		'_options' => [
			'engine' => 'InnoDB',
			'collation' => 'utf8_general_ci'
		],
	];

	/**
	 * Records
	 *
	 * @var array
	 */
	public $records = [
		[
			'id' => '1',
			'username' => 'adminuser',
			'slug' => 'adminuser',
			'password' => 'test', // test
			'password_token' => 'testtoken',
			'email' => 'adminuser@testuser.com',
			'email_verified' => 1,
			'email_token' => 'testtoken',
			'email_token_expires' => '2008-03-25 02:45:46',
			'tos' => 1,
			'active' => 1,
			'last_action' => '2008-03-25 02:45:46',
			'last_login' => '2008-03-25 02:45:46',
			'is_admin' => 1,
			'role' => 'admin',
			'created' => '2008-03-25 02:45:46',
			'modified' => '2008-03-25 02:45:46'
		],
		[
			'id' => '2',
			'username' => 'newuser',
			'slug' => 'newuser',
			'password' => 'test', // test
			'password_token' => 'newusertoken',
			'password_token_expires' => null,
			'email' => 'newuser@testuser.com',
			'email_verified' => 1,
			'email_token' => 'secondusertesttoken',
			'email_token_expires' => '2008-03-25 02:45:46',
			'tos' => 1,
			'active' => 1,
			'last_action' => '2008-03-25 02:45:46',
			'last_login' => '2008-03-25 02:45:46',
			'is_admin' => 1,
			'role' => 'admin',
			'created' => '2008-03-25 02:45:46',
			'modified' => '2008-03-25 02:45:46'
		],
		[
			'id' => '3',
			'username' => 'notverified',
			'slug' => 'notverified',
			'password' => 'test', // test
			'password_token' => 'notverified',
			'password_token_expires' => null,
			'email' => 'notverified@testuser.com',
			'email_verified' => 0,
			'email_token' => 'thirdusertesttoken',
			'email_token_expires' => '2008-03-25 02:45:46',
			'tos' => 1,
			'active' => 1,
			'last_action' => '2008-03-25 02:45:46',
			'last_login' => '2008-03-25 02:45:46',
			'is_admin' => 1,
			'role' => 'admin',
			'created' => '2008-03-25 02:45:46',
			'modified' => '2008-03-25 02:45:46'
		],
	];
}
