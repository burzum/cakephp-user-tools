User Tools Plugin for CakePHP 3.0
=================================

[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.txt)
[![Build Status](https://img.shields.io/travis/burzum/cakephp-user-tools/master.svg?style=flat-square)](https://travis-ci.org/burzum/cakephp-user-tools)
[![Build Status](https://img.shields.io/coveralls/burzum/cakephp-user-tools/master.svg?style=flat-square)](https://coveralls.io/r/burzum/cakephp-user-tools)

The **UserTools** plugin provides you the building blocks for everything around users. It comes with a CRUD kick start through the [UserToolComponent](src/Controller/Component/UserToolComponent.php) and the [UserBehavior](src/Model/Behavior/UserBehavior.php). Just load the component and you have a working login and registration. Customize it as you like through configuration or use only what you really need.

The plugin is built in a way that you can only use what you really need from it and helps you to avoid to repeat the registration process in each app for example. The plugin should be flexible enough to adapt to almost every use case through configuration options.

Requirements
------------

* CakePHP 3.0+
* PHP 5.4.19

Complementary plugins
---------------------

The following plugins are not required but are a suggestions if you're looking for additional user related things.

 * [Cookie Auth](https://github.com/Xety/Cake3-CookieAuth) - A cookie Auth adapter
 * [JWT Auth](https://github.com/ADmad/cakephp-jwt-auth) - A JWT Auth adapter
 * [Simple RBAC](https://github.com/burzum/cakephp-simple-rbac) - An easy to use role based authorization adapter

Documentation
-------------

For documentation, as well as tutorials, see the [docs](docs/Home.md) directory of this repository.

Support
-------

For support and feature request, please visit the [UserTools Support Site](https://github.com/burzum/cakephp-user-tools/issues).

Branch strategy
-------------

* The **master** branch holds the `STABLE` latest version of the plugin.
* The **develop** branch is `UNSTABLE` and used to test new features before releasing them.
* Only **hot fixes** are accepted against the master branch.

Contributing to this Plugin
---------------------------

Please feel free to contribute to the plugin with new issues, requests, unit tests and code fixes or new features. If you want to contribute some code, create a feature branch from develop, and send us your pull request. Unit tests for new features and issues detected are mandatory to keep quality high.

* Pull requests must be send to the ```develop``` branch.
* Contributions must follow the [PSR2-**R** coding standard recommendation](https://github.com/php-fig-rectified/fig-rectified-standards).
* [Unit tests](http://book.cakephp.org/3.0/en/development/testing.html) are required.

License
-------

Copyright 2013 - 2016 Florian Krämer

Licensed under the [MIT](http://www.opensource.org/licenses/mit-license.php) License. Redistributions of the source code included in this repository must retain the copyright notice found in each file.
