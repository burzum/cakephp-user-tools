The User Behavior
=================

This behavior contains almost everything that is commonly needed for dealing with user registration, password reset and every days tasks. The behaviour is thought to be used with your user model, not any none user related model.

```php
class User extends AppModel {
	public $actsAs = array(
		'UserTools.User'
	);
}
```

The behavior has a good amount of settings that allow you to configure the behavior:

* **defaultValidation**: Automatically sets up validation rules, default is true
* **emailVerification**: Email verification process via token, default is true
* **defaultRole**: Used for a role, default is null, enter a string if you want a default role
* **fieldMap**: Internal field names used by the behavior mapped to the real db fields, change the array values to your table names as needed