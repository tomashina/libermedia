<?php
class ModelExtensionCustom extends Model {
	public function install() {
	$this->db->query("CREATE TABLE IF NOT EXISTS `".DB_PREFIX."custom` (
  `custom_id` int(11) NOT NULL AUTO_INCREMENT,
  `sort_order` int(3) NOT NULL,
  PRIMARY KEY (`custom_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;");

$this->db->query("CREATE TABLE IF NOT EXISTS `".DB_PREFIX."custom_description` (
  `custom_id` int(11) NOT NULL,
  `language_id` int(11) NOT NULL,
  `name` varchar(128) NOT NULL
 ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;");

$this->db->query("CREATE TABLE IF NOT EXISTS `".DB_PREFIX."product_extrafields` (
  `product_extrafieds_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `custom_id` int(11) NOT NULL,
  `imagestatus` int(11) NOT NULL,
  `extra_image` varchar(250) NOT NULL,
  `sort_order` int(11) NOT NULL,
  `date_added` date NOT NULL,
  `date_modified` date NOT NULL,
  PRIMARY KEY (`product_extrafieds_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;");

$this->db->query("CREATE TABLE IF NOT EXISTS `".DB_PREFIX."product_extrafields_description` (
  `product_extrafieds_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `language_id` int(11) NOT NULL,
  `value` text NOT NULL
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;");

$this->db->query("CREATE TABLE IF NOT EXISTS `".DB_PREFIX."category_extrafields` (
  `category_extrafieds_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `custom_id` int(11) NOT NULL,
  `imagestatus` int(11) NOT NULL,
  `extra_image` varchar(250) NOT NULL,
  `extra_sort_order` int(11) NOT NULL,
  `date_added` date NOT NULL,
  `date_modified` date NOT NULL,
  PRIMARY KEY (`category_extrafieds_id`),
  KEY `product_id` (`category_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;");

$this->db->query("CREATE TABLE IF NOT EXISTS `".DB_PREFIX."category_extrafields_description` (
  `category_extrafieds_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `language_id` int(11) NOT NULL,
  `value` text NOT NULL
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;");

	}
	public function uninstall() {
	$this->db->query("DROP TABLE IF EXISTS `".DB_PREFIX."custom`");
	$this->db->query("DROP TABLE IF EXISTS `".DB_PREFIX."custom_description`");
	$this->db->query("DROP TABLE IF EXISTS `".DB_PREFIX."product_extrafields`");
	$this->db->query("DROP TABLE IF EXISTS `".DB_PREFIX."product_extrafields_description`");
	$this->db->query("DROP TABLE IF EXISTS `".DB_PREFIX."category_extrafields`");
	$this->db->query("DROP TABLE IF EXISTS `".DB_PREFIX."category_extrafields_description`");
	}
	
	public function addCustom($data) {
		$this->event->trigger('pre.admin.custom.add', $data);

		$this->db->query("INSERT INTO `" . DB_PREFIX . "custom` SET sort_order = '" . (int)$data['sort_order'] . "'");

		$custom_id = $this->db->getLastId();

		foreach ($data['custom_description'] as $language_id => $value) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "custom_description SET custom_id = '" . (int)$custom_id . "', language_id = '" . (int)$language_id . "', name = '" . $this->db->escape($value['name']) . "'");
		}

		

		return $custom_id;
	}

	public function editCustom($custom_id, $data) {
		$this->event->trigger('pre.admin.custom.edit', $data);

		$this->db->query("UPDATE `" . DB_PREFIX . "custom` SET  sort_order = '" . (int)$data['sort_order'] . "' WHERE custom_id = '" . (int)$custom_id . "'");

		$this->db->query("DELETE FROM " . DB_PREFIX . "custom_description WHERE custom_id = '" . (int)$custom_id . "'");

		foreach ($data['custom_description'] as $language_id => $value) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "custom_description SET custom_id = '" . (int)$custom_id . "', language_id = '" . (int)$language_id . "', name = '" . $this->db->escape($value['name']) . "'");
		}


		
	}

	public function deleteCustom($custom_id) {
		
		$this->db->query("DELETE FROM `" . DB_PREFIX . "custom` WHERE custom_id = '" . (int)$custom_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "custom_description WHERE custom_id = '" . (int)$custom_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_extrafields WHERE custom_id = '" . (int)$custom_id . "'");

	}

	public function getCustom($custom_id) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "custom` o LEFT JOIN " . DB_PREFIX . "custom_description od ON (o.custom_id = od.custom_id) WHERE o.custom_id = '" . (int)$custom_id . "' AND od.language_id = '" . (int)$this->config->get('config_language_id') . "'");

		return $query->row;
	}

	public function getCustoms($data = array()) {
		$sql = "SELECT * FROM `" . DB_PREFIX . "custom` o LEFT JOIN " . DB_PREFIX . "custom_description od ON (o.custom_id = od.custom_id) WHERE od.language_id = '" . (int)$this->config->get('config_language_id') . "'";

		if (!empty($data['filter_name'])) {
			$sql .= " AND od.name LIKE '" . $this->db->escape($data['filter_name']) . "%'";
		}

		$sort_data = array(
			'od.name',
			'o.sort_order'
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY od.name";
		}

		if (isset($data['order']) && ($data['order'] == 'DESC')) {
			$sql .= " DESC";
		} else {
			$sql .= " ASC";
		}

		if (isset($data['start']) || isset($data['limit'])) {
			if ($data['start'] < 0) {
				$data['start'] = 0;
			}

			if ($data['limit'] < 1) {
				$data['limit'] = 20;
			}

			$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
		}

		$query = $this->db->query($sql);

		return $query->rows;
	}

	public function getCustomDescriptions($custom_id) {
		$custom_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "custom_description WHERE custom_id = '" . (int)$custom_id . "'");

		foreach ($query->rows as $result) {
			$custom_data[$result['language_id']] = array('name' => $result['name']);
		}

		return $custom_data;
	}
        
	
	public function getTotalCustoms($data = array()) {
		$sql = "SELECT COUNT(*) as total FROM `" . DB_PREFIX . "custom` o LEFT JOIN " . DB_PREFIX . "custom_description od ON (o.custom_id = od.custom_id) WHERE od.language_id = '" . (int)$this->config->get('config_language_id') . "'";

		if (!empty($data['filter_name'])) {
			$sql .= " AND od.name LIKE '" . $this->db->escape($data['filter_name']) . "%'";
		}

        $query = $this->db->query($sql);

		return $query->row['total'];
	}
}