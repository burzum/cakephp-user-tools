<?php
echo $this->Form->create($entity);
echo $this->Form->input('password', array(
	'label' => __d('burzum/user_tools', 'New password'),
	'required' => false,
));
echo $this->Form->input('confirm_password', array(
	'type' => 'password',
	'label' => __d('burzum/user_tools', 'Repeat password'),
	'required' => false,
));
echo $this->Form->submit(__d('burzum/user_tools', 'Submit'));
echo $this->Form->end();
