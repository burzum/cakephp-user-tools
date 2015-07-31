Authentication & Authorization
==============================

Setting up Auth
---------------

If you haven't configured the `AuthComponent` in your `AppController` the `UserToolsComponent` will instantiate it and load it with it's default settings. For details about Auth please [check the official documentation](http://book.cakephp.org/3.0/en/controllers/components/authentication.html). It is highly recommended that you add the AuthComponent to your AppController and configure it as you need it.

Allow / Deny Access to Controller Actions
-----------------------------------------

If you'd like that your Authentication/Authorization rules apply to the views of the plugin, you have to add the 
corresponding *empty* action methods in your UsersController.

So for example if you'd like to only allow logged in users to access the `change_password` view, you'll have to add the following code to your `UsersController`. For example:

```php
public function change_password() {}
```

The reason for this is how CakePHP works. It check the controller for it's actions, but if you're using the plugin in it's "out of the box" mode you won't need controller actions. The UserToolsComponent intercepts the requests and routes it to it's internal methods.

Maybe there will be a work-around implemented in the future.
