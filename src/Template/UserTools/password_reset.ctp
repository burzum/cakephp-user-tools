<?php
echo $this->Form->create('User');
echo $this->Form->input('password', array(
	'label' => __d('user_tools', 'New Password'),
	'required' => false,
));
echo $this->Form->input('confirm_password', array(
	'label' => __d('user_tools', 'Repeat Password'),
	'required' => false,
));
echo $this->Form->submit(__d('user_tools', 'Submit'));
echo $this->Form->end();