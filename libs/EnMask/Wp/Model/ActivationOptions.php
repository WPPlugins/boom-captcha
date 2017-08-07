<?php

require_once 'EnMask/Wp/Model/Abstract.php';

class EnMask_Wp_Model_ActivationOptions extends EnMask_Wp_Model_Abstract
{
	protected $_name = 'options';

	public static function model()
	{
		return new self();
	}

	public function getOptionsLabels()
	{
		return array(
			EnMask_Wp::PROTECT_COMMENT_FORM => __('Enable for comment forms', 'enmask'),
			EnMask_Wp::PROTECT_REGISTER_FORM => __('Enable for register forms', 'enmask'),
			EnMask_Wp::PROTECT_LOGGED_IN_FORM => __('Enable for logged in users', 'enmask'),
			EnMask_Wp::PROTECT_RESTORE_PASS_FORM => __('Enable for lost password', 'enmask'),
			EnMask_Wp::PROTECT_LOGIN_FORM => __('Enable for login form', 'enmask')
		);
	}

	public function getOptionsForList()
	{
		$out = array();
		foreach ($this->getOptionsLabels() as $key => $text) {
			$out[$key] = array(
				'text' => $text,
				'value' => get_option($key)
			);
		}

		return $out;
	}

	public function resetAll()
	{
		$options = array_keys($this->getOptionsLabels());

		$this->_db->query('
			update
				' . $this->_name . '
			set
				option_value = 0
			where
				option_name in ("' . implode('","', $options) . '")
		');
	}

	public function setTrue($option)
	{
		$options = $this->getOptionsLabels();

		if (isset($options[$option])) {
			require_once 'EnMask/Wp/Model/Options.php';
			EnMask_Wp_Model_Options::model()->setVal($option, 1);

			return true;
		} else {
			return false;
		}
	}
}