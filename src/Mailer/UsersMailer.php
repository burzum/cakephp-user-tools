<?php
namespace Burzum\UserTools\Mailer;

use Cake\Mailer\Mailer;

class UsersMailer extends Mailer {

	/**
	 * Sends the verification email to an user.
	 *
	 * @param \Burzum\UserTools\Model\Entity\User
	 * @param array $options
	 * @return void
	 */
	public function verificationEmail($user, array $options = []) {
		$defaults = [
			'to' => $user->email,
			'subject' => __d('user_tools', 'Please verify your Email'),
			'template' => 'Burzum/UserTools.Users/verification_email',
		];
		$this->_applyOptions(array_merge($defaults, $options));
		$this->set('user', $user);
	}

	/**
	 * Sends the password reset token
	 *
	 * @param \Burzum\UserTools\Model\Entity\User
	 * @param array $options
	 * @return void
	 */
	public function passwordResetToken($user, array $options = []) {
		$defaults = [
			'to' => $user->email,
			'subject' => __d('user_tools', 'Your password reset'),
			'template' => 'Burzum/UserTools.Users/password_reset'
		];
		$this->_applyOptions(array_merge($defaults, $options));
		$this->set('user', $user);
	}

	/**
	 * Sends the new password email
	 *
	 * @param \Burzum\UserTools\Model\Entity\User
	 * @param array $options
	 * @return void
	 */
	public function sendNewPasswordEmail($user, array $options = []) {
		$defaults = [
			'to' => $user->email,
			'subject' => __d('user_tools', 'Your new password'),
			'template' => 'Burzum/UserTools.Users/new_password'
		];
		$this->_applyOptions(array_merge($defaults, $options));
		$this->set('user', $user);
	}

	/**
	 * Sets the options from the array to the corresponding mailer methods
	 *
	 * @param array
	 * @return void
	 */
	protected function _applyOptions($options) {
		foreach ($options as $method => $value) {
			$this->{$method}($value);
		}
	}

}
