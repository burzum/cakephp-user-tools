<?php
App::uses('Component', 'Controller');

/**
 * UserToolComponent
 *
 * @author Florian Krämer
 * @copyright 2013 Florian Krämer
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
	protected $_defaults = array(
		'autoloadBehavior' => true,
		'actionMapping' => true,
		'directMapping' => false,
		'userModel' => null,
		'passwordReset' => 'token',
		'registration' => array(
			'enabled' => true,
			'successMessage' => 'Thank you for signing up!',
			'successRedirectUrl' => '/',
			'errorMessage' => 'Please check your inputs',
			'errorRedirectUrl' => false,
		),
		'login' => array(
			'redirect' => true,
			'successMessage' => 'Thank you for signing up!',
			'successRedirectUrl' => '/',
			'errorMessage' => 'Please check your inputs',
			'errorRedirectUrl' => false,
		),
		'verifyEmail' => array(

		),
		'requestPasswordChange' => array(

		),
		'actionMap' => array(
			'register' => array(
				'method' => 'register',
				'view' => 'UserTools.UserTools/register'
			),
			'login' => array(
				'method' => 'login',
				'view' => 'UserTools.UserTools/login',
			),
			'logout' => array(
				'method' => 'logout',
				'view' => null
			)
		)
	);

/**
 * Settings of the component
 *
 * @var array
 */
	public $settings = [];

/**
 * User Model
 *
 * @var Model
 */
	public $UserModel = null;

/**
 * Initializes the component
 *
 * @param Controller $Controller
 * @param array $settings
 * @return void
 */
	public function initialize(Controller $Controller, $settings = []) {
		parent::initialize($Controller, $settings);

		$this->settings = Set::merge($this->_defaults, $settings);
		$this->Controller = $Controller;

		$this->setUserModel($this->settings['userModel']);
		$this->loadUserBehaviour();
	}

/**
 * loadUserBehaviour
 *
 * @return void
 */
	public function loadUserBehaviour() {
		if ($this->settings['autoloadBehavior'] && !$this->UserModel->Behaviors->loaded('UserTools.User')) {
			if (is_array($this->settings['autoloadBehavior'])) {
				$this->UserModel->Behaviors->load('UserTools.User', $this->settings['autoloadBehavior']);
			} else {
				$this->UserModel->Behaviors->load('UserTools.User');
			}
		}
	}

/**
 * Sets or instantiates the user model class
 *
 * @param mixed $modelClass
 * @throws RuntimeException
 * @return void
 */
	public function setUserModel($modelClass = null) {
		if ($modelClass === null) {
			$this->UserModel = $this->Controller{$this->Controller->modelClass};
		} else {
			if (is_object($modelClass)) {
				if (!is_a($modelClass, 'Model')) {
					throw new RuntimeException(__d('user_tools', 'Passed object is not of type Model'));
				}
				$this->UserModel = $modelClass;
			}
			if (is_string($modelClass)) {
				$this->UserModel = ClassRegistry::init($modelClass);
			}
		}
		$this->Controller->set('userModel', $this->UserModel->alias);
	}

/**
 * Start up
 *
 * @param Controller $Controller
 * @return void
 */
	public function startup(Controller $Controller) {
		parent::startup($Controller);

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

/**
 * Login
 *
 * @var array
 * @return bool
 */
	public function login($options = []) {
		$options = $this->_mergeOptions($this->settings['login'], $options);

		if (!$this->Controller->request->is('get')) {
			$this->Auth->request = $this->Controller->request;
			$this->Auth->response = $this->Controller->response;
			if ($this->Auth->login()) {
				if ($options['cookie'] !== false) {
					$this->setUserCookie($this->Auth->user());
				}

				if ($options['redirect'] === false) {
					return true;
				}
				$this->Controller->redirect($options['redirect']);
			}
		}
		return false;
	}

/**
 *
 */
	public function setUserCookie($user = [], $options) {
		$options = $this->_mergeOptions($this->settings['userCookie'], $options);
		$this->_Collection->Cookie->write($this->settings['userModel'], $user);
	}

/**
 * Logout
 *
 * @param array $options
 * @return void
 */
	public function logout($options = []) {
		$options = $this->_mergeOptions($this->settings['login'], $options);
		$user = $this->Auth->user();

		$this->Session->destroy();
		if (isset($_COOKIE[$this->Cookie->name])) {
			$this->Cookie->destroy();
		}
		$this->Session->setFlash(__d('user_tools', '%s you have successfully logged out'), $user['username']);
		$this->Controller->redirect($this->Auth->logout());
	}

/**
 * User registration
 *
 * @throws NotFoundException
 * @param array $options
 * @return void
 */
	public function register($options = []) {
		if ($this->settings['registration'] === false) {
			throw new NotFoundException();
		}

		$options = $this->_mergeOptions($this->settings['registration'], $options);

		if (!$this->Controller->request->is('get')) {
			if ($this->UserModel->register($this->Controller->request->data)) {
				$this->handleFlashAndRedirect('success', $options);
			} else {
				$this->handleFlashAndRedirect('error', $options);
			}
		}
	}

/**
 * Verify Token
 *
 * @throws NotFoundException
 * @param array
 * @return mixed
 */
	public function verifyToken($options = []) {
		$options = $this->_mergeOptions(array('queryParam' => 'token', 'type' => 'Email'), $options);

		if (!isset($this->Contoller->request->params[$options['queryParam']])) {
			throw new NotFoundException(__d('user_tools', 'No token present!'));
		}

		$methodName = 'verify' . $options['type'] . 'Token';
		$result = $this->UserModel->$methodName($this->Contoller->request->params[$options['queryParam']]);

		if ($result !== false) {
			$this->handleFlashAndRedirect('success', $options);
		} else {
			$this->handleFlashAndRedirect('error', $options);
		}

		return $result;
	}

/**
 * @todo finish me
 */
	public function requestPasswordChange() {
		if (!$this->Controller->request->is('get')) {
			$this->UserModel->requestPasswordChange($this->Contoller->request->data);
		}
	}

/**
 * @todo finish me
 */
	public function changePassword() {
		$this->verifyToken(array(
			'type' => 'Password'
		));
	}

/**
 * Handles flashes and redirects if needed
 *
 * @param string
 * @param array $options
 * @return void
 */
	public function handleFlashAndRedirect($type, $options) {
		if ($options[$type . 'Message'] !== false) {
			if (is_string($options[$type . 'Message'])) {
				$this->Session->setFlash($options[$type . 'Message']);
			}
			if (is_array($options[$type . 'Message'])) {
				$this->Session->setFlash($options[$type . 'Message']['message'], $options[$type . 'Message']['key'], $options[$type . 'Message']['message']);
			}
		}
		if ($options[$type . 'RedirectUrl'] !== false) {
			$this->Controller->redirect($options[$type . 'RedirectUrl']);
		}
	}

/**
 * Wrapper around Hash::merge
 *
 * @param array
 * @param array
 * @return array
 */
	protected function _mergeOptions($array, $array2) {
		return Hash::merge($array, $array2);
	}
}