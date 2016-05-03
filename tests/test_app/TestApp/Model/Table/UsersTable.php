<?php
namespace TestApp\Model\Table;

class UsersTable extends Table {

	public function initialize(array $config) {
		$this->table('users');
		$this->alias('Users');
		$this->hasOne('Profile', [
			'className' => 'Profiles'
		]);
		$this->addBehavior('Burzum/UserTools.User');
	}
}
