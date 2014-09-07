<h2><?php echo h($user->username); ?></h2>
<dl>
	<dt><?php echo __('Username'); ?></dt>
	<dd><?php echo h($user->username); ?></dd>
	<dt><?php echo __('Email'); ?></dt>
	<dd><?php echo h($user->email); ?></dd>
	<dt><?php echo __('Active'); ?></dt>
	<dd><?php echo h($user->active); ?></dd>
	<dt><?php echo __('Email Verified'); ?></dt>
	<dd><?php echo h($user->email_verified); ?></dd>
	<dt><?php echo __('Created'); ?></dt>
	<dd><?php echo h($user->created); ?></dd>
</dl>