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

	public function setupAuthentication() {
		if (!$this->components()->loaded('Auth')) {
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