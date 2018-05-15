<?php
namespace TestApp\Model\Table;

use Cake\ORM\Table;

/**
 * ProfilesTable
 */
class ProfilesTable extends Table {

	public function initialize(array $config) {
		$this->setPrimaryKey('id');
		$this->setTable('profiles');

		$this->belongsTo('Users', [
			'className' => 'TestApp\Model\Table\UsersTable',
			'foreignKey' => 'user_id'
		]);
	}
}
