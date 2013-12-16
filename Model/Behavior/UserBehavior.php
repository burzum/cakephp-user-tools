<?php
App::uses('ModelBehavior', 'Model');
App::uses('CakeEmail', 'Network/Email');

/**
 * UserBehavior
 *
 * @author Florian Krämer
 * @copyright 2013 Florian Krämer
 * @copyright 2012 Cake Development Corporation
 * @license MIT
 */
class UserBehavior extends ModelBehavior {

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
	public function setup(Model $Model, $config = []) {
		$this->settings[$Model->alias] = $this->_mergeOptions($this->_defaults, $config);
		if ($this->settings[$Model->alias]['defaultValidation'] === true) {
			$this->setupValidationDefaults($Model);
		}
	}

/**
 * Gets the mapped field name of the model
 *
 * @param $Model
 * @param string $field
 * @throws RuntimeException
 * @return string field name of the model
 */
	protected function _field($Model, $field) {
		if (!isset($this->settings[$Model->alias]['fieldMap'][$field])) {
			throw new \RuntimeException(__d('user_tools', 'Invalid field %s!', $field));
		}
		return $this->settings[$Model->alias]['fieldMap'][$field];
	}

/**
 * Sets validation rules up
 *
 * @param Model $Model
 * @return void
 */
	public function setupValidationDefaults(Model $Model) {
		$Model->validate = $this->_mergeOptions(
			array(
				$this->_field($Model, 'username') => array(
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
						'rule' => array('isUnique', $this->_field($Model, 'username')),
						'message' => 'This username is already in use.',
					),
				),
				$this->_field($Model, 'email') => array(
					'email' => array(
						'rule' => array('email'),
						'message' => 'This is not a valid email'
					),
					'unique' => array(
						'rule' => array('isUnique', $this->_field($Model, 'email')),
						'message' => 'The email is already in use'
					)
				),
				$this->_field($Model, 'password') => array(
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
				$this->_field($Model, 'passwordCheck') => array(
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
			$Model->validate
		);
	}

/**
 * Custom validation method to ensure that the two entered passwords match
 *
 * @param Model $Model
 * @param string $password Password
 * @return boolean Success
 */
	public function confirmPassword(Model $Model, $password = null) {
		$passwordCheck = $this->_field($Model, 'passwordCheck');
		$password = $this->_field($Model, 'password');
		if ((isset($Model->data[$Model->alias][$passwordCheck]) && isset($Model->data[$Model->alias][$password]))
			&& ($Model->data[$Model->alias][$passwordCheck] === $Model->data[$Model->alias][$password])) {
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
	public function expirationTime(Model $Model, $time = '+1 day', $dateFormat = 'Y-m-d H:i:s') {
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
	public function updateLastActivity(Model $Model, $userId = null, $field = 'last_action', $dateFormat = 'Y-m-d H:i:s') {
		if (!empty($userId)) {
			$Model->id = $userId;
		}
		if ($Model->exists()) {
			return $Model->saveField($field, date($dateFormat));
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
	public function hash(Model $Model, $string, $type = 'sha1', $salt = true) {
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
	public function hashPassword(Model $Model, $password, $options = []) {
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
	protected function _beforeRegister(Model $Model, $postData, $options) {
		extract($this->_mergeOptions($this->settings[$Model->alias]['register'], $options));

		if ($userActive === true) {
			$postData[$Model->alias][$this->_field($Model, 'active')] = 1;
		}

		if ($emailVerification === true) {
			$postData[$Model->alias][$this->_field($Model, 'emailToken')] = $this->generateToken($Model, 16);
			if ($verificationExpirationTime !== false) {
				$postData[$Model->alias][$this->_field($Model, 'emailTokenExpires')] = $this->expirationTime($Model, $verificationExpirationTime);
			}
			$postData[$Model->alias][$this->_field($Model, 'emailVerified')] = 0;
		} else {
			$postData[$Model->alias][$this->_field($Model, 'emailVerified')] = 1;
		}

		if (!isset($postData[$Model->alias][$this->_field($Model, 'role')])) {
			$postData[$Model->alias][$this->_field($Model, 'role')] = $defaultRole;
		}

		if ($generatePassword !== false) {
			$password = $this->generatePassword($Model, (int)$generatePassword);
			$postData[$Model->alias][$this->_field($Model, 'password')] = $password;
			$postData[$Model->alias]['clear_password'] = $password;
		}

		if ($hashPassword === true) {
			$postData[$Model->alias][$this->_field($Model, 'password')] = $this->hashPassword($Model, $postData[$Model->alias][$this->_field($Model, 'password')]);
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
	public function register(Model $Model, $postData, $options = []) {
		if (!$Model->saveAll($postData, array('validate' => 'only'))) {
			return false;
		}

		$this->settings[$Model->alias] = $this->_mergeOptions($this->settings[$Model->alias], $options);
		if ($this->settings[$Model->alias]['register']['beforeRegister'] === true) {
			$postData = $this->_beforeRegister($Model, $postData, $options);
		}

		if (method_exists($Model, 'beforeRegister')) {
			if (!$Model->beforeRegister()) {
				return false;
			}
		}

		$result = $Model->saveAll($postData, array('validate' => false));

		if ($result) {
			$postData[$Model->alias][$Model->primaryKey] = $Model->getLastInsertID();
			$Model->data = $postData;

			if ($this->settings[$Model->alias]['register']['emailVerification'] === true) {
				$this->sendVerificationEmail($Model, $Model->data, array(
					'to' => $Model->data[$Model->alias][$this->_field($Model, 'email')]
				));
			}

			if (method_exists($Model, 'afterRegister')) {
				return $Model->afterRegister();
			}

			return true;
		}

		return false;

	}

/**
 * Verify the email token
 *
 * @throws NotFoundException if the token was not found at all
 * @param Model $Model
 * @param string $token
 * @param array $options
 * @return boolean|array Returns false if the token has expired
 */
	public function verifyToken(Model $Model, $token, $options = []) {
		$defaults = array(
			'tokenField' => $this->_field($Model, 'emailToken'),
			'expirationField' => $this->_field($Model, 'emailTokenExpires'),
			'returnData' => false,
		);

		$options = $this->_mergeOptions($defaults, $options);

		$result = $Model->find('first', array(
			'conditions' => array(
				$options['tokenField'] => $token
			)
		));

		if (empty($result)) {
			throw new NotFoundException(__d('user_tools', 'Invalid token'));
		}

		$isExpired = $result[$Model->alias][$this->_field($Model, 'emailTokenExpires')] <= date('Y-m-d H:i:s');

		if ($options['returnData'] === true) {
			$result[$Model->alias]['is_expired'] = $isExpired;
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
	public function verifyEmailToken(Model $Model, $token, $options = []) {
		$defaults = array(
			'tokenField' => $this->_field($Model, 'emailToken'),
			'expirationField' => $this->_field($Model, 'emailTokenExpires'),
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
	public function verifyPasswordResetToken(Model $Model, $token, $options = []) {
		$defaults = array(
			'tokenField' => $this->_field($Model, 'passwordToken'),
			'expirationField' => $this->_field($Model, 'passwordTokenExpires'),
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
	public function generatePassword(Model $Model, $length = 8, $options = []) {
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
	public function generateToken(Model $Model, $length = 10, $chars = '0123456789abcdefghijklmnopqrstuvwxyz') {
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
	public function removeExpiredRegistrations(Model $Model, $conditions = []) {
		$defaults = array(
			$Model->alias . '.' . $this->_field($Model, 'emailVerified') => 0,
			$Model->alias . '.' . $this->_field($Model, 'emailTokenExpires') . ' <' => date('Y-m-d H:i:s')
		);

		$Model->deleteAll($this->_mergeOptions($defaults, $conditions));
	}

/**
 * Returns an email instance
 *
 * @param Model $Model
 * @param array $config
 * @return CakeEmail CakeEmail instance
 */
	public function getMailInstance(Model $Model, $config = null) {
		if (empty($config)) {
			$config = $this->settings[$Model->alias]['emailConfig'];
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
	public function sendVerificationEmail(Model $Model, $data, $options = []) {
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
	public function sendEmail(Model $Model, $options = []) {
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

}