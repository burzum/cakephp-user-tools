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
		'emailVerification' => true,
		'defaultRole' => null,
		'hashPassword' => true,
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
	public function setup(Model $Model, $config = array()) {
		$this->settings[$Model->alias] = Hash::merge($this->_defaults, $config);
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
			throw new \RuntimeException(__('Invalid field %s!', $field));
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
		$Model->validate = Hash::merge(
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
 *
 */
	protected function _beforeRegister(Model $Model, $postData, $options) {
		$Model->set($postData);
		$this->settings[$Model->alias] = Hash::merge($this->settings[$Model->alias], $options);

		if ($this->settings[$Model->alias]['emailVerification'] === true) {
			$postData[$Model->alias][$this->_field($Model, 'emailToken')] = $this->generateToken($Model);
			$postData[$Model->alias][$this->_field($Model, 'emailTokenExpires')] = $this->expirationTime($Model);
			$postData[$Model->alias][$this->_field($Model, 'emailVerified')] = 0;
		} else {
			$postData[$Model->alias][$this->_field($Model, 'emailVerified')] = 1;
		}

		if (!isset($postData[$Model->alias][$this->_field($Model, 'role')])) {
			$postData[$Model->alias][$this->_field($Model, 'role')] = $this->settings[$Model->alias]['defaultRole'];
		}

		if ($this->settings[$Model->alias]['hashPassword'] === true) {
			$Model->data[$Model->alias][$this->_field($Model, 'password')] = $this->hash($Model, $Model->data[$Model->alias][$this->_field($Model, 'password')]);
		}
	}

/**
 * Registers a new user
 *
 * @param Model $Model
 * @param array post data
 * @param array options
 * @return boolean
 */
	public function register(Model $Model, $postData, $options = array()) {
		if (!$Model->saveAll($postData, array('validate' => 'only'))) {
			return false;
		}

		$this->settings[$Model->alias] = Hash::merge($this->settings[$Model->alias], $options);
		$Model->set($this->_beforeRegister($Model, $postData, $options));

		if (method_exists($Model, 'beforeRegister')) {
			if (!$Model->beforeRegister()) {
				return false;
			}
		}

		$data = $Model->data;
		$result = $Model->saveAll($Model->data, array('validate' => false));

		if ($result) {
			$data[$Model->alias][$Model->primaryKey] = $Model->getLastInsertID();
			$Model->data = $data;

			if ($this->settings[$Model->alias]['emailVerification'] === true) {
				$this->sendVerificationEmail($Model, $Model->data, array(
					'receiver' => $Model->data[$Model->alias][$this->_field($Model, 'email')]
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
 * @return boolean Returns false if the token has expired
 */
	public function verifyToken(Model $Model, $token, $options = array()) {
		$defaults = array(
			'tokenField' => 'email_token',
			'expirationField' => 'email_token_expires'
		);

		$options = Hash::merge($defaults, $options);

		$result = $Model->find('first', array(
			'conditions' => array(
				$this->_field($Model, 'emailToken') => $token
			)
		));

		if (empty($result)) {
			throw new NotFoundException(__('Invalid token'));
		}

		return ($result[$Model->alias][$this->_field($Model, 'emailTokenExpires')] > date('Y-m-d H:i:s'));
	}

/**
 * Generates a password
 *
 * @param Model $Model
 * @param int $length Password length
 * @param array $options
 * @return string
 */
	public function generatePassword(Model $Model, $length = 8, $options = array()) {
		srand((double)microtime() * 1000000);

		$defaults = array(
			'vovels' => array(
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

		for ($i = 0; $i < $length; $i++) {
			$password =
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
	public function removeExpiredRegistrations(Model $Model, $conditions = array()) {
		$defaults = array(
			$Model->alias . '.' . $this->_field($Model, 'emailVerified') => 0,
			$Model->alias . '.' . $this->_field($Model, 'emailTokenExpires') . ' <' => date('Y-m-d H:i:s')
		);

		$Model->deleteAll(Hash::merge($defaults, $conditions));
	}

/**
 * Returns an email instance
 *
 * @param Model $Model
 * @return CakeEmail CakeEmail instance
 */
	public function getMailInstance(Model $Model) {
		return new CakeEmail($this->settings[$Model->alias]['emailConfig']);
	}

/**
 *
 */
	public function sendRegistrationConfirmation(Model $Model, $data, $options = array()) {
		$defaults = array(
			'subject' => __('Please verify your Email'),
			'viewVars' => array(
				'data' => $data
			)
		);
		$this->sendEmail($Model, Hash::merge($defaults, $options));
	}

/**
 *
 */
	public function sendVerificationEmail(Model $Model, $data, $options = array()) {
		$defaults = array(
			'subject' => __('Please verify your Email'),
			'viewVars' => array(
				'data' => $data
			)
		);
		$this->sendEmail($Model, Hash::merge($defaults, $options));
	}

/**
 *
 */
	public function sendEmail(Model $Model, $options = array()) {
		/*
		$Email = $this->getMailInstance($Model);
		$Email->subject($options['subject'])
			->to($options['receiver'])
			->viewVars($options['viewVars'])
			->template($options['template'])
			->send();
		*/
	}

}