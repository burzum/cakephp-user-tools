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

Configuration Options
---------------------

The behavior has a good amount of settings that allow you to configure the behavior. All configuration of the behavior can be configured in bootstrap as well by putting the config into

```php
Configure::write('UserTools.Behavior', [/* Config goes here */]);
```

* **emailConfig**: Email configuration to use, default is default
* **defaultValidation**: Automatically sets up validation rules, default is true
* **validatorClass**: The user registration validation is set up trough a validator class, default is \UserTools\Validation\UserRegistrationValidator
* **entityClass**: The entity class to user for the users table, default \Cake\ORM\Entity
* **useUuid**:
* **passwordHasher**:
* **register**: Registration method default settings
* **fieldMap**: Internal field names used by the behavior mapped to the real table fields, change the array values to your table names as needed

**register options**

* **defaultRole**: Used for a role, default is null, enter a string if you want a default role
* **hashPassword**: Hash the password or not, default is true
* **userActive**: Use the active field or not
* **generatePassword**: Generate and send the password instead of using token verification, default is false
* **emailVerification**: Email verification process via token, default is true
* **verificationExpirationTime:** Email token expiration time, strtotime() compatible string, default is +1 day

**fieldMap options**

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