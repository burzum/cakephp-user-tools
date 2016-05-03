<?php
namespace TestApp\Model\Table;

use Cake\ORM\Table;

class ProfilesTable extends Table {

	public function initialize(array $config) {
		$this->primaryKey('id');
		$this->table('profiles');

		$this->belongsTo('Users', [
			'className' => 'TestApp\Model\Table\UsersTable',
			'foreignKey' => 'user_id'
		]);
	}
}
