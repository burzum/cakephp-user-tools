Overview
========

Features

* Made as much configurable as possible
* User registration
* Email verification via token
* Password reset via token
* Password generation (not recommended but some people want it)
* Basic CRUD for users, including registration, token verification and password reset
* A shell for maintenance tasks (cleanup expired registrations, password reset, ...)
* Events to add your customization in the process:
 * User.beforeRegister (Behavior)
 * User.afterRegister (Behavior)
 * User.afterTokenVerification (Behavior)
 * User.beforeLogin (Component)
 * User.afterLogin (Component)