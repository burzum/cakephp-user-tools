<?php
/**
 * UserBehavior
 *
 * @author Florian Krämer
 * @copyright 2013 - 2017 Florian Krämer
 * @copyright 2012 Cake Development Corporation
 * @license MIT
 */
namespace Burzum\UserTools\Model\Behavior;

use Burzum\UserTools\Mailer\UsersMailer;
use Burzum\UserTools\Model\PasswordAndTokenTrait;
use Burzum\UserTools\Model\PasswordHasherTrait;
use Burzum\UserTools\Model\UserValidationTrait;
use Cake\Core\Configure;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Event\Event;
use Cake\Event\EventDispatcherTrait;
use Cake\I18n\Time;
use Cake\Mailer\MailerAwareTrait;
use Cake\ORM\Behavior;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\Utility\Hash;
use Cake\Utility\Text;
use RuntimeException;

/**
 * User Behavior
 *
 * Options:
 * - `emailConfig` - Email configuration to use, default is `default`
 * - `defaultValidation` - Set up the default validation rules of the plugin.
 *    Default is true.
 * - `useUuid` - If your app is using ints instead of UUIDs set this to false.
 * - `passwordHasher` - Password hasher to use, default is `Default`
 * - `mailer` - Mailer class to use, default is
 *   `Burzum\UserTools\Mailer\UsersMailer`
 * - `passwordMinLength` - Minimum password length for validation, default is 6
 * - `getUser` - An array of options, please see UserBehavior::getUser()
 * - `register` - An array of options, please see UserBehavior::register()
 * - `loginFields` -
 * - `fieldMap` -
 * - `beforeSave` -
 * - `changePassword` -
 * - `updateLastActivity` -
 * - `initPasswordReset` -
 * - `sendVerificationEmail` -
 * - `sendNewPasswordEmail` -
 * - `sendPasswordResetToken` -
 * - `implementedFinders` - List of implemented finders, `active`
 *    and `emailVerified`
 */
class UserBehavior extends Behavior {

	use EventDispatcherTrait;
	use MailerAwareTrait;
	use PasswordAndTokenTrait;
	use PasswordHasherTrait;
	use UserValidationTrait;

	/**
	 * Default config
	 *
	 * @var array
	 */
	protected $_defaultConfig = [
		'emailConfig' => 'default',
		'defaultValidation' => true,
		'useUuid' => true,
		'passwordHasher' => 'Default',
		'mailer' => UsersMailer::class,
		'passwordMinLength' => 6,
		'getUser' => [],
		'register' => [
			'defaultRole' => null,
			'hashPassword' => true,
			'userActive' => true,
			'generatePassword' => false,
			'emailVerification' => true,
			'verificationExpirationTime' => '+1 day',
			'beforeRegister' => true,
			'afterRegister' => true,
			'tokenLength' => 32,
			'saveOptions' => []
		],
		'loginFields' => [
			'username' => 'email',
			'password' => 'password'
		],
		'fieldMap' => [
			'username' => 'username',
			'password' => 'password',
			'email' => 'email',
			'passwordCheck' => 'confirm_password',
			'oldPassword' => 'old_password',
			'lastAction' => 'last_action',
			'lastLogin' => 'last_login',
			'role' => 'role',
			'emailToken' => 'email_token',
			'emailVerified' => 'email_verified',
			'emailTokenExpires' => 'email_token_expires',
			'passwordToken' => 'password_token',
			'passwordTokenExpires' => 'password_token_expires',
			'active' => 'active',
			'slug' => 'slug',
		],
		'beforeSave' => [
			// Enable this only if you're not using the built in password change
			// and want the password hash to be updated automatically
			'handleNewPasswordByOldPassword' => false
		],
		'changePassword' => [
			'hashPassword' => true
		],
		'updateLastActivity' => [
			'dateFormat' => 'Y-m-d H:i:s',
		],
		'initPasswordReset' => [
			'tokenLength' => 32,
			'expires' => '+1 day'
		],
		'sendVerificationEmail' => [
			'template' => 'Burzum/UserTools.Users/verification_email',
		],
		'sendNewPasswordEmail' => [
			'template' => 'Burzum/UserTools.Users/new_password',
		],
		'sendPasswordResetToken' => [
			'template' => 'Burzum/UserTools.Users/password_reset',
		],
		'implementedFinders' => [
			'active' => 'findActive',
			'emailVerified' => 'findEmailVerified'
		]
	];

