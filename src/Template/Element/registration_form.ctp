<?php
echo $this->Form->create($userEntity, [
	'novalidate' => 'novalidate',
	'context' => array(
		'table' => 'users'
	)
]);
echo $this->Form->input('username', array(
	'label' => __d('burzum/user_tools', 'Username')
));
echo $this->Form->input('email');
echo $this->Form->input('password');
echo $this->Form->input('confirm_password', array(
	'type' => 'password',
));
echo $this->Form->submit(__d('burzum/user_tools', 'Sign up'));
echo $this->Form->end();
