<?php
/**
 * FlashAndRedirectTrait
 *
 * @author Florian Krämer
 * @copyright 2013 - 2016 Florian Krämer
 * @license MIT
 */
namespace Burzum\UserTools\Controller\Component;

trait FlashAndRedirectTrait {

	/**
	 * Helper property to detect a redirect
	 *
	 * @see UserToolComponent::handleFlashAndRedirect();
	 * @var \Cake\Network\Response
	 */
	protected $_redirectResponse = null;

	/**
	 * Handles flashes and redirects
	 *
	 * @param string $type Prefix for the array key, mostly "success" or "error"
	 * @param array $options Options
	 * @return mixed
	 */
	public function handleFlashAndRedirect($type, $options) {
		$this->_handleFlash($type, $options);
		return $this->_handleRedirect($type, $options);
	}

	/**
	 * Handles the redirect options.
	 *
	 * @param string $type Prefix for the array key, mostly "success" or "error"
	 * @param array $options Options
	 * @return mixed
	 */
	protected function _handleRedirect($type, $options) {
		if (isset($options[$type . 'RedirectUrl']) && $options[$type . 'RedirectUrl'] !== false) {
			$controller = $this->_registry->getController();
			$result = $controller->redirect($options[$type . 'RedirectUrl']);
			$this->_redirectResponse = $result;
			return $result;
		}
		return false;
	}

	/**
	 * Handles the flash options.
	 *
	 * @param string $type Prefix for the array key, mostly "success" or "error"
	 * @param array $options Options
	 * @return boolean
	 */
	protected function _handleFlash($type, $options) {
		if (isset($options[$type . 'Message']) && $options[$type . 'Message'] !== false) {
			if (is_string($options[$type . 'Message'])) {
				$flashOptions = [];
				if (isset($options[$type . 'FlashOptions'])) {
					$flashOptions = $options[$type . 'FlashOptions'];
				}
				$this->Flash->$type($options[$type . 'Message'], $flashOptions);
				return true;
			}
		}
		return false;
	}
}