	/**
	 * Keeping a reference to the table in order to be able to retrieve associations
	 * and fetch records for counting.
	 *
	 * @var \Cake\ORM\Table
	 */
	protected $_table;

	/**
	 * Constructor
	 *
	 * @param \Cake\ORM\Table $table The table this behavior is attached to.
	 * @param array $config The settings for this behavior.
	 */
	public function __construct(Table $table, array $config = []) {
		$this->_defaultConfig = Hash::merge($this->_defaultConfig, (array)Configure::read('UserTools.Behavior'));
		parent::__construct($table, $config);

		$this->_defaultPasswordHasher = $this->getConfig('passwordHasher');
		$this->_table = $table;

		if ($this->_config['defaultValidation'] === true) {
			$this->setupDefaultValidation();
		}

		$this->eventManager()->on($this->_table);
	}

	/**
	 * Gets the mapped field name of the table
	 *
	 * @param string $field Field name to get the mapped field for
	 * @throws \RuntimeException
	 * @return string field name of the table
	 */
	protected function _field($field) {
		if (!isset($this->_config['fieldMap'][$field])) {
			throw new RuntimeException(__d('user_tools', 'Invalid field "%s"!', $field));
		}

		return $this->_config['fieldMap'][$field];
	}

	/**
	 * Sets validation rules for users up.
	 *
	 * @return void
	 */
	public function setupDefaultValidation() {
		$validator = $this->_table->validator('default');
		$validator = $this->validationUserName($validator);
		$validator = $this->validationPassword($validator);
		$validator = $this->validationConfirmPassword($validator);
		$this->validationEmail($validator);
	}

	/**
	 * Returns a datetime in the format Y-m-d H:i:s
	 *
	 * @param string $time strtotime() compatible string, default is "+1 day"
	 * @param string $dateFormat date() compatible date format string
	 * @return string
	 */
	public function expirationTime($time = '+1 day', $dateFormat = 'Y-m-d H:i:s') {
		return date($dateFormat, strtotime($time));
	}

	/**
	 * Updates a given field with the current date time in the format Y-m-d H:i:s
	 *
	 * @param string $userId User id
	 * @param string $field Default is "last_action", changing it allows you to use this method also for "last_login" for example
	 * @param array $options Options array
	 * @return bool True on success
	 */
	public function updateLastActivity($userId = null, $field = 'last_action', $options = []) {
		$options = Hash::merge($this->_config['updateLastActivity'], $options);
		if ($this->_table->exists([
			$this->_table->aliasField($this->_table->primaryKey()) => $userId
		])) {
			return $this->_table->updateAll(
				[$field => date($options['dateFormat'])],
				[$this->_table->primaryKey() => $userId]
			);
		}

		return false;
	}

	/**
	 * Handles the email verification if required
	 *
	 * @param \Cake\Datasource\EntityInterface $entity User entity
	 * @param array $options Options array
	 * @return void
	 */
	protected function _emailVerification(EntityInterface &$entity, $options) {
		if ($options['emailVerification'] === true) {
			$entity->set($this->_field('emailToken'), $this->generateToken($options['tokenLength']));
			if ($options['verificationExpirationTime'] !== false) {
				$entity->set($this->_field('emailTokenExpires'), $this->expirationTime($options['verificationExpirationTime']));
			}
			$entity->set($this->_field('emailVerified'), false);
		} else {
			$entity->set($this->_field('emailVerified'), true);
		}
	}

