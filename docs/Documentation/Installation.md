Installation
============

The plugin should be installed using Composer.

Use the inline `require` for composer:

```
composer require burzum/cakephp-user-tools:~1.0
```

or add this to your composer.json configuration:

```
{
	"require" : {
		"burzum/cakephp-user-tools": "~1.0"
	}
}
```

Use the [CakePHP Migration plugin](https://github.com/cakephp/migrations) to create the `users` table if you don't want to use your own table.

```
bin/cake Migrations.migrations migrate -p Burzum/UserTools
```

Or create your users table manually. Please note that if you are **not** using the same schema as the plugin provides in [the migration file](../../config/Migrations/20140902003044_initial.php) you will have to [configure the fieldMap](The-User-Behavior.md) config setting of the UsersBehavior to map your schema.

Add the plugin to your applications `bootstrap.php`

```php
Plugin::load('Burzum/UserTools');
```

For a quick start simply attach the UserTool component to a controller and go to to `/<your-controller>/register` and register a new user.

If you're unsure how to do that see the [Quick Start](../Tutorials/Quick-Start.md).

Some default URLs that become available when the routes are loaded:

* `/<your-controller>/index`
* `/<your-controller>/login`
* `/<your-controller>/request_password`

For all the customization options the plugin offers please see the documentation or check the default configuration of the component and behavior in the class files. Second is the better option right now because the documentation is not yet completed.
