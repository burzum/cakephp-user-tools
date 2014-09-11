<p>
	<?= __d('user_tools', 'Hello, %s!', h($user->username)) ?>
</p>
<p>
	<?= __d('user_tools', 'Please click this link to activate your account.') ?>
</p>
<p>
	<?= \Cake\Routing\Router::url(['controller' => 'users', 'action' => 'verify', $user->email_token], true) ?>
</p>