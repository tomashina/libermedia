<?php
class ModelExtensionbgcombipack extends Model {
	public function addbgcombipack($data) {
		$customer_group = (isset($data['customer_group']) && $data['customer_group']) ? implode(",",$data['customer_group']) : '';
		$store = (isset($data['store']) && $data['store']) ? implode(",",$data['store']) : ''; 
		
		$buyproduct = (isset($data['buyproduct']) && $data['buyproduct']) ? implode(",",$data['buyproduct']) : '';
		$buycategory = (isset($data['buycategory']) && $data['buycategory']) ? implode(",",$data['buycategory']) : '';
 		$buymanufacturer = (isset($data['buymanufacturer']) && $data['buymanufacturer']) ? implode(",",$data['buymanufacturer']) : '';
		$exbuyproduct = (isset($data['exbuyproduct']) && $data['exbuyproduct']) ? implode(",",$data['exbuyproduct']) : '';
		$exbuycategory = (isset($data['exbuycategory']) && $data['exbuycategory']) ? implode(",",$data['exbuycategory']) : '';
 		$exbuymanufacturer = (isset($data['exbuymanufacturer']) && $data['exbuymanufacturer']) ? implode(",",$data['exbuymanufacturer']) : '';
		
		$getproduct = (isset($data['getproduct']) && $data['getproduct']) ? implode(",",$data['getproduct']) : '';
		$getcategory = (isset($data['getcategory']) && $data['getcategory']) ? implode(",",$data['getcategory']) : '';
 		$getmanufacturer = (isset($data['getmanufacturer']) && $data['getmanufacturer']) ? implode(",",$data['getmanufacturer']) : '';
		$exgetproduct = (isset($data['exgetproduct']) && $data['exgetproduct']) ? implode(",",$data['exgetproduct']) : '';
		$exgetcategory = (isset($data['exgetcategory']) && $data['exgetcategory']) ? implode(",",$data['exgetcategory']) : '';
 		$exgetmanufacturer = (isset($data['exgetmanufacturer']) && $data['exgetmanufacturer']) ? implode(",",$data['exgetmanufacturer']) : '';

 		//print_r($store);exit;
 
		$this->db->query("INSERT INTO " . DB_PREFIX . "bgcombipack SET title = '" . $this->db->escape(json_encode($data['title'],true)) . "', ribbontext = '" . $this->db->escape(json_encode($data['ribbontext'],true)) . "', ordtotaltext = '" . $this->db->escape(json_encode($data['ordtotaltext'],true)) . "', status = '" . (int)$data['status'] . "', disctype = '" . (int)$data['disctype'] . "', discount = '" . (float)$data['discount'] . "', buyqty = '" . (int)$data['buyqty'] . "', getqty = '" . (int)$data['getqty'] . "', `startdate` = '" . $this->db->escape($data['startdate']) . "', `enddate` = '" . $this->db->escape($data['enddate']) . "', customer_group = '" . $this->db->escape($customer_group) . "', store = '" . $this->db->escape($store) . "', showofferat = '" . (int)$data['showofferat'] . "', `offer_heading` = '" . $this->db->escape(json_encode($data['offer_heading'], true)) . "', `offer_content` = '" . $this->db->escape(json_encode($data['offer_content'], true)) . "', buyproduct = '" . $this->db->escape($buyproduct) . "', buycategory = '" . $this->db->escape($buycategory) . "', buymanufacturer = '" . $this->db->escape($buymanufacturer) . "', exbuyproduct = '" . $this->db->escape($exbuyproduct) . "', exbuycategory = '" . $this->db->escape($exbuycategory) . "', exbuymanufacturer = '" . $this->db->escape($exbuymanufacturer) . "', getproduct = '" . $this->db->escape($getproduct) . "', getcategory = '" . $this->db->escape($getcategory) . "', getmanufacturer = '" . $this->db->escape($getmanufacturer) . "', exgetproduct = '" . $this->db->escape($exgetproduct) . "', exgetcategory = '" . $this->db->escape($exgetcategory) . "', exgetmanufacturer = '" . $this->db->escape($exgetmanufacturer) . "' , date_added = now()");
 
		$bgcombipack_id = $this->db->getLastId();		
		
		return $bgcombipack_id;
	}

