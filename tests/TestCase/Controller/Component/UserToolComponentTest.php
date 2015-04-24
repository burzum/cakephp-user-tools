<?php
namespace Burzum\UserTools\Test\TestCase\Controller\Component;

use Cake\Controller\ComponentRegistry;
use Cake\Controller\Controller;
use Cake\Network\Response;
use Cake\Network\Request;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * UserToolComponent
 *
 * @author Florian Krämer
 * ]@copyright 2013 - 2014 Florian Krämer
 * @license MIT
 */
class UserToolComponentTest extends TestCase {

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
	public function setUp() {
		parent::setUp();
		$this->request = new Request();
		$this->response = new Response();
		$this->Users = TableRegistry::get('Users');
//		$this->Controller = new Controller($this->request, $this->response);
//		$this->Registry = new ComponentRegistry($this->Controller);
	}

/**
 * tearDown
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
	}

/**
 *
 */
	public function testMapAction() {
		//$this->Registry->load('Burzum/UserTools.UserTool');
	}

}