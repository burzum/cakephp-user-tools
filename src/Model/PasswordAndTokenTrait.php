<?php
namespace Burzum\UserTools\Model;

use Cake\Utility\Hash;
use Cake\Validation\Validator;

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
		$options = $this->_passwordDictionary($options);
		$password = '';

		srand((double) microtime() * 1000000);
		for ($i = 0; $i < $length; $i++) {
			$password .=
				$options['cons'][mt_rand(0, count($options['cons']) - 1)] .
				$options['vowels'][mt_rand(0, count($options['vowels']) - 1)];
		}

		return substr($password, 0, $length);
	}

	/**
	 * The dictionary of vowels and consonants for the password generation.
	 *
	 * @param array $options
	 * @return array
	 */
	public function _passwordDictionary(array $options = []) {
		$defaults = [
			'vowels' => [
				'a', 'e', 'i', 'o', 'u'
			],
			'cons' => [
				'b', 'c', 'd', 'g', 'h', 'j', 'k', 'l', 'm', 'n',
				'p', 'r', 's', 't', 'u', 'v', 'w', 'tr', 'cr', 'br', 'fr', 'th',
				'dr', 'ch', 'ph', 'wr', 'st', 'sp', 'sw', 'pr', 'sl', 'cl'
			]
		];
		if (isset($options['cons'])) {
			unset($defaults['cons']);
		}
		if (isset($options['vowels'])) {
			unset($defaults['vowels']);
		}
		return Hash::merge($defaults, $options);
	}

	/**
	 * Generate token used by the user registration system
	 *
	 * @param integer $length Token Length
	 * @param string $chars
	 * @return string
	 */
	public function generateToken($length = 10, $chars = '0123456789abcdefghijklmnopqrstuvwxyz') {
		$token = '';
		$i = 0;
		while ($i < $length) {
			$char = substr($chars, mt_rand(0, strlen($chars) - 1), 1);
			if (!stristr($token, $char)) {
				$token .= $char;
				$i++;
			}
		}
		return $token;
	}

}
