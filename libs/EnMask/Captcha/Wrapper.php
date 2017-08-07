<?php

require_once 'EnMask/Captcha.php';
require_once 'EnMask/Captcha/Render.php';

class EnMask_Captcha_Wrapper
{
	protected $_captcha;

	protected $_render;

	public function __construct(array $config = null)
	{
		$this->_captcha = new EnMask_Captcha();
		$this->_render = new EnMask_Captcha_Render(array(
			'captcha' => $this->_captcha
		));

		if (!is_null($config)) {
			$this->setConfig($config);
		}

		$this->_captcha->make();
	}

	public function getCaptcha()
	{
		return $this->_captcha;
	}

	public function getRender()
	{
		return $this->_render;
	}

	protected function setConfig(array $config)
	{
		$captchaPublicVars = $this->_captcha->getPublicVars();
		$renderPublicVars = $this->_render->getPublicVars();

		$captchaConfig = array();
		$renderConfig = array();

		foreach ($config as $key => $val) {
			if (in_array($key, $captchaPublicVars)) {
				$captchaConfig[$key] = $val;
			} elseif (in_array($key, $renderPublicVars)) {
				$renderConfig[$key] = $val;
			}
		}

		$this->_captcha->setConfig($captchaConfig);
		$this->_render->setConfig($renderConfig);

		return $this;
	}
}