<?php
echo $this->Form->create($userModel);
echo $this->Form->input('username', array(
	'label' => __d('user_tools', 'Username'),
	'required' => false,
));
echo $this->Form->input('password', array(
	'type' => 'password',
	'label' => __d('user_tools', 'Password'),
	'required' => false,
));
echo $this->Form->end(__d('user_tools', 'Login'));