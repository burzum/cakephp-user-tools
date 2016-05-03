<?= __d('burzum/user_tools', 'Hello {0}!', h($user->username)) ?>

<?= __d('burzum/user_tools', 'Please click this link to activate your account.') ?>

<?= \Cake\Routing\Router::url(['controller' => 'users', 'action' => 'verify_email', '?' => ['token' => $user->email_token]], true) ?>
