<?php
App::uses('AppHelper', 'View/Helper');
App::uses('CakeSession', 'Model/Datasource');

/**
 * AuthHelper
 *
 * @author Florian Krämer
 * @copyright 2013 Florian Krämer
 * @license MIT
 */
class AuthHelper extends AppHelper {

/**
 * Default settings
 *
 * @var array
 */
	public $defaults = array(
		'session' => false,
		'viewVar' => 'userData',
		'viewVarException' => true,
		'roleField' => 'role'
	);

/**
 * User data
 *
 * @array
 */
	protected $_userData = array();

/**
 * Constructor
 *
 * @param View $View
 * @param array $settings
 * @throws RuntimeException
 * @return AuthHelper
 */
	public function __construct(View $View, $settings = array()) {
		parent::__construct($View, $settings);
		$settings = Hash::merge($this->defaults, $settings);
		$this->settings = $settings;
		$this->_setupUserData();
	}

/**
 * Sets up the user data from session or view var
 *
 * @throws RuntimeException
 * @return void
 */
	protected function _setupUserData() {
		if (is_string($this->settings['session'])) {
			$this->_userData = CakeSession::read($this->settings['session']);
		} else {
			if (!isset($this->_View->viewVars[$this->settings['viewVar']])) {
				if ($this->settings['viewVarException'] === true) {
					throw new RuntimeException(__d('user_tools', 'View var %s not present!'));
				}
			} else {
				$this->_userData = $this->_View->viewVars[$this->settings['viewVar']];
			}
		}
	}

/**
 * Checks if a user is logged in
 *
 * @return boolean
 */
	public function isLoggedin() {
		return (!empty($this->_userData));
	}

/**
 * This check can be used to tell if a record that belongs to some user is the
 * current logged in user
 *
 * @param string|integer $userId
 * @param string $field Name of the field in the user record to check against, id by default
 * @return boolean
 */
	public function isMe($userId, $field = 'id') {
		return ($userId === $this->user($field));
	}

/**
 * Method equal to the AuthComponent::user()
 *
 * @param string $key
 * @return mixed
 */
	public function user($key) {
		return Hash::get($this->_userData, $key);
	}

/**
 * Role check
 *
 * @param string
 * @return boolean
 */
	public function hasRole($role) {
		$roles = $this->user($this->settings['roleField']);
		if (is_string($roles)) {
			return ($role === $roles);
		}
		if (is_array($roles)) {
			return (in_array($role, $roles));
		}
		return false;
	}

}