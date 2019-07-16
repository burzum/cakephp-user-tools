<?php
declare(strict_types=1);

namespace Burzum\UserTools\Test\TestCase\Utility;

use Burzum\UserTools\Utility\PasswordGenerator;
use Cake\TestSuite\TestCase;

/**
 * PasswordGeneratorTest
 *
 * @author Florian Krämer
 * @copyright 2013 - 2017 Florian Krämer
 * @license MIT
 */
class PasswordGeneratorTest extends TestCase
{

    /**
     * testGenerate
     *
     * @return void
     */
    public function testGenerate(): void
    {
        $generator = new PasswordGenerator();
        $result = $generator->generate();
        $this->assertEquals(10, strlen($result));

        $result = $generator->generate(6);
        $this->assertEquals(6, strlen($result));
    }
}
