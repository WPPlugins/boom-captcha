<?php

require_once 'EnMask/Wp/Page/Abstract.php';

class EnMask_Wp_Page_Test extends EnMask_Wp_Page_Abstract {

	public static function get()
	{
		$obj = parent::model(__CLASS__);
		$obj->start();
	}

	public function start()
	{
		require_once 'EnMask/Wp/Model/Keywords.php';

//		EnMask_Wp_Model_Keywords::model()->createTbl();
//		EnMask_Wp_Model_Keywords::model()->insert(array(
//			'keyword_name' => 'test"',
//			'keyword_percent' => '10',
//			'keyword_hit' => 100
//		));

		var_dump(EnMask_Wp_Model_Keywords::model()->getCaptchaKeywords(5));
	}
}