# User Tools Plugin for CakePHP #

This plugin works very similar like the CakeDC users plugin and is a derivative work but instead of providing a full stack that is more or less hard to change and extend this plugin just provides you the building blocks for everything around users.

## The UserTool component

```php
class UsersController extends AppController {
	public $components = array(
		'UserTools.UserTool'
	);
}
```

## The User Behavior

This behavior contains almost everything that is commonly needed for dealing with user registration, password reset and every days tasks.

```php
class User extends AppModel {
	public $actsAs = array(
		'UserTools.User'
	);
}
```

The behavior has a good amount of settings that allow you to configure the behavior:

* defaultValidation: Automatically sets up validation rules, default is true
* emailVerification: Email verification process via token, default is true
* defaultRole: Used for a role, default is null, enter a string if you want a default role
* fieldMap: Internal field names used by the behavior mapped to the real db fields, change the array values to your table names as needed

## The Auth Helper

The Auth helper allows you to access the user session in a convinient way like through the components AuthComponent::user() call but also lets you get the user data from a variable in the view.

```php
class AppController extends Controller {
	public $helpers = array(
		'UserTools.Auth'
	);
}
```

* session: Session key to read the user data from if you want to get it from the session, default is false
* viewVar: Name of the view var you have to set somewhere (AppController::beforeRender for example), default is `userData`
* viewVarException: Throws an exception if the viewVar is not found, default true
* roleField: Name of the array key in the user data that contains a role to check

Example of it's use in a view:

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

## Support ##

For support and feature request, please visit the [UserTools Support Site](https://github.com/burzum/CakePHP-UserTools/issues).

## Branch strategy ##

The master branch holds the STABLE latest version of the plugin. 
Develop branch is UNSTABLE and used to test new features before releasing them. 

Previous maintenance versions are named after the CakePHP compatible version, for example, branch 1.3 is the maintenance version compatible with CakePHP 1.3.
All versions are updated with security patches.

## Contributing to this Plugin ##

Please feel free to contribute to the plugin with new issues, requests, unit tests and code fixes or new features. If you want to contribute some code, create a feature branch from develop, and send us your pull request. Unit tests for new features and issues detected are mandatory to keep quality high.

Pull request *must* be made against the develop branch!

## License ##

Copyright 2013 Florian Krämer

Licensed under [The MIT License](http://www.opensource.org/licenses/mit-license.php)<br/>
Redistributions of files must retain the above copyright notice.

## Copyright ###

Copyright 2013 Florian Krämer


