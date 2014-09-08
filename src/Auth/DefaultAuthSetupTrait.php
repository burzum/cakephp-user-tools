<?php
/**
 * UserToolComponent
 *
 * @author Florian Krämer
 * @copyright 2013 - 2014 Florian Krämer
 * @license MIT
 */
namespace UserTools\Auth;

trait DefaultAuthSetupTrait {

	public function setupAuthentication() {
		if (!$this->_components->loaded('Auth')) {
			$this->_components->load('Auth');
		}
		$this->_components->Auth->config('authenticate', [
			'UserTools.MultiColumn' => [
				'userModel' => 'Users',
				'fields' => [
					'username' => 'email',
					'password' => 'password'
				],
				'columns' => [
					'username',
					'email'
				],
				'scope' => [
					'Users.email_verified' => 1
				]
			]
		]);
	}
}