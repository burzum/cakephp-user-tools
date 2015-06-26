User Validation
===============

If you followed the Quick Start guide you'll now have the most basic out of the box on configuration for the plugin done.

The plugin will by default use it's own validator class that comes with some built in validation rules that should be common. This means on the other hand the plugin assumes that you don't have any validation set up in your UsersTable. If you now add your custom validation it won't work because it bypasses [validationDefault()](http://api.cakephp.org/3.0/class-Cake.Validation.ValidatorAwareTrait.html#_validationDefault).

You now have two options: Just disable the default validation of the behavior or change it's class:

Disable it:

```php
$this->addBehavior('Burzum/UserTools.User', [
	'defaultValidation' => false
]);
```

Tell it to use your own validation class:

```php
$this->addBehavior('Burzum/UserTools.User', [
	'validatorClass' => '\App\Validation\MyUsersValidator',
]);
```
