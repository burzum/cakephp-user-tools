Kick Start
==========

If you database follows the schema the plugin expects to work with you can simply kick start your users in the application by adding the component.

```php
UsersController extends AppController {

	public $components = array(
		'UserTools.UserTool
	);

}
```

The component will take the main model of the controller and load the ```UserBehavior``` with it's default settings.

Go to /users/register and try to register, make sure your email config is set up properly.

You should receive an email notification with a token and a verification URL. After verifying your user go to /users/login and try to login.