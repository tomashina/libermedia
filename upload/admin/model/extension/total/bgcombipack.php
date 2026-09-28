<?php
class ModelExtensionTotalbgcombipack extends Model {
	private $error = array();
	private $modpath = 'total/bgcombipack'; 
	private $modvar = 'model_total_bgcombipack';
	private $modtpl = 'total/bgcombipack.tpl';
	private $modname = 'bgcombipack';
	private $evntcode = 'bgcombipack';
 	private $modurl = 'extension/total';
	private $token = '';

	public function __construct($registry) {		
		parent::__construct($registry);		
		ini_set("serialize_precision", -1);
				
		if(substr(VERSION,0,3)=='2.0') {
			if(isset($this->session->data['token'])) { $this->token = 'token=' . $this->session->data['token'] . '&type=total'; }
		}
		if(substr(VERSION,0,3)=='2.1') {
			if(isset($this->session->data['token'])) { $this->token = 'token=' . $this->session->data['token'] . '&type=total'; }
		}		
		if(substr(VERSION,0,3)=='2.2') {
			$this->modtpl = 'total/bgcombipack';
			if(isset($this->session->data['token'])) { $this->token = 'token=' . $this->session->data['token'] . '&type=total'; }
		}
		if(substr(VERSION,0,3)=='2.3') {
			$this->modpath = 'extension/total/bgcombipack';
			$this->modvar = 'model_extension_total_bgcombipack';
			$this->modtpl = 'extension/total/bgcombipack';			
			$this->modurl = 'extension/extension';
			if(isset($this->session->data['token'])) { $this->token = 'token=' . $this->session->data['token'] . '&type=total'; }
		}
		if(substr(VERSION,0,3)=='3.0') {			
			$this->modpath = 'extension/total/bgcombipack';
			$this->modvar = 'model_extension_total_bgcombipack';
			$this->modtpl = 'extension/total/bgcombipack30X';
			$this->modname = 'total_bgcombipack';
			$this->modurl = 'marketplace/extension'; 
			if(isset($this->session->data['user_token'])) { $this->token = 'user_token=' . $this->session->data['user_token'] . '&type=total'; }
		} 
		if(substr(VERSION,0,3)=='4.0') {
			$this->modpath = 'extension/bgcombipack/total/bgcombipack';
			$this->modvar = 'model_extension_bgcombipack_total_bgcombipack';
			$this->modtpl = 'extension/bgcombipack/total/bgcombipack40X';			
			$this->modname = 'total_bgcombipack';
			$this->modurl = 'marketplace/extension'; 
			if(isset($this->session->data['user_token'])) { $this->token = 'user_token=' . $this->session->data['user_token'] . '&type=total'; }
		}
 	}
	public function get_InpTxt_html($name, $val, $divcls, $lblcls, $entry, $help = '') {
		return sprintf('<div class="'.$divcls.'"> <label class="col-sm-2 '.$lblcls.'">%s</label><div class="col-sm-10"> <input type="text" name="%s" value="%s" class="form-control"/> %s </div> </div>', $entry, $name, $val, $help);
	}
	public function get_YESNORDO_html($name, $val, $divcls, $lblcls, $entry, $txtyes, $text_no) {
		$sel1 = $val == 1 ? 'checked="checked"' : '';
		$sel2 = $val == 0 ? 'checked="checked"' : '';
		return sprintf('<div class="'.$divcls.'"> <label class="col-sm-2 '.$lblcls.'">%s</label><div class="col-sm-10"> <label class="radio-inline"> <input type="radio" name="%s" value="1" %s/> %s </label> <label class="radio-inline"> <input type="radio" name="%s" value="0" %s/> %s </label> </div> </div>', $entry, $name, $sel1, $txtyes, $name, $sel2, $text_no);		
	}
	public function save() {
		$this->load->language($this->modpath);

		$json = array();

		if (!$this->user->hasPermission('modify', $this->modpath)) {
			$json['error'] = $this->language->get('error_permission');
		}

		if (!$json) {
			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting($this->modname, $this->request->post);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	
	public function install() {
		$query = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "bgcombipack' ");
		if($query->num_rows == 0) {
			$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "bgcombipack` (
				  `bgcombipack_id` int(11) NOT NULL AUTO_INCREMENT,
				  `title` text NOT NULL,
				  `ribbontext` text NOT NULL,
				  `ordtotaltext` text NOT NULL,
   				  `status` tinyint(1) NOT NULL,
				  `disctype` tinyint(1) NOT NULL,
				  `discount` decimal(10,2) NOT NULL,
				  `buyqty` int(5) NOT NULL,
				  `getqty` int(5) NOT NULL,
				  
				  `startdate` date,
				  `enddate` date,
				  `customer_group` text,
				  `store` text,	
				  
				  `showofferat` tinyint(1) NOT NULL,
				  `offer_heading` text,
				  `offer_content` text,
				  			  
 				  `buyproduct` text,
				  `buycategory` text,
				  `buymanufacturer` text,
				  `exbuyproduct` text,
				  `exbuycategory` text,
				  `exbuymanufacturer` text,
				  
				  `getproduct` text,
				  `getcategory` text,
				  `getmanufacturer` text,
				  `exgetproduct` text,
				  `exgetcategory` text,
				  `exgetmanufacturer` text,
				  
				  `date_added` datetime,
				  				  
   				  PRIMARY KEY (`bgcombipack_id`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
			");
 		}	
		
		$viewtmp = '';
 		if(substr(VERSION,0,3)=='2.2' || substr(VERSION,0,3)=='2.3') {
			$viewtmp = '*/template/';
		}
		
		$seprtor = '/';
		if(substr(VERSION,0,5) == '4.0.0' || substr(VERSION,0,5) == '4.0.1') {
			$seprtor = '|';
		} 
		if(substr(VERSION,0,5) >= '4.0.2') {
			$seprtor = '.';
		}
		
		if(substr(VERSION,0,3)=='2.2') {
			$this->addtoevent('admin/view/common/menu/before', $seprtor. 'addmenu');
		} else {
			$this->addtoevent('admin/view/common/column_left/before', $seprtor. 'addmenu');
		}
		$this->addtoevent('catalog/controller/common/header/before', $seprtor. 'loadjscss');
 	}
	public function uninstall() {
		if(substr(VERSION,0,3)=='2.2') {
			$this->load->model('extension/event');
			$this->model_extension_event->deleteEvent($this->evntcode);
		}
		if(substr(VERSION,0,3)=='2.3') {
			$this->load->model('extension/event');
			$this->model_extension_event->deleteEvent($this->evntcode);
		}
		if(substr(VERSION,0,3)=='3.0') {			
			$this->load->model('setting/event');
			$this->model_setting_event->deleteEventByCode($this->evntcode);
		} 
		if(substr(VERSION,0,3)=='4.0') {
			$this->load->model('setting/event');
			$this->model_setting_event->deleteEventByCode($this->evntcode);
		}
	}
	public function addtoevent($taregt, $func) {
		if(stristr($taregt, '/before') && substr(VERSION,0,3)!='2.2') {
			$taregt = str_replace('*/template/','',$taregt);
		}
		
		if(substr(VERSION,0,3)=='2.2') {
			$this->load->model('extension/event');
			$this->model_extension_event->addEvent($this->evntcode, $taregt, $this->modpath. $func);
		}
		if(substr(VERSION,0,3)=='2.3') {
			$this->load->model('extension/event');
			$this->model_extension_event->addEvent($this->evntcode, $taregt, $this->modpath. $func);
		}
		if(substr(VERSION,0,3)=='3.0') {		
			$this->load->model('setting/event');	
			$this->model_setting_event->addEvent($this->evntcode, $taregt, $this->modpath. $func);
		}
		if(substr(VERSION,0,3)=='4.0') {
			$this->load->model('setting/event');
			$comval = array('code'=> $this->evntcode, 'description' => '', 'status'=>1, 'sort_order'=>1);
			$this->model_setting_event->addEvent(array_merge($comval, array('trigger' => $taregt, 'action' => $this->modpath. $func)));
		}
	}
	public function loadjscss() {
		if($this->config->get($this->modname.'_status')) {
			if(substr(VERSION,0,3)=='4.0') {
				$this->document->addScript('../extension/bgcombipack/admin/view/javascript/bgcombipack.js?vr='.rand());
				$this->document->addStyle('../extension/bgcombipack/admin/view/javascript/bgcombipack.css?vr='.rand());
			} else { 
				$this->document->addScript('view/javascript/bgcombipack.js?vr='.rand());
				$this->document->addStyle('view/javascript/bgcombipack.css?vr='.rand());
			}
		}			
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
	public function getCustomerGroups() {
 		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "customer_group_description WHERE language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY name");
 		return $query->rows;
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
}