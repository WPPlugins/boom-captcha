<?php

require_once 'EnMask/Captcha/Abstract.php';

class EnMask_Captcha_Render extends EnMask_Captcha_Abstract
{
	protected $_captcha;

	protected $_id = 'boomcaptcha';

	protected $_webFontsDir;

	protected $_webFontPath;

	protected $_jsParams = array();

	protected $_jsGlobalVar = 'bookCaptcha';

	protected $_refreshUrl;

	protected $_resultInputName = 'code';

	protected $_htmlForAppend;

	protected $_htmlForPrepend;

	protected $_verifyUrl = false;

	protected $_verifyLabel = 'Verify';

	protected $_stripOutput = true;

	protected $_copyright = false;

	protected $_additionalClasses = array();

	protected $_publicVars = array(
		'captcha', 'jsParams', 'id', 'resultInputName', 'htmlForAppend', 'htmlForPrepend',
		'stripOutput', 'webFontsDir', 'jsGlobalVar', 'refreshUrl', 'verifyUrl',
		'copyright', 'webFontPath', 'verifyLabel', 'additionalClasses'
	);

	public function __construct(array $config = null)
	{
		if (!is_null($config)) {
			$this->setConfig($config);
		}
	}

	public function getJs($scriptTag = true)
	{
		$this->_jsParams['fontsDir'] = $this->_webFontsDir;
		$jsParams = json_encode($this->_jsParams);

		$js = ($scriptTag) ? '<script type="text/javascript">' : '';

		$js .= <<<EOT
	if (typeof({$this->_jsGlobalVar}) == 'undefined') {
		var {$this->_jsGlobalVar} = null;
	}

	(function($) {
		$(document).ready(function() {
			{$this->_jsGlobalVar} = new enmask.control($('#{$this->_id}'), {$jsParams});
			{$this->_jsGlobalVar}.setup();
		});
	}) (jQuery);
EOT;

		if ($scriptTag) {
			$js .= '</script>';
		}

		if ($this->_stripOutput) {
			$js = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), '', $js);
		}

		return $js;
	}

	public function getHtml()
	{
		if (!$this->_captcha instanceof EnMask_Captcha) {
			throw new Exception('You must set captcha before!');
		}

		$copyright = ($this->_copyright) ? '<a href="http://boomcaptcha.com/" class="enmask-copyright" target="blank">BoomCaptcha.com</a>' : '';

		$code = htmlspecialchars($this->_captcha->getEncryptedCode());

		$refresh = (!empty($this->_refreshUrl)) ? '<a href="' . $this->_refreshUrl . '" class="enmask-refresh"></a>' : '';

		$cssClasses = array_merge(array('enmask-captcha'), $this->_additionalClasses);

		if (!empty($this->_resultInputName) && !empty($this->_verifyUrl)) {
			$cssClasses[] = 'enmask-with-verify';

			if ($this->_copyright) {
				$cssClasses[] = 'enmask-with-verify-copyright';
			}

			$classes = implode(' ', $cssClasses);
			$html = <<<EOT
<div class="{$classes}" id="{$this->_id}">
	<p class="enmask-code">{$code}</p>
	<input type="text" name="{$this->_resultInputName}" value="" id="{$this->_id}enmask-code" class="enmask-result" />
	<div class="enmask-control">
		<div class="enmask-size">
			<div class="enmask-slider">
				<span class="enmask-knob"></span>
			</div>
		</div>
		<a href="{$this->_verifyUrl}" class="enmask-verify">{$this->_verifyLabel}</a>
		{$refresh}
		<div class="enmask-clear"></div>
	</div>
	<div class="enmask-clear"></div>
	{$copyright}
</div>
EOT;

		} else {
			if ($this->_copyright) {
				$cssClasses[] = 'enmask-with-copyright';
			}

			$prepend = (!empty($this->_htmlForPrepend)) ? $this->_htmlForPrepend : '';

			$append = '';
			if (!empty($this->_resultInputName)) {
				$append .= '<input type="text" name="' . $this->_resultInputName . '" value="" id="' . $this->_id . 'enmask-code" class="enmask-result" />';
			}

			$append .= $this->_htmlForAppend;

			$classes = implode(' ', $cssClasses);
			$html = <<<EOT
<div class="{$classes}" id="{$this->_id}">
	<p class="enmask-code">{$code}</p>
	<div class="enmask-control">
		<div class="enmask-size">
			<div class="enmask-slider">
				<span class="enmask-knob"></span>
			</div>
		</div>
		{$refresh}
		<div class="enmask-clear"></div>
	</div>
	<div class="enmask-clear"></div>
	{$prepend}{$append}{$copyright}
</div>
EOT;

		}

		if ($this->_stripOutput) {
			$html = preg_replace('/>\s+(\S*)\s+</u', '> $1 <', $html);
			$html = str_replace(array('>  <', '> <', ">\r\n<", ">\r<", ">\n<", ">\t<"), '><', $html);
		}

		return $html;
	}

	public function getCssFonts($cssTag = true)
	{
		if (!$this->_captcha instanceof EnMask_Captcha) {
			throw new Exception('You must set captcha before!');
		}

		$rndSuffix = rand(100, 999);
		$fontName = $this->_captcha->getFontName() . $rndSuffix;

		$webPath = (!empty($this->_webFontPath)) ? $this->_webFontPath : $this->_webFontsDir . $this->_captcha->getFontSubPath();

		$css = ($cssTag) ? '<style type="text/css">' : '';
		$css .= <<<EOT
	@font-face {
		font-family: "{$fontName}enc";
		src: url("{$webPath}/encrypted.eot?rnd={$rndSuffix}");
		src: url("{$webPath}/encrypted.eot?rnd={$rndSuffix}#iefix") format("embedded-opentype"),
		url("{$webPath}/encrypted.ttf?rnd={$rndSuffix}") format("truetype");
		font-weight: normal;
		font-style: normal;
	}

	@font-face {
		font-family: "{$fontName}real";
		src: url("{$webPath}/real.eot?rnd={$rndSuffix}");
		src: url("{$webPath}/real.eot?rnd={$rndSuffix}#iefix") format("embedded-opentype"),
		url("{$webPath}/real.ttf?rnd={$rndSuffix}") format("truetype");
		font-weight: normal;
		font-style: normal;
	}

	#{$this->_id} .enmask-code {
		font-family: "{$fontName}enc";
	}

	#{$this->_id} .enmask-result {
		font-family: "{$fontName}real";
	}
EOT;

		if ($cssTag) {
			$css .= '</style>';
		}

		if ($this->_stripOutput) {
			$css = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), '', $css);
		}

		return $css;
	}

	public function setCaptcha(EnMask_Captcha $captcha)
	{
		$this->_captcha = $captcha;

		return $this;
	}

	public function setAdditionalClasses(array $classes)
	{
		$this->_additionalClasses = $classes;

		return $this;
	}
}