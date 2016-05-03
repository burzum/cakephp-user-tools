<?php
namespace TestApp\Model\Table;

use Cake\ORM\Table;

class UsersTable extends Table {

	public function initialize(array $config) {
		$this->table('users');
		$this->primaryKey('id');
		$this->hasOne('Profiles', [
			'className' => 'TestApp\Model\Table\ProfilesTable',
			'foreignKey' => 'user_id'
		]);
		$this->addBehavior('Burzum/UserTools.User');
	}

}
