<?php
namespace Burzum\UserTools\Model;

use Cake\Core\Configure;
use Cake\Validation\Validator;
use RuntimeException;

/**
 * UserValidationTrait
 */
trait UserValidationTrait {

	/**
	 * Validates the password reset.
	 *
	 * Override it as needed to change the rules for only that field.
	 *
	 * @param \Cake\Validation\Validator $validator Validator
	 * @return \Cake\Validation\Validator
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
	 * @param \Cake\Validation\Validator $validator Validator
	 * @return \Cake\Validation\Validator
	 */
	public function validationUserName(Validator $validator) {
		$validator->setProvider('userTable', $this->_table);

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
	 * @param \Cake\Validation\Validator $validator Validator
	 * @return \Cake\Validation\Validator
	 */
	public function validationEmail(Validator $validator) {
		$validator->setProvider('userTable', $this->_table);

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
	 * @param \Cake\Validation\Validator $validator Validator
	 * @return \Cake\Validation\Validator
	 */
	public function validationPassword(Validator $validator) {
		$validator->setProvider('userTable', $this->_table);

		$validator->add($this->_field('password'), [
			'notBlank' => [
				'rule' => 'notBlank',
				'message' => __d('user_tools', 'A password is required.')
			],
			'minLength' => [
				'rule' => ['minLength', $this->_config['passwordMinLength']],
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
	 * @param \Cake\Validation\Validator $validator Validator
	 * @return \Cake\Validation\Validator
	 */
	public function validationConfirmPassword(Validator $validator) {
		$validator->setProvider('userBehavior', $this);

		$validator->add($this->_field('passwordCheck'), [
			'notBlank' => [
				'rule' => 'notBlank',
				'message' => __d('user_tools', 'A password is required.')
			],
			'minLength' => [
				'rule' => ['minLength', $this->_config['passwordMinLength']],
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
	 * Validation rules for the password reset request.
	 *
	 * @param \Cake\Validation\Validator $validator Validator
	 * @return \Cake\Validation\Validator
	 * @see \Burzum\UserTools\Controller\Component\UserToolComponent::requestPassword()
	 */
	public function validationRequestPassword(Validator $validator) {
		$validator = $this->_table->validationDefault($validator);
		$validator->remove($this->_field('email'), 'unique');

		return $validator;
	}

	/**
	 * Configures the validator with rules for the password change
	 *
	 * @param \Cake\Validation\Validator $validator Validator
	 * @return \Cake\Validation\Validator
	 */
	public function validationChangePassword($validator) {
		$validator->setProvider('userBehavior', $this);

		$validator = $this->validationPassword($validator);
		$validator = $this->validationConfirmPassword($validator);
		$validator = $this->validationOldPassword($validator);

		return $validator;
	}

	/**
	 * Configures the validator with rules to check the old password
	 *
	 * @param \Cake\Validation\Validator $validator Validator
	 * @return \Cake\Validation\Validator
	 */
	protected function validationOldPassword($validator) {
		$validator->setProvider('userBehavior', $this);
		$validator->setProvider('userTable', $this->_table);

		$validator->add('old_password', 'notBlank', [
			'rule' => 'notBlank',
			'message' => __d('user_tools', 'Enter your old password.')
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
	 * This method will hash the old password and compare it to the stored hash
	 * in the database. You don't have to hash it manually before validating.
	 *
	 * @param mixed $value Value
	 * @param string $field Field
	 * @param mixed $context Context
	 * @return bool
	 */
	public function validateOldPassword($value, $field, $context) {
		if (Configure::read('debug') > 0 && empty($context['data'][$this->_table->getPrimaryKey()])) {
			throw new RuntimeException('The user id is required as well to validate the old password!');
		}

		$result = $this->_table->find()
			->select([
				$this->_table->aliasField($field)
			])
			->where([
				$this->_table->getPrimaryKey() => $context['data'][$this->_table->getPrimaryKey()],
			])
			->first();

		if (!$result) {
			return false;
		}

		return $this->getPasswordHasher()->check($value, $result->get($field));
	}

	/**
	 * Compares the value of two fields.
	 *
	 * @param mixed $value Value
	 * @param string $field Field
	 * @param Entity $context Context
	 * @return bool
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
