<?php
/**
 * UserToolComponent
 *
 * @author Florian Krämer
 * @copyright 2013 - 2014 Florian Krämer
 * @license MIT
 */
namespace Burzum\UserTools\Auth;

trait DefaultAuthSetupTrait {

	public function setupAuthentication() {
		if (!$this->_components->loaded('Auth')) {
			$this->_components->load('Auth');
		}
		$this->_components->Auth->config('authenticate', [
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