	/**
	 * Behavior internal before registration callback
	 *
	 * This method deals with most of the settings for the registration that can be
	 * applied before the actual user record is saved.
	 *
	 * @param \Cake\Datasource\EntityInterface $entity Users entity
	 * @param array $options Options
	 * @return \Cake\Datasource\EntityInterface
	 */
	protected function _beforeRegister(EntityInterface $entity, $options = []) {
		$options = Hash::merge($this->_config['register'], $options);

		$schema = $this->_table->getSchema();
		$columnType = $schema->columnType($this->_table->getPrimaryKey());

		if ($this->_config['useUuid'] === true && $columnType !== 'integer') {
			$primaryKey = $this->_table->getPrimaryKey();
			$entity->set($primaryKey, Text::uuid());
		}

		if ($options['userActive'] === true) {
			$entity->set($this->_field('active'), true);
		}

		$this->_emailVerification($entity, $options);

		if (!isset($entity->{$this->_field('role')})) {
			$entity->set($this->_field('role'), $options['defaultRole']);
		}

		if ($options['generatePassword'] === true) {
			$password = $this->generatePassword();
			$entity->set($this->_field('password'), $password);
			$entity->set('clear_password', $password);
		}

		if ($options['hashPassword'] === true) {
			$entity->set($this->_field('password'), $this->hashPassword($entity->get($this->_field('password'))));
		}

		return $entity;
	}

	/**
	 * Find users with verified emails.
	 *
	 * @param \Cake\ORM\Query $query Query object
	 * @param array $options Options
	 * @return Query
	 */
	public function findEmailVerified(Query $query, array $options) {
		$query->where([
			$this->_table->aliasField($this->_field('emailVerified')) => true,
		]);

		return $query;
	}

	/**
	 * Find Active Users.
	 *
	 * @param \Cake\ORM\Query $query Query object
	 * @param array $options Options
	 * @return \Cake\ORM\Query Modified query
	 */
	public function findActive(Query $query, array $options) {
		$query->where([
			$this->_table->aliasField($this->_field('active')) => true,
		]);

		return $query;
	}

	/**
	 * Registers a new user
	 *
	 * You can modify the registration process through implementing an event
	 * listener for the User.beforeRegister and User.afterRegister events.
	 *
	 * If you stop the events the result of the event will be returned.
	 *
	 * Flow:
	 * - calls the behaviors _beforeRegister method if not disabled via config
	 * - Fires the User.beforeRegister event
	 * - Attempt to save the user data
	 * - calls the behaviors _afterRegister method if not disabled via config
	 * - Fires the User.afterRegister event
	 *
	 * Options:
	 * - `beforeRegister` - Bool to call the internal _beforeRegister() method.
	 *    Default is true
	 * - `afterRegister` - Bool to call the internal _beforeRegister() method.
	 *    Default is true
	 * - `tokenLength` - Length of the verification token, default is 32
	 * - `saveOptions` Optional save options to be passed to the save() call.
	 * - `verificationExpirationTime` - Default is `+1 day`
	 * - `emailVerification` - Use email verification or not, default is true.
	 * - `defaultRole` - Default role to set in the mapped `role` field.
	 * - `userActive` - Set the user to active by default on registration.
	 *    Default is true
	 * - `generatePassword` - To generate a password or not. Default is false.
	 * - `hashPassword` - To has the password or not, default is true.
	 *
	 * @param \Cake\Datasource\EntityInterface $entity User Entity
	 * @param array $options Options
	 * @throws \InvalidArgumentException
	 * @return \Cake\Datasource\EntityInterface|bool Returns bool false if the user could not be saved.
	 */
	public function register(EntityInterface $entity, $options = []) {
		$options = array_merge($this->_config['register'], $options);

		if ($options['beforeRegister'] === true) {
			$entity = $this->_beforeRegister($entity, $options);
		}

		$event = $this->dispatchEvent('User.beforeRegister', [
			'data' => $entity,
			'table' => $this->_table
		]);

		if ($event->isStopped()) {
			return $event->result;
		}

		$result = $this->_table->save($entity, $options['saveOptions']);

		if (!$result) {
			return $result;
		}

		if ($options['afterRegister'] === true) {
			$this->_afterRegister($result, $options);
		}

		$event = $this->dispatchEvent('user.afterRegister', [
			'data' => $result,
			'table' => $this->_table
		]);

		if ($event->isStopped()) {
			return $event->result;
		}

		return $result;
	}

