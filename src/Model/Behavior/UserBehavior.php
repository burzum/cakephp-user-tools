<?php
/**
 * UserBehavior
 *
 * @author Florian Krämer
 * @copyright 2013 - 2015 Florian Krämer
 * @copyright 2012 Cake Development Corporation
 * @license MIT
 */
namespace Burzum\UserTools\Model\Behavior;

use Cake\Auth\PasswordHasherFactory;
use Cake\Core\Configure;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Event\Event;
use Cake\Event\EventDispatcherTrait ;
use Cake\I18n\Time;
use Cake\Mailer\Email;
use Cake\ORM\Behavior;
use Cake\ORM\Table;
use Cake\ORM\Entity;
use Cake\Utility\Hash;
use Cake\Utility\Text;
use Cake\Validation\Validator;

class UserBehavior extends Behavior {

	use EventDispatcherTrait;

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
 * @var array
 */
	protected $_table;

/**
 * AbstractPasswordHasher instance.
 *
 * @var AbstractPasswordHasher
 */
	protected $_passwordHasher;

/**
 * Constructor
 *
 * @param \Cake\ORM\Table $table The table this behavior is attached to.
 * @param array $config The settings for this behavior.
 */
	public function __construct(Table $table, array $config = []) {
		$this->_defaultConfig = Hash::merge($this->_defaultConfig, (array) Configure::read('UserTools.Behavior'));
		parent::__construct($table, $config);
		$this->_table = $table;

		if ($this->_config['defaultValidation'] === true) {
			$this->setupDefaultValidation($this->_table);
		}

		$this->eventManager()->attach($this->_table);
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
		if ($this->_table->exists([
			$this->_table->alias() . '.' . $this->_table->primaryKey() => $userId
		])) {
			return $this->_table->updateAll(
				[$field => date($options['dateFormat'])],
				[$this->_table->primaryKey() => $userId]
			);
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
 * _emailVerification
 *
 * @param \Cake\Datasource\EntityInterface $entity
 * @param array $options
 * @return void
 */
	protected function _emailVerification(EntityInterface &$entity, $options) {
		if ($options['emailVerification'] === true) {
			$entity->{$this->_field('emailToken')} = $this->generateToken($options['tokenLength']);
			if ($options['verificationExpirationTime'] !== false) {
				$entity->{$this->_field('emailTokenExpires')} = $this->expirationTime($options['verificationExpirationTime']);
			}
			$entity->{$this->_field('emailVerified')} = false;
		} else {
			$entity->{$this->_field('emailVerified')} = true;
		}
	}

/**
 * Behavior internal before registration callback
 *
 * This method deals with most of the settings for the registration that can be
 * applied before the actual user record is saved.
 *
 * @param \Cake\ORM\Entity $entity
 * @param array $options
 * @return Entity
 */
	protected function _beforeRegister(Entity $entity, $options = []) {
		$options = Hash::merge($this->_config['register'], $options);

		if ($this->_config['useUuid'] === true) {
			$primaryKey = $this->_table->primaryKey();
			$entity->{$primaryKey} = Text::uuid();
		}

		if ($options['userActive'] === true) {
			$entity->{$this->_field('active')} = true;
		}

		$this->_emailVerification($entity, $options);

		if (!isset($entity->{$this->_field('role')})) {
			$entity->{$this->_field('role')} = $options['defaultRole'];
		}

		if ($options['generatePassword'] === true) {
			$password = $this->generatePassword();
			$entity->{$this->_field('password')} = $password;
			$entity->clear_password = $password;
		}

		if ($options['hashPassword'] === true) {
			$entity->{$this->_field('password')} = $this->hashPassword($entity->{$this->_field('password')});
		}

		return $entity;
	}

	/**
	 * Find users with verified emails.
	 *
	 * @param Query $query
	 * @param array $options
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
	 * @param Query $query
	 * @param array $options
	 * @return Query
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
 * Flow:
 * - validates the passed $postData
 * - calls the behaviors _beforeRegister if not disabled
 * - calls Model::beforeRegister if implemented
 * - saves the user data
 * - calls Model::afterRegister if implemented
 *
 * @param \Cake\ORM\Entity $entity
 * @param array $options
 * @throws \InvalidArgumentException
 * @return boolean
 */
	public function register(Entity $entity, $options = []) {
		$options = array_merge($this->_config['register'], $options);
		if ($entity->errors()) {
			$this->_table->entity = $entity;
			return false;
		}

		if ($options['beforeRegister'] === true) {
			$entity = $this->_beforeRegister($entity, $options);
		}

		$event = new Event('User.beforeRegister', $this, [
			'data' => $entity,
			'table' => $this->_table
		]);
		$this->eventManager()->dispatch($event);
		if ($event->isStopped()) {
			return (bool) $event->result;
		}

		$result = $this->_table->save($entity, array('validate' => false));

		if ($options['afterRegister'] === true) {
			$entity = $this->_afterRegister($result, $options);
		}

		$event = new Event('User.afterRegister', $this, [
			'data' => $result,
			'table' => $this->_table
		]);
		$this->eventManager()->dispatch($event);
		if ($event->isStopped()) {
			return $event->result;
		}

		return $result;
	}

/**
 * _afterRegister
 *
 * @param \Cake\ORM\Entity $entity
 * @param array $options
 * @return \Cake\ORM\Entity
 */
	protected function _afterRegister($entity, $options) {
		if ($entity) {
			if ($options['emailVerification'] === true) {
				$this->sendVerificationEmail($entity, array(
					'to' => $entity->{$this->_field('email')}
				));
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
 * @return boolean|\Cake\ORM\Entity Returns false if the token has expired
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
			'notFoundErrorMessage' => __d('user_tools', 'Invalid token.')
		]);

		$time = new Time();
		$result->token_is_expired = $result->{$options['expirationField']} <= $time;

		$this->afterTokenVerification($result, $options);

		$event = new Event('User.afterTokenVerification', $this, [
			'data' => $result,
			'options' => $options
		]);
		$this->eventManager()->dispatch($event);
		if ($event->isStopped()) {
			return (bool) $event->result;
		}

		if ($options['returnData'] === true) {
			return $result;
		}
		return $result->token_is_expired;
	}

/**
 * afterTokenVerification
 *
 * @param \Cake\ORM\Entity $user Entity object.
 * @param array $options Options array.
 * @return mixed
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
		return $this->_table->save($user, ['validate' => false]);
	}

/**
 * Verify the email token
 *
 * @param string $token Token string to check.
 * @param array $options Options array.
 * @throws \Cake\Datasource\Exception\RecordNotFoundException if the token was not found at all
 * @return boolean Returns false if the token has expired
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
 * @param string $token
 * @param array $options
 * @throws \Cake\Datasource\Exception\RecordNotFoundException if the token was not found at all
 * @return boolean Returns false if the token has expired
 */
	public function verifyPasswordResetToken($token, $options = []) {
		$defaults = array(
			'tokenField' => $this->_field('passwordToken'),
			'expirationField' => $this->_field('passwordTokenExpires'),
			'returnData' => true,
		);
		return $this->verifyToken($token, Hash::merge($defaults, $options));
	}

/**
 * Password reset, compares the two passwords and saves the new password.
 *
 * @param \Cake\ORM\Entity $user Entity object.
 * @return void
 */
	public function resetPassword(Entity $user) {
		if (!$user->errors()) {
			$user->{$this->_field('password')} = $this->hashPassword($user->{$this->_field('password')});
			$user->{$this->_field('passwordToken')} = null;
			$user->{$this->_field('passwordTokenExpires')} = null;
			return $this->_table->save($user, ['checkRules' => false]);
		}
		return false;
	}

/**
 * Generates a random password that is more or less user friendly.
 *
 * @param int $length Password length, default is 8
 * @param array $options Options array.
 * @return string
 */
	public function generatePassword($length = 8, $options = []) {
		$options = $this->_passwordDictionary($options);
		$password = '';

		srand((double) microtime() * 1000000);
		for ($i = 0; $i < $length; $i++) {
			$password .=
				$options['cons'][mt_rand(0, count($options['cons']) - 1)] .
				$options['vowels'][mt_rand(0, count($options['vowels']) - 1)];
		}

		return substr($password, 0, $length);
	}

/**
 * The dictionary of vowels and consonants for the password generation.
 *
 * @param array $options
 * @return array
 */
	public function _passwordDictionary(array $options = []) {
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
		return Hash::merge($defaults, $options);
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
 * @param \Cake\ORM\Entity $entity User entity
 * @return boolean
 */
	public function changePassword(Entity $entity) {
		if ($entity->errors()) {
			return false;
		}
		$entity->password = $this->hashPassword($entity->password);
		if ($this->_table->save($entity)) {
			return true;
		}
		return false;
	}

	public function validationChangePassword($validator) {
		$validator->provider('userBehavior', $this);
		$validator = $this->validationPassword($validator);
		$validator = $this->validationConfirmPassword($validator);
		$validator = $this->validationOldPassword($validator);
		return $validator;
	}

	protected function validationOldPassword($validator) {
		$validator->provider('userBehavior', $this);

		$validator->provider('userTable', $this->_table);
		$validator->add('old_password', 'notBlank', [
			'rule' => 'notBlank',
			'message' => __d('userTools', 'Enter your old password.')
		]);
		$validator->add('old_password', 'oldPassword', [
			'rule' => ['validateOldPassword', 'password'],
			'provider' => 'userBehavior',
			'message' => __d('user_tools', 'Wrong password, please try again.')
		]);
		return $validator;
	}

/**
 * Validation method for the old password.
 *
 * @param mixed $value
 * @param string $field
 * @param mixed $context
 * @return boolean
 */
	public function validateOldPassword($value, $field, $context) {
		if (Configure::read('debug') > 0 && empty($context['data'][$this->_table->primaryKey()])) {
			throw new \RuntimeException('The user id is required as well to validate the old password!');
		}

		$result = $this->_table->find()
			->select([
				$this->_field('password')
			])
			->where([
				$this->_table->primaryKey() => $context['data'][$this->_table->primaryKey()],
			])
			->first();

		if (!$result) {
			return false;
		}
		return $this->passwordHasher()->check($value, $result->password);
	}

/**
 * Validation rules for the password reset request.
 *
 * @param \Cake\Validation\Validator $validator
 * @return \Cake\Validation\Validator
 * @see Burzum\UserTools\Controller\Component\UserToolComponent::requestPassword()
 */
	public function validationRequestPassword(Validator $validator) {
		$validator = $this->_table->validationDefault($validator);
		$validator->remove($this->_field('email'), 'unique');
		return $validator;
	}

/**
 * Initializes a password reset process.
 *
 * @param mixed $value
 * @param array $options
 * @return array
 */
	public function initPasswordReset($value, $options = []) {
		$defaults = [
			'field' => [
				$this->_table->alias() . '.' . $this->_field('email'),
				$this->_table->alias() . '.' . $this->_field('username')
			]
		];

		$options = Hash::merge($defaults, $this->_config['initPasswordReset'], $options);
		$result = $this->_getUser($value, $options);

		if (empty($result)) {
			throw new RecordNotFoundException(__d('user_tools', 'User not found.'));
		}

		$result->{$this->_field('passwordToken')} = $this->generateToken($options['tokenLength']);
		$result->{$this->_field('passwordTokenExpires')} = $this->expirationTime($options['expires']);

		$this->_table->save($result, ['checkRules' => false]);
		return $this->sendPasswordResetToken($result, [
			'to' => $result->{$this->_field('email')}
		]);
	}

/**
 * Finds a single user, convenience method.
 *
 * @param mixed $value
 * @param array $options
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
 * @param mixed $value
 * @param array $options
 * @throws \Cake\Datasource\Exception\RecordNotFoundException
 * @return \Cake\ORM\Entity
 */
	protected function _getUser($value, $options = []) {
		$defaults = [
			'notFoundErrorMessage' => __d('user_tools', 'User not found'),
			'field' => $this->_table->alias() . '.' . $this->_table->primaryKey()
		];
		$options = Hash::merge($defaults, $options);

		$query = $this->_table->find();

		if (is_array($options['field'])) {
			foreach ($options['field'] as $field) {
				$query->orWhere([$field => $value]);
			}
		} else {
			$query->where([$options['field'] => $value]);
		}

		$result = $query->first();

		if (empty($result)) {
			throw new RecordNotFoundException($options['notFoundErrorMessage']);
		}
		return $result;
	}

/**
 * Sends a new password to an user by email.
 *
 * Note that this is *not* a recommended way to reset an user password. A much
 * more secure approach is to have the user manually enter a new password and
 * only send him an URL with a token.
 *
 * @param string $email
 * @param array $options
 * @throws \Cake\Datasource\Exception\RecordNotFoundException
 * @return boolean
 */
	public function sendNewPassword($email, $options = []) {
		$result = $this->_table->find()
			->where([
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
 * @return \Cake\Mailer\Email;
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
 * @return array
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
 * @return array
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

/**
 * Validates the password reset.
 *
 * Override it as needed to change the rules for only that field.
 *
 * @return void
 */
	public function validationPasswordReset(Validator $validator) {
		return $this->validateOldPassword($validator)
			->validatePassword($validator)
			->validateConfirmPassword($validator);
	}

/**
 * Validates the username field.
 *
 * Override it as needed to change the rules for only that field.
 *
 * @return void
 */
	public function validationUserName(Validator $validator) {
		$validator->provider('userTable', $this->_table);

		$validator->add($this->_field('username'), [
			'notBlank' => [
				'rule' => 'notBlank',
				'message' => __d('user_tools', 'An username is required.')
			],
			'length' => [
				'rule' => ['lengthBetween', 3, 32],
				'message' => __d('user_tools', 'The username must be between 3 and 32 characters.')
			],
			'unique' => [
				'rule' => ['validateUnique', ['scope' => 'username']],
				'provider' => 'userTable',
				'message' => __d('user_tools', 'The username is already in use.')
			],
			'alphaNumeric' => [
				'rule' => 'alphaNumeric',
				'message' => __d('user_tools', 'The username must be alpha numeric.')
			]
		]);
		return $validator;
	}

/**
 * Validates the email field.
 *
 * Override it as needed to change the rules for only that field.
 *
 * @return void
 */
	public function validationEmail(Validator $validator) {
		$validator->provider('userTable', $this->_table);

		$validator->add($this->_field('email'), [
			'notBlank' => [
				'rule' => 'notBlank',
				'message' => __d('user_tools', 'An email is required.')
			],
			'unique' => [
				'rule' => ['validateUnique', [
					'scope' => $this->_field('email')
				]],
				'provider' => 'table',
				'message' => __d('user_tools', 'The email is already in use.')
			],
			'validEmail' => [
				'rule' => 'email',
				'message' => __d('user_tools', 'Must be a valid email address.')
			]
		]);
		return $validator;
	}

/**
 * Validates the password field.
 *
 * Override it as needed to change the rules for only that field.
 *
 * @return void
 */
	public function validationPassword(Validator $validator) {
		$validator->provider('userTable', $this->_table);

		$validator->add($this->_field('password'), [
			'notBlank' => [
				'rule' => 'notBlank',
				'message' => __d('user_tools', 'A password is required.')
			],
			'minLength' => [
				'rule' => ['minLength', 6],
				'message' => __d('user_tools', 'The password must have at least 6 characters.')
			],
			'confirmPassword' => [
				'rule' => ['compareFields', 'confirm_password'],
				'message' => __d('user_tools', 'The passwords don\'t match!'),
				'provider' => 'userTable',
			]
		]);
		return $validator;
	}

/**
 * Validates the confirm_password field.
 *
 * Override it as needed to change the rules for only that field.
 *
 * @return void
 */
	public function validationConfirmPassword(Validator $validator) {
		$validator->provider('userBehavior', $this);

		$validator->add($this->_field('passwordCheck'), [
			'notBlank' => [
				'rule' => 'notBlank',
				'message' => __d('user_tools', 'A password is required.')
			],
			'minLength' => [
				'rule' => ['minLength', 6],
				'message' => __d('user_tools', 'The password must have at least 6 characters.')
			],
			'confirmPassword' => [
				'rule' => ['compareFields', 'password'],
				'message' => __d('user_tools', 'The passwords don\'t match!'),
				'provider' => 'userBehavior',
			]
		]);
		return $validator;
	}

/**
 * Compares the value of two fields.
 *
 * @param mixed $value
 * @param string $field
 * @param Entity $context
 * @return boolean
 */
	public function compareFields($value, $field, $context) {
		if (!isset($context['data'][$field])) {
			return true;
		}
		if ($value === $context['data'][$field]) {
			return true;
		}
		return false;
	}
}
