<table>
	<tr>
		<th><?php echo __('Username'); ?></th>
		<th><?php echo __('Email'); ?></th>
	</tr>
	<?php foreach ($users as $user) : ?>
		<tr>
			<td>
				<?php
					echo $this->Html->link($user->username, ['action' => 'view', $user->id]);
				?>
			</td>
			<td><?php echo h($user->email); ?></td>
		</tr>
	<?php endforeach; ?>
</table>