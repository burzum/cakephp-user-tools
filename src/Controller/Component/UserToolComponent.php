<?php
/**
 * UserToolComponent
 *
 * @author Florian Krämer
 * @copyright 2013 - 2014 Florian Krämer
 * @copyright 2012 Cake Development Corporation
 * @license MIT
 */
namespace UserTools\Controller\Component;

use Cake\Controller\Component;
use Cake\ORM\TableRegistry;
use Cake\Utility;
use Cake\Event\Event;
use Cake\Controller\ComponentRegistry;
use Cake\Utility\Hash;
use Cake\Error\NotFoundException;

class UserToolComponent extends Component {

/**
 * Components
 *
 * @var array
 */
	public $components = array(
		'Session',
		'Cookie',
		'Flash',
	);

/**
 * Default config
 *
 * These are merged with user-provided config when the component is used.
 *
 * @var array
 */
	protected $_defaultConfig = [
		'autoloadBehavior' => true,
		'actionMapping' => true,
		'directMapping' => false,
		'userModel' => null,
		'passwordReset' => 'token',
		'auth' => [
			'authenticate' => [
				'UserTools.MultiColumn' => [
					'userModel' => 'Users',
					'fields' => [
						'username' => 'email',
						'password' => 'password'
					],
					'columns' => [
						'username',
						'email'
					],
					'scope' => [
						'Users.email_verified' => 1
					]
				]
			]
		],
		'registration' => [
			'enabled' => true,
			'successMessage' => 'Thank you for signing up!',
			'successFlashOptions' => [],
			'successRedirectUrl' => '/',
			'errorMessage' => 'Please check your inputs',
			'errorFlashOptions' => [],
			'errorRedirectUrl' => false,
		],
		'login' => [
			'redirect' => '/',
			'successMessage' => 'Thank you for signing up!',
			'successFlashOptions' => [],
			'successRedirectUrl' => '/',
			'errorMessage' => 'Please check your inputs',
			'errorFlashOptions' => [],
			'errorRedirectUrl' => false,
		],
		'verifyEmail' => [

		],
		'requestPasswordChange' => [

		],
		'actionMap' => [
			'index' => [
				'method' => 'listing',
				'view' => 'UserTools.UserTools/index',
			],
			'register' => [
				'method' => 'register',
				'view' => 'UserTools.UserTools/register'
			],
			'login' => [
				'method' => 'login',
				'view' => 'UserTools.UserTools/login',
			],
			'logout' => [
				'method' => 'logout',
				'view' => null
			],
			'verify_email' => [
				'method' => 'verifyEmailToken',
				'view' => 'UserTools.UserTools/verify_email',
			]
		]
	];

/**
 * User Table
 *
 * @var \Cake\ORM\Table $UserTable
 */
	public $UserTable = null;

/**
 * Events supported by this component.
 *
 * @return array
 */
	public function implementedEvents() {
		return [
			'Controller.initialize' => 'initialize',
			'Controller.startup' => 'startup',
			'Controller.beforeRender' => 'beforeRender',
		];
	}

/**
 * Constructor. Parses the accepted content types accepted by the client using HTTP_ACCEPT
 *
 * @param ComponentRegistry $registry ComponentRegistry object.
 * @param array $config Array of settings.
 */
	public function __construct(ComponentRegistry $registry, $config = []) {
		parent::__construct($registry, $config);
		$this->Collection = $registry;
		$this->Controller = $registry->getController();
		$this->request = $this->Controller->request;
		$this->response = $this->Controller->response;
		$this->_methods = $this->Controller->methods;
	}

/**
 * Initializes the component
 *
 * @param Event $Event
 * @return void
 */
	public function initialize(Event $Event) {
		$this->Controller = $Event->subject();
		$this->setUserTable($this->_config['userModel']);
		$this->loadUserBehaviour();
		//$this->loadHelpers();
	}

/**
 *
 */
	public function loadHelpers() {
		$helpers = ['Flash', 'UserTools.Auth'];
		foreach ($helpers as $helper) {
			if (
				!in_array($helper, $this->Controller->helpers) &&
				!array_key_exists($helper, $this->Controller->helpers)
			) {
				$this->Controller->helpers[] = $helper;
			}
		}
	}

/**
 * User listing with pagination
 *
 * @param array $options Pagination options
 * @return \Cake\ORM\Query
 */
	public function listing($options = []) {
		$this->Controller->set('users', $this->Controller->paginate($this->UserTable, $options));
	}

/**
 * Loads the User behavior for the user model if it is not already loaded
 *
 * @return void
 */
	public function loadUserBehaviour() {
		if ($this->_config['autoloadBehavior'] && !$this->UserTable->hasBehavior('UserTools.User')) {
			if (is_array($this->_config['autoloadBehavior'])) {
				$this->UserTable->addBehavior('UserTools.User', $this->_config['autoloadBehavior']);
			} else {
				$this->UserTable->addBehavior('UserTools.User');
			}
		}
	}

/**
 * Sets or instantiates the user model class
 *
 * @param mixed $table
 * @throws \RuntimeException
 * @return void
 */
	public function setUserTable($table = null) {
		if ($table === null) {
			$this->UserTable = $this->Controller->{$this->Controller->modelClass};
		} else {
			if (is_object($table)) {
				if (!is_a($table, '\Cake\ORM\Table')) {
					throw new \RuntimeException(__d('user_tools', 'Passed object is not of type \Cake\ORM\Table!'));
				}
				$this->UserTable = $table;
			}
			if (is_string($table)) {
				$this->UserTable = TableRegistry::get($table);
			}
		}
		$this->Controller->set('userTable', $this->UserTable->alias());
	}

/**
 * Start up
 *
 * @param Event $Event
 * @return void
 */
	public function startup(Event $Event) {
		if ($this->_config['actionMapping'] === true) {
			$this->mapAction();
		}
	}

/**
 * Maps a called controller action to a component method
 *
 * @return boolean False if the action could not be mapped
 */
	public function mapAction() {
		$action = $this->request->params['action'];

		if ($this->_config['directMapping'] === true) {
			if (!method_exists($this, $action)) {
				return false;
			}

			$this->{$action}();
			$this->Controller->response = $this->Controller->render($action);
			$this->Controller->response->send();
			$this->Controller->_stop();
		}

		if (isset($this->_config['actionMap'][$action]) && method_exists($this, $this->_config['actionMap'][$action]['method'])) {
			$this->{$this->_config['actionMap'][$action]['method']}();
			$this->Controller->response = $this->Controller->render($this->_config['actionMap'][$action]['view']);
			$this->Controller->response->send();
			exit;
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
		$Controller = $this->Controller;
		$options = Hash::merge($this->_config['login'], $options);

		if ($Controller->request->is('post')) {
			$Auth = $this->_getAuthObject();

			$user = $Auth->identify();

			if ($user) {
				$Auth->setUser($user);
				return $this->redirect($Auth->redirectUrl());
			} else {
				$this->Flash->set(__('Username or password is incorrect'), [
					'element' => 'error',
					'key' => 'auth'
				]);
			}

			if ($options['redirect'] === false) {
				return true;
			}
			$Controller->redirect($options['redirect']);
		}
	}

/**
 * Logout
 *
 * @param array $options
 * @return void
 */
	public function logout($options = []) {
		$Controller = $this->_Collection->getController();
		$Auth = $this->_getAuthObject();
		$options = Hash::merge($this->_config['login'], $options);
		$user = $Auth->user();

		$this->Session->destroy();
		if (isset($_COOKIE[$this->Cookie->name])) {
			$this->Cookie->destroy();
		}
		$this->Flash->set(__d('user_tools', '%s you have successfully logged out'), $user['username']);
		$this->Controller->redirect($Auth->logout());
	}

/**
 * User registration
 *
 * - `enabled` Disables/enables the registration. If false a NotFoundException is thrown. Default true.
 * - `successMessage` The success flash message.
 * - `successRedirectUrl` Success redirect url. Default /.
 * - `errorMessage` The error flash message.
 * - `errorRedirectUrl` The error redirect url.
 *
 * @throws \Cake\Error\NotFoundException
 * @param array $options
 * @return void
 */
	public function register($options = []) {
		if ($this->_config['registration'] === false) {
			throw new NotFoundException();
		}

		$options = Hash::merge($this->_config['registration'], $options);

		if ($this->Controller->request->is('post')) {
			if ($this->UserTable->register($this->Controller->request->data)) {
				$this->handleFlashAndRedirect('success', $options);
			} else {
				$this->handleFlashAndRedirect('error', $options);
				$this->Controller->set('usersEntity', $this->UserTable->entity);
			}
		} else {
			$this->Controller->set('usersEntity', null);
		}
	}

/**
 * verifyEmailToken
 */
	public function verifyEmailToken($options) {
		$defaults = array(
			'queryParam' => 'token',
			'type' => 'Email',
			'successMessage' => __d('user_tools', 'Email verified, you can now login!'),
			'successRedirectUrl' => array('action' => 'login'),
			'errorMessage' => __d('user_tools', 'Invalid email token!'),
			'errorRedirectUrl' => '/'
		);
		return $this->verifyToken(Hash::merge($defaults, $options));
	}

/**
 * Verify Token
 *
 * @throws NotFoundException
 * @param array
 * @return mixed
 */
	public function verifyToken($options = []) {
		$Controller = $this->_Collection->getController();
		$options = Hash::merge(
			array(
				'queryParam' => 'token',
				'type' => 'Email',
				'successMessage' => __d('user_tools', 'Token verified!'),
				'successRedirectUrl' => array('action' => 'login'),
				'errorMessage' => __d('user_tools', 'Invalid token!'),
				'errorRedirectUrl' => '/'
			),
			$options);

		if (!isset($Controller->request->query[$options['queryParam']])) {
			throw new NotFoundException(__d('user_tools', 'No token present!'));
		}

		$methodName = 'verify' . $options['type'] . 'Token';
		$result = $this->UserTable->$methodName($Controller->request->query[$options['queryParam']]);

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
			$this->UserTable->requestPasswordChange($this->Contoller->request->data);
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
				$flashOptions = [];
				if (isset($options[$type . 'FlashOptions'])) {
					$flashOptions = $options[$type . 'FlashOptions'];
				}
				$this->Flash->set($options[$type . 'Message'], $flashOptions);
			}
		}
		if ($options[$type . 'RedirectUrl'] !== false) {
			$this->Controller->redirect($options[$type . 'RedirectUrl']);
		}
	}

/**
 * Gets the auth component object
 *
 * @return AuthComponent
 */
	protected function _getAuthObject() {
		if (!$this->Collection->loaded('Auth')) {
			$Auth = $this->Collection->load('Auth', $this->_config['auth']);
			$Auth->request = $this->Controller->request;
			$Auth->response = $this->Controller->response;
			return $Auth;
		} else {
			return $this->Collection->load('Auth');
		}
	}

	public function beforeRender(Event $Event) {
		$this->Controller->set('userData', $this->_getAuthObject()->user());
	}
}