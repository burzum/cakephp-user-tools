<?php
echo $this->Form->create($userModel);
echo $this->Form->input('email', array(
	'label' => __d('user_tools', 'Email'),
	'required' => false,
));
echo $this->Form->input('password', array(
	'type' => 'password',
	'label' => __d('user_tools', 'Password'),
	'required' => false,
));
echo $this->Form->end(__d('user_tools', 'Login'));