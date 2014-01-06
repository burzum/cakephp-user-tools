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
 *			'userModel' => 'User',
 *			'scope' => array('User.active' => 1)
 *		)
 *	)
 * }}}
 *
 * @author Ceeram
 * @copyright Ceeram
 * @license MIT
 * @link https://github.com/ceeram/Authenticate
 */
namespace UserTools\Controller\Component\Auth;

use Cake\Controller\Component\Auth\FormAuthenticate;

class MultiColumnAuthenticate extends FormAuthenticate {

/**
 * _findUser
 * @param string $username
 * @param string $password
 * @return mixed
 */
	protected function _findUser($username, $password = null) {
		$userModel = $this->settings['userModel'];
		list($plugin, $model) = pluginSplit($userModel);
		$fields = $this->settings['fields'];

		if (is_array($username)) {
			$conditions = $username;
		} else {
			$conditions = array($model . '.' . $fields['username'] => $username);
			if ($this->settings['columns'] && is_array($this->settings['columns'])) {
				$columns = [];
				foreach ($this->settings['columns'] as $column) {
					$columns[] = array($model . '.' . $column => $username);
				}
				$conditions = array('OR' => $columns);
			}
		}

		if (!empty($this->settings['scope'])) {
			$conditions = array_merge($conditions, $this->settings['scope']);
		}

		$result = ClassRegistry::init($userModel)->find('first', array(
			'conditions' => $conditions,
			'recursive' => $this->settings['recursive'],
			'contain' => $this->settings['contain'],
		));
		if (empty($result[$model])) {
			$this->passwordHasher()->hash($password);
			return false;
		}

		$user = $result[$model];
		if ($password) {
			if (!$this->passwordHasher()->check($password, $user[$fields['password']])) {
				return false;
			}
			unset($user[$fields['password']]);
		}

		unset($result[$model]);
		return array_merge($user, $result);
	}

}
