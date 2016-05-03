<h2><?= h($user->username) ?></h2>
<dl>
	<dt><?= __d('burzum/user_tools', 'Username') ?></dt>
	<dd><?= h($user->username) ?></dd>
	<dt><?= __d('burzum/user_tools', 'Email') ?></dt>
	<dd><?= h($user->email) ?></dd>
	<dt><?= __d('burzum/user_tools', 'Active') ?></dt>
	<dd><?= h($user->active) ?></dd>
	<dt><?= __d('burzum/user_tools', 'Email Verified') ?></dt>
	<dd><?= h($user->email_verified) ?></dd>
	<dt><?= __d('burzum/user_tools', 'Created') ?></dt>
	<dd><?= h($user->created) ?></dd>
</dl>
