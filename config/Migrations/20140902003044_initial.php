<?php
/**
 * Migration
 *
 * @author Florian Krämer
 * @copyright 2013 - 2014 Florian Krämer
 * @copyright 2012 Cake Development Corporation
 * @license MIT
 */
use Phinx\Migration\AbstractMigration;

class Initial extends AbstractMigration {

/**
 * Migrate Up.
 *
 * @return void
 */
	public function up() {
		$this->table('users', ['id' => false, 'primary_key' => 'id'])
			->addColumn('id', 'char', ['limit' => 36])
			->addColumn('username', 'string', ['limit' => 64])
			->addColumn('email', 'string', ['limit' => 255])
			->addColumn('email_token', 'string', ['limit' => 64, 'default' => null, 'null' => true])
			->addColumn('email_verified', 'boolean', ['default' => false])
			->addColumn('email_token_expires', 'datetime', ['default' => null, 'null' => true])
			->addColumn('active', 'boolean', ['default' => false])
			->addColumn('password', 'string', ['limit' => 64])
			->addColumn('password_token', 'string', ['limit' => 64, 'default' => null, 'null' => true])
			->addColumn('password_token_expires', 'datetime', ['default' => null, 'null' => true])
			->addColumn('role', 'string', ['limit' => 32, 'default' => null, 'null' => true])
			->addColumn('last_login', 'datetime', ['default' => null, 'null' => true])
			->addColumn('created', 'datetime', ['default' => null, 'null' => true])
			->addColumn('modified', 'datetime', ['default' => null, 'null' => true])
			->addIndex(['username', 'email'])
			->create();
	}

/**
 * Migrate Down.
 *
 * @return void
 */
	public function down() {
		$this->table('users')
			->drop();
	}
}
