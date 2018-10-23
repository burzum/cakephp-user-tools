<?php
declare(strict_types = 1);

namespace Burzum\UserTools\Utility;

/**
 * Password Generator
 */
class PasswordGenerator {

	/**
	 * Vowels
	 *
	 * @var array
	 */
	protected $vowels = [
		'a', 'e', 'i', 'o', 'u'
	];

	/**
	 * Constants
	 *
	 * @var array
	 */
	protected $consonants = [
		'b', 'c', 'd', 'g', 'h', 'j', 'k', 'l', 'm', 'n',
		'p', 'r', 's', 't', 'u', 'v', 'w', 'tr', 'cr', 'br', 'fr', 'th',
		'dr', 'ch', 'ph', 'wr', 'st', 'sp', 'sw', 'pr', 'sl', 'cl'
	];

	/**
	 * Special Chars
	 *
	 * @var array
	 */
	protected $specialChars = [
		'!', '#', '/', '(', ')', '[', ']', '.', '-', '_', '=', '>', '<', '*', '+'
	];

	/**
	 * Use Special Chars
	 *
	 * @var bool
	 */
	protected $useSpecialChars = false;

	/**
	 * Generates a random password that is more or less user friendly.
	 *
	 * @param int $length Password length, default is 8
	 * @return string
	 */
	public function generate(int $length = 10): string {
		$password = '';

		for ($i = 0; $i < $length; $i++) {
			$password .=
				$this->consonants[random_int(0, count($this->consonants) - 1)] .
				$this->vowels[random_int(0, count($this->vowels) - 1)];

			if ($this->useSpecialChars) {
				$password .= $this->specialChars[random_int(0, count($this->specialChars) - 1)];
			}
		}

		return substr($password, 0, $length);
	}

	/**
	 * Use special chars
	 *
	 * @param bool$useSpecialChars Use special chars
	 * @return $this
	 */
	public function useSpecialChars(bool $useSpecialChars): self {
		$this->useSpecialChars = $useSpecialChars;

		return $this;
	}

	/**
	 * Sets the Consonants
	 *
	 * @param array $cons
	 * @return $this
	 */
	public function setConsonants(array $cons): self {
		$this->cons = $cons;

		return $this;
	}

	/**
	 * Sets the Vowels
	 *
	 * @param array $vowels Vowels
	 * @return $this
	 */
	public function setVowels(array $vowels): self {
		$this->vowels = $vowels;

		return $this;
	}
}
