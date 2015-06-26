User Validation
===============

If you followed the Quick Start guide you'll now have the most basic out of the box on configuration for the plugin done.

The plugin will by default use it's own validator class that comes with some built in validation rules that should be common. This means on the other hand the plugin assumes that you don't have any validation set up in your UsersTable. If you now add your custom validation it won't work because it bypasses [validationDefault()](http://api.cakephp.org/3.0/class-Cake.Validation.ValidatorAwareTrait.html#_validationDefault).

You now have two options: Just disable the default validation of the behavior or change it's class:

Either disable it

```php
$this->addBehavior('Burzum/UserTools.User', [
	'defaultValidation' => false
]);
```

or tell it to use your own validation class.

```php
$this->addBehavior('Burzum/UserTools.User', [
	'validatorClass' => '\App\Validation\MyUsersValidator',
]);
```

Probably the best way to do it is to extend the validation class that comes with the plugin and use it as described above.

```php
namespace App\Validation;

use Burzum\UserTools\Validation\UsersValidator as PluginValidator;

class MyUsersValidator extends PluginValidator {

	public function __construct() {
		parent::__construct();

		$this
			->requirePresence('confirm_password', 'create')
			->notEmpty('confirm_password');

		$this
			->requirePresence('name', 'create')
			->notEmpty('name');

		$this
			->requirePresence('company_name', 'create')
			->notEmpty('company_name');

		$this
			->requirePresence('website', 'create')
			->notEmpty('website')
			->add('website', [
				'valid_url' => [
					'rule' => 'url',
					'message' => __('Enter a valid URL')
				]
			]);

	}
}
```