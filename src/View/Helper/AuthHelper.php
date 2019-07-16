<?php
declare(strict_types = 1);

/**
 * AuthHelper
 *
 * @author Florian Krämer
 * @copyright Florian Krämer
 * @license MIT
 */
namespace Burzum\UserTools\View\Helper;

use Cake\Utility\Hash;
use Cake\View\Helper;
use InvalidArgumentException;

/**
 * AuthHelper
 */
class AuthHelper extends Helper
{

    /**
     * Default settings
     *
     * @var array
     */
    protected $_defaultConfig = [
        'session' => false,
        'viewVar' => 'userData',
        'viewVarException' => false,
        'roleField' => 'role'
    ];

    /**
     * User data
     *
     * @array
     */
    protected $_userData = [];

    /**
     * Constructor
     *
     * @param \Cake\View\View $View View object
     * @param array $settings Settings
     * @throws RuntimeException
     */
    public function __construct(\Cake\View\View $View, $settings = [])
    {
        parent::__construct($View, $settings);

        $this->_setupUserData();
    }

    /**
     * Sets up the user data from session or view var
     *
     * @throws \RuntimeException
     * @return void
     */
    protected function _setupUserData()
    {
        if (is_string($this->_config['session'])) {
            $this->_userData = $this->getView()->getRequest()->getSession()->read($this->_config['session']);
        } else {
            if (!array_key_exists($this->_config['viewVar'], $this->_View->viewVars)) {
                if ($this->_config['viewVarException'] === true) {
                    throw new \RuntimeException(sprintf('View var `%s` not present! Please set the auth data to the view. See the documentation.', $this->_config['viewVar']));
                } else {
                    $this->_userData = [];
                }
            } else {
                $this->_userData = $this->getView()->get($this->_config['viewVar']);
            }
        }
    }

    /**
     * Convenience method to the the user data in any case as array.
     * This is mostly done because accessing the Entity object via Hash() caused an error when passing an object.
     *
     * @param bool $asArray Return as array, default true.
     * @return array
     */
    protected function _userData($asArray = true)
    {
        if ($asArray === true) {
            if (is_a($this->_userData, '\Cake\ORM\Entity')) {
                $this->_userData = $this->_userData->toArray();
            }
        }

        return $this->_userData;
    }

    /**
     * Checks if a user is logged in
     *
     * @return bool
     */
    public function isLoggedIn()
    {
        return (!empty($this->_userData));
    }

    /**
     * This check can be used to tell if a record that belongs to some user is the
     * current logged in user
     *
     * @param int $userId User Id
     * @param string $field Name of the field in the user record to check against, id by default
     * @return bool
     */
    public function isMe($userId, $field = 'id')
    {
        return ($userId === $this->user($field));
    }

    /**
     * Method equal to the AuthComponent::user()
     *
     * @param string $key Key to read from the user object
     * @return mixed
     */
    public function user($key = null)
    {
        if ($key === null) {
            return $this->_userData;
        }

        return Hash::get((array)$this->_userData(true), $key);
    }

    /**
     * Role check.
     *
     * @param array|string $requestedRole Role string or set of role identifiers.
     * @return bool|null True if the role is in the set of roles for the active user data.
     */
    public function hasRole($requestedRole)
    {
        $roles = $this->user($this->getConfig('roleField'));
        if (is_null($roles)) {
            return false;
        }

        return $this->_checkRoles($requestedRole, $roles);
    }

    /**
     * Checks the roles.
     *
     * @param string|array $requestedRole Requested role
     * @param string|array $roles List of roles
     * @return bool
     */
    protected function _checkRoles($requestedRole, $roles)
    {
        if (is_string($roles)) {
            $roles = [$roles];
        }
        if (is_string($requestedRole)) {
            $requestedRole = [$requestedRole];
        }
        if (!is_array($requestedRole)) {
            throw new InvalidArgumentException('The requested role is not a string or an array!');
        }
        $result = array_intersect($roles, $requestedRole);

        return (count($result) > 0);
    }
}
