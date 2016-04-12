# Customizing the User Registration

If you want to manually control the registration process on the controller level, you can call the UserToolComponent::register() method manually.

The following example will show you how to deal with a registration that is using a single users table (as it should always be) that takes two different kinds of users. In our case `company` and `developer`. The company doesn't have any associated data, if it's a developer he has to fill a developer profile as well and we need another validation set.

## Controller

```php
namespace App\Controller;

use App\Controller\AppController;
use Cake\Event\Event;
use Cake\Core\Configure;
use Cake\Network\Exception\NotFoundException;
use Cake\ORM\TableRegistry;

/**
 * Users Controller
 *
 * @property App\Model\Table\UsersTable $Users
 */
class UsersController extends AppController {

	/**
	 * Initialization hook method.
	 *
	 * Use this method to add common initialization code like loading components.
	 *
	 * @return void
	 */
	public function initialize() {
		parent::initialize();
		$this->Auth->allow([
			'login', 'register', 'add'
		]);
		$this->loadComponent('Burzum/UserTools.UserTool', [
			'actionMap' => [
				'register' => [
					'view' => 'register',
					// Disable the proxy to the component by setting the method  to false
					'method' => false
				]
			]
		]);
	}

	/**
	 * User Registration
	 *
	 * @param String $role
	 * @return void
	 */
	public function register($role = 'company') {
		if (!in_array($role, ['company', 'developer'])) {
			throw new NotFoundException();
		}

		$options = [];
		if ($role === 'developer') {
			$options ['validation'] = 'developer';
		}
		$this->UserTool->register($options);

		$this->set('role', $role);
		$this->render('register_' . $role);
	}

}
```

## Table / Model

In your `App\Model\Table\UsersTable.php` you can implement event listeners to listen to the events fired by the behaviors registration method. It fires `User.beforeRegister` and `User.afterRegister`.

```php
class UsersTable extends Table {

	/**
	 * @inheritdoc
	 */
	public function implementedEvents() {
		return array_merge(parent::implementedEvents(), [
			'User.beforeRegister' => 'beforeRegister',
			'User.afterRegister' => 'afterRegister'
		]);
	}

	/**
	 * @inheritdoc
	 */
	public function initialize(array $config) {
		parent::initialize($config);
		$this->addBehavior('Burzum/UserTools.User');
	}

	/**/
	 * Before register event listener
	 *
	 * You can modify the result of the register call here. Don't forget to check
	 * that `$event->data['data']` is not false to distinguish a successful save
	 * from an unsuccessful save.
	 *
	 * @param \Cake\Event\Event $event
	 * @return void
	 */
	public function beforeRegister(Event $event) {
		// Do something here with the event
	}

	/**/
	 * After register event listener
	 *
	 * You can modify the result of the register call here. Don't forget to check
	 * that `$event->data['data']` is not false to distinguish a successful save
	 * from an unsuccessful save.
	 *
	 * @param \Cake\Event\Event $event
	 * @return void
	 */
	public function afterRegister(Event $event) {
		if ($event->data['data']) {
			// Do something here with the event
		}
	}

}
```
