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
use Cake\ORM\Exception\RecordNotFoundException;
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
			'successFlashOptions' => [],
			'successRedirectUrl' => '/',
			'errorFlashOptions' => [],
			'errorRedirectUrl' => false,
		],
		'login' => [
			'successFlashOptions' => [],
			'successRedirectUrl' => '/',
			'errorFlashOptions' => [],
			'errorRedirectUrl' => false,
		],
		'verifyEmailToken' => [
			'queryParam' => 'token',
			'type' => 'Email',
			'successRedirectUrl' => [
				'action' => 'login'
			],
			'errorRedirectUrl' => '/'
		],
		'requestPassword' => [
			'successFlashOptions' => [],
			'successRedirectUrl' => '/',
			'errorFlashOptions' => [],
			'errorRedirectUrl' => '/',
			'field' => 'email'
		],
		'resetPassword' => [
			'queryParam' => 'token',
		],
		'verifyToken' => [
			'queryParam' => 'token',
			'type' => 'Email',
			'successRedirectUrl' => [
				'action' => 'login'
			],
			'errorMessage' => null,
			'errorRedirectUrl' => '/'
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
			'reset_password' => [
				'method' => 'resetPassword',
				'view' => 'UserTools.UserTools/reset_password',
			],
			'request_password' => [
				'method' => 'requestPassword',
				'view' => 'UserTools.UserTools/request_password',
			],
			'verify_email' => [
				'method' => 'verifyEmailToken',
				'view' => 'UserTools.UserTools/verify_email',
			],
			'view' => [
				'method' => 'getUser',
				'view' => 'UserTools.UserTools/view',
			],
		],
		'getUser' => [
			'viewVar' => 'user'
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
		];
	}

/**
 * Constructor. Parses the accepted content types accepted by the client using HTTP_ACCEPT
 *
 * @param ComponentRegistry $registry ComponentRegistry object.
 * @param array $config Config options array
 */
	public function __construct(ComponentRegistry $registry, $config = []) {
		$this->_defaultConfig = Hash::merge(
			$this->_defaultConfig,
			$this->_translatedConfigMessages(),
			(array)Configure::read('UserTools.Component')
		);
		parent::__construct($registry, $config);
		$this->Collection = $registry;
		$this->Controller = $registry->getController();
		$this->request = $this->Controller->request;
		$this->response = $this->Controller->response;
	}

/**
 * Translates the messages in the configuration array
 *
 * @return array
 */
	protected function _translatedConfigMessages() {
		return [
			'requestPassword' => [
				'successMessage' => __d('user_tools', 'An email was send to your address, please check your inbox.'),
				'errorMessage' => __d('user_tools', 'Invalid user.'),
			],
			'registration' => [
				'successMessage' => __d('user_tools', 'Thank you for signing up!'),
				'errorMessage' => __d('user_tools', 'Please check your inputs'),
			],
			'login' => [
				'successMessage' => __d('user_tools', 'You are logged in!'),
				'errorMessage' => __d('user_tools', 'Invalid login credentials.'),
			],
			'verifyEmailToken' => [
				'successMessage' => __d('user_tools', 'Email verified, you can now login!'),
				'errorMessage' => __d('user_tools', 'Invalid email token!'),
			],
			'verifyToken' => [
				'successMessage' => __d('user_tools', 'Token verified!'),
			]
		];
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
		$this->loadHelpers();
	}

/**
 * Checks and loads helper if they're not found
 *
 * @return void
 */
	public function loadHelpers() {
		$helpers = ['Flash'];
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
		$this->Controller->set('_serialize', ['users']);
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
		$options = Hash::merge($this->_config['login'], $options);

		if ($this->request->is('post')) {
			$Auth = $this->_getAuthObject();
			$user = $Auth->identify();

			if ($user) {
				$Auth->setUser($user);
				if ($options['successRedirectUrl'] === null) {
					$options['successRedirectUrl'] = $Auth->redirectUrl();
				}
				$this->handleFlashAndRedirect('success', $options);
			} else {
				$this->handleFlashAndRedirect('error', $options);
			}
		}
	}

/**
 * View
 *
 * @param mixed $userId
 * @param array $options
 * @return void
 */
	public function getUser($userId = null, $options = []) {
		$options = Hash::merge($this->_config['getUser'], $options);
		if (is_null($userId)) {
			if (isset($this->request->params['pass'][0])) {
				$userId = $this->request->params['pass'][0];
			}
		}
		$this->Controller->set($options['viewVar'], $this->UserTable->getUser($userId));
		$this->Controller->set('_serialize', [$options['viewVar']]);
	}

/**
 * Logout
 *
 * @return void
 */
	public function logout() {
		$Auth = $this->_getAuthObject();
		$user = $Auth->user();
		if (empty($user)) {
			$this->Controller->redirect($this->Controller->referer());
			return;
		}
		$this->Flash->set(__d('user_tools', '%s you have successfully logged out'), $user->username);
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

		if ($this->request->is('post')) {
			if ($this->UserTable->register($this->request->data)) {
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
 *
 * @param array $options
 * @return mixed
 */
	public function verifyEmailToken($options = []) {
		return $this->verifyToken(Hash::merge($this->_defaultConfig['verifyEmailToken'], $options, ['type' => 'Email']));
	}

/**
 * The user can request a new password reset token, an email is send to him
 *
 * @param array $options
 * @return void
 */
	public function requestPassword($options = []) {
		$options = Hash::merge($this->_config['requestPassword'], $options);

		if ($this->request->is('post')) {
			try {
				$this->UserTable->initPasswordReset($this->request->data[$options['field']]);
				$this->handleFlashAndRedirect('success', $options);
			} catch (RecordNotFoundException $e) {
				$this->handleFlashAndRedirect('error', $options);
			}
			unset($this->request->data[$options['field']]);
		}
	}

/**
 * Allows the user to enter a new password
 *
 * @param array $options
 * @return void
 */
	public function resetPassword($options = []) {
		$this->verifyToken(Hash::merge($this->_defaultConfig['resetPassword'], $options));
		if ($this->request->is('post')) {
			// @todo
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
		$options = Hash::merge($this->_defaultConfig['verifyToken'], $options);

		if (!isset($this->request->query[$options['queryParam']])) {
			throw new NotFoundException(__d('user_tools', 'No token present!'));
		}

		$methodName = 'verify' . $options['type'] . 'Token';
		try {
			$result = $this->UserTable->$methodName($this->request->query[$options['queryParam']]);
			$this->handleFlashAndRedirect('success', $options);
		} catch (RecordNotFoundException $e) {
			if (is_null($options['errorMessage'])) {
				$options['errorMessage'] = $e->getMessage();
			}
			$this->handleFlashAndRedirect('error', $options);
			$result = false;
		}

		return $result;
	}

/**
 * Handles flashes and redirects
 *
 * @param string $type "success" or "error"
 * @param array $options Options
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

}