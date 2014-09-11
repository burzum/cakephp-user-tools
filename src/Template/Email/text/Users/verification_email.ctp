<?= __d('user_tools', 'Hello, %s!', h($user->username)) ?>

<?= __d('user_tools', 'Please click this link to activate your account.') ?>

<?= \Cake\Routing\Router::url(['controller' => 'users', 'action' => 'verify', $user->email_token], true) ?>