<?php
declare(strict_types = 1);

/**
 * UserToolComponent
 *
 * @author Florian Krämer
 * @copyright Florian Krämer
 * @license MIT
 */
namespace Burzum\UserTools\Controller\Component;

use Cake\Controller\Component;
use Cake\Controller\ComponentRegistry;
use Cake\Core\Configure;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Event\Event;
use Cake\Event\EventDispatcherTrait;
use Cake\Http\Response;
use Cake\Network\Exception\NotFoundException;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use Cake\View\Exception\MissingTemplateException;
use RuntimeException;

/**
 * UserToolComponent
 */
class UserToolComponent extends Component {

	use EventDispatcherTrait;
	use FlashAndRedirectTrait;

	/**
	 * Components
	 *
	 * @var array
	 */
	public $components = [
		'Session',
		'Flash',
	];

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
				'Form' => [
					'userModel' => 'Users',
					'fields' => [
						'username' => 'email',
						'password' => 'password'
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
			'setEntity' => true,
			'validation' => 'default'
		],
		'login' => [
			'alreadyLoggedInFlashOptions' => [],
			'alreadyLoggedInRedirectUrl' => null,
			'successRedirectUrl' => null,
			'successFlashOptions' => [],
			'errorFlashOptions' => [],
			'errorRedirectUrl' => false,
			'setEntity' => true,
		],
		'logout' => [
			'successFlashOptions' => [],
			'successRedirectUrl' => null,
		],
		'verifyEmailToken' => [
			'queryParam' => 'token',
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
			'field' => 'email',
			'setEntity' => true,
		],
		'resetPassword' => [
			'queryParam' => 'token',
			'tokenOptions' => [],
			// Success
			'successFlashOptions' => [],
			'successRedirectUrl' => '/',
			// Normal error
			'errorFlashOptions' => [],
			'errorRedirectUrl' => false,
			// Invalid Token error
			'invalidErrorFlashOptions' => [
				'element' => 'Flash/error'
			],
			'invalidErrorRedirectUrl' => '/',
			// Token expired error
			'expiredErrorFlashOptions' => [
				'element' => 'Flash/error'
			],
			'expiredErrorRedirectUrl' => '/'
		],
		'changePassword' => [
			'successFlashOptions' => [],
			'successRedirectUrl' => '/',
			'errorFlashOptions' => [],
			'errorRedirectUrl' => false,
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
		'getUser' => [
			'viewVar' => 'user'
		],
		'actionMap' => [
			'index' => [
				'method' => 'listing',
				'view' => 'Burzum/UserTools.UserTools/index',
			],
			'register' => [
				'method' => 'register',
				'view' => 'Burzum/UserTools.UserTools/register'
			],
			'login' => [
				'method' => 'login',
				'view' => 'Burzum/UserTools.UserTools/login',
			],
			'logout' => [
				'method' => 'logout',
				'view' => null
			],
			'view' => [
				'method' => 'getUser',
				'view' => 'Burzum/UserTools.UserTools/view',
			],
			// camelCased method names
			'resetPassword' => [
				'method' => 'resetPassword',
				'view' => 'Burzum/UserTools.UserTools/reset_password',
			],
			'requestPassword' => [
				'method' => 'requestPassword',
				'view' => 'Burzum/UserTools.UserTools/request_password',
			],
			'changePassword' => [
				'method' => 'changePassword',
				'view' => 'Burzum/UserTools.UserTools/change_password',
			],
			'verifyEmail' => [
				'method' => 'verifyEmailToken',
				'view' => 'Burzum/UserTools.UserTools/verify_email',
			],
			// DEPRECATED underscored method names
			'reset_password' => [
				'method' => 'resetPassword',
				'view' => 'Burzum/UserTools.UserTools/reset_password',
			],
			'request_password' => [
				'method' => 'requestPassword',
				'view' => 'Burzum/UserTools.UserTools/request_password',
			],
			'change_password' => [
				'method' => 'changePassword',
				'view' => 'Burzum/UserTools.UserTools/change_password',
			],
			'verify_email' => [
				'method' => 'verifyEmailToken',
				'view' => 'Burzum/UserTools.UserTools/verify_email',
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
	 * Constructor. Parses the accepted content types accepted by the client using HTTP_ACCEPT
	 *
	 * @param ComponentRegistry $registry ComponentRegistry object.
	 * @param array $config Config options array
	 */
	public function __construct(ComponentRegistry $registry, $config = []) {
		$this->_defaultConfig = Hash::merge(
			$this->_defaultConfig,
			$this->_translateConfigMessages(),
			(array)Configure::read('UserTools.Component')
		);

		parent::__construct($registry, $config);
	}

	/**
	 * Gets the request from the controller
	 *
	 * @return \Cake\Http\ServerRequest
	 */
	protected function _getRequest() {
		return $this->getController()->request;
	}

	/**
	 * Gets the response from the controller
	 *
	 * @return \Cake\Http\Response
	 */
	protected function _getResponse() {
		return $this->getController()->response;
	}

	/**
	 * Translates the messages in the configuration array
	 *
	 * @return array
	 */
	protected function _translateConfigMessages() {
		return [
			'requestPassword' => [
				'successMessage' => __d('burzum/user_tools', 'An email was send to your address, please check your inbox.'),
				'errorMessage' => __d('burzum/user_tools', 'Invalid user.'),
			],
			'resetPassword' => [
				'successMessage' => __d('burzum/user_tools', 'Your password has been reset, you can now login.'),
				'errorMessage' => __d('burzum/user_tools', 'Please check your inputs.'),
				'invalidErrorMessage' => __d('burzum/user_tools', 'Invalid token!'),
				'expiredErrorMessage' => __d('burzum/user_tools', 'The token has expired!')
			],
			'changePassword' => [
				'successMessage' => __d('burzum/user_tools', 'Your password has been updated.'),
				'errorMessage' => __d('burzum/user_tools', 'Could not update your password, please check for errors and try again.'),
			],
			'registration' => [
				'successMessage' => __d('burzum/user_tools', 'Thank you for signing up!'),
				'errorMessage' => __d('burzum/user_tools', 'Please check your inputs'),
			],
			'login' => [
				'successMessage' => __d('burzum/user_tools', 'You are logged in!'),
				'errorMessage' => __d('burzum/user_tools', 'Invalid login credentials.'),
			],
			'logout' => [
				'successMessage' => __d('burzum/user_tools', 'You are logged out!'),
			],
			'verifyEmailToken' => [
				'successMessage' => __d('burzum/user_tools', 'Email verified, you can now login!'),
				'errorMessage' => __d('burzum/user_tools', 'Invalid email token!'),
			],
			'verifyToken' => [
				'successMessage' => __d('burzum/user_tools', 'Token verified!'),
			]
		];
	}

	/**
	 * Initializes the component.
	 *
	 * @param array $config Config array
	 * @return void
	 */
	public function initialize(array $config) {
		$this->setUserTable($this->getConfig('userModel'));
		$this->loadUserBehaviour();
	}

	/**
	 * User listing with pagination.
	 *
	 * @param array $options Pagination options
	 * @return void
	 */
	public function listing($options = []) {
		$this->getController()->set('users', $this->getController()->paginate($this->UserTable, $options));
		$this->getController()->set('_serialize', ['users']);
	}

	/**
	 * Loads the User behavior for the user model if it is not already loaded
	 *
	 * @return void
	 */
	public function loadUserBehaviour() {
		if ($this->getConfig('autoloadBehavior') && !$this->UserTable->hasBehavior('UserTools.User')) {
			if (is_array($this->getConfig('autoloadBehavior'))) {
				$this->UserTable->addBehavior('Burzum/UserTools.User', $this->getConfig('autoloadBehavior'));
			} else {
				$this->UserTable->addBehavior('Burzum/UserTools.User');
			}
		}
	}

	/**
	 * Sets or instantiates the user model class.
	 *
	 * @param null|string|\Cake\ORM\Table $table Table name or a table object
	 * @throws \RuntimeException
	 * @return void
	 */
	public function setUserTable($table = null) {
		if ($table === null) {
			$this->UserTable = $this->getController()->{$this->getController()->modelClass};
		} else {
			if (is_object($table)) {
				if (!is_a($table, Table::class)) {
					throw new RuntimeException('Passed object is not of type \Cake\ORM\Table!');
				}
				$this->UserTable = $table->getAlias();
			}
			if (is_string($table)) {
				$this->UserTable = TableRegistry::get($table);
			}
		}

		$this->getController()->set('userTable', $this->UserTable->getAlias());
	}

	/**
	 * Start up
	 *
	 * @link https://github.com/cakephp/cakephp/issues/4530
	 * @param \Cake\Event\Event $Event Event object
	 * @return void|\Cake\Http\Response
	 */
	public function startup(Event $Event) {
		if ($this->getConfig('actionMapping') === true) {
			$result = $this->mapAction();
			if ($result instanceof Response) {
				return $result;
			}
		}
	}

	/**
	 * Maps a called controller action to a component method
	 *
	 * @return bool|\Cake\Http\Response
	 */
	public function mapAction() {
		$action = $this->_getRequest()->getParam('action');
		if ($this->getConfig('directMapping') === true) {
			$this->_directMapping($action);
		}

		return $this->_mapAction($action);
	}

	/**
	 * Direct Mapping
	 *
	 * @param string $action Action name
	 * @return \Cake\Http\Response|bool A response object containing the rendered view.
	 */
	protected function _directMapping($action) {
		if (!method_exists($this, $action)) {
			return false;
		}

		$pass = (array)$this->_getRequest()->getParam('pass');
		$result = call_user_func_array([$this, $action], $pass);

		if ($result instanceof Response) {
			return $result;
		}

		return $this->getController()->render($action);
	}

	/**
	 * Maps an action of the controller to the component
	 *
	 * @param string $action Action name
	 * @return bool|\Cake\Http\Response
	 */
	protected function _mapAction($action) {
		$actionMap = (array)$this->getConfig('actionMap');

		if (isset($actionMap[$action]['method']) && method_exists($this, $actionMap[$action]['method'])) {
			$pass = (array)$this->_getRequest()->getParam('pass');
			call_user_func_array([$this, $actionMap[$action]['method']], $pass);

			if ($this->_redirectResponse instanceof Response) {
				return $this->_redirectResponse;
			}

			if (isset($actionMap[$action]['view']) && is_string($actionMap[$action]['view'])) {
				try {
					return $this->getController()->render($this->_getRequest()->getParam('action'));
				} catch (MissingTemplateException $e) {
					return $this->getController()->render($actionMap[$action]['view']);
				}
			}

			return $this->_getResponse();
		}

		return false;
	}

	/**
	 * Handles the case when the user is already logged in and triggers a redirect
	 * and flash message if configured for that.
	 *
	 * @see UserToolComponent::login()
	 * @param array $options Options
	 * @return void
	 */
	protected function _handleUserBeingAlreadyLoggedIn(array $options) {
		$Auth = $this->_getAuthObject();
		if ((bool)$Auth->user()) {
			if ($options['alreadyLoggedInRedirectUrl'] === null) {
				$options['alreadyLoggedInRedirectUrl'] = $this->_getRequest()->referer();
			}
			$this->handleFlashAndRedirect('alreadyLoggedIn', $options);
		}
	}

	/**
	 * Internal callback to prepare the credentials for the login.
	 *
	 * @param \Cake\Datasource\EntityInterface $entity User entity
	 * @param array $options Options
	 * @return mixed
	 */
	protected function _beforeLogin(EntityInterface $entity, array $options) {
		$entity = $this->UserTable->patchEntity($entity, $this->_getRequest()->getData(), ['validate' => false]);

		$event = $this->dispatchEvent('User.beforeLogin', [
			'options' => $options,
			'entity' => $entity
		]);

		if ($event->isStopped()) {
			return $event->result;
		}

		return $this->_getAuthObject()->identify();
	}

	/**
	 * Internal callback to handle the after login procedure.
	 *
	 * @param array $user User data
	 * @param array $options Options
	 * @return mixed
	 */
	protected function _afterLogin($user, array $options) {
		$event = $this->dispatchEvent('User.afterLogin', [
			'user' => $user,
			'options' => $options
		]);
		if ($event->isStopped()) {
			return $event->result;
		}

		$Auth = $this->_getAuthObject();
		$Auth->setUser($user);

		if ($options['successRedirectUrl'] === null) {
			$options['successRedirectUrl'] = $Auth->redirectUrl();
		}

		$this->handleFlashAndRedirect('success', $options);

		return true;
	}

	/**
	 * Login
	 *
	 * @param array $options Options
	 * @return bool
	 */
	public function login($options = []) {
		$options = Hash::merge($this->getConfig('login'), $options);
		$this->_handleUserBeingAlreadyLoggedIn($options);
		$entity = $this->UserTable->newEntity(null, ['validate' => false]);

		if ($this->_getRequest()->is('post')) {
			$user = $this->_beforeLogin($entity, $options);
			if ($user) {
				return $this->_afterLogin($user, $options);
			} else {
				$this->handleFlashAndRedirect('error', $options);
			}
		}

		if ($options['setEntity']) {
			$this->_setViewVar('userEntity', $entity);
		}

		return false;
	}

	/**
	 * Gets an user based on it's user id.
	 *
	 * - `viewVar` it sets the entity to the view. It's set by default to `user`. To
	 *    disable setting the view var just set it to false.
	 *
	 * @param int|string $userId UUID or integer type user id.
	 * @param array $options Configuration options.
	 * @return mixed
	 */
	public function getUser($userId = null, $options = []) {
		$options = Hash::merge($this->getConfig('getUser'), $options);

		if (is_null($userId)) {
			if (isset($this->_getRequest()->getParam('pass')[0])) {
				$userId = $this->_getRequest()->getParam('pass')[0];
			}
		}

		$entity = $this->UserTable->getUser($userId);
		if ($options['viewVar'] !== false) {
			$this->getController()->set($options['viewVar'], $entity);
			$this->getController()->set('_serialize', [$options['viewVar']]);
		}

		return $entity;
	}

	/**
	 * Deletes an user record
	 *
	 * @param mixed $userId User ID
	 * @param array $options Options
	 * @return bool
	 */
	public function deleteUser($userId = null, $options = []) {
		$entity = $this->_getUserEntity($userId);
		if ($this->UserTable->delete($entity)) {
			$this->handleFlashAndRedirect('success', $options);

			return true;
		} else {
			$this->handleFlashAndRedirect('error', $options);

			return false;
		}
	}

	/**
	 * Gets or constructs an user entity with a given id.
	 *
	 * @param mixed array|int|string $userId User ID
	 * @return \Cake\Datasource\EntityInterface
	 */
	protected function _getUserEntity($userId) {
		if (is_a($userId, EntityInterface::class)) {
			return $userId;
		}

		if (is_string($userId) || is_integer($userId)) {
			$entity = $this->UserTable->newEntity();

			return $this->UserTable->patchEntity(
				$entity,
				[$this->UserTable->getPrimaryKey() => $userId],
				['guard' => false]
			);
		}

		if (is_array($userId)) {
			$entity = $this->UserTable->newEntity();

			return $this->UserTable->patchEntity(
				$entity,
				$userId,
				['guard' => false]
			);
		}
	}

	/**
	 * Logout
	 *
	 * @param array $options Options array.
	 * @return \Cake\Http\Response
	 */
	public function logout($options = []) {
		$options = Hash::merge($this->getConfig('logout'), $options);
		$Auth = $this->_getAuthObject();
		$user = $Auth->user();

		if (empty($user)) {
			return $this->getController()->redirect(
				$this->getController()->referer()
			);
		}

		$logoutRedirect = $Auth->logout();
		if (is_null($options['successRedirectUrl'])) {
			$options['successRedirectUrl'] = $logoutRedirect;
		}

		return $this->handleFlashAndRedirect('success', $options);
	}

	/**
	 * User registration
	 *
	 * Options:
	 *
	 * - `enabled` Disables/enables the registration. If false a NotFoundException is thrown. Default true.
	 * - `successMessage` The success flash message.
	 * - `successRedirectUrl` Success redirect url. Default /.
	 * - `errorMessage` The error flash message.
	 * - `errorRedirectUrl` The error redirect url.
	 * - `setEntity` Set the entity to the view or not, default is true.
	 *
	 * @throws \Cake\Error\NotFoundException
	 * @param array $options Options
	 * @return bool|null
	 */
	public function register($options = []) {
		$options = Hash::merge($this->getConfig('registration'), $options);
		if ($options['enabled'] === false) {
			throw new NotFoundException();
		}

		$return = false;
		$entity = $this->UserTable->newEntity();
		// Make the field accessible in the case the default entity class is used.
		$entity->setAccess('confirm_password', true);
		if ($this->_getRequest()->is('post')) {
			$entity = $this->UserTable->patchEntity($entity, $this->_getRequest()->getData(), [
				'validate' => $options['validation']
			]);
			if ($this->UserTable->register($entity)) {
				$this->handleFlashAndRedirect('success', $options);
				$return = true;
			} else {
				$this->handleFlashAndRedirect('error', $options);
			}
		}
		if ($options['setEntity'] === true) {
			$this->_setViewVar('userEntity', $entity);
			// For backward compatibility
			$this->_setViewVar('usersEntity', $entity);
		}

		return $return;
	}

	/**
	 * Verifies an email token
	 *
	 * @param array $options Options
	 * @return mixed
	 */
	public function verifyEmailToken($options = []) {
		$options = Hash::merge(
			$this->getConfig('verifyEmailToken'),
			$options,
			['type' => 'Email']
		);

		return $this->verifyToken($options);
	}

	/**
	 * The user can request a new password reset token, an email is send to him.
	 *
	 * @param array $options Options
	 * @throws \Cake\Datasource\Exception\RecordNotFoundException
	 * @return bool|null
	 */
	public function requestPassword($options = []) {
		$options = Hash::merge($this->getConfig('requestPassword'), $options);
		$entity = $this->UserTable->newEntity(null, [
			'validate' => 'requestPassword'
		]);

		if ($this->_getRequest()->is('post')) {
			$entity = $this->UserTable->patchEntity($entity, $this->_getRequest()->getData(), [
				'validate' => 'requestPassword'
			]);

			if (!$entity->getError($options['field']) && $this->_initPasswordReset($entity, $options)) {
				return true;
			}

			if ($options['setEntity']) {
				if ($entity->isDirty('email') && !$entity->getError('email')) {
					$entity->email = '';
				}
				$this->_setViewVar('userEntity', $entity);
			}
			unset($this->_getRequest()->data[$options['field']]);

			return false;
		}

		if ($options['setEntity']) {
			$this->getController()->set('userEntity', $entity);
		}
	}

	/**
	 * Initializes the password reset and handles a possible errors.
	 *
	 * @param \Cake\Datasource\EntityInterface $entity User entity
	 * @param array $options Options array Options
	 * @return bool
	 */
	protected function _initPasswordReset(EntityInterface $entity, $options) {
		try {
			$this->UserTable->initPasswordReset($this->_getRequest()->getData($options['field']), $options);
			$this->handleFlashAndRedirect('success', $options);
			if ($options['setEntity']) {
				$this->_setViewVar('userEntity', $entity);
			}

			return true;
		} catch (RecordNotFoundException $e) {
			$this->handleFlashAndRedirect('error', $options);
		}

		return false;
	}

	/**
	 * Allows the user to enter a new password.
	 *
	 * @param string|null $token Token
	 * @param array $options Options
	 * @return null|\Cake\Http\Response
	 */
	public function resetPassword($token = null, $options = []) {
		$options = Hash::merge($this->getConfig('resetPassword'), $options);

		$tokenParam = $this->_getRequest()->getQuery($options['queryParam']);
		if (!empty($tokenParam)) {
			$token = $tokenParam;
		}

		// Check of the token exists
		try {
			$entity = $this->UserTable->verifyPasswordResetToken($token, $options['tokenOptions']);
		} catch (RecordNotFoundException $e) {
			if (empty($options['errorMessage']) && $options['errorMessage'] !== false) {
				$options['errorMessage'] = $e->getMessage();
			}

			$redirect = $this->handleFlashAndRedirect('invalidError', $options);
			if ($redirect instanceof Response) {
				return $redirect;
			}
			$entity = $this->UserTable->newEntity();
		}

		// Check if the token has expired
		if ($entity->get('token_is_expired') === true) {
			if (empty($options['invalidErrorMessage'])) {
				$options['invalidErrorMessage'] = $e->getMessage();
			}

			$redirect = $this->handleFlashAndRedirect('expiredError', $options);
			if ($redirect instanceof Response) {
				return $redirect;
			}
		}

		// Handle the POST
		if ($this->_getRequest()->is('post')) {
			$entity = $this->UserTable->patchEntity($entity, $this->_getRequest()->getData());
			if ($this->UserTable->resetPassword($entity)) {
				$redirect = $this->handleFlashAndRedirect('success', $options);
			} else {
				$redirect = $this->handleFlashAndRedirect('error', $options);
			}
			if ($redirect instanceof Response) {
				return $redirect;
			}
		} else {
			$entity = $this->UserTable->newEntity();
		}

		$this->_setViewVar('entity', $entity);
	}

	/**
	 * Let the logged in user change his password.
	 *
	 * @param array $options Options
	 * @return void
	 */
	public function changePassword($options = []) {
		$options = Hash::merge($this->getConfig('changePassword'), $options);

		$entity = $this->UserTable->newEntity();
		$entity->setAccess([
			'old_password',
			'password',
			'new_password',
			'confirm_password'
		], true);

		if ($this->_getRequest()->is(['post', 'put'])) {
			$entity = $this->UserTable->get($this->_getAuthObject()->user('id'));
			$entity = $this->UserTable->patchEntity($entity, $this->_getRequest()->getData(), [
				'validate' => 'changePassword'
			]);

			if ($this->UserTable->changePassword($entity)) {
				$this->_getRequest()->data = [];
				$entity = $this->UserTable->newEntity();
				$this->handleFlashAndRedirect('success', $options);
			} else {
				$this->handleFlashAndRedirect('error', $options);
			}
		}

		$this->_setViewVar('entity', $entity);
	}

	/**
	 * Verify Token
	 *
	 * @param array $options Options
	 * @throws \Cake\Error\NotFoundException;
	 * @return mixed
	 */
	public function verifyToken($options = []) {
		$options = Hash::merge($this->_defaultConfig['verifyToken'], $options);

		$token = $this->_getRequest()->getQuery($options['queryParam']);
		if (empty($token)) {
			throw new NotFoundException(__d('burzum/user_tools', 'No token present!'));
		}

		$methodName = 'verify' . $options['type'] . 'Token';

		try {
			$result = $this->UserTable->$methodName($token);
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
	 * Gets the auth component object
	 *
	 * If there is an auth component loaded it will take that one from the
	 * controller. If not the configured default settings will be used to create
	 * a new instance of the auth component. This is mostly thought as a fallback,
	 * in a real world scenario the app should have set auth set up in it's
	 * AppController.
	 *
	 * @return \Cake\Controller\Component\AuthComponent
	 */
	protected function _getAuthObject() {
		if (!$this->_registry->has('Auth')) {
			$Auth = $this->_registry->load('Auth', $this->getConfig('auth'));
			$Auth->request = $this->_getRequest();
			$Auth->response = $this->_getResponse();

			return $Auth;
		}

			return $this->_registry->Auth;
	}

	/**
	 * Handles the optional setting of view vars within the component.
	 *
	 * @param bool $viewVar Set the view var or not
	 * @param \Cake\Datasource\EntityInterface $entity User entity
	 * @return void
	 */
	protected function _setViewVar($viewVar, $entity) {
		if ($viewVar === false) {
			return;
		}

		$this->getController()->set($viewVar, $entity);
	}
}
