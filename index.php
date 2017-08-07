<?php

require_once(realpath(dirname(__FILE__) . '/../../../wp-load.php' ));

$libsPath = realpath(dirname(__FILE__) . '/libs');
set_include_path(get_include_path() . PATH_SEPARATOR . $libsPath);

define('ENMASK_CSV_PATH', realpath(dirname(__FILE__) . '/csv'));

require_once 'EnMask/Wp/Init.php';

if (!isset($_GET['mode'])) {
	throw new Exception('Empty  mode!');
}

list($version, $expire) = EnMask_Wp_Init::determineVersion();

switch ($_GET['mode']) {
	case 'refresh':
		$wrapper = EnMask_Wp_Init::getCaptchaWrapper();
		$_SESSION[EnMask_Wp::CAPTCHA_SESS_KEY] = serialize($wrapper->getCaptcha());

		header('Content-type: text/json');
		echo json_encode(array(
			'code' => $wrapper->getCaptcha()->getEncryptedCode(),
			'fontSubPath' => $wrapper->getCaptcha()->getFontSubPath(),
			'fontName' => $wrapper->getCaptcha()->getFontName()
		));
		exit();
		break;
	case 'csv':
		if (!current_user_can('manage_options')) {
			throw new Exception('You cannot access to this page!');
		}

		if ($version == EnMask_Wp::VERSION_FREE) {
			throw new Exception('This feature is unavaible in free version!');
		}

		require_once 'EnMask/Wp/Page/Csv.php';
		$csvPage = new EnMask_Wp_Page_Csv();
		$csvPage->start();
		break;
	case 'license':
		if (isset($_POST['license'])) {
			update_option(EnMask_Wp::LICENSE_OPTION_NAME, $_POST['license']);

			header('Location: ' . get_site_url(null, 'wp-admin/plugins.php?page=enmask-settings'));
			exit();
		}
		break;
	case 'validate':
		$code = (isset($_POST['code'])) ? $_POST['code'] : '';

		if (EnMask_Wp_Init::validate($code)) {
			$out = array('result' => true);

//			$_SESSION[EnMask_Wp::CAPTCHA_CHECKED_SESS_KEY] = true;
		} else {
			$wrapper = EnMask_Wp_Init::getCaptchaWrapper();
			$_SESSION[EnMask_Wp::CAPTCHA_SESS_KEY] = serialize($wrapper->getCaptcha());

			$out = array(
				'result' => false,
				'code' => $wrapper->getCaptcha()->getEncryptedCode(),
				'fontSubPath' => $wrapper->getCaptcha()->getFontSubPath(),
				'fontName' => $wrapper->getCaptcha()->getFontName()
			);
		}

		header('Content-type: text/json');
		echo json_encode($out);
		break;
	case 'activation':
		require_once 'EnMask/Wp/Model/ActivationOptions.php';
		EnMask_Wp_Model_ActivationOptions::model()->resetAll();

		if (!empty($_POST['protect']) && is_array($_POST['protect'])) {
			foreach ($_POST['protect'] as $option) {
				EnMask_Wp_Model_ActivationOptions::model()->setTrue($option);
			}
		}

		header('Location: ' . get_site_url(null, 'wp-admin/plugins.php?page=enmask-settings#activation-options'));
		exit();
		break;
	default:
		throw new Exception('Incorrect mode!');
		break;
}