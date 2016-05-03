<?php
namespace Burzum\UserTools\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * ProfileFixture
 *
 * @author Florian Krämer
 * ]@copyright 2013 - 2016 Florian Krämer
 * @license MIT
 */
class ProfileFixture extends TestFixture {

/**
 * Fields
 *
 * @var array
 */
	public $fields = [
		'id' => ['type' => 'uuid', 'length' => null, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null],
		'user_id' => ['type' => 'string', 'null' => false, 'length' => 36],
		'first_name' => ['type' => 'string', 'null' => false, 'default' => null],
		'last_name' => ['type' => 'string', 'null' => false, 'default' => null],
		'_constraints' => [
			'primary' => ['type' => 'primary', 'columns' => ['id'], 'length' => []],
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
			'user_id' => 1,
			'first_name' => 'Cake',
			'last_name' => 'PHP'
		],
		[
			'id' => '2',
			'user_id' => 2,
			'first_name' => 'Cake',
			'last_name' => 'PHP'
		],
		[
			'id' => '3',
			'user_id' => 3,
			'first_name' => 'Cake',
			'last_name' => 'PHP'
		],
	];
}
