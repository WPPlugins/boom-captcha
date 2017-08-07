<?php

require_once 'EnMask/Captcha/Abstract.php';

/**
 * EnMask Captcha
 *
 */
class EnMask_Captcha extends EnMask_Captcha_Abstract
{
	/**
	 * Path to directiry with fonts
	 *
	 * @var string
	 */
	protected $_fontsDir;

	/**
	 * Path to font directory. For example:
	 * ./fonts/FONT_NAME
	 *
	 * @var string
	 */
	protected $_fontPath;

	protected $_cryptTable;

	protected $_code;

	protected $_encryptedCode;

	protected $_codePrefix;

	protected $_codeSuffix;

	protected $_codeLength = array(4,6);

	protected $_codeRandUpper = true;

	/**
	 * If 0 = o in validation
	 *
	 * @var boolean
	 */
	protected $_zeroSameO = true;

	protected $_fontListLimit;

	protected $_publicVars = array('fontsDir', 'fontPath', 'code', 'encryptedCode', 'codePrefix', 'codeSuffix', 'codeLength', 'codeRandUpper', 'zeroSameO', 'fontListLimit');

	protected $_codeSymbols = array(
		'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n',
		'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z',
		0, 1, 2, 3, 4, 5, 6, 7, 8, 9
	);

	public function __construct(array $config = null)
	{
		if (!is_null($config)) {
			$this->setConfig($config);
		}
	}

	public function make()
	{
		if (empty($this->_code)) {
			$this->setupCode();
		}

		if (empty($this->_fontPath)) {
			$this->setupFont();
		}

		$this->setupCryptTable();

		$this->_encryptedCode = '';
		for ($i = 0; $i < strlen($this->_code); $i++) {
			if (!isset($this->_cryptTable[$this->_code[$i]])) {
				$this->_encryptedCode .= $this->_code[$i];
			} else {
				$this->_encryptedCode .= $this->_cryptTable[$this->_code[$i]];
			}
		}

		return $this;
	}

	/**
	 * Validation has an extra logick: perhaps, in future, space symbol will be in cryptTable.
	 * Now, it convert 0 to o. 0=O.
	 *
	 * @param string $code
	 * @return bolean
	 */
	public function validate($code)
	{
		$code = strtolower($code);
		$code = str_replace(' ', '', $code);

		$validCode = strtolower($this->_code);
		$validCode = str_replace(' ', '', $validCode);

		if ($this->_zeroSameO) {
			$code = str_replace('o', '0', $code);
			$validCode = str_replace('o', '0', $validCode);
		}

		if ($code == $validCode) {
			return true;
		} else {
			return false;
		}
	}

	public function setFontPath($path)
	{
		if (!is_dir($path)) {
			throw new Exception('Font path must be a directory!');
		}

		$this->checkFontPath($path);

		$this->_fontPath = $path;

		return $this;
	}

	public function setCodeLength(array $range)
	{
		if (!isset($range[0], $range[1])) {
			throw new Exception('Incorrect range');
		}

		if ($range[0] > $range[1]) {
			throw new Exception('Min cannot be greater then max');
		}

		$this->_codeLength = array($range[0], $range[1]);

		return $this;
	}

	/**
	 * This method is same as getFontSubPath, because early font's files structure was another,
	 * and this method had another source.
	 *
	 * @return string
	 */
	public function getFontName()
	{
		return trim(str_replace($this->_fontsDir, '', $this->_fontPath), '/');
	}

	public function getFontSubPath()
	{
		return trim(str_replace($this->_fontsDir, '', $this->_fontPath), '/');
	}

	protected function setupCode()
	{
		if (!empty($this->_codePrefix)) {
			$this->_code = $this->_codePrefix;
		}

		$codeLength = rand($this->_codeLength[0], $this->_codeLength[1]);

		$maxKey = sizeof($this->_codeSymbols) - 1;
		for ($i = 0; $i < $codeLength; $i++) {
			$symbol = $this->_codeSymbols[rand(0, $maxKey)];

			if ($this->_codeRandUpper && rand(0, 1)) {
				$symbol = strtoupper($symbol);
			}

			$this->_code .= $symbol;
		}

		if (!empty($this->_codeSuffix)) {
			$this->_code .= $this->_codeSuffix;
		}
	}

	protected function setupFont()
	{
		if (!is_dir($this->_fontsDir)) {
			throw new Exception('fontsDir is not a directory!');
		}

		$list = $this->clearFontList(scandir($this->_fontsDir));

		if (empty($list)) {
			throw new Exception('Dir with fonts is empty!');
		}

		if (!empty($this->_fontListLimit)) {
			$list = array_slice($list, 0, $this->_fontListLimit);
		}

		$item = array_rand($list, 1);
		$fontName = $list[$item];

		$this->_fontPath = $this->_fontsDir . DIRECTORY_SEPARATOR . $fontName;

		$this->checkFontPath($this->_fontPath);
	}

	protected function clearFontList(array $list)
	{
		//remove system files and current dir
		foreach ($list as $key => $item) {
			if (preg_match('/^\./', $item)) {
				unset($list[$key]);
			} else {
				break;
			}
		}

		return $list;
	}

	protected function setupCryptTable()
	{
		$cryptTablePath = $this->_fontPath . DIRECTORY_SEPARATOR . 'table.php';

		$cryptTable = require($cryptTablePath);
		foreach ($cryptTable as $codeChr => $encryptChr) {
			$this->_cryptTable[chr($codeChr)] = chr($encryptChr);
		}
	}

	protected function checkFontPath($path)
	{
		$requiredFiles = array('encrypted.eot', 'encrypted.ttf', 'real.eot', 'real.ttf', 'table.php');

		foreach ($requiredFiles as $file) {
			if (!is_file($path . DIRECTORY_SEPARATOR . $file)) {
				throw new Exception('File ' . $file . ' not found in the ' . $path);
			}
		}
	}
}