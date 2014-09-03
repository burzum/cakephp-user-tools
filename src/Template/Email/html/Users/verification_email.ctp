<p>
	<?php echo __d('user_tools', 'Hello, %s!', h($user->username)); ?>
</p>
<p>
	<?php echo __d('user_tools', 'Please click this link to activate your account.'); ?>
</p>
<p>
	<?php echo \Cake\Routing\Router::url(['controller' => 'users', 'action' => 'verify', $user->email_token], true); ?>
</p>