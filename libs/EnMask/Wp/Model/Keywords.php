<?php

require_once 'EnMask/Wp/Model/Abstract.php';

class EnMask_Wp_Model_Keywords extends EnMask_Wp_Model_Abstract
{
	protected $_keywordTbl = 'enmask_keywords';

	protected $_hitTbl = 'enmask_hits';

	public static function model()
	{
		return new self();
	}

	public function insert(array $data)
	{
		$this->_db->insert($this->_keywordTbl, $data);

		return $this->_db->insert_id;
	}

	public function getList($version = null)
	{
		$rows = $this->_db->get_results('
			select
				k.*,
				count(hit_id) as keyword_hit
			from
				' . $this->_keywordTbl . ' k
				left join ' . $this->_hitTbl . ' h on k.keyword_id = h.keyword_id
			group by
				k.keyword_id
			order by keyword_id', ARRAY_A);

//		if (!empty($rows) && !is_null($version) && $version == EnMask_Wp::VERSION_FREE) {
//			$rows = array($rows[0]);
//		}

		return $rows;
	}

	public function getStatByDay()
	{
		return $this->_db->get_results('
			select
				keyword_id,
				count(hit_id) as total,
				date_format(hit_ts, "%d.%m.%Y") as day
			from
				wp_enmask_hits
			group by
				keyword_id, day(hit_ts)
			order by
				hit_ts desc
		', ARRAY_A);
	}

	public function deleteNotInList(array $list)
	{
		if (empty($list)) {
			$this->_db->query('delete from ' . $this->_keywordTbl . '');
			$this->_db->query('delete from ' . $this->_hitTbl . '');
		} else {
			$this->_db->query('delete from ' . $this->_keywordTbl . ' where keyword_id not in (' . implode(',', $list) . ')');
			$this->_db->query('delete from ' . $this->_hitTbl . ' where keyword_id not in (' . implode(',', $list) . ')');
		}
	}

	public function clear()
	{
		$this->_db->query('delete from ' . $this->_keywordTbl);
		$this->_db->query('delete from ' . $this->_hitTbl);
	}

	public function update($id, array $data)
	{
		$this->_db->update($this->_keywordTbl, $data, array('keyword_id' => $id));
	}

	public function resetRealHit()
	{
		$this->_db->query('update ' . $this->_keywordTbl . ' set keyword_real_hit = 0');
	}

	public function increaseHit($id)
	{
		$this->_db->insert($this->_hitTbl, array('keyword_id' => $id));

		$this->_db->query($this->_db->prepare('
			update
				' . $this->_keywordTbl . '
			set
				keyword_real_hit = keyword_real_hit + 1
			where
				keyword_id = %d', $id));
	}

	public function createTbls()
	{
		$this->_db->query("
			CREATE TABLE IF NOT EXISTS `" . $this->_keywordTbl . "` (
			  `keyword_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			  `keyword_name` varchar(255) NOT NULL,
			  `keyword_percent` tinyint(3) unsigned NOT NULL,
			  `keyword_real_hit` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'This count will be reseted after each keywords updates. Needs for count show percent.',
			  PRIMARY KEY (`keyword_id`)
			) ENGINE=MyISAM
		");

		$this->_db->query('
			CREATE TABLE IF NOT EXISTS `' . $this->_hitTbl . '` (
			  `hit_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			  `keyword_id` int(10) unsigned NOT NULL,
			  `hit_ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
			  PRIMARY KEY (`hit_id`),
			  KEY `keyword_id` (`keyword_id`,`hit_ts`)
			) ENGINE=MyISAM
		');
	}

	public function getCaptchaKeywords($total, $version)
	{
		$out = array();
		$keywords = $this->getList($version);
		$sum = 0;

		foreach ($keywords as $row) {
//			if ($version == EnMask_Wp::VERSION_FREE) {
//				$row['keyword_percent'] = 10;
//			}

			if ($total == 0 || $row['keyword_real_hit'] == 0 || $row['keyword_percent'] == 100) {
				$out[] = $row;
			} else {
				$curPercent = round($row['keyword_real_hit'] / $total, 2) * 100;

				if ($curPercent <= $row['keyword_percent']) {
					$out[] = $row;
				}
			}

			$sum += $row['keyword_percent'];
		}

		if ($sum == 100) {
			$out = $keywords;
		}

		return $out;
	}

	protected function setupName()
	{
		$this->_keywordTbl = $this->_db->prefix . $this->_keywordTbl;
		$this->_hitTbl = $this->_db->prefix . $this->_hitTbl;
	}
}