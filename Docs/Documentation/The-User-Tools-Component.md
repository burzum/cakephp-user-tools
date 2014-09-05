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

By default the users component will intercept the calls to some methods if not configured otherwise:

 * login
 * register
 * logout
