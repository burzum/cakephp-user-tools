<?php
namespace TestApp\Model\Table;

class ProfilesTable extends Table {

	public function initialize(array $config) {
		$this->table('profiles');
		$this->alias('Profiles');
		$this->belongsTo('Users', [
			'className' => 'Users',
			'foreignKey' => 'user_id'
		]);
	}
}
