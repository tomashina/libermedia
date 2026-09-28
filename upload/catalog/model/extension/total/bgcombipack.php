<?php
class ModelExtensionTotalbgcombipack extends Model {
	private $error = array();
	private $modpath = 'total/bgcombipack'; 
	private $modvar = 'model_total_bgcombipack';
	private $modtpl = 'total/bgcombipack.tpl';
	private $modname = 'bgcombipack';
	private $evntcode = 'bgcombipack';
 	private $modurl = 'extension/total';
	private $status = false;
	private $setting = array();
	private $langid = 0;
	private $storeid = 0;
	private $cgid = 0;

	public function __construct($registry) {		
		parent::__construct($registry);		
		ini_set("serialize_precision", -1);
		
		$this->langid = (int)$this->config->get('config_language_id');
		$this->storeid = (int)$this->config->get('config_store_id');
		$this->cgid = (int)$this->config->get('config_customer_group_id');
		
		if(substr(VERSION,0,3)=='2.0') {
		}
		if(substr(VERSION,0,3)=='2.1') {
		}		
		if(substr(VERSION,0,3)=='2.2') {
			$this->modtpl = 'total/bgcombipack';
		}
		if(substr(VERSION,0,3)=='2.3') {
			$this->modpath = 'extension/total/bgcombipack';
			$this->modvar = 'model_extension_total_bgcombipack';
			$this->modtpl = 'extension/total/bgcombipack';			
			$this->modurl = 'extension/extension';
		}
		if(substr(VERSION,0,3)=='3.0') {			
			$this->modpath = 'extension/total/bgcombipack';
			$this->modvar = 'model_extension_total_bgcombipack';
			$this->modtpl = 'extension/total/bgcombipack30X';
			$this->modname = 'total_bgcombipack';
			$this->modurl = 'marketplace/extension'; 
		} 
		if(substr(VERSION,0,3)=='4.0') {
			$this->modpath = 'extension/bgcombipack/total/bgcombipack';
			$this->modvar = 'model_extension_bgcombipack_total_bgcombipack';
			$this->modtpl = 'extension/bgcombipack/total/bgcombipack40X';			
			$this->modname = 'total_bgcombipack';
			$this->modurl = 'marketplace/extension'; 
		}
		
		//$this->setting = $this->getSetting();
		//$this->status = ($this->config->get($this->modname.'_status') && $this->setting['status']) ? true : false;	
		$this->status = $this->config->get($this->modname.'_status');
 	}
	
	//oc2.0 and 2.1 public function getTotal(&$total_data, &$total, &$taxes)
	//oc3.X, 2.3 and 2.2 public function getTotal($total)
	//oc4.X public function getTotal(array &$totals, array &$taxes, float &$total)

