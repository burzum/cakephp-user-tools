<?php echo __d('user_tools', 'Hello, %s!', h($user->username)); ?>

<?php echo __d('user_tools', 'Please click this link to reset your password.'); ?>

<?php echo \Cake\Routing\Router::url(['controller' => 'users', 'action' => 'verify', $user->password_token], true); ?>