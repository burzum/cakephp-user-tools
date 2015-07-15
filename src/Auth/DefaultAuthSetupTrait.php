<?php
/**
 * UserToolComponent
 *
 * @author Florian Krämer
 * @copyright 2013 - 2015 Florian Krämer
 * @license MIT
 */
namespace Burzum\UserTools\Auth;

trait DefaultAuthSetupTrait {

/**
 * Gets the component registry.
 *
 * @return \Cake\Controller\ComponentRegistry
 */
	abstract public function components();

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
		$this->components()->Auth->config('authenticate', [
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