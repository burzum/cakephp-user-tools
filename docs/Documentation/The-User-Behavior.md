The User Behavior
=================

This behavior contains almost everything that is commonly needed for dealing with user registration, password reset and every days tasks. The behaviour is thought to be used with your user model, not any none user related model.

```php
class User extends Table {
	public function initialize() {
		parent::initialize();
		$this->addBehavior('Burzum/UserTools.User');
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
* **passwordHasher**: The [password hasher](http://book.cakephp.org/3.0/en/controllers/components/authentication.html#hashing-passwords) to use for the password
* **register**: Registration method default settings
* **fieldMap**: Internal field names used by the behavior mapped to the real table fields, change the array values to your table names as needed

**register options**

* **defaultRole**: Used for a role, default is null, enter a string if you want a default role
* **hashPassword**: Hash the password or not, default is true
* **userActive**: Use the `active` field or not. By default users are set to active.
* **generatePassword**: Generate and send the password instead of using token verification, default is false
* **emailVerification**: Email verification process via token, default is true
* **verificationExpirationTime:** Email token expiration time, strtotime() compatible string, default is +1 day

```php
'register' => [
	'defaultRole' => null,
	'hashPassword' => true,
	'userActive' => true,
	'generatePassword' => false,
	'emailVerification' => true,
	'verificationExpirationTime' => '+1 day',
	'beforeRegister' => true,
	'afterRegister' => true,
	'tokenLength' => 32,
],
```

**loginFields** options

By default the username is mapped to the email field and the password to password.

```php
'loginFields' => [
	'username' => 'email',
	'password' => 'password'
],
```

If you want to use other fields than these defaults you'll have to change these fields. Don't forget to change the component settings as well and vice versa.

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

**updateLastActivity options**

Configures the behavior of the updateLastActivity method.

By default the date format is `Y-m-d H:i:s`.

```php
'updateLastActivity' => [
	'dateFormat' => 'Y-m-d H:i:s',
],
```

**initPasswordReset options**

Options to configure the password reset.

By default the token length is 32 characters and the token will expire after one day.

```
'initPasswordReset' => [
	'tokenLength' => 32,
	'expires' => '+1 day'
],
```
