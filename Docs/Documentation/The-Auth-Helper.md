The Auth Helper
---------------

The Auth helper allows you to access the user data in a convenient way like through the components AuthComponent::user().

You can configure the Auth helper to read the data directly from the session or a view variable. By default it is using the view variable named `userData`.

Add the auth helper to your AppController.

Configuration Options
---------------------

* **viewVar:** Name of the view variable used to get the user data from. Default is ```userData```.
* **session:** Set the session key if you want to read the user data from session instead of from a view variable. Default is ```false'''.
* **viewVarException:** Throw an exception if the view variable is not present. Default is ```true```.
* **roleField:** The name of the field that contains the role(s) if any are present. Default is ```role```.

Example
-------

```php
class AppController extends Controller {
	public $helpers = array(
		'Auth'
	);
}
```

In any view file you can now access the AuthHelper.

```php
if ($this->Auth->isLoggedIn()) {
	echo __('Hello %s!', $this->Auth->user('username'));
}

if ($this->Auth->isMe($record['Record']['user_id']) {
	echo '<h2>' . __('Your records') . '</h2>';
}

if ($this->Auth->hasRole('admin') {
	echo $this->Html->link(__('delete'), array('action' => 'delete'));
}
```