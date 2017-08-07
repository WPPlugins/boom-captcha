<?php
/*
Plugin Name: BoomCaptcha
Plugin URI: http://boomcaptcha.com
Description: captcha with encrypted fonts.
Version: 1.1
*/

$libsPath = realpath(dirname(__FILE__) . '/libs');
set_include_path(get_include_path() . PATH_SEPARATOR . $libsPath);

define('ENMASK_PLUGIN_FILE', __FILE__);
define('ENMASK_TPLS_PATH', realpath(dirname(__FILE__) . '/tpls'));
define('ENMASK_FONTS_PATH', realpath(dirname(__FILE__) . '/fonts'));

require_once(ABSPATH . 'wp-includes/pluggable.php');
require_once 'EnMask/Wp/Init.php';

load_plugin_textdomain('enmask', false, basename(dirname( __FILE__ )) . '/languages');

register_activation_hook(__FILE__, array('EnMask_Wp_Init', 'activation'));

EnMask_Wp_Init::setupHooks(is_admin(), get_current_user_id());