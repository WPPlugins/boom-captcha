<?php

abstract class EnMask_Wp_Model_Abstract {

	protected $_db;

	protected $_name;

	public function __construct()
	{
		global $wpdb;

		$this->_db = $wpdb;

		$this->setupName();
	}

	abstract public static function model();

	protected function setupName()
	{
		$this->_name = $this->_db->prefix . $this->_name;
	}
}