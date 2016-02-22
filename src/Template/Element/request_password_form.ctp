<?php
echo $this->Form->create($userEntity);
echo $this->Form->input('email', array(
	'label' => __d('burzum/user_tools', 'Email'),
	'required' => false,
));
echo $this->Form->submit(__d('burzum/user_tools', 'Submit'));
echo $this->Form->end();
