<h2><?= h($user->username) ?></h2>
<dl>
	<dt><?= __('Username') ?></dt>
	<dd><?= h($user->username) ?></dd>
	<dt><?= __('Email') ?></dt>
	<dd><?= h($user->email) ?></dd>
	<dt><?= __('Active') ?></dt>
	<dd><?= h($user->active) ?></dd>
	<dt><?= __('Email Verified') ?></dt>
	<dd><?= h($user->email_verified) ?></dd>
	<dt><?= __('Created') ?></dt>
	<dd><?= h($user->created) ?></dd>
</dl>