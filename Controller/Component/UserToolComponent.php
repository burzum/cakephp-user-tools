<?php
App::uses('Component', 'Controller');

/**
 * UserToolComponent
 *
 * @author Florian Krämer
 * @copyright 2012 Florian Krämer
 * @copyright 2012 Cake Development Corporation
 * @license MIT
 */
class UserToolComponent extends Component {

/**
 * Components
 *
 * @var array
 */
	public $components = array(
		'Session',
		'Auth',
		'Cookie'
	);

/**
 * Default settings
 *
 * @var array
 */
	public $defaults = array(
		'autoloadBehavior' => true,
		'actionMapping' => true,
		'directMapping' => false,
		'userModel' => null,
		'setupAuth' => false,
		'registration' => array(
			'enabled' => true,
			'successMessage' => 'Thank you for signing up!',
			'successRedirectUrl' => '/',
			'errorMessage' => 'Please check your inputs',
			'errorRedirectUrl' => false,
		),
		'actionMap' => array(
			'register' => array(
				'method' => 'register',
				'view' => 'UserTools.UserTools/register'
			),
			'login' => array(
				'method' => 'login',
				'view' => 'UserTools.UserTools/login',
			)
		)
	);

/**
 * Initializes the component
 *
 * @param Controller $controller
 * @param array $settings
 * @return void
 */
	public function initialize(Controller $Controller, $settings = array()) {
		$this->settings = Set::merge($this->defaults, $settings);
		$this->Controller = $Controller;

		$this->_loadUserBehaviour();
		$this->_setUserModelClass();
	}

/**
 * _loadUserBehaviour
 *
 * @return void
 */
	protected function _loadUserBehaviour() {
		if ($this->settings['autoloadBehavior'] || !$this->Controller->{$this->Controller->modelClass}->Behaviors->loaded('UserTools.User')) {
			$this->Controller->{$this->Controller->modelClass}->Behaviors->load('UserTools.User');
		}
	}

/**
 * _setUserModelClass
 *
 * @return void
 */
	protected function _setUserModelClass() {
		if ($this->settings['userModel'] === null) {
			$this->settings['userModel'] = $this->Controller->modelClass;
		}
		$this->Controller->set('userModel', $this->Controller->{$this->settings['userModel']}->alias);
	}

/**
 * Start up
 *
 * @param Controller $controller
 * @return void
 */
	public function startup(Controller $Controller) {
		if ($this->settings['setupAuth'] !== false) {
			if (is_string($this->settings['setupAuth'])) {
				$this->Controller->{$this->settings['setupAuth']};
			} else {
				$this->setupAuth();
			}
		}
		if ($this->settings['actionMapping'] === true) {
			$this->mapAction();
		}
	}

/**
 * Maps a called controller action to a component method
 *
 * @throws MissingActionException
 * @return void
 */
	public function mapAction() {
		$action = $this->Controller->action;

		if ($this->settings['directMapping'] === true) {
			if (!method_exists($this, $action)) {
				return false;
			}

			$this->{$action}();
			$this->Controller->response = $this->Controller->render($action);
			$this->Controller->response->send();
			$this->Controller->_stop();
		}

		if (isset($this->settings['actionMap'][$action]) && method_exists($this, $this->settings['actionMap'][$action]['method'])) {
			$this->{$this->settings['actionMap'][$action]['method']}();
			$this->Controller->response = $this->Controller->render($this->settings['actionMap'][$action]['view']);
			$this->Controller->response->send();
			$this->Controller->_stop();
		}

		return false;
	}

	public function login() {
		if (!$this->Controller->request->is('get')) {
			if ($this->Auth->login()) {

			}
		}
	}

	public function logout() {
		$user = $this->Auth->user();
		$this->Session->destroy();
		if (isset($_COOKIE[$this->Cookie->name])) {
			$this->Cookie->destroy();
		}
		$this->Session->setFlash(__d('user_tools', '%s you have successfully logged out'), $user[$this->Controller{$this->settings['userModel']}->displayField]);
		$this->Controller->redirect($this->Auth->logout());
	}

/**
 * User registration
 *
 * @throws NotFoundException
 * @param array $options
 * @return void
 */
	public function register($options = array()) {
		if ($this->settings['registration'] === false) {
			throw new NotFoundException();
		}

		$options = Hash::merge($this->settings['registration'], $options);

		if (!$this->Controller->request->is('get')) {
			if ($this->Controller->{$this->Controller->modelClass}->register($this->Controller->request->data)) {
				$this->handleFlashAndRedirect('success', $options);
			} else {
				$this->handleFlashAndRedirect('error', $options);
			}
		}
	}

/**
 * Handles flashes and redirects if needed
 *
 * @param string
 * @param array $options
 * @return void
 */
	public function handleFlashAndRedirect($type, $options) {
		if (is_string($options[$type . 'Message'])) {
			$this->Session->setFlash($options['errorMessage']);
		}
		if (is_array($options[$type . 'Message'])) {
			$this->Session->setFlash($options[$type . 'Message']['message'], $options[$type . 'Message']['key'], $options[$type . 'Message']['message']);
		}
		if ($options[$type . 'RedirectUrl'] !== false) {
			$this->Controller->redirect($options[$type . 'RedirectUrl']);
		}
	}

/**
 * Default auth setup
 *
 * @return void
 */
	public function setupAuth() {
		$this->Auth->allow('register', 'reset', 'verify', 'logout', 'reset_password', 'login', 'resend_verification');

		if ($this->request->action == 'register') {
			$this->Components->disable('Auth');
		}

		$this->Auth->authenticate = array(
			'Form' => array(
				'fields' => array(
					'username' => 'email',
					'password' => 'password'),
				'userModel' => $this->Controller->modelClass,
				'scope' => array(
					$this->Controller->modelClass . '.active' => 1,
					$this->Controller->modelClass . '.email_verified' => 1)));

		$this->Auth->loginRedirect = '/';
		$this->Auth->logoutRedirect = array('plugin' => Inflector::underscore($this->plugin), 'controller' => 'users', 'action' => 'login');
		$this->Auth->loginAction = array('admin' => false, 'plugin' => Inflector::underscore($this->plugin), 'controller' => 'users', 'action' => 'login');
	}

}