<?php

require_once 'EnMask/Wp/Model/Abstract.php';

class EnMask_Wp_Model_Options extends EnMask_Wp_Model_Abstract
{
	protected $_name = 'options';

	public static function model()
	{
		return new self();
	}

	public function increaseEnmaskTotalHits($option)
	{
		$sql = $this->_db->prepare('
			update
				' . $this->_name . '
			set
				option_value = option_value + 1
			where
				option_name = %s', $option);

		$this->_db->query($sql);
	}

	public function setVal($key, $val)
	{
		$sql = $this->_db->prepare('
			update
				' . $this->_name . '
			set
				option_value = %s
			where
				option_name = %s', $val, $key);

		$this->_db->query($sql);
	}
}