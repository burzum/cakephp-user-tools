<?php
declare(strict_types = 1);

namespace Burzum\UserTools\Model;

use Burzum\UserTools\Utility\PasswordGenerator;
use Burzum\UserTools\Utility\TokenGenerator;
use Cake\Utility\Hash;

/**
 * A trait to add methods to a class to generate a password and token.
 */
trait PasswordAndTokenTrait {

	/**
	 * Generates a random password that is more or less user friendly.
	 *
	 * @param int $length Password length, default is 8
	 * @param array $options Options array.
	 * @return string
	 */
	public function generatePassword($length = 8, $options = []) {
		$generator = new PasswordGenerator();

		if (isset($options['cons'])) {
			$generator->setConsonants($options['cons']);
		}
		if (isset($options['vowels'])) {
			$generator->setVowels($options['vowels']);
		}

		return $generator->generate($length);
	}

	/**
	 * Generate token used by the user registration system
	 *
	 * @param int $length Token Length
	 * @param string $chars Characters used in the token
	 * @return string
	 */
	public function generateToken($length = 10, $chars = '0123456789abcdefghijklmnopqrstuvwxyz') {
		return (new TokenGenerator())->generate($length, $chars);
	}
}
