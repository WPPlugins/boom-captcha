<?php

require_once 'EnMask/Wp.php';
require_once 'EnMask/Wp/Model/Keywords.php';
require_once 'EnMask/Wp/Model/Options.php';
require_once 'EnMask/Captcha/Wrapper.php';
require_once 'EnMask/Wp/Page/Test.php';
require_once 'EnMask/Wp/Page/Settings.php';

class EnMask_Wp_Init
{
	public static function setupHooks($isAdmin, $userId)
	{
		if ($isAdmin) {
			add_filter('plugin_action_links', array('EnMask_Wp_Init', 'settingsLinks'), 10, 2);
			add_action('admin_menu', array('EnMask_Wp_Init', 'adminMenu'));
		}

		$setupCaptcha = false;

		/* comment form */
		if (
			(empty($userId) && get_option(EnMask_Wp::PROTECT_COMMENT_FORM))
			|| (!empty($userId) && get_option(EnMask_Wp::PROTECT_LOGGED_IN_FORM))
		) {
			$setupCaptcha = true;

			if (empty($userId)) {
				add_action('comment_form_after_fields', array('EnMask_Wp_Init', 'appendCaptcha'));
			} else {
				add_action('comment_form', array('EnMask_Wp_Init', 'appendCaptcha'));
			}

			add_action('comment_post', array('EnMask_Wp_Init', 'commentValidateCaptcha'));
		}

		/* login form */
		if (get_option(EnMask_Wp::PROTECT_LOGIN_FORM)) {
			$setupCaptcha = true;
			add_action('login_form', array('EnMask_Wp_Init', 'appendCaptchaLogin'));
			add_filter('authenticate', array('EnMask_Wp_Init', 'authValidateCaptcha'));
			add_action('login_head', array('EnMask_Wp_Init', 'loginHead'));
		}

		/* lost password form */
		if (get_option(EnMask_Wp::PROTECT_RESTORE_PASS_FORM)) {
			$setupCaptcha = true;
			add_action('lostpassword_form', array('EnMask_Wp_Init', 'appendCaptchaLogin'));
			add_action('lostpassword_post', array('EnMask_Wp_Init', 'lostPassValidateCaptcha'));
		}

		/* register form */
		if (get_option(EnMask_Wp::PROTECT_REGISTER_FORM)) {
			$setupCaptcha = true;
		    add_action('register_form', array('EnMask_Wp_Init', 'appendCaptchaLogin'));
		    add_filter('registration_errors', array('EnMask_Wp_Init', 'registerValidateCaptcha'));
		}

		if ($setupCaptcha) {
			add_action('init', array('EnMask_Wp_Init', 'startSess'));
			add_action('init', array('EnMask_Wp_Init', 'appendCaptchaToHead'));
		}
	}

	public static function loginHead()
	{
		echo '<link rel="stylesheet"  href="' . get_site_url(null, 'wp-content/plugins/boom-captcha/css/captcha.css') . '" type="text/css" />';
	}

	public static function activation()
	{
		global $wpdb;

		$options = array(
			EnMask_WP::TOTAL_OPTION_NAME => 0,
			EnMask_WP::LICENSE_OPTION_NAME => '',
			EnMask_Wp::TOTAL_PERCENT_OPTION => 0,
			EnMask_Wp::PROTECT_COMMENT_FORM => 1,
			EnMask_Wp::PROTECT_REGISTER_FORM => 0,
			EnMask_Wp::PROTECT_LOGGED_IN_FORM => 0,
			EnMask_Wp::PROTECT_RESTORE_PASS_FORM => 0,
			EnMask_Wp::PROTECT_LOGIN_FORM => 0
		);

		foreach ($options as $key => $val) {
			if (false === get_option($key)) {
				add_option($key, $val);
			}
		}

		EnMask_Wp_Model_Keywords::model()->createTbls();
	}

	public static function settingsLinks($links, $file = null)
	{
		if ($file == plugin_basename(ENMASK_PLUGIN_FILE)) {
			$links[] = '<a href="admin.php?page=enmask-settings">'.__('Settings', 'enmask').'</a>';
		}

		return $links;
	}

	public static function adminMenu()
	{
		add_submenu_page('plugins.php', __('Settings BoomCaptcha', 'enmask'), __('Settings BoomCaptcha', 'enmask'), 'manage_options', 'enmask-settings', array('EnMask_Wp_Page_Settings', 'get'));
	}

	public static function appendCaptchaToHead()
	{
		wp_enqueue_script('jquery');
		wp_enqueue_script('enmask_captcha_control', '/wp-content/plugins/boom-captcha/js/control.js');
		wp_enqueue_script('enmask_captcha_validation', '/wp-content/plugins/boom-captcha/js/validation.js');
		wp_enqueue_style('enmask_captcha_control', '/wp-content/plugins/boom-captcha/css/captcha.css');
	}

	public static function appendCaptchaLogin()
	{
		self::appendCaptcha(array('enmask-login-form'));
	}

	public static function appendCaptcha($classes = array())
	{
		$wrapper = self::getCaptchaWrapper();

		$_SESSION[EnMask_Wp::CAPTCHA_SESS_KEY] = serialize($wrapper->getCaptcha());

		if (!empty($classes) && is_array($classes)) {
			$wrapper->getRender()->setAdditionalClasses($classes);
		}

		echo $wrapper->getRender()->getCssFonts();
		echo $wrapper->getRender()->getHtml();
		echo $wrapper->getRender()->getJs();

		$jsonConfig = json_encode(array(
			'loadingText' => __('Checking the captcha...', 'enmask'),
			'errorText' => __('Captcha code is incorrect', 'enmask'),
			'validationUrl' => get_site_url(null, 'wp-content/plugins/boom-captcha/index.php?mode=validate')
		));

		echo <<<EOF
<script type="text/javascript">
	jQuery(document).ready(function() {
		var validation = new enmask.validation({$wrapper->getRender()->getJsGlobalVar()}, {$jsonConfig});
		validation.setup();
	});
</script>
<div id="enmask-captcha-checking"></div>
EOF;

	}

