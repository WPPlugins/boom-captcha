<?php

require_once 'EnMask/Wp/Page/Abstract.php';
require_once 'EnMask/Wp/Model/Keywords.php';

class EnMask_Wp_Page_Csv extends EnMask_Wp_Page_Abstract {

	public static function get()
	{
		$obj = parent::model(__CLASS__);
		$obj->output();
	}

	public function start()
	{
		$csvPath = time() . '.csv';
		$fp = fopen(ENMASK_CSV_PATH . DIRECTORY_SEPARATOR . $csvPath, 'w');

		$header = array('Date');
		$keywords = EnMask_Wp_Model_Keywords::model()->getList();
		foreach ($keywords as $row) {
			$header[] = $row['keyword_name'];
		}

		fputcsv($fp, $header);

		$curDate = null;
		$csvRow = array();
		$stat = EnMask_Wp_Model_Keywords::model()->getStatByDay();
		foreach ($stat as $row) {
			if ($curDate != $row['day']) {
				if (!empty($csvRow)) {
					fputcsv($fp, $this->prepareCsvRow($curDate, $csvRow, $keywords));
					$csvRow = array();
				}

				$curDate = $row['day'];
			}

			$csvRow[$row['keyword_id']] = $row['total'];
		}

		if (!empty($csvRow)) {
			fputcsv($fp, $this->prepareCsvRow($curDate, $csvRow, $keywords));
		}

		fclose($fp);

		header('Location: ' . get_site_url(null, 'wp-content/plugins/boomcaptcha/csv/' . $csvPath));
		exit();
	}

	protected function prepareCsvRow($curDate, array $csvRow, array $keywords)
	{
		$out = array($curDate);
		foreach ($keywords as $keyword) {
			$out[] = (isset($csvRow[$keyword['keyword_id']])) ? $csvRow[$keyword['keyword_id']] : 0;
		}

		return $out;
	}
}