	/**
	 * _afterRegister
	 *
	 * @param \Cake\Datasource\EntityInterface $entity User entity
	 * @param array $options Options
	 * @return \Cake\Datasource\EntityInterface
	 */
	protected function _afterRegister(EntityInterface $entity, $options) {
		if ($entity) {
			if ($options['emailVerification'] === true) {
				$this->sendVerificationEmail($entity, [
					'to' => $entity->get($this->_field('email'))
				]);
			}
		}

		return $entity;
	}

	/**
	 * Verify the email token
	 *
	 * @param string $token The token to check.
	 * @param array $options Options array.
	 * @throws \Cake\Datasource\Exception\RecordNotFoundException if the token was not found at all
	 * @return bool|\Cake\ORM\Entity Returns false if the token has expired
	 */
	public function verifyToken($token, $options = []) {
		$defaults = [
			'tokenField' => $this->_field('emailToken'),
			'expirationField' => $this->_field('emailTokenExpires'),
			'returnData' => false,
		];
		$options = Hash::merge($defaults, $options);

		$result = $this->_getUser($token, [
			'field' => $options['tokenField'],
			'notFoundErrorMessage' => __d('burzum/user_tools', 'Invalid token!')
		]);

		$time = new Time();
		$result->set(
			'token_is_expired',
			$result->get($options['expirationField']) <= $time
		);

		$this->afterTokenVerification($result, $options);

		$event = $this->dispatchEvent('User.afterTokenVerification', [
			'data' => $result,
			'options' => $options
		]);

		$this->eventManager()->dispatch($event);
		if ($event->isStopped()) {
			return (bool)$event->result;
		}

		if ($options['returnData'] === true) {
			return $result;
		}

		return $result->get('token_is_expired');
	}

	/**
	 * afterTokenVerification
	 *
	 * @param \Cake\ORM\Entity $user Entity object.
	 * @param array $options Options array.
	 * @return mixed
	 */
	public function afterTokenVerification(Entity $user, $options = []) {
		if ($user->get('token_is_expired') === true) {
			return false;
		}

		if ($options['tokenField'] === $this->_field('emailToken')) {
			$user->set([
				$this->_field('emailVerified') => true,
				$this->_field('emailToken') => null,
				$this->_field('emailTokenExpires') => null
			], [
				'guard' => false
			]);
		}

		return $this->_table->save($user, ['validate' => false]);
	}

	/**
	 * Verify the email token
	 *
	 * @param string $token Token string to check.
	 * @param array $options Options array.
	 * @throws \Cake\Datasource\Exception\RecordNotFoundException if the token was not found at all
	 * @return bool Returns false if the token has expired
	 */
	public function verifyEmailToken($token, $options = []) {
		$defaults = [
			'tokenField' => $this->_field('emailToken'),
			'expirationField' => $this->_field('emailTokenExpires'),
		];

		return $this->verifyToken($token, Hash::merge($defaults, $options));
	}

	/**
	 * Verify the password reset token
	 *
	 * - `tokenField` - The field to check for the token
	 * - `expirationField` - The field to check for the token expiration date
	 *
	 * @param string $token Token string
	 * @param array $options Options
	 * @throws \Cake\Datasource\Exception\RecordNotFoundException if the token was not found at all
	 * @return bool Returns false if the token has expired
	 */
	public function verifyPasswordResetToken($token, $options = []) {
		$defaults = [
			'tokenField' => $this->_field('passwordToken'),
			'expirationField' => $this->_field('passwordTokenExpires'),
			'returnData' => true,
		];

		return $this->verifyToken($token, Hash::merge($defaults, $options));
	}

	/**
	 * Password reset, compares the two passwords and saves the new password.
	 *
	 * @param \Cake\Datasource\EntityInterface $user Entity object.
	 * @return bool|\Cake\Datasource\EntityInterface
	 */
	public function resetPassword(Entity $user) {
		if (!$user->getErrors()) {
			$user->{$this->_field('password')} = $this->hashPassword($user->{$this->_field('password')});
			$user->{$this->_field('passwordToken')} = null;
			$user->{$this->_field('passwordTokenExpires')} = null;

			return $this->_table->save($user, ['checkRules' => false]);
		}

		return false;
	}

