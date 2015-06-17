The Auth Helper
===============

The Auth helper allows you to access the user data in a convenient way like through the components `AuthComponent::user()`. Just add the auth helper to your AppView and set the user data from the component to the view.

You can configure the Auth helper to read the data directly from the session or a view variable. By default it is using the view variable named `userData`.

Configuration Options
---------------------

* **viewVar:** Name of the view variable used to get the user data from. Default is ```userData```.
* **session:** Set the session key if you want to read the user data from session instead of from a view variable. Default is ```false```.
* **viewVarException:** Throw an exception if the view variable is not present. Default is ```false```.
* **roleField:** The name of the field that contains the role(s) if any are present. Default is ```role```.

Example
-------

In \src\Controller\AppController:

```php
use Cake\Event\Event;
class AppController extends Controller {
	public function beforeRender(Event $event) {
		$this->set('userData', $this->Auth->user());
	}
}
```

In your \src\View\AppView:

```php
class AppView extends View {
	public function initialize() {
		parent::initialize();
		$this->loadHelper('Burzum/UserTools.Auth');
	}
}
```

In any view file you can now access the AuthHelper.

```php
// Check if the user is logged in
if ($this->Auth->isLoggedIn()) {
	echo __('Hello %s!', $this->Auth->user('username'));
}

// Check if an id is the current logged in user
if ($this->Auth->isMe($record['Record']['user_id'])) {
	echo '<h2>' . __('Your records') . '</h2>';
}

// Checks the role field of the user for a role
if ($this->Auth->hasRole('admin')) {
	echo $this->Html->link(__('delete'), array('action' => 'delete'));
}

// Access some data from the user
echo h($this->Auth->user('email'));
```
