<?php
declare(strict_types = 1);

namespace Burzum\UserTools\Utility;

/**
 * Token Generator
 */
class TokenGenerator
{
	/**
	 * Generates a token string
	 *
	 * @param int $length Token Length, default is 10
	 * @param string $chars Characters used in the token
	 * @return string
	 */
	public function generate(int $length = 10, string $chars = '0123456789abcdefghijklmnopqrstuvwxyz'): string {
		$token = '';
		$i = 0;

		while ($i < $length) {
			$char = substr($chars, random_int(0, strlen($chars) - 1), 1);
			if (!stristr($token, $char)) {
				$token .= $char;
				$i++;
			}
		}

		return $token;
	}
}
