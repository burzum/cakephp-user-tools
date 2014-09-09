<?php
echo $this->Form->create('User');
echo $this->Form->input('email', array(
	'label' => __d('user_tools', 'Email'),
	'required' => false,
));
echo $this->Form->submit(__d('user_tools', 'Submit'));
echo $this->Form->end();