The UserTool component
======================

If you want to go for a kick start and use the component as it is, you can simply use it as it is pre-configured. This component is thought to be used with a controller that is your users controller, not any none user related controller.

```php
UsersController extends AppController {
	public function initialize() {
		parent::initialize();
		$this->loadComponent('Burzum/UserTools.UserTool');
	);
}
```

Configuration Options
---------------------

All configuration of the component can be configured in bootstrap as well by writing the config to:

```php
Configure::write('UserTools.Component', [/* Config goes here */]);
```

* **autoloadBehavior**: If not already loaded the component will load the UserBehavior for the current controllers `$modelClass`. Default is `true`.
* **actionMapping**: Enables the CRUD functionality of the plugin, if method doesn't exist in the controller and is mapped by the component the component method is executed. Default is `true`.
* **directMapping**: TBD
* **userModel**: TBD
* **passwordReset**: TBD
* **auth**: TBD
* **registration**: Explained below.
* **login**: Explained below.
* **verifyEmailToken**: Explained below.
* **requestPassword**: Explained below.
* **resetPassword**: Explained below.
* **verifyToken**: Explained below.
* **getUser**: Explained below.
* **actionMap**: Explained below.

actionMap Option
----------------

By default the users component will intercept requests to certain actions if the action methods don't exist in the controller and map them. The actionMap is an array of this structure:

```php
'actionName' => [
	'method' => 'componentMethod',
	'view' => 'someViewFileName',
],
```

You can use it to configure different views than the defaults for the actions. It's defaults are:

```php
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
```
