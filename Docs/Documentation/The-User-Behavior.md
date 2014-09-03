The User Behavior
=================

This behavior contains almost everything that is commonly needed for dealing with user registration, password reset and every days tasks. The behaviour is thought to be used with your user model, not any none user related model.

```php
class User extends Table {
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

The behavior doesn't force you to match your users table field names to the behavior. The behavior can be configured to use any fields and is using mapping for that. The following array is the *default* configuration of the field mapping. You can override that when you configure the behavior. The key is the mapping name and value of the array is the field name.

```php
'fieldMap' => [
	'username' => 'username',
	'password' => 'password',
	'email' => 'email',
	'passwordCheck' => 'confirm_password',
	'lastAction' => 'last_action',
	'lastLogin' => 'last_login',
	'role' => 'role',
	'emailToken' => 'email_token',
	'emailTokenExpires' => 'email_token_expires',
	'passwordToken' => 'password_token',
	'passwordTokenExpires' => 'password_token_expires',
	'emailVerified' => 'email_verified',
	'active' => 'active',
]
```