<?php
echo $this->Form->create($userEntity);
echo $this->Form->input('email', array(
	'label' => __d('user_tools', 'Email'),
	'required' => false,
));
echo $this->Form->input('password', array(
	'type' => 'password',
	'label' => __d('user_tools', 'Password'),
	'required' => false,
));
?>
<p>
	<?php
		echo $this->Html->link(__d('user_tools', 'Register'), ['action' => 'register']);
		echo ' | ';
		echo $this->Html->link(__d('user_tools', 'Reset Password'), ['action' => 'request_password']);
	?>
</p>
<?php
echo $this->Form->submit(__d('user_tools', 'Login'));
echo $this->Form->end();