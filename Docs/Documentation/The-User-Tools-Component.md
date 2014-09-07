The UserTool component
======================

If you want to go for a kick start and use the component as it is, you can simply use it as it is pre-configured. This component is thought to be used with a controller that is your users controller, not any none user related controller.

```php
class UsersController extends AppController {

	public $components = array(
		'UserTools.UserTool'
	);
}
```

By default the users component will intercept requests to certain actions if the action methods don't exist in the controller.

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

The method and the view of each can be changed through the configuration options of the component.

All configuration of the component can be configured in bootstrap as well by putting the config into

```php
Configure::write('UserTools.Component', [/* Config goes here */]);
```