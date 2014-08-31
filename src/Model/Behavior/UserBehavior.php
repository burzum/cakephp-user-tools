<?php
/**
 * UserBehavior
 *
 * @author Florian Krämer
 * ]@copyright 2013 - 2014 Florian Krämer
 * @copyright 2012 Cake Development Corporation
 * @license MIT
 */
namespace UserTools\Model\Behavior;

use Cake\ORM\Behavior;
use Cake\ORM\Table;
use Cake\Utility\Hash;
use Cake\Utility\String;
use Cake\Error\NotFoundException;
use Cake\Network\Email\Email;
use Cake\Event\EventManager;
use Cake\Validation\Validator;
use Cake\Auth\PasswordHasherFactory;

class UserBehavior extends Behavior {

/**
 * Default config
 *
 * @var array
 */
	protected $_defaultConfig = array(
		'emailConfig' => 'default',
		'defaultValidation' => true,
		'entityClass' => '\Cake\ORM\Entity',
		'useUuid' => true,
		'passwordHasher' => 'Default',
		'register' => array(
			'defaultRole' => null,
			'hashPassword' => true,
			'userActive' => true,
			'generatePassword' => false,
			'emailVerification' => true,
			'verificationExpirationTime' => '+1 day',
			'beforeRegister' => true
		),
		'fieldMap' => array(
			'username' => 'username',
			'password' => 'password',
			'email' => 'email',
			'passwordCheck' => 'confirm_password',
			'lastAction' => 'last_action',
			'lastLogin' => 'last_login',
			'role' => 'role',
			'emailToken' => 'email_token',
			'emailTokenExpires' => 'email_token_expires',
			'passwordToken' => 'password_token',
			'passwordTokenExpires' => 'password_token_expires',
			'emailVerified' => 'email_verified',
			'active' => 'active',
		)
	);

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
		parent::__construct($table, $config);
		$this->_table = $table;

		$eventManager = null;
		if (!empty($config['eventManager'])) {
			$eventManager = $config['eventManager'];
		}
		$this->_eventManager = $eventManager ?: new EventManager();

