<?php
/**
 * UserShell
 *
 * @author Florian Krämer
 * @copyright 2013 - 2014 Florian Krämer
 * @license MIT
 */
namespace UserTools\Shell;

use Cake\Cache\Cache;
use Cake\Console\Shell;
use Cake\Core\Configure;
use Cake\Model\Model;
use Cake\Core\ConventionsTrait;
use Cake\ORM\TableRegistry;

class UserShell extends Shell {

/**
 * The connection being used.
 *
 * @var string
 */
	public $connection = 'default';

/**
 * Constructor
 *
 * @param ConsoleIo $io
 * @internal param $ \Cake
 */
	public function __construct(ConsoleIo $io = null) {
		parent::__construct($io);
		$table = 'Users';
		$this->UserTable = TableRegistry::get($table);
		if (!$this->UserTable->hasBehavior('UserTools.User')) {
			$this->UserTable->addBehavior('UserTools.User');
		}
	}

/**
 * Assign $this->connection to the active task if a connection param is set.
 *
 * @return void
 */
	public function startup() {
		parent::startup();
		Cache::disable();
	}

/**
 * Removes expired registrations
 *
 * @return void
 */
	public function removeExpired() {
		$count = $this->UserTable->removeExpiredRegistrations();
		$this->out(__dn('user_tools', 'Removed {0,number,integer} expired registration.', 'Removed {0,number,integer} expired registrations.', $count, $count));
	}

/**
 * Sets a new password for an user
 *
 * @return void
 */
	public function setPassword() {
		if (count($this->args) < 2) {
			$this->error(__d('user_tools', 'You need to call this command with at least tow arguments.'));
		}
		$field = 'username';
		if (count($this->args) >= 3) {
			$field = $this->args[3];
		}
		$user = $this->UserTable->find()->where([$field => $this->args[0]])->first();
		$user->password = $this->UserTable->hashPassword($this->args[1]);
		if ($this->UserTable->save($user, ['validate' => false])) {
			$this->out('Password saved');
		}
	}

/**
 * Gets the option parser instance and configures it.
 *
 * @return \Cake\Console\ConsoleOptionParser
 */
	public function getOptionParser() {
		$parser = parent::getOptionParser();

		$parser->description(
			'Users utility shell'
		)
		->addOption('table', [
			'short' => 't',
			'help' => 'User model to load',
			'default' => 'Users'
		])
		->addOption('behavior', [
			'short' => 'b',
			'help' => 'Auto-load the behavior if the model doesn\'t have it loaded.',
			'default' => 1
		]);

		return $parser;
	}

}
