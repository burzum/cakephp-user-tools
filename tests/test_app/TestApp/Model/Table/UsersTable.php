<?php
namespace TestApp\Model\Table;

use Cake\ORM\Table;

/**
 * UsersTable
 */
class UsersTable extends Table
{

    public function initialize(array $config)
    {
        $this->setTable('users');
        $this->setPrimaryKey('id');
        $this->hasOne('Profiles', [
            'className' => 'TestApp\Model\Table\ProfilesTable',
            'foreignKey' => 'user_id'
        ]);
        $this->addBehavior('Burzum/UserTools.User');
    }
}