	public function editbgcombipack($bgcombipack_id, $data) {
		$customer_group = (isset($data['customer_group']) && $data['customer_group']) ? implode(",",$data['customer_group']) : '';
		$store = (isset($data['store']) && $data['store']) ? implode(",",$data['store']) : ''; 
		
		$buyproduct = (isset($data['buyproduct']) && $data['buyproduct']) ? implode(",",$data['buyproduct']) : '';
		$buycategory = (isset($data['buycategory']) && $data['buycategory']) ? implode(",",$data['buycategory']) : '';
 		$buymanufacturer = (isset($data['buymanufacturer']) && $data['buymanufacturer']) ? implode(",",$data['buymanufacturer']) : '';
		$exbuyproduct = (isset($data['exbuyproduct']) && $data['exbuyproduct']) ? implode(",",$data['exbuyproduct']) : '';
		$exbuycategory = (isset($data['exbuycategory']) && $data['exbuycategory']) ? implode(",",$data['exbuycategory']) : '';
 		$exbuymanufacturer = (isset($data['exbuymanufacturer']) && $data['exbuymanufacturer']) ? implode(",",$data['exbuymanufacturer']) : '';
		
		$getproduct = (isset($data['getproduct']) && $data['getproduct']) ? implode(",",$data['getproduct']) : '';
		$getcategory = (isset($data['getcategory']) && $data['getcategory']) ? implode(",",$data['getcategory']) : '';
 		$getmanufacturer = (isset($data['getmanufacturer']) && $data['getmanufacturer']) ? implode(",",$data['getmanufacturer']) : '';
		$exgetproduct = (isset($data['exgetproduct']) && $data['exgetproduct']) ? implode(",",$data['exgetproduct']) : '';
		$exgetcategory = (isset($data['exgetcategory']) && $data['exgetcategory']) ? implode(",",$data['exgetcategory']) : '';
 		$exgetmanufacturer = (isset($data['exgetmanufacturer']) && $data['exgetmanufacturer']) ? implode(",",$data['exgetmanufacturer']) : '';
		
		$this->db->query("UPDATE " . DB_PREFIX . "bgcombipack SET title = '" . $this->db->escape(json_encode($data['title'],true)) . "', ribbontext = '" . $this->db->escape(json_encode($data['ribbontext'],true)) . "', ordtotaltext = '" . $this->db->escape(json_encode($data['ordtotaltext'],true)) . "', status = '" . (int)$data['status'] . "', disctype = '" . (int)$data['disctype'] . "', discount = '" . (float)$data['discount'] . "', buyqty = '" . (int)$data['buyqty'] . "', getqty = '" . (int)$data['getqty'] . "', `startdate` = '" . $this->db->escape($data['startdate']) . "', `enddate` = '" . $this->db->escape($data['enddate']) . "', customer_group = '" . $this->db->escape($customer_group) . "', store = '" . $this->db->escape($store) . "', showofferat = '" . (int)$data['showofferat'] . "', `offer_heading` = '" . $this->db->escape(json_encode($data['offer_heading'], true)) . "', `offer_content` = '" . $this->db->escape(json_encode($data['offer_content'], true)) . "', buyproduct = '" . $this->db->escape($buyproduct) . "', buycategory = '" . $this->db->escape($buycategory) . "', buymanufacturer = '" . $this->db->escape($buymanufacturer) . "', exbuyproduct = '" . $this->db->escape($exbuyproduct) . "', exbuycategory = '" . $this->db->escape($exbuycategory) . "', exbuymanufacturer = '" . $this->db->escape($exbuymanufacturer) . "', getproduct = '" . $this->db->escape($getproduct) . "', getcategory = '" . $this->db->escape($getcategory) . "', getmanufacturer = '" . $this->db->escape($getmanufacturer) . "', exgetproduct = '" . $this->db->escape($exgetproduct) . "', exgetcategory = '" . $this->db->escape($exgetcategory) . "', exgetmanufacturer = '" . $this->db->escape($exgetmanufacturer) . "' WHERE bgcombipack_id = '" . (int)$bgcombipack_id . "'");
 	}

