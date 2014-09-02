<table>
	<tr>
		<th><?php echo __('Username'); ?></th>
		<th><?php echo __('Email'); ?></th>
	</tr>
	<?php foreach ($users as $user) : ?>
		<tr>
			<td><?php echo h($user->username); ?></td>
			<td><?php echo h($user->email); ?></td>
		</tr>
	<?php endforeach; ?>
</table>