		if ($this->_config['defaultValidation'] === true) {
			$this->setupValidationDefaults($this->_table);
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
 * Gets the mapped field name of the model
 *
 * @param string $field
 * @throws \RuntimeException
 * @return string field name of the model
 */
	protected function _field($field) {
		if (!isset($this->_config['fieldMap'][$field])) {
			throw new \RuntimeException(__d('user_tools', 'Invalid field %s!', $field));
		}
		return $this->_config['fieldMap'][$field];
	}

/**
 * Sets validation rules up
 *
 * @return void
 */
	public function setupValidationDefaults() {
		$Validator = new Validator($this->_table);
		$Validator->add('username', 'not-empty', [
			'rule' => 'notEmpty'
		]);
		$Validator->add('username', 'between', [
			'rule' => ['between', 3, 16]
		]);
		$Validator->add('username', 'alpha-numeric', [
			'rule' => 'alphaNumeric'
		]);
		$Validator->add('email', 'valid-email', [
			'rule' => 'email'
		]);
		$Validator->add('email', 'not-empty', [
			'rule' => 'notEmpty'
		]);
		$this->_table->validator('userRegistration', $Validator);

		/*
		$this->_table->validate = Hash::merge(
			array(
				$this->_field('username') => array(
					'notEmpty' => array(
						'rule' => array('notEmpty'),
						'message' => 'You must fill this field.',
					),
					'alphaNumeric' => array(
						'rule' => array('alphaNumeric'),
						'message' => 'The username must be alphanumeric.'
					),
					'between' => array(
						'rule' => array('between', 3, 16),
						'message' => 'Between 3 to 16 characters'
					),
					'unique' => array(
						'rule' => array('isUnique', $this->_field('username')),
						'message' => 'This username is already in use.',
					),
				),
				$this->_field('email') => array(
					'email' => array(
						'rule' => array('email'),
						'message' => 'This is not a valid email'
					),
					'unique' => array(
						'rule' => array('isUnique', $this->_field('email')),
						'message' => 'The email is already in use'
					)
				),
				$this->_field('password') => array(
					'notEmpty' => array(
						'rule' => array('notEmpty'),
						'message' => 'You must fill this field.',
					),
					'between' => array(
						'rule' => array('between', 6, 64),
						'message' => 'Between 3 to 16 characters'
					),
					'confirmPassword' => array(
						'rule' => array('confirmPassword'),
						'message' => 'The passwords don\'t match!',
					)
				),
				$this->_field('passwordCheck') => array(
					'notEmpty' => array(
						'rule' => array('notEmpty'),
						'message' => 'You must fill this field.',
					),
					'confirmPassword' => array(
						'rule' => array('confirmPassword'),
						'message' => 'The passwords don\'t match!',
					)
				),
			),
			$this->_table->validate
		);
		*/
	}

/**
 * Custom validation method to ensure that the two entered passwords match
 *
 * @param string $password Password
 * @return boolean Success
 */
	public function confirmPassword($password = null) {
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
 * @param string $dateFormat compatible date format string
 * @return boolean True on success
 */
	public function updateLastActivity($userId = null, $field = 'last_action', $dateFormat = 'Y-m-d H:i:s') {
		if (!empty($userId)) {
			$this->_table->id = $userId;
		}
		if ($this->_table->exists([$this->_table->alias() . '.' . $this->_table->primaryKey()])) {
			return $this->_table->saveField($field, date($dateFormat));
		}
		return false;
	}

/**
 * Hash password
 *
 * @param $password
 * @return string Hash
 */
	public function hashPassword($password) {
		$Hasher = $this->passwordHasher();
		return $Hasher->hash($password);
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

		if ($generatePassword !== false) {
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
 * @param array post data
 * @param array options
 * @return boolean
 */
	public function register($postData, $options = []) {
		$options = array_merge($this->_config['register'], $options);

		if (is_array($postData)) {
			$postData = new $this->_config['entityClass']($postData);
		}

		if (!$this->_table->validate($postData, ['validate' => 'userRegistration'])) {
			$this->_table->entity = $postData;
			return false;
		}

		if ($options['beforeRegister'] === true) {
			$postData = $this->_beforeRegister($postData, $options);
		}

		if (method_exists($this->_table, 'beforeRegister')) {
			if (!$this->_table->beforeRegister($postData, $options)) {
				return false;
			}
		}

		if ($this->_config['useUuid'] === true) {
			$postData->id = String::uuid();
		}
		$result = $this->_table->save($postData, array('validate' => false));

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
 * @throws \Cake\Error\NotFoundException if the token was not found at all
 * @param string $token
 * @param array $options
 * @return boolean|array Returns false if the token has expired
 */
	public function verifyToken($token, $options = []) {
		$defaults = array(
			'tokenField' => $this->_field('emailToken'),
			'expirationField' => $this->_field('emailTokenExpires'),
			'returnData' => false,
		);

		$options = Hash::merge($defaults, $options);

		$result = $this->_table->find('first', array(
			'conditions' => array(
				$options['tokenField'] => $token
			)
		));

		if (empty($result)) {
			throw new NotFoundException(__d('user_tools', 'Invalid token'));
		}

		$isExpired = $result[$this->_table->alias()][$this->_field('emailTokenExpires')] <= date('Y-m-d H:i:s');

		if ($options['returnData'] === true) {
			$result[$this->_table->alias()]['is_expired'] = $isExpired;
			return $result;
		}

		return $isExpired;
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
		$defaults = array(
			'tokenField' => $this->_field('emailToken'),
			'expirationField' => $this->_field('emailTokenExpires'),
		);
		$options = Hash::merge($defaults, $options);
		$this->verifyToken($token, $options);
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
		$options = Hash::merge($defaults, $options);
		$this->verifyToken($token, $options);
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

		$defaults = array(
			'vowels' => array(
				'a', 'e', 'i', 'o', 'u'
			),
			'cons' => array(
				'b', 'c', 'd', 'g', 'h', 'j', 'k', 'l', 'm', 'n',
				'p', 'r', 's', 't', 'u', 'v', 'w', 'tr', 'cr', 'br', 'fr', 'th',
				'dr', 'ch', 'ph', 'wr', 'st', 'sp', 'sw', 'pr', 'sl', 'cl'
			)
		);

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
 * @return void
 */
	public function removeExpiredRegistrations($conditions = []) {
		$defaults = array(
			$this->_table->alias() . '.' . $this->_field('emailVerified') => 0,
			$this->_table->alias() . '.' . $this->_field('emailTokenExpires') . ' <' => date('Y-m-d H:i:s')
		);

		$this->_table->deleteAll(Hash::merge($defaults, $conditions));
	}

/**
 * Returns an email instance
 *
 * @param array $config
 * @return CakeEmail CakeEmail instance
 */
	public function getMailInstance($config = null) {
		if (empty($config)) {
			$config = $this->_config['emailConfig'];
		}
		return new Email($config);
	}

/**
 * sendVerificationEmail
 *
 * @param array $data
 * @param array $options
 * @return boolean
 */
	public function sendVerificationEmail($data, $options = []) {
		$defaults = array(
			'subject' => __d('user_tools', 'Please verify your Email'),
			'template' => 'UserTools.Users/verification_email',
			'viewVars' => array(
				'data' => $data
			)
		);
		return $this->sendEmail(Hash::merge($defaults, $options));
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
 * Gets an users record by id or slug
 *
 * @throws \Cake\Error\NotFoundException
 * @param mixed $userId
 * @param array $options
 * @return array
 */
	public function getUser($userId, $options = []) {
		$defaults = array(
			'recursive' => -1,
			'contain' => array(),
			'conditions' => array(
				'OR' => array(
					$this->_table->alias() . '.' . $this->_table->primaryKey() => $userId,
					$this->_table->alias() . '.slug' => $userId,
				),
				$this->_table->alias() . '.email_verified' => 1
			),
		);

		$result = $this->_table->find('first', Hash::merge($defaults, $options));

		if (empty($result)) {
			throw new NotFoundException(__d('user_tools', 'User not found!'));
		}

		return $result;
	}

/**
 * Get the event manager for this Table.
 *
 * @return \Cake\Event\EventManager
 */
	public function getEventManager() {
		return $this->_eventManager;
	}

/**
 * Return password hasher object
 *
 * @return AbstractPasswordHasher Password hasher instance
 * @throws \RuntimeException If password hasher class not found or
 *   it does not extend AbstractPasswordHasher
 */
	public function passwordHasher() {
		if ($this->_passwordHasher) {
			return $this->_passwordHasher;
		}

		$passwordHasher = $this->_config['passwordHasher'];
		return $this->_passwordHasher = PasswordHasherFactory::build($passwordHasher);
	}
}