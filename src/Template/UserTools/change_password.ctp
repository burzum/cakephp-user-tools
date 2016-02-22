<h2>
	<?php echo __d('burzum/user_tools', 'Change password'); ?>
</h2>
<?php
echo $this->Form->create($entity);
echo $this->Form->input('old_password', [
	'type' => 'password',
	'label' => __d('burzum/user_tools', 'Old password')
]);
?>
<hr />
<?php
echo $this->Form->input('password', [
	'type' => 'password',
	'label' => __d('burzum/user_tools', 'New password')
]);
echo $this->Form->input('confirm_password', [
	'type' => 'password',
	'label' => __d('burzum/user_tools', 'Confirm password')
]);
echo $this->Form->submit(__d('burzum/user_tools', 'Submit'));
echo $this->Form->end();
