The Auth Helper
---------------

Add the AuthHelper to your AppController.

```php
class AppController extends Controller {
	$helpers = array(
		'Auth'
	);
}
```

Configuration Options
---------------------

* **viewVar:** Name of the view variable used to get the user data from. Default is ```userData```.
* **session:** Set the session key if you want to read the user data from session instead of from a view variable. Default is ```false'''.
* **viewVarException:** Throw an exception if the view variable is not present. Default is ```true```.
* **roleField:** The name of the field that contains the role(s) if any are present. Default is ```role```.
