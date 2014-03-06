<?php
/**
 * UserBehavior
 *
 * @author Florian Krämer
 * @copyright 2013 Florian Krämer
 * @copyright 2012 Cake Development Corporation
 * @license MIT
 */
namespace UserTools\Model\Behavior;

use Cake\Event\Event;
use Cake\ORM\Behavior;
use Cake\ORM\Entity;
use Cake\ORM\Table;

class UserBehavior extends Behavior {

/**
 * Default setting
 *
 * @var array
 */
	protected $_defaults = array(
		'emailConfig' => 'default',
		'defaultValidation' => true,
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
 * Constructor
 *
 * @param Table $table The table this behavior is attached to.
 * @param array $settings The settings for this behavior.
 */
	public function __construct(Table $table, array $settings = []) {
		$this->settings = array_merge($this->_defaults, $settings);
		parent::__construct($table, $settings);
		$this->_table = $table;
	}

/**
 * Setup
 *
 * - defaultValidation: Automatically sets up validation rules, default is true
 * - emailVerification: Email verification process via token, default is true
 * - defaultRole: Used for a role, default is null, enter a string if you want a default role
 * - fieldMap: Internal field names used by the behavior mapped to the real db fields, change the array values to your table names as needed
 *
 * @param Model $Model
 * @param array $config
 * @return void
 */
	public function setup($config = []) {
		$this->settings[$this->_table->alias] = $this->_mergeOptions($this->_defaults, $config);
		if ($this->settings[$this->_table->alias]['defaultValidation'] === true) {
			$this->setupValidationDefaults($Model);
		}
	}

/**
 * Gets the mapped field name of the model
 *
 * @param string $field
 * @throws RuntimeException
 * @return string field name of the model
 */
	protected function _field($field) {
		if (!isset($this->settings[$this->_table->alias]['fieldMap'][$field])) {
			throw new \RuntimeException(__d('user_tools', 'Invalid field %s!', $field));
		}
		return $this->settings[$this->_table->alias]['fieldMap'][$field];
	}

/**
 * Sets validation rules up
 *
 * @param Model $Model
 * @return void
 */
	public function setupValidationDefaults() {
		$this->_table->validate = $this->_mergeOptions(
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
	}

/**
 * Custom validation method to ensure that the two entered passwords match
 *
 * @param Model $Model
 * @param string $password Password
 * @return boolean Success
 */
	public function confirmPassword($password = null) {
		$passwordCheck = $this->_field('passwordCheck');
		$password = $this->_field('password');
		if ((isset($this->_table->data[$this->_table->alias][$passwordCheck]) && isset($this->_table->data[$this->_table->alias][$password]))
			&& ($this->_table->data[$this->_table->alias][$passwordCheck] === $this->_table->data[$this->_table->alias][$password])) {
			return true;
		}
		return false;
	}

/**
 * Returns a datetime in the format Y-m-d H:i:s
 *
 * @param Model $Model
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
 * @param Model $Model
 * @param string $userId User id
 * @param string $field Default is "last_action", changing it allows you to use this method also for "last_login" for example
 * @param string date() compatible date format string
 * @return boolean True on success
 */
	public function updateLastActivity($userId = null, $field = 'last_action', $dateFormat = 'Y-m-d H:i:s') {
		if (!empty($userId)) {
			$this->_table->id = $userId;
		}
		if ($this->_table->exists()) {
			return $this->_table->saveField($field, date($dateFormat));
		}
		return false;
	}

/**
 * Create a hash from string using given method.
 * Fallback on next available method.
 *
 * Override this method to use a different hashing method
 *
 * @param Model $Model
 * @param string $string String to hash
 * @param string $type Method to use (sha1/sha256/md5)
 * @param boolean $salt If true, automatically appends the application's salt
 *     value to $string (Security.salt)
 * @return string Hash
 */
	public function hash($string, $type = 'sha1', $salt = true) {
		return Security::hash($string, $type, $salt);
	}

/**
 * Hash password
 *
 * @param Model $Model
 * @param $password
 * @param array $options
 * @return string Hash
 */
	public function hashPassword($password, $options = []) {
		return $this->hash($Model, $password);
	}

/**
 * Behavior internal before registration callback
 *
 * This method deals with most of the settings for the registration that can be
 * applied before the actual user record is saved.
 *
 * @param Model $Model
 * @param array $postData
 * @param array $options
 * @return void
 */
	protected function _beforeRegister($postData, $options) {
		extract($this->_mergeOptions($this->settings[$this->_table->alias]['register'], $options));

		if ($userActive === true) {
			$postData[$this->_table->alias][$this->_field('active')] = 1;
		}

		if ($emailVerification === true) {
			$postData[$this->_table->alias][$this->_field('emailToken')] = $this->generateToken($Model, 16);
			if ($verificationExpirationTime !== false) {
				$postData[$this->_table->alias][$this->_field('emailTokenExpires')] = $this->expirationTime($Model, $verificationExpirationTime);
			}
			$postData[$this->_table->alias][$this->_field('emailVerified')] = 0;
		} else {
			$postData[$this->_table->alias][$this->_field('emailVerified')] = 1;
		}

		if (!isset($postData[$this->_table->alias][$this->_field('role')])) {
			$postData[$this->_table->alias][$this->_field('role')] = $defaultRole;
		}

		if ($generatePassword !== false) {
			$password = $this->generatePassword($Model, (int)$generatePassword);
			$postData[$this->_table->alias][$this->_field('password')] = $password;
			$postData[$this->_table->alias]['clear_password'] = $password;
		}

		if ($hashPassword === true) {
			$postData[$this->_table->alias][$this->_field('password')] = $this->hashPassword($Model, $postData[$this->_table->alias][$this->_field('password')]);
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
 * @param Model $Model
 * @param array post data
 * @param array options
 * @return boolean
 */
	public function register($postData, $options = []) {
		if (!$this->_table->save($postData, array('validate' => 'only'))) {
			return false;
		}

		$this->settings[$this->_table->alias] = $this->_mergeOptions($this->settings[$this->_table->alias], $options);
		if ($this->settings[$this->_table->alias]['register']['beforeRegister'] === true) {
			$postData = $this->_beforeRegister($Model, $postData, $options);
		}

		if (method_exists($Model, 'beforeRegister')) {
			if (!$this->_table->beforeRegister($postData, $options)) {
				return false;
			}
		}

		$result = $this->_table->saveAll($postData, array('validate' => false));

		if ($result) {
			$postData[$this->_table->alias][$this->_table->primaryKey] = $this->_table->getLastInsertID();
			$this->_table->data = $postData;

			if ($this->settings[$this->_table->alias]['register']['emailVerification'] === true) {
				$this->sendVerificationEmail($Model, $this->_table->data, array(
					'to' => $this->_table->data[$this->_table->alias][$this->_field('email')]
				));
			}

			if (method_exists($Model, 'afterRegister')) {
				return $this->_table->afterRegister();
			}

			return true;
		}

		return false;
	}

/**
 * Verify the email token
 *
 * @throws NotFoundException if the token was not found at all
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

		$options = $this->_mergeOptions($defaults, $options);

		$result = $this->_table->find('first', array(
			'conditions' => array(
				$options['tokenField'] => $token
			)
		));

		if (empty($result)) {
			throw new NotFoundException(__d('user_tools', 'Invalid token'));
		}

		$isExpired = $result[$this->_table->alias][$this->_field('emailTokenExpires')] <= date('Y-m-d H:i:s');

		if ($options['returnData'] === true) {
			$result[$this->_table->alias]['is_expired'] = $isExpired;
			return $result;
		}

		return $isExpired;
	}

/**
 * Verify the email token
 *
 * @throws NotFoundException if the token was not found at all
 * @param Model $Model
 * @param string $token
 * @param array $options
 * @return boolean Returns false if the token has expired
 */
	public function verifyEmailToken($token, $options = []) {
		$defaults = array(
			'tokenField' => $this->_field('emailToken'),
			'expirationField' => $this->_field('emailTokenExpires'),
		);
		$options = $this->_mergeOptions($defaults, $options);
		$this->verifyToken($Model, $token, $options);
	}

/**
 * Verify the password reset token
 *
 * @throws NotFoundException if the token was not found at all
 * @param Model $Model
 * @param string $token
 * @param array $options
 * @return boolean Returns false if the token has expired
 */
	public function verifyPasswordResetToken($token, $options = []) {
		$defaults = array(
			'tokenField' => $this->_field('passwordToken'),
			'expirationField' => $this->_field('passwordTokenExpires'),
		);
		$options = $this->_mergeOptions($defaults, $options);
		$this->verifyToken($Model, $token, $options);
	}

/**
 * Generates a password
 *
 * @param Model $Model
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

		$options = $this->_mergeOptions($defaults, $options);
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
 * @parem Model $Model
 * @param Model $Model
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
 * Removes all users from the user table that are outdated
 *
 * @param Model $Model
 * @param array $conditions
 * @return void
 */
	public function removeExpiredRegistrations($conditions = []) {
		$defaults = array(
			$this->_table->alias . '.' . $this->_field('emailVerified') => 0,
			$this->_table->alias . '.' . $this->_field('emailTokenExpires') . ' <' => date('Y-m-d H:i:s')
		);

		$this->_table->deleteAll($this->_mergeOptions($defaults, $conditions));
	}

/**
 * Returns an email instance
 *
 * @param Model $Model
 * @param array $config
 * @return CakeEmail CakeEmail instance
 */
	public function getMailInstance($config = null) {
		if (empty($config)) {
			$config = $this->settings[$this->_table->alias]['emailConfig'];
		}
		return new CakeEmail($config);
	}

/**
 * sendVerificationEmail
 *
 * @param Model $Model
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
		return $this->sendEmail($Model, $this->_mergeOptions($defaults, $options));
	}

/**
 * sendEmail
 *
 * @param Model $Model
 * @param array $options
 * @return boolean
 */
	public function sendEmail($options = []) {
		$Email = $this->getMailInstance($Model);
		foreach ($options as $option => $value) {
			$Email->{$option}($value);
		}
		return $Email->send();
	}

/**
 * Wrapper around Hash::merge and Set::merge
 *
 * @param array $array1
 * @param array $array2
 * @return array
 */
	protected function _mergeOptions($array1, $array2) {
		if (class_exists('Hash')) {
			return Hash::merge($array1, $array2);
		}
		return Set::merge($array1, $array2);
	}

/**
 * Gets an users record by id or slug
 *
 * @throws NotFoundException
 * @param Model $model
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
					$this->_table->alias . '.' . $this->_table->primaryKey => $userId,
					$this->_table->alias . '.slug' => $userId,
				),
				$this->_table->alias . '.email_verified' => 1
			),
		);

		$result = $this->_table->find('first', Hash::merge($defaults = $options));

		if (empty($result)) {
			throw new NotFoundException(__d('user_tools', 'User not found!'));
		}

		return $result;
	}

}