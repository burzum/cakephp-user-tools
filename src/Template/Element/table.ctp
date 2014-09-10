<table>
	<tr>
		<th><?php echo $this->Paginator->sort('username', __d('user_tools', 'Username')); ?></th>
		<th><?php echo $this->Paginator->sort('email', __d('user_tools', 'Email')); ?></th>
		<th><?php echo $this->Paginator->sort('email_verified', __d('user_tools', 'Email Verified')); ?></th>
		<th><?php echo $this->Paginator->sort('created', __d('user_tools', 'Created')); ?></th>
	</tr>
	<?php foreach ($users as $user) : ?>
		<tr>
			<td>
				<?php
					echo $this->Html->link($user->username, ['action' => 'view', $user->id]);
				?>
			</td>
			<td>
				<?php echo h($user->email); ?>
			</td>
			<td>
				<?php echo $user->email_verified == 1 ? __d('user_tools', 'Yes') : __d('user_tools', 'No'); ?>
			</td>
			<td>
				<?php
					if (empty($user->created)) {
						echo __d('user_tools', 'N/A');
					} else {
						echo h($this->Time->format($user->created, '%c'));
					}
				?>
			</td>
		</tr>
	<?php endforeach; ?>
</table>