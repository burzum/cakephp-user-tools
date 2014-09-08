<?php
/**
 * UserBehavior
 *
 * @author Florian Krämer
 * @copyright 2013 - 2014 Florian Krämer
 * @copyright 2012 Cake Development Corporation
 * @license MIT
 */
namespace UserTools\Model\Behavior;

use Cake\ORM\Behavior;
use Cake\ORM\Table;
use Cake\ORM\Entity;
use Cake\Utility\Hash;
use Cake\Core\Configure;
use Cake\Error\NotFoundException;
use Cake\Network\Email\Email;
use Cake\Event\EventManager;
use Cake\Auth\PasswordHasherFactory;
use Cake\ORM\Exception\RecordNotFoundException;
use Cake\Event\Event;
use Cake\Utility\String;

class UserBehavior extends Behavior {

/**
 * Default config
 *
 * @var array
 */
	protected $_defaultConfig = [
		'emailConfig' => 'default',
		'defaultValidation' => true,
		'validatorClass' => '\UserTools\Validation\UserRegistrationValidator',
		'entityClass' => '\Cake\ORM\Entity',
		'useUuid' => true,
		'passwordHasher' => 'Default',
		'register' => [
			'defaultRole' => null,
			'hashPassword' => true,
			'userActive' => true,
			'generatePassword' => false,
			'emailVerification' => true,
			'verificationExpirationTime' => '+1 day',
			'beforeRegister' => true
		],
		'fieldMap' => [
			'username' => 'username',
			'password' => 'password',
			'email' => 'email',
			'passwordCheck' => 'confirm_password',
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
		'updateLastActivity' => [
			'dateFormat' => 'Y-m-d H:i:s',
			'validate' => false
		],
		'initPasswordReset' => [
			'tokenLength' => 10,
			'expires' => '+1 day'
		],
		'sendVerificationEmail' => [
			'template' => 'UserTools.Users/verification_email',
		],
		'sendNewPasswordEmail' => [
			'template' => 'UserTools.Users/new_password',
		],
		'sendPasswordResetToken' => [
			'template' => 'UserTools.Users/password_reset',
		]
	];

/**
 * Keeping a reference to the table in order to,
 * be able to retrieve associations and fetch records for counting.
 *
 * @var array
 */
	protected $_table;

/**
 * Password hasher instance.
 *
 * @var AbstractPasswordHasher
 */
	protected $_passwordHasher;

/**
 * Constructor
 *
 * @param Table $table The table this behavior is attached to.
 * @param array $config The settings for this behavior.
 */
	public function __construct(Table $table, array $config = []) {
		$this->_defaultConfig = Hash::merge($this->_defaultConfig, (array)Configure::read('UserTools.Behavior'));
		parent::__construct($table, $config);
		$this->_table = $table;

		$eventManager = null;
		if (!empty($config['eventManager'])) {
			$eventManager = $config['eventManager'];
		}
		$this->_eventManager = $eventManager ?: new EventManager();

		if ($this->_config['defaultValidation'] === true) {
			$this->setupRegistrationValidation($this->_table);
		}
	}

/**
 * Get the Model callbacks this table is interested in.
 *
 * By implementing the conventional methods a table class is assumed
 * to be interested in the related event.
 *
 * Override this method if you need to add non-conventional event listeners.
 * Or if you want you table to listen to non-standard events.
 *
 * @return array
 */
	public function implementedEvents() {
		return [
			'UserBehavior.beforeRegister' => 'beforeRegister',
			'UserBehavior.afterRegister' => 'afterRegister',
		];
	}

/**
 * Gets the mapped field name of the table
 *
 * @param string $field
 * @throws \RuntimeException
 * @return string field name of the table
 */
	protected function _field($field) {
		if (!isset($this->_config['fieldMap'][$field])) {
			throw new \RuntimeException(__d('user_tools', 'Invalid field "%s"!', $field));
		}
		return $this->_config['fieldMap'][$field];
	}

/**
 * Sets validation rules up
 *
 * @return void
 */
	public function setupRegistrationValidation() {
		$Validator = new $this->_config['validatorClass']($this->_table);
		$this->_table->validator('userRegistration', $Validator);
	}

/**
 * Custom validation method to ensure that the two entered passwords match
 *
 * @todo finish me
 * @param mixed $value The value of column to be checked for uniqueness
 * @param array $options The options array, optionally containing the 'scope' key
 * @param array $context The validation context as provided by the validation routine
 * @return bool true if the value is unique
 */
	public function confirmPassword($value, array $options, array $context = []) {
		$passwordCheck = $this->_field('passwordCheck');
		$password = $this->_field('password');
		if ((isset($this->_table->data[$this->_table->alias()][$passwordCheck]) && isset($this->_table->data[$this->_table->alias()][$password]))
			&& ($this->_table->data[$this->_table->alias()][$passwordCheck] === $this->_table->data[$this->_table->alias()][$password])) {
			return true;
		}
		return false;
	}

/**
 * Returns a datetime in the format Y-m-d H:i:s
 *
 * @param string strtotime compatible string, default is "+1 day"
 * @param string date() compatible date format string
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
 * @param array $options
 * @return boolean True on success
 */
	public function updateLastActivity($userId = null, $field = 'last_action', $options = []) {
		$options = Hash::merge($this->_config['updateLastActivity'], $options);
		if ($this->_table->exists([$this->_table->alias() . '.' . $this->_table->primaryKey()])) {
			return $this->_table->save(new $this->_config['entityClass']([
				$this->_table->primaryKey() => $userId,
				$field => date($options['dateFormat'])
			]), ['validate' => $options['validate']]);
		}
		return false;
	}

/**
 * Hashes a password
 *
 * @param $password
 * @return string Hash
 */
	public function hashPassword($password) {
		return $this->passwordHasher()->hash($password);
	}

/**
 * Behavior internal before registration callback
 *
 * This method deals with most of the settings for the registration that can be
 * applied before the actual user record is saved.
 *
 * @param array $postData
 * @param array $options
 * @return void
 */
	protected function _beforeRegister($postData, $options) {
		extract(Hash::merge($this->_config['register'], $options));

		if ($this->_config['useUuid'] === true) {
			$primaryKey = $this->_table->primaryKey();
			$postData->{$primaryKey} = String::uuid();
		}

		if ($userActive === true) {
			$postData->{$this->_field('active')} = 1;
		}

		if ($emailVerification === true) {
			$postData->{$this->_field('emailToken')} = $this->generateToken(16);
			if ($verificationExpirationTime !== false) {
				$postData->{$this->_field('emailTokenExpires')} = $this->expirationTime($verificationExpirationTime);
			}
			$postData->{$this->_field('emailVerified')} = 0;
		} else {
			$postData->{$this->_field('emailVerified')} = 1;
		}

		if (!isset($postData->{$this->_field('role')})) {
			$postData->{$this->_field('role')} = $defaultRole;
		}

		if ($generatePassword === true) {
			$password = $this->generatePassword((int)$generatePassword);
			$postData->{$this->_field('password')} = $password;
			$postData->clear_password = $password;
		}

		if ($hashPassword === true) {
			$postData->{$this->_field('password')} = $this->hashPassword($postData->{$this->_field('password')});
		}

		return $postData;
	}

/**
 * Registers a new user
 *
 * Flow:
 * - validates the passed $postData
 * - calls the behaviors _beforeRegister if not disabled
 * - calls Model::beforeRegister if implemented
 * - saves the user data
 * - calls Model::afterRegister if implemented
 *
 * @param mixed post data
 * @param array options
 * @throws \InvalidArgumentException
 * @return boolean
 */
	public function register($postData, $options = []) {
		$options = array_merge($this->_config['register'], $options);

		if (is_array($postData)) {
			$postData = new $this->_config['entityClass']($postData);
		} elseif (!is_a('\Cake\ORM\Entity', $postData)) {
			throw new \InvalidArgumentException(__d('user_tools', 'Invalid data passed!'));
		}

		if (!$this->_table->validate($postData, ['validate' => 'userRegistration'])) {
			$this->_table->entity = $postData;
			return false;
		}

		if ($options['beforeRegister'] === true) {
			$postData = $this->_beforeRegister($postData, $options);
			if (method_exists($this->_table, 'beforeRegister')) {
				if (!$this->_table->beforeRegister($postData, $options)) {
					return false;
				}
			}
		}

		$event = new Event('User.beforeRegister', $this, ['data' => $postData]);
		$this->_table->eventManager()->dispatch($event);
		if ($event->isStopped()) {
			return (bool)$event->result;
		}

		$result = $this->_table->save($postData, array('validate' => false));

		$event = new Event('User.afterRegister', $this, ['data' => $result]);
		$this->_table->eventManager()->dispatch($event);
		if ($event->isStopped()) {
			return (bool)$event->result;
		}

		if ($result) {
			if ($options['emailVerification'] === true) {
				$this->sendVerificationEmail($result, array(
					'to' => $result->{$this->_field('email')}
				));
			}

			if (method_exists($this->_table, 'afterRegister')) {
				return $this->_table->afterRegister();
			}

			return true;
		}

		return false;
	}

/**
 * Verify the email token
 *
 * @throws \Cake\ORM\Exception\RecordNotFoundException if the token was not found at all
 * @param string $token
 * @param array $options
 * @return boolean|\Cake\ORM\Entity Returns false if the token has expired
 */
	public function verifyToken($token, $options = []) {
		$defaults = [
			'tokenField' => $this->_field('emailToken'),
			'expirationField' => $this->_field('emailTokenExpires'),
			'returnData' => false,
		];
		$options = Hash::merge($defaults, $options);

		$result = $this->_table->find()
			->where([$options['tokenField'] => $token])
			->first();

		if (empty($result)) {
			throw new RecordNotFoundException(__d('user_tools', 'Invalid token'));
		}

		$time = new \Cake\Utility\Time();
		$result->token_is_expired = $result->{$this->_field('emailTokenExpires')} <= $time;

		$this->afterTokenVerification($result, $options);

		if ($options['returnData'] === true) {
			return $result;
		}

		return $result->token_is_expired;
	}

/**
 *
 */
	public function afterTokenVerification(Entity $user, $options = []) {
		if ($user->token_is_expired === true) {
			return false;
		}
		if ($options['tokenField'] === $this->_field('emailToken')) {
			$user->{$this->_field('emailVerified')} = 1;
			$user->{$this->_field('emailToken')} = null;
			$user->{$this->_field('emailTokenExpires')} = null;
		}
		if ($options['tokenField'] === $this->_field('passwordToken')) {
			$user->{$this->_field('passwordToken')} = null;
			$user->{$this->_field('passwordTokenExpires')} = null;
		}
		return $this->_table->save($user, ['validate' => false]);
	}

/**
 * Verify the email token
 *
 * @throws NotFoundException if the token was not found at all
 * @param string $token
 * @param array $options
 * @return boolean Returns false if the token has expired
 */
	public function verifyEmailToken($token, $options = []) {
		$defaults = [
			'tokenField' => $this->_field('emailToken'),
			'expirationField' => $this->_field('emailTokenExpires'),
		];
		$this->verifyToken($token, Hash::merge($defaults, $options));
	}

/**
 * Verify the password reset token
 *
 * @throws NotFoundException if the token was not found at all
 * @param string $token
 * @param array $options
 * @return boolean Returns false if the token has expired
 */
	public function verifyPasswordResetToken($token, $options = []) {
		$defaults = array(
			'tokenField' => $this->_field('passwordToken'),
			'expirationField' => $this->_field('passwordTokenExpires'),
		);
		$this->verifyToken($token, Hash::merge($defaults, $options));
	}

/**
 * Generates a random password that is more or less user friendly
 *
 * @param int $length Password length
 * @param array $options
 * @return string
 */
	public function generatePassword($length = 8, $options = []) {
		srand((double)microtime() * 1000000);

		$defaults = [
			'vowels' => [
				'a', 'e', 'i', 'o', 'u'
			],
			'cons' => [
				'b', 'c', 'd', 'g', 'h', 'j', 'k', 'l', 'm', 'n',
				'p', 'r', 's', 't', 'u', 'v', 'w', 'tr', 'cr', 'br', 'fr', 'th',
				'dr', 'ch', 'ph', 'wr', 'st', 'sp', 'sw', 'pr', 'sl', 'cl'
			]
		];

		if (isset($options['cons'])) {
			unset($defaults['cons']);
		}

		if (isset($options['vowels'])) {
			unset($defaults['vowels']);
		}

		$options = Hash::merge($defaults, $options);
		$password = '';

		for ($i = 0; $i < $length; $i++) {
			$password .=
				$options['cons'][mt_rand(0, count($options['cons']) - 1)] .
				$options['vowels'][mt_rand(0, count($options['vowels']) - 1)];
		}

		return substr($password, 0, $length);
	}

/**
 * Generate token used by the user registration system
 *
 * @param integer $length Token Length
 * @param string $chars
 * @return string
 */
	public function generateToken($length = 10, $chars = '0123456789abcdefghijklmnopqrstuvwxyz') {
		$token = '';
		$i = 0;
		while ($i < $length) {
			$char = substr($chars, mt_rand(0, strlen($chars) - 1), 1);
			if (!stristr($token, $char)) {
				$token .= $char;
				$i++;
			}
		}
		return $token;
	}

/**
 * Removes all users from the user table that did not complete the registration
 *
 * @param array $conditions
 * @return integer Number of removed records
 */
	public function removeExpiredRegistrations($conditions = []) {
		$defaults = [
			$this->_table->alias() . '.' . $this->_field('emailVerified') => 0,
			$this->_table->alias() . '.' . $this->_field('emailTokenExpires') . ' <' => date('Y-m-d H:i:s')
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
 * Initializes a password reset process
 *
 * @param mixed $value
 * @param array $options
 * @return boolean
 */
	public function initPasswordReset($value, $options = []) {
		$defaults = [
			'field' => [
				$this->_table->alias() . '.' . $this->_field('email'),
				$this->_table->alias() . '.' . $this->_field('username')
			]
		];
		$options = Hash::merge($defaults, $this->_config['initPasswordReset'], $options);
		$result = $this->_findUserForPasswordReset($value, $options);
		$result->{$this->_field('passwordToken')} = $this->generateToken($options['tokenLength']);
		$result->{$this->_field('passwordTokenExpires')} = $this->expirationTime($options['expires']);
		$this->_table->save($result, ['validate' => false]);
		return $this->sendPasswordResetToken($result, ['to' => $result->{$this->_field('email')}]);
	}

/**
 * Finds the user for the password reset
 *
 * Extend the behavior and override this method if the configuration options
 * are not sufficient.
 *
 * @param mixed $value
 * @param array $options
 * @throws \Cake\ORM\Exception\RecordNotFoundException
 * @return \Cake\ORM\Entity
 */
	protected function _findUserForPasswordReset($value, $options) {
		$this->_table->connection()->logQueries(true);
		$query = $this->_table->find();
		/*
		if (is_array($options['field'])) {
			$orConditions = [];
			foreach ($options['field'] as $field) {
				$orConditions[$field] = $value;
			}
			$query->orWhere($orConditions);
		} else {
			$query->where([$options['field'] => $value]);
		}*/
		//$query->where([$options['field'] => $value]);
		$query->where(['Users.email' => $value]);
		$result = $query->first();

		if (empty($result)) {
			throw new RecordNotFoundException(__d('user_tools', 'Invalid user'));
		}
		return $result;
	}

/**
 * Sends a new password to an user by email
 *
 * Note that this is *not* a recommended way to reset an user password. A much
 * more secure approach is to have the user manually enter a new password and
 * only send him an URL with a token.
 *
 * @param string $email
 * @param array $options
 * @throws \Cake\ORM\Exception\RecordNotFoundException
 * @return boolean
 */
	public function sendNewPassword($email, $options = []) {
		$result = $this->_table->find()
			->conditions([
				$this->_table->alias() . '.' . $this->_field('email') => $email
			])
			->first();
		if (empty($result)) {
			throw new RecordNotFoundException(__d('user_tools', 'Invalid user'));
		}
		$result->password = $result->clear_password = $this->generatePassword();
		$result->password = $this->hashPassword($result->password);
		$this->_table->save($result, ['validate' => false]);
		return $this->sendNewPasswordEmail($result, ['to' => $result->{$this->_field('email')}]);
	}

/**
 * Gets an users record by id or slug
 *
 * @throws \Cake\ORM\Exception\RecordNotFoundException
 * @param mixed $userId
 * @param array $options
 * @return array
 */
	public function getUser($userId, $options = []) {
		$result = $this->_table
			->find()
			->where([$this->_table->alias() . '.' . $this->_table->primaryKey() => $userId])
			->first();

		if (empty($result)) {
			throw new RecordNotFoundException(__d('user_tools', 'User not found!'));
		}

		return $result;
	}

/**
 * Return password hasher object
 *
 * @return \Cake\Auth\AbstractPasswordHasher Password hasher instance
 * @throws \RuntimeException If password hasher class not found or
 *   it does not extend AbstractPasswordHasher
 */
	public function passwordHasher() {
		if ($this->_passwordHasher) {
			return $this->_passwordHasher;
		}
		return $this->_passwordHasher = PasswordHasherFactory::build($this->_config['passwordHasher']);
	}

/**
 * Returns an email instance
 *
 * @param array $config
 * @return \Cake\Network\Email\Email Email instance
 */
	public function getMailInstance($config = null) {
		if (empty($config)) {
			$config = $this->_config['emailConfig'];
		}
		return new Email($config);
	}

/**
 * sendEmail
 *
 * @param array $options
 * @return boolean
 */
	public function sendEmail($options = []) {
		$Email = $this->getMailInstance();
		foreach ($options as $option => $value) {
			$Email->{$option}($value);
		}
		return $Email->send();
	}

/**
 * sendNewPasswordEmail
 *
 * @param \Cake\ORM\Entity $user
 * @param array $options
 * @return void
 */
	public function sendPasswordResetToken(Entity $user, $options = []) {
		$defaults = [
			'subject' => __d('user_tools', 'Your password reset'),
			'viewVars' => [
				'user' => $user
			]
		];
		return $this->sendEmail(Hash::merge($defaults, $this->_config['sendPasswordResetToken'], $options));
	}

/**
 * sendNewPasswordEmail
 *
 * @param \Cake\ORM\Entity $user
 * @param array $options
 * @return void
 */
	public function sendNewPasswordEmail(Entity $user, $options = []) {
		$defaults = [
			'subject' => __d('user_tools', 'Your new password'),
			'viewVars' => [
				'user' => $user
			]
		];
		return $this->sendEmail(Hash::merge($defaults, $this->_config['sendNewPasswordEmail'], $options));
	}

/**
 * sendVerificationEmail
 *
 * @param \Cake\ORM\Entity $data
 * @param array $options
 * @return boolean
 */
	public function sendVerificationEmail(Entity $data, $options = []) {
		$defaults = [
			'subject' => __d('user_tools', 'Please verify your Email'),
			'viewVars' => [
				'user' => $data
			]
		];
		return $this->sendEmail(Hash::merge($defaults, $this->_config['sendVerificationEmail'], $options));
	}
}
