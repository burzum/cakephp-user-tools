<?php
use Phinx\Migration\AbstractMigration;

class Initial extends AbstractMigration {

/**
 * Migrate Up.
 *
 * @return void
 */
	public function up() {
		$this->table('users')
			->addColumn('id', 'string', ['limit' => 36])
			->addColumn('username', 'string', ['limit' => 64])
			->addColumn('email', 'string', ['limit' => 255])
			->addColumn('email_token', 'string', ['limit' => 64])
			->addColumn('email_verified', 'boolean')
			->addColumn('email_token_expires', 'datetime')
			->addColumn('active', 'boolean')
			->addColumn('password', 'string', ['limit' => 64])
			->addColumn('password_token', 'string', ['limit' => 64])
			->addColumn('password_token_expires', 'datetime')
			->addColumn('role', 'string', ['limit' => 32])
			->addColumn('last_login', 'datetime')
			->addColumn('created', 'datetime')
			->addColumn('modified', 'datetime')
			->create();
	}

/**
 * Migrate Down.
 *
 * @return void
 */
	public function down() {
		$this->table('users')->drop();
	}
}
