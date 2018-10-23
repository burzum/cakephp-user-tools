<?php
declare(strict_types=1);

namespace Burzum\UserTools\Test\TestCase\Utility;

use Burzum\UserTools\Utility\TokenGenerator;
use Cake\TestSuite\TestCase;

/**
 * TokenGeneratorTest
 *
 * @author Florian Krämer
 * @copyright 2013 - 2017 Florian Krämer
 * @license MIT
 */
class TokenGeneratorTest extends TestCase {
	/**
	 * testGenerate
	 *
	 * @return void
	 */
	public function testGenerate(): void {
		$generator = new TokenGenerator();
		$result = $generator->generate();
		$this->assertEquals(10, strlen($result));

		$result = $generator->generate(6);
		$this->assertEquals(6, strlen($result));
	}
}