	public function deletebgcombipack($bgcombipack_id) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "bgcombipack WHERE bgcombipack_id = '" . (int)$bgcombipack_id . "'");
	}

	public function getbgcombipack($bgcombipack_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "bgcombipack WHERE bgcombipack_id = '" . (int)$bgcombipack_id . "' ");

		return $query->row;
	}

	public function getbgcombipacks($data = array()) {
		$sql = "SELECT * FROM " . DB_PREFIX . "bgcombipack WHERE 1 ";
		
		if (!empty($data['filter_title'])) {
			$sql .= " AND title LIKE '%" . $this->db->escape($data['filter_title']) . "%'";
		}
		
		if (isset($data['filter_status']) && $data['filter_status'] != '') {
			$sql .= " AND status = ".(int)$data['filter_status'];
		}
		
		if (!empty($data['filter_disctype'])) {
			$sql .= " AND disctype = ".(int)$data['filter_disctype'];
		}		
		if (!empty($data['filter_discount'])) {
			$sql .= " AND discount = ".(float)$data['filter_discount'];
		}
		
		if (!empty($data['filter_buyqty'])) {
			$sql .= " AND buyqty = " . (int)$data['filter_buyqty'] . "";
		}		
		if (!empty($data['filter_getqty'])) {
			$sql .= " AND getqty = " . (int)$data['filter_getqty'] . "";
		}
		
		if (!empty($data['filter_startdate'])) {
			$sql .= " AND DATE(startdate) = DATE('" . $this->db->escape($data['filter_startdate']) . "')";
		}		
		if (!empty($data['filter_enddate'])) {
			$sql .= " AND DATE(enddate) = DATE('" . $this->db->escape($data['filter_enddate']) . "')";
		}
				
		if (!empty($data['filter_customer_group_id'])) {
			$sql .= " AND find_in_set(".$data['filter_customer_group_id'].", customer_group)";
		}		
		if (isset($data['filter_store_id']) && $data['filter_store_id'] != '') {
			$sql .= " AND find_in_set(".$data['filter_store_id'].", store)";
		}
		
		// buy
		if (!empty($data['filter_buyproduct_id'])) {
			$sql .= " AND find_in_set(".$data['filter_buyproduct_id'].", buyproduct)";
		}		
		if (!empty($data['filter_buycategory_id'])) {
			$sql .= " AND find_in_set(".$data['filter_buycategory_id'].", buycategory)";
		}		
		if (!empty($data['filter_buymanufacturer_id'])) {
			$sql .= " AND find_in_set(".$data['filter_buymanufacturer_id'].", buymanufacturer)";
		}
		
		if (!empty($data['filter_exbuyproduct_id'])) {
			$sql .= " AND find_in_set(".$data['filter_exbuyproduct_id'].", exbuyproduct)";
		}		
		if (!empty($data['filter_exbuycategory_id'])) {
			$sql .= " AND find_in_set(".$data['filter_exbuycategory_id'].", exbuycategory)";
		}		
		if (!empty($data['filter_exbuymanufacturer_id'])) {
			$sql .= " AND find_in_set(".$data['filter_exbuymanufacturer_id'].", exbuymanufacturer)";
		}
		
		// get
		if (!empty($data['filter_getproduct_id'])) {
			$sql .= " AND find_in_set(".$data['filter_getproduct_id'].", getproduct)";
		}		
		if (!empty($data['filter_getcategory_id'])) {
			$sql .= " AND find_in_set(".$data['filter_getcategory_id'].", getcategory)";
		}		
		if (!empty($data['filter_getmanufacturer_id'])) {
			$sql .= " AND find_in_set(".$data['filter_getmanufacturer_id'].", getmanufacturer)";
		}
		
		if (!empty($data['filter_exgetproduct_id'])) {
			$sql .= " AND find_in_set(".$data['filter_exgetproduct_id'].", exgetproduct)";
		}		
		if (!empty($data['filter_exgetcategory_id'])) {
			$sql .= " AND find_in_set(".$data['filter_exgetcategory_id'].", exgetcategory)";
		}		
		if (!empty($data['filter_exgetmanufacturer_id'])) {
			$sql .= " AND find_in_set(".$data['filter_exgetmanufacturer_id'].", exgetmanufacturer)";
		}
 
		$sql .= " GROUP BY bgcombipack_id";

		$sort_data = array(
 			'bgcombipack_id',
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY bgcombipack_id";
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
 
	public function getTotalbgcombipacks($data = array()) {
		$sql = "SELECT COUNT(*) AS total FROM " . DB_PREFIX . "bgcombipack WHERE 1 ";
  		
		if (!empty($data['filter_title'])) {
			$sql .= " AND title LIKE '%" . $this->db->escape($data['filter_title']) . "%'";
		}
		
		if (isset($data['filter_status']) && $data['filter_status'] != '') {
			$sql .= " AND status = ".(int)$data['filter_status'];
		}
		
		if (!empty($data['filter_disctype'])) {
			$sql .= " AND disctype = ".(int)$data['filter_disctype'];
		}		
		if (!empty($data['filter_discount'])) {
			$sql .= " AND discount = ".(float)$data['filter_discount'];
		}
		
		if (!empty($data['filter_buyqty'])) {
			$sql .= " AND buyqty = " . (int)$data['filter_buyqty'] . "";
		}		
		if (!empty($data['filter_getqty'])) {
			$sql .= " AND getqty = " . (int)$data['filter_getqty'] . "";
		}
		
		if (!empty($data['filter_startdate'])) {
			$sql .= " AND DATE(startdate) = DATE('" . $this->db->escape($data['filter_startdate']) . "')";
		}		
		if (!empty($data['filter_enddate'])) {
			$sql .= " AND DATE(enddate) = DATE('" . $this->db->escape($data['filter_enddate']) . "')";
		}
				
		if (!empty($data['filter_customer_group_id'])) {
			$sql .= " AND find_in_set(".$data['filter_customer_group_id'].", customer_group)";
		}		
		if (isset($data['filter_store_id']) && $data['filter_store_id'] != '') {
			$sql .= " AND find_in_set(".$data['filter_store_id'].", store)";
		}
		
		// buy
		if (!empty($data['filter_buyproduct_id'])) {
			$sql .= " AND find_in_set(".$data['filter_buyproduct_id'].", buyproduct)";
		}		
		if (!empty($data['filter_buycategory_id'])) {
			$sql .= " AND find_in_set(".$data['filter_buycategory_id'].", buycategory)";
		}		
		if (!empty($data['filter_buymanufacturer_id'])) {
			$sql .= " AND find_in_set(".$data['filter_buymanufacturer_id'].", buymanufacturer)";
		}
		
		if (!empty($data['filter_exbuyproduct_id'])) {
			$sql .= " AND find_in_set(".$data['filter_exbuyproduct_id'].", exbuyproduct)";
		}		
		if (!empty($data['filter_exbuycategory_id'])) {
			$sql .= " AND find_in_set(".$data['filter_exbuycategory_id'].", exbuycategory)";
		}		
		if (!empty($data['filter_exbuymanufacturer_id'])) {
			$sql .= " AND find_in_set(".$data['filter_exbuymanufacturer_id'].", exbuymanufacturer)";
		}
		
		// get
		if (!empty($data['filter_getproduct_id'])) {
			$sql .= " AND find_in_set(".$data['filter_getproduct_id'].", getproduct)";
		}		
		if (!empty($data['filter_getcategory_id'])) {
			$sql .= " AND find_in_set(".$data['filter_getcategory_id'].", getcategory)";
		}		
		if (!empty($data['filter_getmanufacturer_id'])) {
			$sql .= " AND find_in_set(".$data['filter_getmanufacturer_id'].", getmanufacturer)";
		}
		
		if (!empty($data['filter_exgetproduct_id'])) {
			$sql .= " AND find_in_set(".$data['filter_exgetproduct_id'].", exgetproduct)";
		}		
		if (!empty($data['filter_exgetcategory_id'])) {
			$sql .= " AND find_in_set(".$data['filter_exgetcategory_id'].", exgetcategory)";
		}		
		if (!empty($data['filter_exgetmanufacturer_id'])) {
			$sql .= " AND find_in_set(".$data['filter_exgetmanufacturer_id'].", exgetmanufacturer)";
		}
		  		
		$query = $this->db->query($sql);

		return isset($query->row['total']) ? $query->row['total'] : 0;
	}
	public function getprodcatman($target_val, $target) {
		$this->load->model('catalog/product');
		$this->load->model('catalog/category');
 		$this->load->model('catalog/manufacturer');
		
		$return = array();
		$explode = explode(",",$target_val);
		if($explode) {
			foreach ($explode as $id) {
				if($target == 'product') {
					$info = $this->model_catalog_product->getProduct((int)$id);
					if ($info) {
						$return[$info['product_id']] = $info['name'];
					}
				} else if($target == 'category') {
					$info = $this->model_catalog_category->getCategory((int)$id);;
 					if ($info) {
						$return[$info['category_id']] = ($info['path']) ? $info['path'] . ' &gt; ' . $info['name'] : $info['name'];
					}
				} if($target == 'manufacturer') {
					$info = $this->model_catalog_manufacturer->getManufacturer((int)$id);
 					if ($info) {
						$return[$info['manufacturer_id']] = $info['name'];
					}
				}			
			}
		}
		return $return;
	}
	public function getprodcatmanform($bginfo, $target_ele, $target) {
		$this->load->model('catalog/product');
		$this->load->model('catalog/category');
 		$this->load->model('catalog/manufacturer');
		
		$return_data[0] = array();
		$return_data[1] = array();
 		
  		if (isset($this->request->post[$target_ele])) {
			$return_data[0] = $this->request->post[$target_ele];
		} elseif (!empty($bginfo)) {
			$return_data[0] = ($bginfo[$target_ele]) ? explode(",",$bginfo[$target_ele]) : array();
 		}
		
 		$this->load->model('catalog/product');
		
 		if($return_data[0]) {
 			foreach ($return_data[0] as $id) {
				if($target == 'product') {
					$info = $this->model_catalog_product->getProduct((int)$id);
					if ($info) {
						$return_data[1][] = array(
							'product_id' => $info['product_id'],
							'name'       => $info['name']
						);
					}
				} else if($target == 'category') {
					$info = $this->model_catalog_category->getCategory((int)$id);;
 					if ($info) {
						$return_data[1][] = array(
							'category_id' => $info['category_id'],
							'name' => ($info['path']) ? $info['path'] . ' &gt; ' . $info['name'] : $info['name']
						);
					}					
				} if($target == 'manufacturer') {
					$info = $this->model_catalog_manufacturer->getManufacturer((int)$id);
 					if ($info) {
						$return_data[1][] = array(
							'manufacturer_id' => $info['manufacturer_id'],
							'name' => $info['name'],
						);
					}
				}								
			}
		}
		
		return $return_data;
	}
	public function getcgstore($target, $arr, $id, $nm) {
		$info = array();
		$ids = explode(",",$target);
		if($ids) { 
			foreach ($arr as $arval) {
				if (in_array($arval[$id], $ids)) {
					$info[$arval[$id]] = $arval[$nm];
				}
			}
		}
		return $info;
	}
	
	// Filter HTML starts
	public function get_InpTxt_FILTER_html($name, $val, $divcls, $lblcls, $entry) {
		return sprintf('<div class="'.$divcls.' col-sm-2"> <label class="'.$lblcls.'">%s</label><input type="text" name="%s" value="%s" class="form-control"/> </div>', $entry, $name, $val);
	}
	public function get_InpTxt_DATE_FILTER_html($name, $val, $divcls, $lblcls, $entry) {
		return sprintf('<div class="'.$divcls.' col-sm-2"> <label class="'.$lblcls.'">%s</label><input type="text" name="%s" value="%s" class="form-control date" data-date-format="YYYY-MM-DD"/> </div>', $entry, $name, $val);
	}
	public function get_SELECT_FILTER_html($name, $val, $divcls, $lblcls, $entry, $opttxt) {		
		$html = array();
		
		$selclass = substr(VERSION,0,3)=='4.0' ? 'form-select' : 'form-control';
		$html[] = sprintf('<div class="'.$divcls.' col-sm-2"> <label class="'.$lblcls.'">%s</label> <select name="%s" id="%s" class="%s"> <option value=""></option>', $entry, $name, $name, $selclass);
		foreach($opttxt as $ky => $op) {
			$sel = $val === (string)$ky ? 'selected="selected"' : '';
			$html[] = sprintf('<option value="%s" %s>%s</option>', $ky, $sel, $op);
		}
		$html[] = sprintf('</select></div>');
		
		return join($html);
	}
	public function get_SELECT_CGSTORE_FILTER_html($name, $val, $looparr, $loopky, $loopval, $divcls, $lblcls, $entry) {		
		$html = array();
		
		$selclass = substr(VERSION,0,3)=='4.0' ? 'form-select' : 'form-control';
		$html[] = sprintf('<div class="'.$divcls.' col-sm-2"> <label class="'.$lblcls.'">%s</label> <select name="%s" id="%s" class="%s"> <option value=""></option>', $entry, $name, $name, $selclass);
		foreach ($looparr as $rs) {
			$sel = $val == $rs[$loopky] ? 'selected="selected"' : '';
			$html[] = sprintf('<option value="%s" %s>%s</option>', $rs[$loopky], $sel, $rs[$loopval]);
		}
		$html[] = sprintf('</select></div>');
		
		return join($html);
	}
	public function get_FILTER_pcm_html($name, $val, $nameid, $valid, $divcls, $lblcls, $entry) {
		if(substr(VERSION,0,3)=='4.0') {
			return sprintf('<div class="'.$divcls.' col-sm-2"> <label class="'.$lblcls.'">%s</label> <input type="text" name="%s" value="%s" class="form-control" id="input-%s" list="input-%s" data-oc-target="autocomplete-%s" autocomplete="off"/> <input type="hidden" name="%s" value="%s" class="form-control"/> <ul id="autocomplete-%s" class="dropdown-menu"></ul> </div>', $entry, $name, $val, $name, $name, $name, $nameid, $valid, $name);	
		} else {
			return sprintf('<div class="'.$divcls.' col-sm-2"> <label class="'.$lblcls.'">%s</label> <input type="text" name="%s" value="%s" class="form-control" id="%s"/> <input type="hidden" name="%s" value="%s" class="form-control"/> </div>', $entry, $name, $val, $name, $nameid, $valid);	
		}
	}
	// Filter ends here
	
	// Form HTML STARTS
	public function get_InpTxt_html($name, $val, $divcls, $lblcls, $entry, $help = '') {
		return sprintf('<div class="'.$divcls.'"> <label class="col-sm-2 '.$lblcls.'">%s</label><div class="col-sm-10"> <input type="text" name="%s" value="%s" class="form-control"/> %s </div> </div>', $entry, $name, $val, $help);
	}
	public function get_InpDATE_html($name, $val, $divcls, $lblcls, $entry, $help = '') {
		return sprintf('<div class="'.$divcls.'"> <label class="col-sm-2 '.$lblcls.'">%s</label><div class="col-sm-10"> <input type="text" name="%s" value="%s" class="form-control date" data-date-format="YYYY-MM-DD"/> %s </div> </div>', $entry, $name, $val, $help);
	}
	public function get_RDO_html($name, $val, $divcls, $lblcls, $entry, $opttxt) {		
		$html = array();
		
		$html[] = sprintf('<div class="'.$divcls.'"> <label class="col-sm-2 '.$lblcls.'">%s</label><div class="col-sm-10">', $entry);
		foreach($opttxt as $ky => $op) {
			$sel = $val == $ky ? 'checked="checked"' : '';
			$html[] = sprintf('<label class="radio-inline"> <input type="radio" name="%s" value="%s" %s/> %s </label>', $name, $ky, $sel, $op);
		}
		$html[] = sprintf('</div> </div>');
		
		return join($html);
	}
	public function get_InpTxt_CHKBOXWELL_html($name, $val, $looparr, $loopky, $loopval, $divcls, $lblcls, $wellcls, $entry, $help = '') {
		$inphtml = array();
		
		foreach ($looparr as $rs) {
			$chk = in_array($rs[$loopky], $val) ? 'checked' : '';
			$inphtml[] = sprintf('<div class="checkbox"> <label> <input type="checkbox" name="%s[]" value="%s" %s/>%s</label></div>', $name, $rs[$loopky], $chk, $rs[$loopval]);
		}
		
		$chkall = '<a class="badge bg-secondary" onclick="$(this).parent().find(\':checkbox\').prop(\'checked\', true);">Check All</a> / <a class="badge bg-secondary" onclick="$(this).parent().find(\':checkbox\').prop(\'checked\', false);">Uncheck All</a>';
		
		return sprintf('<div class="'.$divcls.'"> <label class="col-sm-2 '.$lblcls.'">%s</label><div class="col-sm-10"> <div class="'.$wellcls.'" style="height: 150px; overflow: auto;"> %s </div> %s </div> %s </div>', $entry, join($inphtml), $chkall, $help);		
	}
	public function get_pcm_autocmp_html($name, $looparr, $loopky, $loopval, $divcls, $lblcls, $wellcls, $entry, $help = '') {
		$html = array();
		
		if(substr(VERSION,0,3)=='4.0') { 
			$html[] = sprintf('<div class="'.$divcls.'i" style="width: 48%%; float:left; margin: 5px;"> <label class="col-sm-i '.$lblcls.'" style="text-align:left">%s</label><div class="col-sm-i"> <input type="text" name="%s" value="" id="input-%s" list="input-%s" data-oc-target="autocomplete-%s" class="form-control" autocomplete="off"/> <ul id="autocomplete-%s" class="dropdown-menu"></ul> <div class="input-group"> <div class="form-control p-0" style="height: 150px; overflow: auto;"> <table id="tbl-%s" class="table table-sm m-0"> <tbody> ', $entry, $name, $name, $name, $name, $name, $name, $wellcls);
					
			foreach ($looparr as $rs) {
				$html[] = sprintf('<tr id="tr-%s-%s"> <td>%s<input type="hidden" name="%s[]" value="%s"/></td> <td class="text-end"><button type="button" class="btn btn-danger btn-sm"><i class="fas fa-minus-circle"></i></button></td> </tr>', $name, $rs[$loopky], $rs[$loopval], $name, $rs[$loopky] );
			}
			
			$html[] = '</tbody> </table> </div> </div> </div> </div>';
		} else {
			$html[] = sprintf('<div class="'.$divcls.'i" style="width: 48%%; float:left; margin: 5px;"> <label class="col-sm-i '.$lblcls.'">%s</label><div class="col-sm-i"> <input type="text" name="%s" value="" id="input-%s" class="form-control" /> <div id="%s" class="%s" style="height: 150px; overflow: auto;"> ', $entry, $name, $name, $name, $wellcls);
		
			foreach ($looparr as $rs) {
				$html[] = sprintf('<div id="%s-%s"><i class="fa fa-minus-circle"></i> %s <input type="hidden" name="%s[]" value="%s" /> </div>', $name, $rs[$loopky], $rs[$loopval], $name, $rs[$loopky] );
			}
			
			$html[] = '</div> </div> </div>';
		} 
		
		return join($html);
	}
	public function get_InpTxt_LANG_ULTAB_html($name, $val, $langid, $divcls, $lblcls, $entry, $help = '', $istxtarea = 0) {
		$html = array();
		
		if($istxtarea == 1) {
			if(substr(VERSION,0,3)=='4.0') { 
				$html[] = sprintf('<div class="'.$divcls.'"> <label class="col-sm-2 '.$lblcls.'">%s</label> <div class="col-sm-10"> <textarea name="%s[%s]" class="form-control summernote" data-oc-toggle="ckeditor" data-lang="ckeditor"> %s </textarea> </div> </div>', $entry, $name, $langid, $val); 	
			} else {
				$html[] = sprintf('<div class="'.$divcls.'"> <label class="col-sm-2 '.$lblcls.'">%s</label> <div class="col-sm-10"> <textarea name="%s[%s]" class="form-control summernote" data-toggle="summernote" data-lang="summernote"> %s </textarea> </div> </div>', $entry, $name, $langid, $val); 	
			}
		} else {
			$html[] = sprintf('<div class="'.$divcls.'"> <label class="col-sm-2 '.$lblcls.'">%s</label> <div class="col-sm-10"> <input type="text" name="%s[%s]" value="%s" placeholder="%s" class="form-control" /> </div> </div>', $entry, $name, $langid, $val, $entry); 	
		}			
		 
		return join($html);
	}
	
	public function getStores() {
		$result = array();
		$result[0] = array('store_id' => '0', 'name' => $this->config->get('config_name'));
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "store WHERE 1 ORDER BY store_id");
		if($query->num_rows) { 
			foreach($query->rows as $rs) { 
				$result[$rs['store_id']] = $rs;
			}
		}
		return $result;
	}
	public function getLang() {
 		$lang = array();
		$this->load->model('localisation/language');
  		$languages = $this->model_localisation_language->getLanguages();
		foreach($languages as $language) {
			if(substr(VERSION,0,3)>='3.0' || substr(VERSION,0,3)=='2.3' || substr(VERSION,0,3)=='2.2') {
				$imgsrc = "language/".$language['code']."/".$language['code'].".png";
			} else {
				$imgsrc = "view/image/flags/".$language['image'];
			}
			$lang[] = array("language_id" => $language['language_id'], "name" => $language['name'], "imgsrc" => $imgsrc);
		}
 		return $lang;
	}
	public function getCustomerGroups() {
 		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "customer_group_description WHERE language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY name");
 		return $query->rows;
	}
	public function getstoreid($name) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "store WHERE name LIKE '" . $this->db->escape($name) . "'");
 		return (!empty($query->row['store_id'])) ? $query->row['store_id'] : 0;
	}
	public function getcgid($name) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "customer_group_description WHERE name LIKE '" . $this->db->escape(htmlentities($name)) . "'");
 		return (!empty($query->row['customer_group_id'])) ? $query->row['customer_group_id'] : 0;
	}
}