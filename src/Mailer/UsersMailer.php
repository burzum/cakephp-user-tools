<?php
declare(strict_types = 1);

namespace Burzum\UserTools\Mailer;

use Cake\Datasource\EntityInterface;
use Cake\Mailer\Mailer;

/**
 * Users Mailer
 */
class UsersMailer extends Mailer
{

    /**
     * Sends the verification email to an user.
     *
     * @param \Cake\Datasource\EntityInterface $user User entity
     * @param array $options Options
     * @return void
     */
    public function verificationEmail(EntityInterface $user, array $options = [])
    {
        $defaults = [
            'to' => $user->get('email'),
            'subject' => __d('user_tools', 'Please verify your Email'),
            'template' => 'Burzum/UserTools.Users/verification_email',
        ];
        $this->_applyOptions(array_merge($defaults, $options));
        $this->set('user', $user);
    }

    /**
     * Sends the password reset token
     *
     * @param \Cake\Datasource\EntityInterface $user User entity
     * @param array $options Options
     * @return void
     */
    public function passwordResetToken(EntityInterface $user, array $options = [])
    {
        $defaults = [
            'to' => $user->get('email'),
            'subject' => __d('user_tools', 'Your password reset'),
            'template' => 'Burzum/UserTools.Users/password_reset'
        ];
        $this->_applyOptions(array_merge($defaults, $options));
        $this->set('user', $user);
    }

    /**
     * Sends the new password email
     *
     * @param \Cake\Datasource\EntityInterface $user User entity
     * @param array $options Options
     * @return void
     */
    public function sendNewPasswordEmail(EntityInterface $user, array $options = [])
    {
        $defaults = [
            'to' => $user->get('email'),
            'subject' => __d('user_tools', 'Your new password'),
            'template' => 'Burzum/UserTools.Users/new_password'
        ];
        $this->_applyOptions(array_merge($defaults, $options));
        $this->set('user', $user);
    }

    /**
     * Sets the options from the array to the corresponding mailer methods
     *
     * @param array $options Options
     * @return void
     */
    protected function _applyOptions($options)
    {
        $builderVars = [
            'template',
            'layout',
            'theme',
            'var',
            'vars'
        ];

        foreach ($options as $method => $value) {
            $methodName = 'set' . $method;

            if (in_array($method, $builderVars)) {
                $this->viewBuilder()->{$methodName}($value);
                continue;
            }

            $this->{$methodName}($value);
        }
    }
}
