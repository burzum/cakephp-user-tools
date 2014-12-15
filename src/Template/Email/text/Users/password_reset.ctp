<?= __d('user_tools', 'Hello {0}!', h($user->username)) ?>

<?= __d('user_tools', 'Please click this link to reset your password.') ?>

<?= \Cake\Routing\Router::url(['controller' => 'users', 'action' => 'verify', $user->password_token], true) ?>