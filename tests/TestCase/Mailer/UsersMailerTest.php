<?php
namespace Burzum\UserTools\Test\TestCase\Mailer;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

class ProfileApplicationMailerTest extends TestCase {

	/**
	 * Mailer
	 *
	 * @var \Burzum\UserTools\Mailer\UsersMailer
	 */
	public $Mailer;

	/**
	 * Users Table
	 *
	 * @var \Cake\ORM\Table
	 */
	public $Users;

	/**
	 * Fixtures
	 *
	 * @var array
	 */
	public $fixtures = [
		'plugin.Burzum\UserTools.User'
	];

	/**
	 * setUp method
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		$this->Users = TableRegistry::get('Users');

		$this->Mailer = $this->getMockBuilder('Burzum\UserTools\Mailer\UsersMailer')
			->setMethods(['subject', 'set', 'emailFormat', 'to', 'cc', 'from', 'template'])
			->getMock();
	}

	/**
	 * tearDown method
	 *
	 * @return void
	 */
	public function tearDown() {
		unset($this->Mailer);
	}

	/**
	 * testVerificationEmail
	 *
	 * @return void
	 */
	public function testVerificationEmail() {
		$user = $this->Users->get(1);

		$this->Mailer->expects($this->once())
			->method('to')
			->with('adminuser@testuser.com')
			->will($this->returnSelf());

		$this->Mailer->expects($this->once())
			->method('subject')
			->with('Please verify your Email')
			->will($this->returnSelf());

		$this->Mailer->expects($this->once())
			->method('template')
			->with('Burzum/UserTools.Users/verification_email')
			->will($this->returnSelf());

		$this->Mailer->expects($this->once())
			->method('set')
			->with('user', $user)
			->will($this->returnSelf());

		$this->Mailer->verificationEmail($user);
	}

	/**
	 * testPasswordResetToken
	 *
	 * @return void
	 */
	public function testPasswordResetToken() {
		$user = $this->Users->get(1);

		$this->Mailer->expects($this->once())
			->method('to')
			->with('adminuser@testuser.com')
			->will($this->returnSelf());

		$this->Mailer->expects($this->once())
			->method('subject')
			->with('Your password reset')
			->will($this->returnSelf());

		$this->Mailer->expects($this->once())
			->method('template')
			->with('Burzum/UserTools.Users/password_reset')
			->will($this->returnSelf());

		$this->Mailer->expects($this->once())
			->method('set')
			->with('user', $user)
			->will($this->returnSelf());

		$this->Mailer->passwordResetToken($user);
	}

	/**
	 * testSendNewPasswordEmail
	 *
	 * @return void
	 */
	public function testSendNewPasswordEmail() {
		$user = $this->Users->get(1);

		$this->Mailer->expects($this->once())
			->method('to')
			->with('adminuser@testuser.com')
			->will($this->returnSelf());

		$this->Mailer->expects($this->once())
			->method('subject')
			->with('Your new password')
			->will($this->returnSelf());

		$this->Mailer->expects($this->once())
			->method('template')
			->with('Burzum/UserTools.Users/new_password')
			->will($this->returnSelf());

		$this->Mailer->expects($this->once())
			->method('set')
			->with('user', $user)
			->will($this->returnSelf());

		$this->Mailer->sendNewPasswordEmail($user);
	}
}
