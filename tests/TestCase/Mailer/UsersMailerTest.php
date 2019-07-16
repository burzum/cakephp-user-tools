<?php
namespace Burzum\UserTools\Test\TestCase\Mailer;

use Burzum\UserTools\Mailer\UsersMailer;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Cake\View\ViewBuilder;

/**
 * UsersMailerTest
 */
class UsersMailerTest extends TestCase
{

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
     * @var \Cake\View\ViewBuilder
     */
    public $ViewBuilderMock;

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
    public function setUp()
    {
        parent::setUp();
        $this->Users = TableRegistry::getTableLocator()->get('Users');

        $this->Mailer = $this->getMockBuilder(UsersMailer::class)
            ->setMethods([
                'setSubject', 'set', 'setEmailFormat', 'setTo', 'setCc', 'setFrom', 'viewBuilder'
            ])
            ->getMock();

        $this->ViewBuilderMock = $this->getMockBuilder(ViewBuilder::class)
            ->getMock();

        $this->Mailer->expects($this->any())
            ->method('viewBuilder')
            ->willReturn($this->ViewBuilderMock);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Mailer);
    }

    /**
     * testVerificationEmail
     *
     * @return void
     */
    public function testVerificationEmail()
    {
        $user = $this->Users->get(1);

        $this->Mailer->expects($this->once())
            ->method('setTo')
            ->with('adminuser@testuser.com')
            ->will($this->returnSelf());

        $this->Mailer->expects($this->once())
            ->method('setSubject')
            ->with('Please verify your Email')
            ->will($this->returnSelf());

        $this->Mailer->viewBuilder()->expects($this->once())
            ->method('setTemplate')
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
    public function testPasswordResetToken()
    {
        $user = $this->Users->get(1);

        $this->Mailer->expects($this->once())
            ->method('setTo')
            ->with('adminuser@testuser.com')
            ->will($this->returnSelf());

        $this->Mailer->expects($this->once())
            ->method('setSubject')
            ->with('Your password reset')
            ->will($this->returnSelf());
/*
        $this->Mailer->expects($this->once())
            ->method('template')
            ->with('Burzum/UserTools.Users/password_reset')
            ->will($this->returnSelf());
*/
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
    public function testSendNewPasswordEmail()
    {
        $user = $this->Users->get(1);

        $this->Mailer->expects($this->once())
            ->method('setTo')
            ->with('adminuser@testuser.com')
            ->will($this->returnSelf());

        $this->Mailer->expects($this->once())
            ->method('setSubject')
            ->with('Your new password')
            ->will($this->returnSelf());
/*
        $this->Mailer->expects($this->once())
            ->method('template')
            ->with('Burzum/UserTools.Users/new_password')
            ->will($this->returnSelf());
*/
        $this->Mailer->expects($this->once())
            ->method('set')
            ->with('user', $user)
            ->will($this->returnSelf());

        $this->Mailer->sendNewPasswordEmail($user);
    }
}
