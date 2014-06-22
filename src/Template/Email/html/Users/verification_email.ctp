<?php
echo __d('users', 'Hello, %s!', h($user->username));

//echo Router::url(array('controller' => 'users', 'action' => 'verify', $user->email_token));