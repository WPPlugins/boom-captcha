<?php

class EnMask_Wp_View
{
	protected $_tpl;

	protected $_vars = array();

	public function __construct($tpl = null, array $vars = null)
	{
		if (!is_null($tpl)) {
			$this->setTpl($tpl);
		}

		if (!is_null($vars)) {
			$this->setVars($vars);
		}
	}

	public function render()
	{
		$_file = ENMASK_TPLS_PATH . DIRECTORY_SEPARATOR . $this->_tpl . '.phtml';

		if (!is_file($_file)) {
			throw new Exception('File "' . $_file . '" not found!');
		}

		extract($this->_vars);

		ob_start();
		include $_file;
		$out = ob_get_clean();

		return $out;
	}

	public function setTpl($tpl)
	{
		$this->_tpl = $tpl;

		return $this;
	}

	public function setVars(array $vars)
	{
		$this->_vars = $vars;

		return $this;
	}
}