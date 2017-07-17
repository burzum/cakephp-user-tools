<?php
namespace Burzum\UserTools\Model;

use Cake\Auth\AbstractPasswordHasher;
use Cake\Auth\PasswordHasherFactory;

/**
 * Password Hasher Trait
 *
 * @author Florian Krämer
 * @copyright 2013 - 2017 Florian Krämer
 * @license MIT
 */
trait PasswordHasherTrait {

	/**
	 * AbstractPasswordHasher instance.
	 *
	 * @var AbstractPasswordHasher
	 */
	protected $_passwordHasher;

	/**
	 * Default password hasher class or config array
	 *
	 * @var string|array
	 */
	protected $_defaultPasswordHasher = 'Default';

	/**
	 * Hashes a password
	 *
	 * @param string $password Password to hash
	 * @return string Hash
	 */
	public function hashPassword($password) {
		return $this->getPasswordHasher()->hash($password);
	}

	/**
	 * Return password hasher object
	 *
	 * @return \Cake\Auth\AbstractPasswordHasher Password hasher instance
	 * @throws \RuntimeException If password hasher class not found or it does not extend AbstractPasswordHasher
	 * @deprecated Use getPasswordHasher() instead
	 */
	public function passwordHasher() {
		return $this->getPasswordHasher();
	}

	/**
	 * Sets a password hasher object
	 *
	 * @param \Cake\Auth\AbstractPasswordHasher $passwordHasher Password Hasher object
	 * @return void
	 */
	public function setPasswordHasher(AbstractPasswordHasher $passwordHasher) {
		$this->_passwordHasher = $passwordHasher;
	}

	/**
	 * Return password hasher object
	 *
	 * @return \Cake\Auth\AbstractPasswordHasher Password hasher instance
	 * @throws \RuntimeException If password hasher class not found or it does not extend AbstractPasswordHasher
	 */
	public function getPasswordHasher() {
		if ($this->_passwordHasher) {
			return $this->_passwordHasher;
		}

		return $this->_passwordHasher = PasswordHasherFactory::build($this->_defaultPasswordHasher);
	}
}
