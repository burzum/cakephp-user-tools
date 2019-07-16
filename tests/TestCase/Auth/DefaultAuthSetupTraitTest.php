<?php
namespace Burzum\UserTools\Test\TestCase\Controller\Component;

use Burzum\UserTools\Auth\DefaultAuthSetupTrait;
use Cake\Controller\Controller;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

class DefaultAuthSetupTraitController extends Controller
{
    use DefaultAuthSetupTrait;
}

/**
 * DefaultAuthSetupTraitTest
 *
 * @author Florian Kr�mer
 * @copyright 2013 - 2017 Florian Kr�mer
 * @license MIT
 */
class DefaultAuthSetupTraitTest extends TestCase
{

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'plugin.Burzum\UserTools.User'
    ];

    /**
     * setup
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->request = new ServerRequest();
        $this->response = new Response();
        $this->Users = TableRegistry::get('Users');
        $this->Controller = new DefaultAuthSetupTraitController($this->request, $this->response);
        $this->Controller->startupProcess();
        $this->Controller->initialize();
    }

    /**
     * tearDown
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
    }

    /**
     * testSetupAuthentication
     *
     * @return void
     */
    public function testSetupAuthentication()
    {
        $this->assertEquals($this->Controller->components()->loaded(), []);
        $this->Controller->setupAuthentication();
        $this->assertEquals($this->Controller->components()->loaded(), ['Auth']);
        $result = $this->Controller->components()->Auth->getConfig('authenticate');
        $expected = [
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
        ];
        $this->assertEquals($result, $expected);
    }
}
