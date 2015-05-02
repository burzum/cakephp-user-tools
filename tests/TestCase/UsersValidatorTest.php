<?php
namespace Burzum\UserTools\Test\TestCase\Validation;

use Burzum\UserTools\Validation\UsersValidator;
use Cake\TestSuite\TestCase;
use Cake\View\View;
use Cake\ORM\Entity;
use Burzum\UserTools\View\Helper\AuthHelper;

/**
 * UsersValidatorTestCase
 *
 * @author Florian Krämer
 * @copyright 2013 - 2015 Florian Krämer
 * @license MIT
 */
class UsersValidatorTestCase extends TestCase {

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->Validator = new UsersValidator();
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		unset($this->Validator);
		parent::tearDown();
	}

/**
 * testCompareFields
 *
 * @return void
 */
	public function testCompareFields() {
		$result = $this->Validator->compareFields('test', 'username', [
			'data' => ['username' => 'test']
		]);
		$this->assertTrue($result);
		$result = $this->Validator->compareFields('wrong', 'username', [
			'data' => ['username' => 'test']
		]);
		$this->assertFalse($result);
	}
}
