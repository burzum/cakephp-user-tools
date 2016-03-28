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
* **userModel**: The model / table object to use with the component. By default it get's the model from the controller it is attached to.
* **passwordReset**: TBD
* **auth**: Explained below.
* **registration**: TBD
* **login**: TBD
* **verifyEmailToken**: TBD
* **requestPassword**: TBD
* **resetPassword**: TBD
* **verifyToken**: TBD
* **getUser**: Explained below.
* **actionMap**: Explained below.

auth config
----

The `auth` config key in the configuration array contains the configuration that is pased to the [AuthComponent](http://book.cakephp.org/3.0/en/controllers/components/authentication.html) in the case no pre-configured AuthComponent instance is found. To make the UserToolComponent work out of the box, even without an existing Auth setup, it uses this configuration to instantiate it. [Please read about the AuthComponent](http://book.cakephp.org/3.0/en/controllers/components/authentication.html) if you don't know what to add here.

getUser config
--------------

```php
'getUser' => [
	'viewVar' => 'user'
],
```

The `viewVar` sets the entity to the view. It's set by default to `user`. To disable setting the view var just set it to false. Or if you like a nother view variable name just change it to antoher string.

actionMap config
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
```
