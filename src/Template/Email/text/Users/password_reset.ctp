<?php use Cake\Routing\Router; ?>

<?= __d('burzum/user_tools', 'Hello {0}!', h($user->username)) ?>

<?= __d('burzum/user_tools', 'Please click this link to reset your password.') ?>

<?= Router::url(['controller' => 'users', 'action' => 'reset_password', '?' => ['token' => $user->password_token]], true) ?>
