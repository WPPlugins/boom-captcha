<?php

abstract class EnMask_Captcha_Abstract
{
	protected $_publicVars = array();

	public function setConfig(array $config)
	{
		foreach ($config as $key => $val) {
			if (!in_array($key, $this->_publicVars)) {
				continue;
			}

			$methodName = 'set' . $key;
			if (method_exists($this, $methodName)) {
				$this->{$methodName}($val);
			} else {
				$this->{'_' . $key} = $val;
			}
		}

		return $this;
	}

	public function __call($name, $arguments)
	{
		$prefix = strtolower(substr($name, 0, 3));
		$varName = $this->prepareVarName(substr($name, 3));

		if ($prefix == 'get') {
			if (!in_array($varName, $this->_publicVars)) {
				throw new Exception('You cannot get property "' . $varName . '".');
			}

			return $this->{'_' . $varName};
		} else if ($prefix == 'set' && array_key_exists(0, $arguments)) {
			if (!in_array($varName, $this->_publicVars)) {
				throw new Exception('You cannot set property "' . $varName . '".');
			}

			$this->{'_' . $varName} = $arguments[0];

			return $this;
		} else {
			throw new Exception('Incorrect method!');
		}
	}

	public function getPublicVars()
	{
		return $this->_publicVars;
	}

	protected function prepareVarName($name)
	{
		if (function_exists('lcfirst')) {
			$name = lcfirst($name);
		} else {
			$name = strtolower(substr($name, 0, 1)) . substr($name, 1);
		}

		return $name;
	}
}