	public static function lostPassValidateCaptcha()
	{
		$code = (isset($_POST[EnMask_Wp::INPUT_NAME])) ? $_POST[EnMask_Wp::INPUT_NAME] : '';

		if (!self::validate($code)) {
			wp_die(__('Captcha code is incorrect', 'enmask'));
		}

		self::resetSess();
	}

	public static function registerValidateCaptcha()
	{
		$code = (isset($_POST[EnMask_Wp::INPUT_NAME])) ? $_POST[EnMask_Wp::INPUT_NAME] : '';

		$error = new WP_Error();

		if (!self::validate($code)) {
			remove_filter('registerValidateCaptcha', array('EnMask_Wp_Init', 'registerValidateCaptcha'));
			$error->add('wrong_captcha', __('Captcha code is incorrect', 'enmask'));
		} else {
			self::resetSess();
		}

		return $error;
	}

	public static function authValidateCaptcha()
	{
		$error = new WP_Error();

		if (!empty($_POST)) {
			$code = (isset($_POST[EnMask_Wp::INPUT_NAME])) ? $_POST[EnMask_Wp::INPUT_NAME] : '';

			if (!self::validate($code)) {
				remove_filter('authenticate', array('EnMask_Wp_Init', 'authValidateCaptcha'));
				$error->add('wrong_captcha', __('Captcha code is incorrect', 'enmask'));
			} else {
				self::resetSess();
			}
		}

		return $error;
	}

	public static function commentValidateCaptcha($commentId)
	{
		$code = (isset($_POST[EnMask_Wp::INPUT_NAME])) ? $_POST[EnMask_Wp::INPUT_NAME] : '';

		if (!self::validate($code)) {
			wp_set_comment_status($commentId, 'trash');
			wp_die(__('Captcha code is incorrect', 'enmask'));
		}

		self::resetSess();
	}

	public static function resetSess()
	{
		$keys = array(EnMask_Wp::CAPTCHA_CHECKED_SESS_KEY, EnMask_Wp::CAPTCHA_SESS_KEY);
		foreach ($keys as $key) {
			if (isset($_SESSION[$key])) {
				unset($_SESSION[$key]);
			}
		}
	}

	public static function validate($code)
	{
		$isValid = false;

		if (!empty($_SESSION[EnMask_Wp::CAPTCHA_CHECKED_SESS_KEY])) {
			$isValid = true;
		} elseif (isset($_SESSION[EnMask_Wp::CAPTCHA_SESS_KEY])) {
			$captcha = unserialize($_SESSION[EnMask_Wp::CAPTCHA_SESS_KEY]);

			if ($captcha->validate($code)) {
				$isValid = true;
			}
		}

		return $isValid;
	}

	public static function startSess()
	{
		if (!session_id()) {
			session_start();
		}
	}

	public static function getCaptchaWrapper()
	{
		$total = get_option(EnMask_WP::TOTAL_PERCENT_OPTION, 0);

		EnMask_Wp_Model_Options::model()->increaseEnmaskTotalHits(EnMask_WP::TOTAL_OPTION_NAME);
		EnMask_Wp_Model_Options::model()->increaseEnmaskTotalHits(EnMask_WP::TOTAL_PERCENT_OPTION);

		list($version, $expire) = EnMask_Wp_Init::determineVersion();

		$config = array(
			'fontsDir' => ENMASK_FONTS_PATH,
			'webFontsDir' => get_site_url(null, 'wp-content/plugins/boom-captcha/fonts/'),
			'resultInputName' => EnMask_Wp::INPUT_NAME,
			'refreshUrl' => get_site_url(null, 'wp-content/plugins/boom-captcha/index.php?mode=refresh'),
			'copyright' => false,
			'htmlForPrepend' => '<p class="help-msg">' . __('Please type the text above:', 'enmask'). '</p>'
		);

		if ($version == EnMask_Wp::VERSION_FREE) {
			$config['fontListLimit'] = 10;
		}

		$keywords = EnMask_Wp_Model_Keywords::model()->getCaptchaKeywords($total, $version);
		if (!empty($keywords)) {
			$row = $keywords[array_rand($keywords, 1)];
			EnMask_Wp_Model_Keywords::model()->increaseHit($row['keyword_id']);

			$config['codePrefix'] = $row['keyword_name'] . ' ';
			$config['codeLength'] = array(1, 3);
		}

		$wrapper = new EnMask_Captcha_Wrapper($config);

		return $wrapper;
	}

	public static function determineVersion()
	{
		$key = get_option(EnMask_Wp::LICENSE_OPTION_NAME);

		if (!empty($key)) {
			$key = base64_decode($key);
			$parts = explode('.', $key);

			if (sizeof($parts) == 2 && is_numeric($parts[0]) && is_numeric($parts[1])) {
				if ($parts[0] > time()) {
					return array(EnMask_Wp::VERSION_PROFESSIONAL, $parts[0]);
				}
			}
		}

		return array(EnMask_Wp::VERSION_FREE, null);
	}
}