<?php
declare(strict_types = 1);

/**
 * UserToolComponent
 *
 * @author Florian Krämer
 * @copyright 2013 - 2017 Florian Krämer
 * @license MIT
 */
namespace Burzum\UserTools\Auth;

/**
 * DefaultAuthSetupTrait
 */
trait DefaultAuthSetupTrait {

	/**
	 * Sets the default authentication settings up.
	 *
	 * Call this in your beforeFilter().
	 *
	 * @return void
	 */
	public function setupAuthentication() {
		if (!in_array('Auth', $this->components()->loaded())) {
			$this->components()->load('Auth');
		}

		$this->components()->Auth->setConfig('authenticate', [
			'Form' => [
				'userModel' => 'Users',
				'fields' => [
					'username' => 'email',
					'password' => 'password'
				],
				'scope' => [
					'Users.email_verified' => 1
				]
			]
		]);
	}
}
