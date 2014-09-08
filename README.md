User Tools Plugin for CakePHP
=============================

The **UserTools** plugin provides you the building blocks for everything around users. However, it has the same kick start ability through the [UserToolComponent](src/Controller/Component/UserToolComponent.php) and [UserBehavior](src/Model/Behavior/UserBehavior.php) but is overall much more configurable and extensible.

This plugin works very similar to the [CakeDC users plugin](https://github.com/cakedc/users) but instead of providing a full stack that is more or less hard to change and extend it allows you to use only what you really need *without* extending the controller or model.

Requirements
------------

* CakePHP 3.0+
* PHP 5.4.19

Documentation
-------------

For documentation, as well as tutorials, see the [Docs](Docs/Home.md) directory of this repository.

Support
-------

For support and feature request, please visit the [UserTools Support Site](https://github.com/burzum/cakephp-user-tools/issues).

Branch strategy
-------------

* The **master** branch holds the 'STABLE' latest version of the plugin.
* The **develop** branch is 'UNSTABLE' and used to test new features before releasing them.
* Only **hot fixes** are accepted against the master branch.

Contributing to this Plugin
---------------------------

Please feel free to contribute to the plugin with new issues, requests, unit tests and code fixes or new features. If you want to contribute some code, create a feature branch from develop, and send us your pull request. Unit tests for new features and issues detected are mandatory to keep quality high.

* Pull requests **must** be made against the develop branch, *except* hot fixes!
* You **must** follow the CakePHP coding standards
* Unit tests are **required** for new features and bug fixes

License
-------

Copyright 2013 - 2014 Florian Krämer

Licensed under the [MIT](http://www.opensource.org/licenses/mit-license.php) License. Redistributions of the source code included in this repository must retain the copyright notice found in each file.