	/**
	 * Removes all users from the user table that did not complete the registration
	 *
	 * @param array $conditions Find conditions passed to where()
	 * @return int Number of removed records
	 */
	public function removeExpiredRegistrations($conditions = []) {
		$defaults = [
			$this->_table->aliasField($this->_field('emailVerified')) => 0,
			$this->_table->aliasField($this->_field('emailTokenExpires')) . ' <' => date('Y-m-d H:i:s')
		];

		$conditions = Hash::merge($defaults, $conditions);
		$count = $this->_table
			->find()
			->where($conditions)
			->count();

		if ($count > 0) {
			$this->_table->deleteAll($conditions);
		}

		return $count;
	}

	/**
	 * Changes the password for an user.
	 *
	 * @param \Cake\Datasource\EntityInterface $entity User entity
	 * @param array $options Options
	 * @return bool
	 */
	public function changePassword(EntityInterface $entity, array $options = []) {
		$options = Hash::merge($this->_config['changePassword'], $options);

		if ($entity->errors()) {
			return false;
		}

		if ($options['hashPassword'] === true) {
			$field = $this->_field('password');
			$entity->set($field, $this->hashPassword($entity->get($field)));
		}

		return (bool)$this->_table->save($entity);
	}

	/**
	 * Initializes a password reset process.
	 *
	 * @param mixed $value User id or other value to look the user up
	 * @param array $options Options
	 * @return void
	 */
	public function initPasswordReset($value, $options = []) {
		$defaults = [
			'field' => [
				$this->_table->aliasField($this->_field('email')),
				$this->_table->aliasField($this->_field('username'))
			]
		];

		$options = Hash::merge($defaults, $this->_config['initPasswordReset'], $options);
		$result = $this->_getUser($value, $options);

		if (empty($result)) {
			throw new RecordNotFoundException(__d('burzum/user_tools', 'User not found.'));
		}

		$result->set([
			$this->_field('passwordToken') => $this->generateToken($options['tokenLength']),
			$this->_field('passwordTokenExpires') => $this->expirationTime($options['expires'])
		], [
			'guard' => false
		]);

		if (!$this->_table->save($result, ['checkRules' => false])) {
			new RuntimeException('Could not initialize password reset. Data could not be saved.');
		}

		$this->sendPasswordResetToken($result, [
			'to' => $result->get($this->_field('email'))
		]);
	}

	/**
	 * Finds a single user, convenience method.
	 *
	 * @param mixed $value User ID or other value to look the user up
	 * @param array $options Options
	 * @throws \Cake\Datasource\Exception\RecordNotFoundException;
	 * @return \Cake\ORM\Entity
	 */
	public function getUser($value, $options = []) {
		return $this->_getUser($value, $options);
	}

	/**
	 * Finds the user for the password reset.
	 *
	 * Extend the behavior and override this method if the configuration options
	 * are not sufficient.
	 *
	 * @param mixed $value User lookup value
	 * @param array $options Options
	 * @throws \Cake\Datasource\Exception\RecordNotFoundException
	 * @return \Cake\Datasource\EntityInterface
	 */
	protected function _getUser($value, $options = []) {
		$defaults = [
			'notFoundErrorMessage' => __d('user_tools', 'User not found.'),
			'field' => $this->_table->aliasField($this->_table->getPrimaryKey())
		];
		$defaults = Hash::merge($defaults, $this->getConfig('getUser'));

		if (isset($options['field'])) {
			$defaults['field'] = $options['field'];
		}
		$options = Hash::merge($defaults, $options);

		$query = $this->_getFindUserQuery($value, $options);

		if (isset($options['queryCallback']) && is_callable($options['queryCallback'])) {
			$query = $options['queryCallback']($query, $options);
		}

		$result = $query->first();

		if (empty($result)) {
			throw new RecordNotFoundException($options['notFoundErrorMessage']);
		}

		return $result;
	}

	/**
	 * Sets the query object for the _getUser() method up.
	 *
	 * @param array|string $value Value
	 * @param array $options Options.
	 * @return \Cake\ORM\Query
	 */
	protected function _getFindUserQuery($value, $options) {
		if (is_string($value) && $this->_table->hasFinder($value)) {
			$query = $this->_table->find($value, ['getUserOptions' => $options]);
		} else {
			$query = $this->_table->find();
		}

		if (is_array($options['field'])) {
			foreach ($options['field'] as $field) {
				$query->orWhere([$field => $value]);
			}

			return $query;
		}

		return $query->where([$options['field'] => $value]);
	}

