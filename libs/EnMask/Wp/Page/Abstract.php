<?php

require_once 'EnMask/Wp/View.php';

abstract class EnMask_Wp_Page_Abstract
{
	const SESS_KEY_FLASH_MESSAGE = 'enmaskFlashMessages';

	protected $_db;

	public function __construct()
	{
		global $wpdb;

		$this->_db = $wpdb;
	}

	public static function model($class)
	{
		$obj = new $class();

		return $obj;
	}

	public function render($tpl, array $data = array())
	{
		$view = new EnMask_Wp_View($tpl, $data);

		return $view->render();
	}

	public function isPost()
	{
		if (strtolower($_SERVER['REQUEST_METHOD']) == 'post') {
			return true;
		} else {
			return false;
		}
	}

	public function addFlashMessage($message)
	{
		if (!isset($_SESSION[self::SESS_KEY_FLASH_MESSAGE])) {
			$_SESSION[self::SESS_KEY_FLASH_MESSAGE] = array();
		}

		$_SESSION[self::SESS_KEY_FLASH_MESSAGE][] = $message;

		return $this;
	}

	public function getFlashMessages()
	{
		$messages = array();
		if (isset($_SESSION[self::SESS_KEY_FLASH_MESSAGE])) {
			$messages = $_SESSION[self::SESS_KEY_FLASH_MESSAGE];
			unset($_SESSION[self::SESS_KEY_FLASH_MESSAGE]);
		}

		return $messages;
	}

	abstract static function get();

	abstract function start();
}