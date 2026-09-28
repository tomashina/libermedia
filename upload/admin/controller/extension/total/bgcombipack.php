<?php
class ControllerExtensionTotalbgcombipack extends Controller {
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
	
	public function index() {
		$lang = $this->load->language($this->modpath); 		
		$data = $this->load->language($this->modpath);
		
		$this->load->model($this->modpath);
		$data['langs'] = $this->{$this->modvar}->getLang();
		$data['stores'] = $this->{$this->modvar}->getStores();
		$data['cgs'] = $this->{$this->modvar}->getCustomerGroups();

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate() && substr(VERSION,0,3)!='4.0') {
			$this->model_setting_setting->editSetting($this->modname, $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link($this->modpath, $this->token, true));
		}
 
		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}
		
		if (isset($this->session->data['success'])) {
			$data['text_success'] = $this->session->data['success'];
			unset($this->session->data['success']);
		} else {
			$data['text_success'] = '';
		}

		$data['breadcrumbs'] = array();
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link($this->modpath, $this->token, true)
		);
		
		$data['action'] = $this->url->link($this->modpath, $this->token, true);
		$data['cancel'] = $this->url->link($this->modurl, $this->token , true); 
		
		if(substr(VERSION,0,5) == '4.0.0' || substr(VERSION,0,5) == '4.0.1') {
			$data['action'] = $this->url->link($this->modpath.'|save', $this->token);
			$data['cancel'] = $this->url->link($this->modurl, $this->token);
		} 
		if(substr(VERSION,0,5) >= '4.0.2') {
			$data['action'] = $this->url->link($this->modpath.'.save', $this->token);
			$data['cancel'] = $this->url->link($this->modurl, $this->token);
		}
		
		if(substr(VERSION,0,3)>='3.0') { 
			$data['user_token'] = $this->session->data['user_token'];
		} else {
			$data['token'] = $this->session->data['token'];
		}
		
		$html = array();
		
		$divcls = substr(VERSION,0,3)>='4.0' ? 'row mb-3' : 'form-group';
		$lblcls = substr(VERSION,0,3)>='4.0' ? 'col-form-label' : 'control-label';
		$wellcls = substr(VERSION,0,3)>='4.0' ? 'form-control' : 'well well-sm';
		$grpcls = substr(VERSION,0,3)>='4.0' ? 'input-group-text' : 'input-group-addon';
		 
		$data[$this->modname.'_status'] = $this->setvalue($this->modname.'_status');	
		$data[$this->modname.'_sort_order'] = $this->setvalue($this->modname.'_sort_order');
		
		$name = sprintf($this->modname.'_sort_order');
		$val = $data[$this->modname.'_sort_order'];
		$html[] = $this->{$this->modvar}->get_InpTxt_html($name, $val, $divcls, $lblcls, $lang['entry_sort_order'], '');
		
		$data['fields_html'] = join($html);
		
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		$this->response->setOutput($this->load->view($this->modtpl, $data));
	}	
	public function save() {
		$this->load->model($this->modpath);
		$this->{$this->modvar}->save();
	}	
	public function install() {
		$this->load->model($this->modpath);
		$this->{$this->modvar}->install();
	}
	public function uninstall() {
		$this->load->model($this->modpath);
		$this->{$this->modvar}->uninstall();
	}
	// events 
	public function addmenu(&$route, &$data, &$output = '') {
		$menulink = 'extension/bgcombipack';
		if(substr(VERSION,0,3)=='4.0') {
			$menulink = 'extension/bgcombipack/extension/bgcombipack';
		}
		if ($this->user->hasPermission('access',  $menulink)) {
			if(substr(VERSION,0,3)=='2.2') {
				$bgcombipack = $this->url->link($menulink, $this->token, 'SSL');	
				$data['text_module'] .= sprintf('</a></li> <li><a href="%s">%s',$bgcombipack, 'BOGO');
			} else{				
				foreach ($data['menus'] as &$menu) {
					if (stristr($menu['id'],'extension')) {
						$menu['children'][] = array(
							'name'     => 'BOGO',
							'href'     => $this->url->link($menulink, $this->token, true),
							'children' => array()
						);
					}
				}
			}
		}
	}
	public function loadjscss(&$route, &$data, &$output = '') {
		$this->load->model($this->modpath);
		$this->{$this->modvar}->loadjscss();
	}
	protected function setvalue($postfield) {
		if (isset($this->request->post[$postfield])) {
			return $this->request->post[$postfield];
		} else {
			return $this->config->get($postfield);
		} 	
	}
	protected function validate() {
		if (!$this->user->hasPermission('modify', $this->modpath)) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
		return !$this->error;
	}
}