<?php
/**
 * An authentication adapter for AuthComponent.  Provides the ability to authenticate using POST
 * data. The username form input can be checked against multiple table columns, for instance username and email
 *
 * {{{
 *	$this->Auth->authenticate = array(
 *		'Authenticate.MultiColumn' => array(
 *			'fields' => array(
 *				'username' => 'username',
 *				'password' => 'password'
 *	 		),
 *			'columns' => array('username', 'email'),
 *			'userModel' => 'Users',
 *			'scope' => array('Users.active' => 1)
 *		)
 *	)
 * }}}
 *
 * @author Ceeram
 * @copyright Ceeram
 * @license MIT
 * @link https://github.com/ceeram/Authenticate
 */
namespace Burzum\UserTools\Auth;

use Cake\Auth\FormAuthenticate;
use Cake\ORM\TableRegistry;

class MultiColumnAuthenticate extends FormAuthenticate {

/**
 * _findUser
 * @param string $username
 * @param string $password
 * @return mixed
 */
	protected function _findUser($username, $password = null) {
		$userModel = $this->_config['userModel'];
		list(, $model) = pluginSplit($userModel);
		$fields = $this->_config['fields'];

		if (is_array($username)) {
			$conditions = $username;
		} else {
			$conditions = array($model . '.' . $fields['username'] => $username);
			if ($this->_config['columns'] && is_array($this->_config['columns'])) {
				$columns = [];
				foreach ($this->_config['columns'] as $column) {
					$columns[] = array($model . '.' . $column => $username);
				}
				$conditions = array('OR' => $columns);
			}
		}

		if (!empty($this->_config['scope'])) {
			$conditions = array_merge($conditions, $this->_config['scope']);
		}

		$table = TableRegistry::get($userModel)->find('all');
		$result = $table
			->where($conditions)
			->contain($this->_config['contain'])
			->hydrate(false)
			->first();

		if (empty($result)) {
			return false;
		}

		if ($password !== null) {
			$hasher = $this->passwordHasher();
			$hashedPassword = $result[$fields['password']];
			if (!$hasher->check($password, $hashedPassword)) {
				return false;
			}

			$this->_needsPasswordRehash = $hasher->needsRehash($hashedPassword);
			unset($result[$fields['password']]);
		}

		return $result;
	}

}