	public function getTotal($total) {	
		if ($this->cart->hasProducts()) {
  			
			$discdata = array();		
			 
 			$sub_total = $this->cart->getSubTotal();
			$buy_product = array();
			$get_product = array();	
			
  			foreach ($this->cart->getProducts() as $product) {
				$pid = $product['product_id'];
				
				$bgdata = $this->getdiscount($pid); 
 				if($bgdata) { 
					foreach($bgdata as $bgcombipack_id => $info) { 
						if(!empty($info)) {	
							if($info['buyflag']) { 
								$buy_product[$bgcombipack_id]['info'] = $info;
								for($x = 1; $x <= $product['quantity']; $x++) {
									$buy_product[$bgcombipack_id]['products'][] = $product;
									$buy_product[$bgcombipack_id]['product_id'][$pid] = $pid;
								}
							}
							if($info['getflag']) {
								$get_product[$bgcombipack_id]['info'] = $info;
								for($x = 1; $x <= $product['quantity']; $x++) {
									$get_product[$bgcombipack_id]['products'][] = $product;
									$get_product[$bgcombipack_id]['product_id'][$pid] = $pid;
								}
							}
						}
					}
				}
			}
			
			//echo "<pre>"; print_r($buy_product); print_r($get_product); exit;
			
			if($buy_product && $get_product) {
				foreach($get_product as $getproduct) {
					$info = $getproduct['info'];
					$bgcombipack_id = $info['bgcombipack_id'];
					if(isset($buy_product[$bgcombipack_id]) && isset($get_product[$bgcombipack_id])) {
						$checkeq = array_diff($buy_product[$bgcombipack_id]['product_id'], $get_product[$bgcombipack_id]['product_id']);
						//echo "<pre>"; print_r($checkeq); exit;
						if(! $checkeq) {
							$get_product[$bgcombipack_id]['info']['buyqty'] = $info['buyqty'] + $info['getqty'];
						}
					}
				}
				
				//echo "<pre>"; print_r($buy_product); print_r($get_product); exit;
				
				foreach($get_product as $getproduct) {
					$info = $getproduct['info'];
					$bgcombipack_id = $info['bgcombipack_id'];
					
					if(isset($buy_product[$bgcombipack_id]) && count($buy_product[$bgcombipack_id]['products']) >= (int)$info['buyqty'] && count($get_product[$bgcombipack_id]['products']) >= (int)$info['getqty']) {
						usort($getproduct['products'], array($this, "sortByPrice"));
						
						$getfreeqty = floor((count($buy_product[$bgcombipack_id]['products']) / $info['buyqty']) * $info['getqty']);
						
						for($i = 0; $i < min($getfreeqty, count($getproduct['products'])); $i++) {				
							$discount = 0;
							
							$product = $getproduct['products'][$i];
							
							//$product['price'] = $this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax'));
							
							$freeqty = 1;
							
 							if($info['disctype'] == 0) { // free
								$discount = ($product['price'] * $freeqty);
							}
							if($info['disctype'] == 1) { // percentage
 								$discount = ($product['price'] / 100 * $info['discount']) * $freeqty;
							}
							if($info['disctype'] == 2) { // fixed amount
								$discount = ($info['discount'] * $freeqty);
							}
  							 
							if($discount) {
 								if($info['disctype'] != 2) {
									//$discount = $this->tax->calculate($discount, $product['tax_class_id'], $this->config->get('config_tax') ? 'P' : false);
									$discount = $this->tax->calculate($discount, $product['tax_class_id'], $this->config->get('config_tax'));
								}
								
								/*$discount = -$discount;	
								if (!empty($product['tax_class_id'])) {
									$tax_rates = $this->tax->getRates(abs($discount), $product['tax_class_id']);
									foreach ($tax_rates as $tax_rate) {
										if ($tax_rate['type'] == 'P') {
											if(substr(VERSION,0,3)=='3.0' || substr(VERSION,0,3)=='2.3' || substr(VERSION,0,3)=='2.2') {												
												$total['taxes'][$tax_rate['tax_rate_id']] -= abs($tax_rate['amount']);
											} else {												
												$taxes[$tax_rate['tax_rate_id']] -= abs($tax_rate['amount']);
											}
										}
									}
								}*/
 								
								$discdata[$bgcombipack_id]['discount'][] = -$discount;
								$discdata[$bgcombipack_id]['prodname'][] = $product['name'];
								$discdata[$bgcombipack_id]['ordtotaltext'] = $info['ordtotaltext'];
							}														
						}  						 
					}
				}
				
 				//echo "<pre>"; print_r($discdata); exit;
				if($discdata) {
					foreach($discdata as $bgcombipack_id => $fddata) {
						$ordtotaltext = sprintf($fddata['ordtotaltext'], implode(' + ',$fddata['prodname']));
						
						$discount = array_sum($fddata['discount']);
 						
						if ($discount > $total) {
							$discount = $total;
						}
						
						if(substr(VERSION,0,3)=='4.0') {
							$totals[] = array(
								'extension'  => 'bgcombipack',
								'code'       => 'bgcombipack',
								'title'      => $ordtotaltext,
								'value'      => $discount,
								'sort_order' => (int)$this->config->get($this->modname.'_sort_order')
							);
		
							$total -= abs($discount);
						} else if(substr(VERSION,0,3)=='3.0' || substr(VERSION,0,3)=='2.3' || substr(VERSION,0,3)=='2.2') {
							$total['totals'][] = array(
								'code'       => 'bgcombipack',
								'title'      => $ordtotaltext,
								'value'      => $discount,
								'sort_order' => $this->config->get($this->modname.'_sort_order'),
							);
							
							$total['total'] -= abs($discount);
						} else {
							$total['totals'][] = array(
								'code'       => 'bgcombipack',
								'title'      => $ordtotaltext,
								'value'      => $discount,
								'sort_order' => $this->config->get($this->modname.'_sort_order'),
							);
							
							$total['total'] -= abs($discount);
						}
					}
				}
			} 	 
		}
	}
	public function getcache() {						
		$json = false;
		if($this->status && !empty($this->request->post['product_ids'])) { 
			$pids = array_unique($this->request->post['product_ids']);
			if($pids) {	
				$rows = $this->getRSdata();
				if($rows) {
					foreach($rows as $rs) {
						foreach ($pids as $pid) {						
							$buyallflag = 0;
							if(empty($rs['buyproduct']) && empty($rs['buycategory']) && empty($rs['buymanufacturer'])) {
								$buyallflag = 1;
							}
							$getallflag = 0;
							if(empty($rs['getproduct']) && empty($rs['getcategory']) && empty($rs['getmanufacturer'])) {
								$getallflag = 1;
							}
							
							$json = $this->CheckJson($rs, $pid, $json);
													
							if(!empty($json[$pid][$rs['bgcombipack_id']]['products']) || !empty($json[$pid][$rs['bgcombipack_id']]['allflag'])) { 
								$ribbontext = json_decode($rs['ribbontext'], true);
								$json[$pid][$rs['bgcombipack_id']]['rib'] = html_entity_decode($ribbontext[$this->langid], ENT_QUOTES, 'UTF-8');
								
								$json[$pid][$rs['bgcombipack_id']]['showofferat'] = $rs['showofferat'];
							
								$offer_heading = json_decode($rs['offer_heading'], true);
								$json[$pid][$rs['bgcombipack_id']]['offer_heading'] = html_entity_decode($offer_heading[$this->langid], ENT_QUOTES, 'UTF-8');
								
								$offer_content = json_decode($rs['offer_content'], true);
								$json[$pid][$rs['bgcombipack_id']]['offer_content'] = html_entity_decode($offer_content[$this->langid], ENT_QUOTES, 'UTF-8');
							}
						}
					}										 
				}
			}
		}
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json,true));
	}
	public function getdiscount($pid) { 	
		$discount = array();
		
		$rows = $this->getRSdata();
		if($rows) {
			foreach($rows as $rs) {
				$flagarray = $this->checkvalidation($rs, $pid);
 				if($flagarray['buyflag'] == true || $flagarray['getflag'] == true) {
					$rs['buyflag'] = $flagarray['buyflag'];
					$rs['getflag'] = $flagarray['getflag'];
					$ordtotaltext = json_decode($rs['ordtotaltext'], true);
					$rs['ordtotaltext'] = $ordtotaltext[$this->langid];					
 					$discount[$rs['bgcombipack_id']] = $rs;
				}
			}
			if(!empty($discount)) { 
				return $discount;
			}
		}
	}
	
	// Helpers
	private function sortByPrice($a, $b) {
		return $a['price'] - $b['price'];
	}
	public function getRSdata() {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "bgcombipack WHERE status = 1 and find_in_set(".$this->storeid.", store) and find_in_set(".$this->cgid.", customer_group) AND ((startdate = '0000-00-00' OR startdate <= curdate()) AND (enddate = '0000-00-00' OR enddate >= curdate()))");

		if($query->num_rows) {
			return $query->rows;
		} 
		
		return false;
	}
	public function CheckJson($rs, $pid, $json) { 		
		$buyallflag = 0;
		if(empty($rs['buyproduct']) && empty($rs['buycategory']) && empty($rs['buymanufacturer'])) {
			$buyallflag = 1;
		}
		$getallflag = 0;
		if(empty($rs['getproduct']) && empty($rs['getcategory']) && empty($rs['getmanufacturer'])) {
			$getallflag = 1;
		}
		
		// buy
		if($buyallflag == 1) {
			$json[$pid][$rs['bgcombipack_id']]['allflag'] = 1;
		} else { 							
			if(! empty($rs['buyproduct'])) {
				$json = $this->get_prod_jsondata($rs['buyproduct'], 0, $pid, $rs, $json);
			}
			if(! empty($rs['buycategory']) && $rs['buycategory']) {
				$json = $this->get_cat_jsondata($rs['buycategory'], 0, $pid, $rs, $json);
			}
			if(! empty($rs['buymanufacturer']) && $rs['buymanufacturer']) {
				$json = $this->get_man_jsondata($rs['buymanufacturer'], 0, $pid, $rs, $json);
			}
		}
		
		// get
		if($getallflag == 1) {
			//$json[$pid][$rs['bgcombipack_id']]['allflag'] = 1;
		} else {
			//$json[$pid][$rs['bgcombipack_id']]['allflag'] = 0;
			if(! empty($rs['getproduct'])) {
				$json = $this->get_prod_jsondata($rs['getproduct'], 0, $pid, $rs, $json);
			}
			if(! empty($rs['getcategory']) && $rs['getcategory']) {
				$json = $this->get_cat_jsondata($rs['getcategory'], 0, $pid, $rs, $json);
			}
			if(! empty($rs['getmanufacturer']) && $rs['getmanufacturer']) {
				$json = $this->get_man_jsondata($rs['getmanufacturer'], 0, $pid, $rs, $json);
			}
		}
		
		// lets check product exclude condition
		// buy
		if(empty($rs['exbuyproduct']) && empty($rs['exbuycategory']) && empty($rs['exbuymanufacturer'])) {
			// Do nothing 				 
		} else {
			if(! empty($rs['exbuyproduct'])) {
				$json = $this->get_prod_jsondata($rs['exbuyproduct'], 1, $pid, $rs, $json);
			}
			if(! empty($rs['exbuycategory']) && $rs['exbuycategory']) {
				$json = $this->get_cat_jsondata($rs['exbuycategory'], 1, $pid, $rs, $json);
			}
			if(! empty($rs['exbuymanufacturer']) && $rs['exbuymanufacturer']) {
				$json = $this->get_man_jsondata($rs['exbuymanufacturer'], 1, $pid, $rs, $json);
			}
		} 
		
		// get
		if(empty($rs['exgetproduct']) && empty($rs['exgetcategory']) && empty($rs['exgetmanufacturer'])) {
			// Do nothing 				 
		} else {
			if(! empty($rs['exgetproduct'])) {
				$json = $this->get_prod_jsondata($rs['exgetproduct'], 1, $pid, $rs, $json);
			}
			if(! empty($rs['exgetcategory']) && $rs['exgetcategory']) {
				$json = $this->get_cat_jsondata($rs['exgetcategory'], 1, $pid, $rs, $json);
			}
			if(! empty($rs['exgetmanufacturer']) && $rs['exgetmanufacturer']) {
				$json = $this->get_man_jsondata($rs['exgetmanufacturer'], 1, $pid, $rs, $json);
			}
		}
		
		return $json;
	}
	public function checkvalidation($rs, $pid) { 		
		$buyflag = false;
		if(empty($rs['buyproduct']) && empty($rs['buycategory']) && empty($rs['buymanufacturer'])) {
			$buyflag = true;
		} else {
			if(! empty($rs['buyproduct'])) {
				$buyflag = $this->get_prod_flag($rs['buyproduct'], 0, $pid, $rs, $buyflag);
			}
			if(! empty($rs['buycategory']) && $rs['buycategory']) {
				$buyflag = $this->get_cat_flag($rs['buycategory'], 0, $pid, $rs, $buyflag);
			}
			if(! empty($rs['buymanufacturer']) && $rs['buymanufacturer']) {
				$buyflag = $this->get_man_flag($rs['buymanufacturer'], 0, $pid, $rs, $buyflag);
			}
 		}
 		
		// lets check product exclude condition
		if(empty($rs['exbuyproduct']) && empty($rs['exbuycategory']) && empty($rs['exbuymanufacturer'])) {
			// Do nothing						 
		} else {
			if(! empty($rs['exbuyproduct'])) {
				$buyflag = $this->get_prod_flag($rs['exbuyproduct'], 1, $pid, $rs, $buyflag);
			}
			if(! empty($rs['exbuycategory']) && $rs['exbuycategory']) {
				$buyflag = $this->get_cat_flag($rs['exbuycategory'], 1, $pid, $rs, $buyflag);
			}
			if(! empty($rs['exbuymanufacturer']) && $rs['exbuymanufacturer']) {
				$buyflag = $this->get_man_flag($rs['exbuymanufacturer'], 1, $pid, $rs, $buyflag);
			}
  		}
		
		// get
		$getflag = false;
		if(empty($rs['getproduct']) && empty($rs['getcategory']) && empty($rs['getmanufacturer'])) {
			$getflag = true;
		} else {
			if(! empty($rs['getproduct'])) {
				$getflag = $this->get_prod_flag($rs['getproduct'], 0, $pid, $rs, $getflag);
			}
			if(! empty($rs['getcategory']) && $rs['getcategory']) {
				$getflag = $this->get_cat_flag($rs['getcategory'], 0, $pid, $rs, $getflag);
			}
			if(! empty($rs['getmanufacturer']) && $rs['getmanufacturer']) {
				$getflag = $this->get_man_flag($rs['getmanufacturer'], 0, $pid, $rs, $getflag);
			}
 		}
		
		// lets check product exclude condition
		if(empty($rs['exgetproduct']) && empty($rs['exgetcategory']) && empty($rs['exgetmanufacturer'])) {
			// Do nothing						 
		} else {
			if(! empty($rs['exgetproduct'])) {
				$getflag = $this->get_prod_flag($rs['exgetproduct'], 1, $pid, $rs, $getflag);
			}
			if(! empty($rs['exgetcategory']) && $rs['exgetcategory']) {
				$getflag = $this->get_cat_flag($rs['exgetcategory'], 1, $pid, $rs, $getflag);
			}
			if(! empty($rs['exgetmanufacturer']) && $rs['exgetmanufacturer']) {
				$getflag = $this->get_man_flag($rs['exgetmanufacturer'], 1, $pid, $rs, $getflag);
			}
  		}
		// echo "buygetflag = $pid = "; print_r(array('buyflag'=> $buyflag, 'getflag'=> $getflag));
		
		return array('buyflag'=> $buyflag, 'getflag'=> $getflag);
	}
	public function get_prod_jsondata($target, $isex, $pid, $rs, $json) {		
		$target_ids = explode(",",$target);
		foreach($target_ids as $id) {
			if($id == $pid) {
				if($isex == 1) {
					unset($json[$id][$rs['bgcombipack_id']]);
				} else {
					$json[$id][$rs['bgcombipack_id']]['products'] = $id;
				}
			}
		}
		return $json;
	}
	public function get_cat_jsondata($target, $isex, $pid, $rs, $json) {		
		$target_ids = explode(",",$target);
		foreach($target_ids as $id) { 
			$subq = $this->db->query("SELECT GROUP_CONCAT(category_id) as subcatsid FROM " . DB_PREFIX . "category_path WHERE path_id = '".(int)$id."' ");
			if(!empty($subq->row['subcatsid'])) {
				$catq = $this->db->query("SELECT product_id FROM " . DB_PREFIX . "product_to_category WHERE category_id in (".$subq->row['subcatsid'].") AND product_id = ".(int)$pid);
				if($catq->num_rows) {
					foreach($catq->rows as $catrs) { 
						if($isex == 1) {
							unset($json[$catrs['product_id']][$rs['bgcombipack_id']]);
						} else {
							$json[$catrs['product_id']][$rs['bgcombipack_id']]['products'] = $catrs['product_id'];
						}
					}
				}
			}
		}
		return $json;
	}
	public function get_man_jsondata($target, $isex, $pid, $rs, $json) {		
		$target_ids = explode(",",$target);
		foreach($target_ids as $id) {
			$manq = $this->db->query("SELECT product_id FROM " . DB_PREFIX . "product WHERE manufacturer_id = '".(int)$id."' AND product_id = ".(int)$pid);
			if($manq->num_rows) {
				foreach($manq->rows as $manrs) {
					if($isex == 1) {
						unset($json[$manrs['product_id']][$rs['bgcombipack_id']]);
					} else {
						$json[$manrs['product_id']][$rs['bgcombipack_id']]['products'] = $manrs['product_id'];
					}
				}
			}
		}
		return $json;
	}
	public function get_prod_flag($target, $isex, $pid, $rs, $buyflag) {		
		$target_ids = explode(",",$target);
		foreach($target_ids as $id) {
			if($id == $pid) {
				if($isex == 1) {
					$buyflag = false;
				} else {
					$buyflag = true;
				}
			}
		}
		return $buyflag;
	}
	public function get_cat_flag($target, $isex, $pid, $rs, $buyflag) {		
		$target_ids = explode(",",$target);
		foreach($target_ids as $id) { 
			$subq = $this->db->query("SELECT GROUP_CONCAT(category_id) as subcatsid FROM " . DB_PREFIX . "category_path WHERE path_id = '".(int)$id."' ");
			if(!empty($subq->row['subcatsid'])) {
				$catq = $this->db->query("SELECT product_id FROM " . DB_PREFIX . "product_to_category WHERE category_id in (".$subq->row['subcatsid'].") AND product_id = ".(int)$pid);
				if($catq->num_rows) {
					if($isex == 1) {
						$buyflag = false; break;
					} else {
						$buyflag = true; break;
					}
				}
			}
		}
		return $buyflag;
	}
	public function get_man_flag($target, $isex, $pid, $rs, $buyflag) {		
		$target_ids = explode(",",$target);
		foreach($target_ids as $id) {
			$manq = $this->db->query("SELECT product_id FROM " . DB_PREFIX . "product WHERE manufacturer_id = '".(int)$id."' AND product_id = ".(int)$pid);
			if($manq->num_rows) {
				if($isex == 1) {
					$buyflag = false; break;
				} else {
					$buyflag = true; break;
				}
			}
		}
		return $buyflag;
	}
	
	public function getSetting() {		
		$storeid = $this->config->get('config_store_id');
		
		$setting = $this->config->get($this->modname.'_setting');		
		
		$setting['status'] = (!isset($setting[$storeid]['status'])) ? false : $setting[$storeid]['status'];
 		
		return $setting;		
	}
	public function loadjscss() {
		if($this->status) {
			if(substr(VERSION,0,3)=='4.0') {
				$this->document->addScript('extension/bgcombipack/catalog/view/javascript/bgcombipack.js?vr='.rand());
				$this->document->addStyle('extension/bgcombipack/catalog/view/javascript/bgcombipack.css?vr='.rand());
			} else { 
				$this->document->addScript('catalog/view/javascript/bgcombipack.js?vr='.rand());
				$this->document->addStyle('catalog/view/javascript/bgcombipack.css?vr='.rand());
			}
		}			
	}
}