	/**
	 * Sends a new password to an user by email.
	 *
	 * Note that this is *not* a recommended way to reset an user password. A much
	 * more secure approach is to have the user manually enter a new password and
	 * only send him an URL with a token.
	 *
	 * @param string|EntityInterface $email The user entity or the email
	 * @param array $options Optional options array, use it to pass config options.
	 * @throws \Cake\Datasource\Exception\RecordNotFoundException
	 * @return bool
	 */
	public function sendNewPassword($email, $options = []) {
		if ($email instanceof EntityInterface) {
			$result = $email;
			$email = $result->get($this->_field('email'));
		} else {
			$result = $this->_table->find()
				->where([
					$this->_table->aliasField($this->_field('email')) => $email
				])
				->first();

			if (empty($result)) {
				throw new RecordNotFoundException(__d('user_tools', 'Invalid user'));
			}
		}

		$password = $this->generatePassword();
		$result->set([
			$this->_field('password') => $this->hashPassword($password),
			'clear_password' => $password
		], [
			'guard' => false
		]);

		$this->_table->save($result, ['validate' => false]);

		return $this->sendNewPasswordEmail($result, ['to' => $result->get($this->_field('email'))]);
	}

	/**
	 * beforeSave callback
	 *
	 * @param \Cake\Event\Event $event Event object
	 * @param \Cake\Datasource\EntityInterface $entity Entity
	 * @return void
	 */
	public function beforeSave(Event $event, EntityInterface $entity) {
		$config = (array)$this->getConfig('beforeSave');
		if ($config['handleNewPasswordByOldPassword'] === true) {
			$this->handleNewPasswordByOldPassword($entity);
		}
	}

	/**
	 * Handles the hashing of the password after the old password of the user
	 * was verified and the new password was validated as well.
	 *
	 * Call this in your models beforeSave() or wherever else you want.
	 *
	 * This method will unset by default the `password` and `confirm_password`
	 * fields if no `old_password` was provided or the entity has validation
	 * errors.
	 *
	 * @param \Cake\Datasource\EntityInterface $user User Entity
	 * @return void
	 */
	public function handleNewPasswordByOldPassword(EntityInterface $user) {
		// Don't do it for new users or the password will end up empty
		if ($user->isNew()) {
			return;
		}

		$oldPassword = $user->get($this->_field('oldPassword'));

		if (empty($oldPassword) || $user->errors()) {
			$user->unsetProperty($this->_field('password'));
			$user->unsetProperty($this->_field('passwordCheck'));

			return;
		}

		$newPassword = $this->hashPassword($user->get($this->_field('password')));
		$user->set($this->_field('password'), $newPassword, ['guard' => false]);
	}

	/**
	 * sendNewPasswordEmail
	 *
	 * @param \Cake\Datasource\EntityInterface $user User entity
	 * @param array $options Options
	 * @return void
	 */
	public function sendPasswordResetToken(EntityInterface $user, $options = []) {
		$options = Hash::merge($this->_config['sendPasswordResetToken'], $options);
		$this->getMailer($this->getConfig('mailer'))
			->send('passwordResetToken', [$user, $options]);
	}

	/**
	 * sendNewPasswordEmail
	 *
	 * @param \Cake\Datasource\EntityInterface $user User entity
	 * @param array $options Options
	 * @return void
	 */
	public function sendNewPasswordEmail(EntityInterface $user, $options = []) {
		$options = Hash::merge($this->_config['sendNewPasswordEmail'], $options);
		$this->getMailer($this->getConfig('mailer'))
			->send('verificationEmail', [$user, $options]);
	}

	/**
	 * sendVerificationEmail
	 *
	 * @param \Cake\Datasource\EntityInterface $user User entity
	 * @param array $options Options
	 * @return void
	 */
	public function sendVerificationEmail(EntityInterface $user, $options = []) {
		$options = Hash::merge($this->_config['sendVerificationEmail'], $options);
		$this->getMailer($this->getConfig('mailer'))
			->send('verificationEmail', [$user, $options]);
	}
}
