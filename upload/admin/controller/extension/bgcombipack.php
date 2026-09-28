<?php
class ControllerExtensionbgcombipack extends Controller {
	private $error = array();  
	private $modsprtor = '/';
	private $modpath = 'extension/bgcombipack';
	private $modvar = 'model_extension_bgcombipack';
	private $modtpl_list = 'extension/bgcombipack_list.tpl';
	private $modtpl_form = 'extension/bgcombipack_form.tpl';	
	private $pagelimit = 50;
	private $modssl = '';
	private $token = '';
	private $urlval = '';
	 
	private $urlfilter = array('filter_title', 'filter_disctype', 'filter_discount', 'filter_buyqty', 'filter_getqty', 'filter_startdate', 'filter_enddate', 'filter_status', 'filter_customer_group_id', 'filter_store_id', 'filter_buyproduct_name', 'filter_buyproduct_id', 'filter_buycategory_name', 'filter_buycategory_id', 'filter_buymanufacturer_name', 'filter_buymanufacturer_id', 'filter_exbuyproduct_name', 'filter_exbuyproduct_id', 'filter_exbuycategory_name', 'filter_exbuycategory_id', 'filter_exbuymanufacturer_name', 'filter_exbuymanufacturer_id', 'filter_getproduct_name', 'filter_getproduct_id', 'filter_getcategory_name', 'filter_getcategory_id', 'filter_getmanufacturer_name', 'filter_getmanufacturer_id', 'filter_exgetproduct_name', 'filter_exgetproduct_id', 'filter_exgetcategory_name', 'filter_exgetcategory_id', 'filter_exgetmanufacturer_name', 'filter_exgetmanufacturer_id');
	
	public function __construct($registry) {
		parent::__construct($registry);
		ini_set("serialize_precision", -1);
				
		foreach($this->urlfilter as $urlval) {
			if (isset($this->request->get[$urlval])) {
				$this->urlval .= '&'.$urlval.'=' . urlencode(html_entity_decode($this->request->get[$urlval], ENT_QUOTES, 'UTF-8'));
			}			
		}
		
		if (isset($this->request->get['sort'])) {
			$this->urlval .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$this->urlval .= '&order=' . $this->request->get['order'];
		}

		if (isset($this->request->get['page'])) {
			$this->urlval .= '&page=' . $this->request->get['page'];
		}
				
		if(substr(VERSION,0,3)=='2.0') {
			$this->pagelimit = $this->config->get('config_limit_admin');
			$this->token = 'token=' . $this->session->data['token'];
			$this->modssl = 'SSL';			
		}
		if(substr(VERSION,0,3)=='2.1') {
			$this->pagelimit = $this->config->get('config_limit_admin');
			$this->token = 'token=' . $this->session->data['token'];	
			$this->modssl = 'SSL';		
		}		
		if(substr(VERSION,0,3)=='2.2') {
			$this->modtpl_list = 'extension/bgcombipack_list';
			$this->modtpl_form = 'extension/bgcombipack_form';
			$this->pagelimit = $this->config->get('config_limit_admin');
			$this->token = 'token=' . $this->session->data['token'];			
		}
		if(substr(VERSION,0,3)=='2.3') {
			$this->modtpl_list = 'extension/bgcombipack_list';
			$this->modtpl_form = 'extension/bgcombipack_form';
			$this->pagelimit = $this->config->get('config_limit_admin');
			$this->token = 'token=' . $this->session->data['token'];			
		}
		if(substr(VERSION,0,3)=='3.0') {			
			$this->modtpl_list = 'extension/bgcombipack_list30X';
			$this->modtpl_form = 'extension/bgcombipack_form30X';
			$this->pagelimit = $this->config->get('config_limit_admin');
			$this->token = 'user_token=' . $this->session->data['user_token'];			
		} 
		if(substr(VERSION,0,3)=='4.0') {
			$this->modsprtor = '.';
			$this->modpath = 'extension/bgcombipack/extension/bgcombipack';			
			$this->modvar = 'model_extension_bgcombipack_extension_bgcombipack';
			$this->modtpl_list = 'extension/bgcombipack/extension/bgcombipack_list40X';			
			$this->modtpl_form = 'extension/bgcombipack/extension/bgcombipack_form40X';			
			$this->pagelimit = $this->config->get('config_pagination_admin');
			$this->token = 'user_token=' . $this->session->data['user_token'];
		}
		
		if(substr(VERSION,0,3)=='3.0' || substr(VERSION,0,3)=='2.3' || substr(VERSION,0,3)=='2.2') { 
			$this->modssl = true;
		} 
 	} 

	public function index() {
		$data = $this->load->language($this->modpath);

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model($this->modpath);
 
		$this->getList();
	}

	public function add() {
		$data = $this->load->language($this->modpath);

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model($this->modpath);

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->{$this->modvar}->addbgcombipack($this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$url = $this->urlval;

			$this->response->redirect($this->url->link($this->modpath, $this->token . $url, $this->modssl));
		}

		$this->getForm();
	}

	public function edit() {
		$data = $this->load->language($this->modpath);

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model($this->modpath);

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->{$this->modvar}->editbgcombipack($this->request->get['bgcombipack_id'], $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$url = $this->urlval;

			$this->response->redirect($this->url->link($this->modpath, $this->token . $url, $this->modssl));
		}

		$this->getForm();
	}

	public function delete() {
		$data = $this->load->language($this->modpath);

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model($this->modpath);

		if (isset($this->request->post['selected']) && $this->validateDelete()) {
			foreach ($this->request->post['selected'] as $bgcombipack_id) {
				$this->{$this->modvar}->deletebgcombipack($bgcombipack_id);
			}

			$this->session->data['success'] = $this->language->get('text_success');

			$url = $this->urlval;

			$this->response->redirect($this->url->link($this->modpath, $this->token . $url, $this->modssl));
		}

		$this->getList();
	}

	protected function getList() {
		$data = $this->load->language($this->modpath);
		$lang = $this->load->language($this->modpath);						
		
		$divcls = substr(VERSION,0,3)>='4.0' ? 'mb-3' : 'form-group';
		$lblcls = substr(VERSION,0,3)>='4.0' ? 'col-form-label' : 'control-label';
		$wellcls = substr(VERSION,0,3)>='4.0' ? 'form-control' : 'well well-sm';
		$grpcls = substr(VERSION,0,3)>='4.0' ? 'input-group-text' : 'input-group-addon';			  		
		
		$filter_val = array();
 		foreach($this->urlfilter as $urlval) {
			$filter_val[$urlval] = isset($this->request->get[$urlval]) ? $this->request->get[$urlval] : null;			
		}
		
 		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = 'date_added';
		}

		if (isset($this->request->get['order'])) {
			$order = $this->request->get['order'];
		} else {
			$order = 'desc';
		}

		if (isset($this->request->get['page'])) {
			$page = $this->request->get['page'];
		} else {
			$page = 1;
		}

		$url = $this->urlval;

		if(substr(VERSION,0,3)=='3.0' || substr(VERSION,0,3)=='4.0') { 
			$data['user_token'] = $this->session->data['user_token'];
		} else {
			$data['token'] = $this->session->data['token'];
		}
		
		$data['breadcrumbs'] = array();
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', $this->token, $this->modssl)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link($this->modpath, $this->token . $url, $this->modssl)
		);
 
		$data['add'] = $this->url->link($this->modpath. $this->modsprtor . 'add', $this->token . $url, $this->modssl);
		$data['delete'] = $this->url->link($this->modpath. $this->modsprtor . 'delete', $this->token . $url, $this->modssl);

		$data['bgcombipacks'] = array();

		$filter_data = array();		
		foreach($this->urlfilter as $urlval) {
			$filter_data[$urlval] = isset($filter_val[$urlval]) ? $filter_val[$urlval] : null;
		}
		
		$filter_data['sort'] = $sort;
		$filter_data['order'] = $order;
		$filter_data['start'] = ($page - 1) * $this->pagelimit;
		$filter_data['limit'] = $this->pagelimit;
  
		$bgcombipack_total = $this->{$this->modvar}->getTotalbgcombipacks($filter_data);

		$results = $this->{$this->modvar}->getbgcombipacks($filter_data);
		
		$data['stores'] = $this->{$this->modvar}->getStores();

		$data['cgs'] = $this->{$this->modvar}->getCustomerGroups();
		
		$this->load->model('catalog/product');
		$this->load->model('catalog/category');
 		$this->load->model('catalog/manufacturer');
 		
		$this->load->model('tool/image');
		
		// Filter HTML
		$html = array();
		$html[] = sprintf('<td class="text-left">%s</td>', $lang['entry_title']);
		$html[] = sprintf('<td class="text-left">%s</td>', $lang['entry_disctype']);
		$html[] = sprintf('<td class="text-left">%s</td>', $lang['entry_discount']);
		$html[] = sprintf('<td class="text-left">%s</td>', $lang['entry_buyqty']);
		$html[] = sprintf('<td class="text-left">%s</td>', $lang['entry_getqty']);
		$html[] = sprintf('<td class="text-left">%s</td>', $lang['entry_startdate']);
		$html[] = sprintf('<td class="text-left">%s</td>', $lang['entry_enddate']);
		$html[] = sprintf('<td class="text-left">%s</td>', $lang['entry_customer_group']);
		$html[] = sprintf('<td class="text-left">%s</td>', $lang['entry_store']);
		
		$html[] = sprintf('<td class="text-left">%s</td>', $lang['entry_buyproduct']);
		$html[] = sprintf('<td class="text-left">%s</td>', $lang['entry_exbuyproduct']);
		$html[] = sprintf('<td class="text-left">%s</td>', $lang['entry_getproduct']);
		$html[] = sprintf('<td class="text-left">%s</td>', $lang['entry_exgetproduct']);
		
		$html[] = sprintf('<td class="text-left">%s</td>', $lang['entry_buycategory']);
		$html[] = sprintf('<td class="text-left">%s</td>', $lang['entry_exbuycategory']);
		$html[] = sprintf('<td class="text-left">%s</td>', $lang['entry_getcategory']);
		$html[] = sprintf('<td class="text-left">%s</td>', $lang['entry_exgetcategory']);
		
		$html[] = sprintf('<td class="text-left">%s</td>', $lang['entry_buymanufacturer']);
		$html[] = sprintf('<td class="text-left">%s</td>', $lang['entry_exbuymanufacturer']);
		$html[] = sprintf('<td class="text-left">%s</td>', $lang['entry_getmanufacturer']);
		$html[] = sprintf('<td class="text-left">%s</td>', $lang['entry_exgetmanufacturer']);
		
		$html[] = sprintf('<td class="text-left">%s</td>', $lang['entry_status']);
		
		$data['coltd_html'] = join($html);
  
		foreach ($results as $result) { 
			// buy
			$buyproduct_data = $this->{$this->modvar}->getprodcatman($result['buyproduct'], 'product');
			$buycategory_data = $this->{$this->modvar}->getprodcatman($result['buycategory'], 'category');
			$buymanufacturer_data = $this->{$this->modvar}->getprodcatman($result['buymanufacturer'], 'manufacturer');
			$exbuyproduct_data = $this->{$this->modvar}->getprodcatman($result['exbuyproduct'], 'product');
			$exbuycategory_data = $this->{$this->modvar}->getprodcatman($result['exbuycategory'], 'category');
			$exbuymanufacturer_data = $this->{$this->modvar}->getprodcatman($result['exbuymanufacturer'], 'manufacturer');
			
			$getproduct_data = $this->{$this->modvar}->getprodcatman($result['getproduct'], 'product');
			$getcategory_data = $this->{$this->modvar}->getprodcatman($result['getcategory'], 'category');
			$getmanufacturer_data = $this->{$this->modvar}->getprodcatman($result['getmanufacturer'], 'manufacturer');
			$exgetproduct_data = $this->{$this->modvar}->getprodcatman($result['exgetproduct'], 'product');
			$exgetcategory_data = $this->{$this->modvar}->getprodcatman($result['exgetcategory'], 'category');
			$exgetmanufacturer_data = $this->{$this->modvar}->getprodcatman($result['exgetmanufacturer'], 'manufacturer');
									 
			$customer_group_data = $this->{$this->modvar}->getcgstore($result['customer_group'], $data['cgs'], 'customer_group_id', 'name');
			$store_data = $this->{$this->modvar}->getcgstore($result['store'], $data['stores'], 'store_id', 'name');
  			 
			$title = json_decode($result['title'],true);
			
			$disctype = $data['text_free'];
			if($result['disctype'] == 1) {
				$disctype = $data['text_per'];
			} else if($result['disctype'] == 2) {
				$disctype = $data['text_fix'];
			}
			
			$html = array();
			$html[] = sprintf('<td class="text-left">%s</td>', $title[(int)$this->config->get('config_language_id')]);
			$html[] = sprintf('<td class="text-left">%s</td>', $disctype);
			$html[] = sprintf('<td class="text-left">%s</td>', ($result['disctype'] == 0) ? $data['text_free'] : $result['discount']);
			$html[] = sprintf('<td class="text-left">%s</td>', $result['buyqty']);
			$html[] = sprintf('<td class="text-left">%s</td>', $result['getqty']);
			$html[] = sprintf('<td class="text-left">%s</td>', $result['startdate'] != '0000-00-00' ? date($this->language->get('date_format_short'), strtotime($result['startdate'])) : '');
			$html[] = sprintf('<td class="text-left">%s</td>', $result['enddate'] != '0000-00-00' ? date($this->language->get('date_format_short'), strtotime($result['enddate'])) : '');
			$html[] = sprintf('<td class="text-left">%s</td>', implode("<br>",$customer_group_data));
			$html[] = sprintf('<td class="text-left">%s</td>', implode("<br>",$store_data));
			
			$html[] = sprintf('<td class="text-left">%s</td>', implode("<br>",$buyproduct_data));
			$html[] = sprintf('<td class="text-left">%s</td>', implode("<br>",$exbuyproduct_data));
			$html[] = sprintf('<td class="text-left">%s</td>', implode("<br>",$getproduct_data));
			$html[] = sprintf('<td class="text-left">%s</td>', implode("<br>",$exgetproduct_data));
			
			$html[] = sprintf('<td class="text-left">%s</td>', implode("<br>",$buycategory_data));
			$html[] = sprintf('<td class="text-left">%s</td>', implode("<br>",$exbuycategory_data));
			$html[] = sprintf('<td class="text-left">%s</td>', implode("<br>",$getcategory_data));
			$html[] = sprintf('<td class="text-left">%s</td>', implode("<br>",$exgetcategory_data));
			
			$html[] = sprintf('<td class="text-left">%s</td>', implode("<br>",$buymanufacturer_data));
			$html[] = sprintf('<td class="text-left">%s</td>', implode("<br>",$exbuymanufacturer_data));
			$html[] = sprintf('<td class="text-left">%s</td>', implode("<br>",$getmanufacturer_data));
			$html[] = sprintf('<td class="text-left">%s</td>', implode("<br>",$exgetmanufacturer_data));

			$html[] = sprintf('<td class="text-left">%s</td>', $result['status'] == 1 ? $data['text_enabled'] : $data['text_disabled']);
			
 			$data['bgcombipacks'][] = array(
				'bgcombipack_id' => $result['bgcombipack_id'],
				'coltd_val_html' => join($html),
				
 				'title' => $title[(int)$this->config->get('config_language_id')],
				'config_language_id' => (int)$this->config->get('config_language_id'),
				'status' => $result['status'] == 1 ? $data['text_enabled'] : $data['text_disabled'],				
				'disctype' => $disctype,
				'discount' => ($result['disctype'] == 0) ? $data['text_free'] : $result['discount'],
				'buyqty' => $result['buyqty'],
				'getqty' => $result['getqty'],
				'startdate' => $result['startdate'] != '0000-00-00' ? date($this->language->get('date_format_short'), strtotime($result['startdate'])) : '',
				'enddate' => $result['enddate'] != '0000-00-00' ? date($this->language->get('date_format_short'), strtotime($result['enddate'])) : '',				
				
				'customer_group_data' => implode("<br>",$customer_group_data),
				'store_data' => implode("<br>",$store_data),
   				
				'buyproduct_data' => implode("<br>",$buyproduct_data),
				'buycategory_data' => implode("<br>",$buycategory_data),
				'buymanufacturer_data' => implode("<br>",$buymanufacturer_data), 
				'exbuyproduct_data' => implode("<br>",$exbuyproduct_data),
				'exbuycategory_data' => implode("<br>",$exbuycategory_data),
				'exbuymanufacturer_data' => implode("<br>",$exbuymanufacturer_data), 
				
				'getproduct_data' => implode("<br>",$getproduct_data),
				'getcategory_data' => implode("<br>",$getcategory_data),
				'getmanufacturer_data' => implode("<br>",$getmanufacturer_data), 
				'exgetproduct_data' => implode("<br>",$exgetproduct_data),
				'exgetcategory_data' => implode("<br>",$exgetcategory_data),
				'exgetmanufacturer_data' => implode("<br>",$exgetmanufacturer_data), 
				
			'date_added' => $result['date_added'] != '0000-00-00' ? date($this->language->get('date_format_short'), strtotime($result['date_added'])) : '',
				
 				'edit' => $this->url->link($this->modpath. $this->modsprtor . 'edit', $this->token . '&bgcombipack_id=' . $result['bgcombipack_id'] . $url, $this->modssl),
				'delete' => $this->url->link($this->modpath. $this->modsprtor . 'delete', $this->token . '&bgcombipack_id=' . $result['bgcombipack_id'] . $url, $this->modssl)
			);
		}
		
		$data['heading_title'] = $this->language->get('heading_title');
 
		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];
 			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		if (isset($this->request->post['selected'])) {
			$data['selected'] = (array)$this->request->post['selected'];
		} else {
			$data['selected'] = array();
		}

		$url = $this->urlval;
		
		if ($order == 'ASC') {
			$url .= '&order=DESC';
		} else {
			$url .= '&order=ASC';
		}

  		$data['sort_status'] = $this->url->link($this->modpath, $this->token . '&sort=status' . $url, $this->modssl);
 
		$url = $this->urlval;
		
		if(substr(VERSION,0,3)=='4.0') {
			$data['pagination'] = $this->load->controller('common/pagination', array(
				'total' => $bgcombipack_total,
				'page'  => $page,
				'limit' => $this->pagelimit,
				'url'   => $this->url->link($this->modpath, $this->token . $url . '&page={page}', $this->modssl)
			));
		} else {
			$pagination = new Pagination();
			$pagination->total = $bgcombipack_total;
			$pagination->page = $page;
			$pagination->limit = $this->pagelimit;
			$pagination->url = $this->url->link($this->modpath, $this->token . $url . '&page={page}', $this->modssl);
			
			$data['pagination'] = $pagination->render();
		}	
		
		$data['results'] = sprintf($this->language->get('text_pagination'), ($bgcombipack_total) ? (($page - 1) * $this->pagelimit) + 1 : 0, ((($page - 1) * $this->pagelimit) > ($bgcombipack_total - $this->pagelimit)) ? $bgcombipack_total : ((($page - 1) * $this->pagelimit) + $this->pagelimit), $bgcombipack_total, ceil($bgcombipack_total / $this->pagelimit));	
		
		foreach($this->urlfilter as $urlval) {
			$data[$urlval] = isset($filter_val[$urlval]) ? $filter_val[$urlval] : null;
		}
		
		// Filter HTML
		$html = array();
		
		$html[] = $this->{$this->modvar}->get_InpTxt_FILTER_html('filter_title', $data['filter_title'], $divcls, $lblcls, $lang['entry_title']);
		$html[] = $this->{$this->modvar}->get_SELECT_FILTER_html('filter_status', $data['filter_status'], $divcls, $lblcls, $lang['entry_status'], array(1=>$lang['text_enabled'], 0=>$lang['text_disabled']));
		
		$html[] = $this->{$this->modvar}->get_SELECT_FILTER_html('filter_disctype', $data['filter_disctype'], $divcls, $lblcls, $lang['entry_disctype'], array($lang['text_free'], $lang['text_per'], $lang['text_fix']));
		$html[] = $this->{$this->modvar}->get_InpTxt_FILTER_html('filter_discount', $data['filter_discount'], $divcls, $lblcls, $lang['entry_discount']);
		$html[] = $this->{$this->modvar}->get_InpTxt_FILTER_html('filter_buyqty', $data['filter_buyqty'], $divcls, $lblcls, $lang['entry_buyqty']);
		$html[] = $this->{$this->modvar}->get_InpTxt_FILTER_html('filter_getqty', $data['filter_getqty'], $divcls, $lblcls, $lang['entry_getqty']);
		
		$html[] = $this->{$this->modvar}->get_InpTxt_DATE_FILTER_html('filter_startdate', $data['filter_startdate'], $divcls, $lblcls, $lang['entry_startdate']);
		$html[] = $this->{$this->modvar}->get_InpTxt_DATE_FILTER_html('filter_enddate', $data['filter_enddate'], $divcls, $lblcls, $lang['entry_enddate']);
 		
		
		$html[] = $this->{$this->modvar}->get_SELECT_CGSTORE_FILTER_html('filter_customer_group_id', $data['filter_customer_group_id'], $data['cgs'], 'customer_group_id', 'name', $divcls, $lblcls, $lang['entry_customer_group']);
		$html[] = $this->{$this->modvar}->get_SELECT_CGSTORE_FILTER_html('filter_store_id', $data['filter_store_id'], $data['stores'], 'store_id', 'name', $divcls, $lblcls, $lang['entry_store']);

		//Products
		$html[] = $this->{$this->modvar}->get_FILTER_pcm_html('filter_buyproduct_name', $data['filter_buyproduct_name'], 'filter_buyproduct_id', $data['filter_buyproduct_id'], $divcls, $lblcls, $lang['entry_buyproduct']);
		$html[] = $this->{$this->modvar}->get_FILTER_pcm_html('filter_exbuyproduct_name', $data['filter_exbuyproduct_name'], 'filter_exbuyproduct_id', $data['filter_exbuyproduct_id'], $divcls, $lblcls, $lang['entry_exbuyproduct']);
		$html[] = $this->{$this->modvar}->get_FILTER_pcm_html('filter_getproduct_name', $data['filter_getproduct_name'], 'filter_getproduct_id', $data['filter_getproduct_id'], $divcls, $lblcls, $lang['entry_getproduct']);
		$html[] = $this->{$this->modvar}->get_FILTER_pcm_html('filter_exgetproduct_name', $data['filter_exgetproduct_name'], 'filter_exgetproduct_id', $data['filter_exgetproduct_id'], $divcls, $lblcls, $lang['entry_exgetproduct']);
		
		//categorys
		$html[] = $this->{$this->modvar}->get_FILTER_pcm_html('filter_buycategory_name', $data['filter_buycategory_name'], 'filter_buycategory_id', $data['filter_buycategory_id'], $divcls, $lblcls, $lang['entry_buycategory']);
		$html[] = $this->{$this->modvar}->get_FILTER_pcm_html('filter_exbuycategory_name', $data['filter_exbuycategory_name'], 'filter_exbuycategory_id', $data['filter_exbuycategory_id'], $divcls, $lblcls, $lang['entry_exbuycategory']);
		$html[] = $this->{$this->modvar}->get_FILTER_pcm_html('filter_getcategory_name', $data['filter_getcategory_name'], 'filter_getcategory_id', $data['filter_getcategory_id'], $divcls, $lblcls, $lang['entry_getcategory']);
		$html[] = $this->{$this->modvar}->get_FILTER_pcm_html('filter_exgetcategory_name', $data['filter_exgetcategory_name'], 'filter_exgetcategory_id', $data['filter_exgetcategory_id'], $divcls, $lblcls, $lang['entry_exgetcategory']);
		
		//manufacturers
		$html[] = $this->{$this->modvar}->get_FILTER_pcm_html('filter_buymanufacturer_name', $data['filter_buymanufacturer_name'], 'filter_buymanufacturer_id', $data['filter_buymanufacturer_id'], $divcls, $lblcls, $lang['entry_buymanufacturer']);
		$html[] = $this->{$this->modvar}->get_FILTER_pcm_html('filter_exbuymanufacturer_name', $data['filter_exbuymanufacturer_name'], 'filter_exbuymanufacturer_id', $data['filter_exbuymanufacturer_id'], $divcls, $lblcls, $lang['entry_exbuymanufacturer']);
		$html[] = $this->{$this->modvar}->get_FILTER_pcm_html('filter_getmanufacturer_name', $data['filter_getmanufacturer_name'], 'filter_getmanufacturer_id', $data['filter_getmanufacturer_id'], $divcls, $lblcls, $lang['entry_getmanufacturer']);
		$html[] = $this->{$this->modvar}->get_FILTER_pcm_html('filter_exgetmanufacturer_name', $data['filter_exgetmanufacturer_name'], 'filter_exgetmanufacturer_id', $data['filter_exgetmanufacturer_id'], $divcls, $lblcls, $lang['entry_exgetmanufacturer']);
		
		$data['filter_html'] = join($html);
  		
		$data['sort'] = $sort;
		$data['order'] = $order;
		
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		$this->response->setOutput($this->load->view($this->modtpl_list, $data));
 	}

	protected function getForm() {
		$data = $this->load->language($this->modpath);
		$lang = $this->load->language($this->modpath);
 		
		$data['heading_title'] = $this->language->get('heading_title');

		$data['text_form'] = !isset($this->request->get['bgcombipack_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');
		
  		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}
		
		$url = $this->urlval;
		
		
		$data['breadcrumbs'] = array();
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', $this->token, $this->modssl)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link($this->modpath, $this->token . $url, $this->modssl)
		); 

		if (!isset($this->request->get['bgcombipack_id'])) {
			$data['action'] = $this->url->link($this->modpath. $this->modsprtor . 'add', $this->token . $url, $this->modssl);
		} else {
			$data['action'] = $this->url->link($this->modpath. $this->modsprtor . 'edit', $this->token . '&bgcombipack_id=' . $this->request->get['bgcombipack_id'] . $url, $this->modssl);
		}

		$data['cancel'] = $this->url->link($this->modpath, $this->token . $url, $this->modssl);

		$rs_info = array();
		if (isset($this->request->get['bgcombipack_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
			$rs_info = $this->{$this->modvar}->getbgcombipack($this->request->get['bgcombipack_id']);
		}

 		if(substr(VERSION,0,3)=='3.0' || substr(VERSION,0,3)=='4.0') { 
			$data['user_token'] = $this->session->data['user_token'];
		} else {
			$data['token'] = $this->session->data['token'];
		}
		
		$html = array();
		
		$divcls = substr(VERSION,0,3)>='4.0' ? 'row mb-3' : 'form-group';
		$lblcls = substr(VERSION,0,3)>='4.0' ? 'col-form-label' : 'control-label';
		$wellcls = substr(VERSION,0,3)>='4.0' ? 'form-control' : 'well well-sm';
		$grpcls = substr(VERSION,0,3)>='4.0' ? 'input-group-text' : 'input-group-addon';

		$data['languages'] = $this->{$this->modvar}->getLang();
		 
		
		// HTML FORMS INPUTS
		$data['status'] = $this->setpostval('status', (!empty($rs_info) ? $rs_info['status'] : 0), 1); 
		$html[] = $this->{$this->modvar}->get_RDO_html('status', $data['status'], $divcls, $lblcls, $lang['entry_status'], array(1=>$lang['text_enabled'], 0=>$lang['text_disabled']));
		
		$data['disctype'] = $this->setpostval('disctype', (!empty($rs_info) ? $rs_info['disctype'] : 0), 1);
		$html[] = $this->{$this->modvar}->get_RDO_html('disctype', $data['disctype'], $divcls, $lblcls, $lang['entry_disctype'], array($lang['text_free'], $lang['text_per'], $lang['text_fix']));
		
		$data['discount'] = $this->setpostval('discount', (!empty($rs_info) ? $rs_info['discount'] : 0), 1);    
		$html[] = $this->{$this->modvar}->get_InpTxt_html('discount', $data['discount'], $divcls, $lblcls, $lang['entry_discount'], '');
		
		$data['buyqty'] = $this->setpostval('buyqty', (!empty($rs_info) ? $rs_info['buyqty'] : 0), 1);    
		$html[] = $this->{$this->modvar}->get_InpTxt_html('buyqty', $data['buyqty'], $divcls, $lblcls, $lang['entry_buyqty'], '');
		
		$data['getqty'] = $this->setpostval('getqty', (!empty($rs_info) ? $rs_info['getqty'] : 0), 1);   
		$html[] = $this->{$this->modvar}->get_InpTxt_html('getqty', $data['getqty'], $divcls, $lblcls, $lang['entry_getqty'], '');
		
		$data['startdate'] = $this->setpostval('startdate', (!empty($rs_info) ? $rs_info['startdate'] : date('Y-m-d')), date('Y-m-d'));  
		$html[] = $this->{$this->modvar}->get_InpDATE_html('startdate', $data['startdate'], $divcls, $lblcls, $lang['entry_startdate'], '');
		
		$data['enddate'] = $this->setpostval('enddate', (!empty($rs_info) ? $rs_info['enddate'] : date('Y-m-d')), date('Y-m-d'));  
		$html[] = $this->{$this->modvar}->get_InpDATE_html('enddate', $data['enddate'], $divcls, $lblcls, $lang['entry_enddate'], '');
		
 		// customer_group
		$data['cgs'] = $this->{$this->modvar}->getCustomerGroups();
		$data['customer_group'] = $this->setpostval('customer_group', (!empty($rs_info) ? explode(",",$rs_info['customer_group']) : array()), array()); 
		$html[] = $this->{$this->modvar}->get_InpTxt_CHKBOXWELL_html('customer_group', $data['customer_group'], $data['cgs'], 'customer_group_id', 'name', $divcls, $lblcls, $wellcls, $lang['entry_customer_group'], '');
		
		// store
		$data['stores'] = $this->{$this->modvar}->getStores();
		$data['store'] = $this->setpostval('store', (!empty($rs_info) ? explode(",",$rs_info['store']) : array()), array()); 
		$html[] = $this->{$this->modvar}->get_InpTxt_CHKBOXWELL_html('store', $data['store'], $data['stores'], 'store_id', 'name', $divcls, $lblcls, $wellcls, $lang['entry_store'], '');
		
		
		$html[] = '<ul class="nav nav-tabs" id="ULTAB_PCM">';
		
		if(substr(VERSION,0,3)=='4.0') { 
			$html[] = sprintf('<li class="nav-item active"><a data-bs-toggle="tab" class="nav-link active" href="#ULTAB_PCM_P" data-toggle="tab"> %s </a></li>', $lang['tab_product']);
			
			$html[] = sprintf('<li class="nav-item"><a data-bs-toggle="tab" class="nav-link" href="#ULTAB_PCM_C" data-toggle="tab"> %s </a></li>', $lang['tab_category']);
			
			$html[] = sprintf('<li class="nav-item"><a data-bs-toggle="tab" class="nav-link" href="#ULTAB_PCM_M" data-toggle="tab"> %s </a></li>', $lang['tab_manufacturer']);			
		} else {
			$html[] = sprintf('<li class="active"><a href="#ULTAB_PCM_P" data-toggle="tab"> %s </a></li>', $lang['tab_product']);
			
			$html[] = sprintf('<li class=""><a href="#ULTAB_PCM_C" data-toggle="tab"> %s </a></li>', $lang['tab_category']);
			
			$html[] = sprintf('<li class=""><a href="#ULTAB_PCM_M" data-toggle="tab"> %s </a></li>', $lang['tab_manufacturer']);
		}
		
		$html[] = '</ul>';
		
		
		$html[] = '<div class="tab-content">';
		
		$html[] = sprintf('<div class="tab-pane active" id="ULTAB_PCM_P">');
		// Product		
		$buyproduct = $this->{$this->modvar}->getprodcatmanform($rs_info, 'buyproduct', 'product');
		$data['buyproduct'] = $buyproduct[0];
		$data['buyproduct_data'] = $buyproduct[1];
		$html[] = $this->{$this->modvar}->get_pcm_autocmp_html('buyproduct', $data['buyproduct_data'], 'product_id', 'name', $divcls, $lblcls, $wellcls, $lang['entry_buyproduct'], '');
		
		$exbuyproduct = $this->{$this->modvar}->getprodcatmanform($rs_info, 'exbuyproduct', 'product');
		$data['exbuyproduct'] = $exbuyproduct[0];
		$data['exbuyproduct_data'] = $exbuyproduct[1];
		$html[] = $this->{$this->modvar}->get_pcm_autocmp_html('exbuyproduct', $data['exbuyproduct_data'], 'product_id', 'name', $divcls, $lblcls, $wellcls, $lang['entry_exbuyproduct'], '');
		
		$getproduct = $this->{$this->modvar}->getprodcatmanform($rs_info, 'getproduct', 'product');
		$data['getproduct'] = $getproduct[0];
		$data['getproduct_data'] = $getproduct[1];
		$html[] = $this->{$this->modvar}->get_pcm_autocmp_html('getproduct', $data['getproduct_data'], 'product_id', 'name', $divcls, $lblcls, $wellcls, $lang['entry_getproduct'], '');
		
		$exgetproduct = $this->{$this->modvar}->getprodcatmanform($rs_info, 'exgetproduct', 'product');
		$data['exgetproduct'] = $exgetproduct[0];
		$data['exgetproduct_data'] = $exgetproduct[1];
		$html[] = $this->{$this->modvar}->get_pcm_autocmp_html('exgetproduct', $data['exgetproduct_data'], 'product_id', 'name', $divcls, $lblcls, $wellcls, $lang['entry_exgetproduct'], '');
		$html[] = '</div>';
		
		
		$html[] = sprintf('<div class="tab-pane" id="ULTAB_PCM_C">');
		// Category
		$buycategory = $this->{$this->modvar}->getprodcatmanform($rs_info, 'buycategory', 'category');
		$data['buycategory'] = $buycategory[0];
		$data['buycategory_data'] = $buycategory[1];
		$html[] = $this->{$this->modvar}->get_pcm_autocmp_html('buycategory', $data['buycategory_data'], 'category_id', 'name', $divcls, $lblcls, $wellcls, $lang['entry_buycategory'], '');
		
		$exbuycategory = $this->{$this->modvar}->getprodcatmanform($rs_info, 'exbuycategory', 'category');
		$data['exbuycategory'] = $exbuycategory[0];
		$data['exbuycategory_data'] = $exbuycategory[1];
		$html[] = $this->{$this->modvar}->get_pcm_autocmp_html('exbuycategory', $data['exbuycategory_data'], 'category_id', 'name', $divcls, $lblcls, $wellcls, $lang['entry_exbuycategory'], '');
		
		$getcategory = $this->{$this->modvar}->getprodcatmanform($rs_info, 'getcategory', 'category');
		$data['getcategory'] = $getcategory[0];
		$data['getcategory_data'] = $getcategory[1];
		$html[] = $this->{$this->modvar}->get_pcm_autocmp_html('getcategory', $data['getcategory_data'], 'category_id', 'name', $divcls, $lblcls, $wellcls, $lang['entry_getcategory'], '');
		
		$exgetcategory = $this->{$this->modvar}->getprodcatmanform($rs_info, 'exgetcategory', 'category');
		$data['exgetcategory'] = $exgetcategory[0];
		$data['exgetcategory_data'] = $exgetcategory[1];
		$html[] = $this->{$this->modvar}->get_pcm_autocmp_html('exgetcategory', $data['exgetcategory_data'], 'category_id', 'name', $divcls, $lblcls, $wellcls, $lang['entry_exgetcategory'], '');
		$html[] = '</div>';
		
		$html[] = sprintf('<div class="tab-pane" id="ULTAB_PCM_M">');
		//Manufacturer
		$buymanufacturer = $this->{$this->modvar}->getprodcatmanform($rs_info, 'buymanufacturer', 'manufacturer');
		$data['buymanufacturer'] = $buymanufacturer[0];
		$data['buymanufacturer_data'] = $buymanufacturer[1];
		$html[] = $this->{$this->modvar}->get_pcm_autocmp_html('buymanufacturer', $data['buymanufacturer_data'], 'manufacturer_id', 'name', $divcls, $lblcls, $wellcls, $lang['entry_buymanufacturer'], '');
		
		$exbuymanufacturer = $this->{$this->modvar}->getprodcatmanform($rs_info, 'exbuymanufacturer', 'manufacturer');
		$data['exbuymanufacturer'] = $exbuymanufacturer[0];
		$data['exbuymanufacturer_data'] = $exbuymanufacturer[1];
		$html[] = $this->{$this->modvar}->get_pcm_autocmp_html('exbuymanufacturer', $data['exbuymanufacturer_data'], 'manufacturer_id', 'name', $divcls, $lblcls, $wellcls, $lang['entry_exbuymanufacturer'], '');
		
		$getmanufacturer = $this->{$this->modvar}->getprodcatmanform($rs_info, 'getmanufacturer', 'manufacturer');
		$data['getmanufacturer'] = $getmanufacturer[0];
		$data['getmanufacturer_data'] = $getmanufacturer[1];
		$html[] = $this->{$this->modvar}->get_pcm_autocmp_html('getmanufacturer', $data['getmanufacturer_data'], 'manufacturer_id', 'name', $divcls, $lblcls, $wellcls, $lang['entry_getmanufacturer'], '');
		
		$exgetmanufacturer = $this->{$this->modvar}->getprodcatmanform($rs_info, 'exgetmanufacturer', 'manufacturer');
		$data['exgetmanufacturer'] = $exgetmanufacturer[0];
		$data['exgetmanufacturer_data'] = $exgetmanufacturer[1];
		$html[] = $this->{$this->modvar}->get_pcm_autocmp_html('exgetmanufacturer', $data['exgetmanufacturer_data'], 'manufacturer_id', 'name', $divcls, $lblcls, $wellcls, $lang['entry_exgetmanufacturer'], '');
		$html[] = '</div>';
		
		$html[] = '</div>';
		
		$html[] = '<div style="clear:both;padding:10px;"></div>';
		
		$data['showofferat'] = $this->setpostval('showofferat', (!empty($rs_info) ? $rs_info['showofferat'] : 0), 1);
		$html[] = $this->{$this->modvar}->get_RDO_html('showofferat', $data['showofferat'], $divcls, $lblcls, $lang['entry_showofferat'], array($lang['text_afterprc']));
 
 		
		$data['title'] = $this->setpostval('title', (!empty($rs_info) ? json_decode($rs_info['title'],true) : array()), array()); 
		$data['ribbontext'] = $this->setpostval('ribbontext', (!empty($rs_info) ? json_decode($rs_info['ribbontext'],true) : array()), array()); 
		$data['ordtotaltext'] = $this->setpostval('ordtotaltext', (!empty($rs_info) ? json_decode($rs_info['ordtotaltext'],true) : array()), array()); 
		$data['offer_heading'] = $this->setpostval('offer_heading', (!empty($rs_info) ? json_decode($rs_info['offer_heading'],true) : array()), array()); 
		$data['offer_content'] = $this->setpostval('offer_content', (!empty($rs_info) ? json_decode($rs_info['offer_content'],true) : array()), array()); 
		
		$html[] = '<ul class="nav nav-tabs" id="ULTAB_language">';
		$i = 0;
		foreach($data['languages'] as $lng) { $i++;
			$class_active = $i == 1 ? ' active ' : '';
			
			if(substr(VERSION,0,3)=='4.0') { 
				$html[] = sprintf('<li class="nav-item %s"><a data-bs-toggle="tab" class="nav-link %s" href="#ULTAB_language%s" data-toggle="tab"><img src="%s"/> %s </a></li>', $class_active, $class_active, $lng['language_id'], $lng['imgsrc'], $lng['name']);
			} else {
				$html[] = sprintf('<li class="%s"><a href="#ULTAB_language%s" data-toggle="tab"><img src="%s"/> %s</a></li>', $class_active, $lng['language_id'], $lng['imgsrc'], $lng['name']);
			}
		}
		$html[] = '</ul>';
		
		$html[] = '<div class="tab-content">';
		$i = 0;
		foreach($data['languages'] as $lng) { $i++;
			$class_active = $i == 1 ? ' active ' : '';
			
			$html[] = sprintf('<div class="tab-pane %s" id="ULTAB_language%s">', $class_active, $lng['language_id']);
			
			$txtval = isset($data['title'][$lng['language_id']]) ? $data['title'][$lng['language_id']] : '';
 			$html[] = $this->{$this->modvar}->get_InpTxt_LANG_ULTAB_html('title', $txtval, $lng['language_id'], $divcls, $lblcls, $lang['entry_title'], '', 0);
			
			$txtval = isset($data['ribbontext'][$lng['language_id']]) ? $data['ribbontext'][$lng['language_id']] : '';
 			$html[] = $this->{$this->modvar}->get_InpTxt_LANG_ULTAB_html('ribbontext', $txtval, $lng['language_id'], $divcls, $lblcls, $lang['entry_ribbontext'], '', 0);
			
			$txtval = isset($data['ordtotaltext'][$lng['language_id']]) ? $data['ordtotaltext'][$lng['language_id']] : '';
 			$html[] = $this->{$this->modvar}->get_InpTxt_LANG_ULTAB_html('ordtotaltext', $txtval, $lng['language_id'], $divcls, $lblcls, $lang['entry_ordtotaltext'], '', 0);
			
			$txtval = isset($data['offer_heading'][$lng['language_id']]) ? $data['offer_heading'][$lng['language_id']] : '';
 			$html[] = $this->{$this->modvar}->get_InpTxt_LANG_ULTAB_html('offer_heading', $txtval, $lng['language_id'], $divcls, $lblcls, $lang['entry_offer_heading'], '', 0);
			
			$txtval = isset($data['offer_content'][$lng['language_id']]) ? $data['offer_content'][$lng['language_id']] : '';
 			$html[] = $this->{$this->modvar}->get_InpTxt_LANG_ULTAB_html('offer_content', $txtval, $lng['language_id'], $divcls, $lblcls, $lang['entry_offer_content'], '', 1);
			  			
			$html[] = '</div>';
		}
		
		if(substr(VERSION,0,3)=='2.0' || substr(VERSION,0,3)=='2.1') {
			$html[] = '<script type="text/javascript">$(\'.summernote\').summernote({height: 300});</script>';
		}
		if(substr(VERSION,0,3)=='2.3') {
			$html[] = '<script type="text/javascript" src="view/javascript/summernote/summernote.js"></script>
			<link href="view/javascript/summernote/summernote.css" rel="stylesheet" />
			<script type="text/javascript" src="view/javascript/summernote/opencart.js"></script>';
		}
		if(substr(VERSION,0,3)=='3.0') {
			$html[] = '<script type="text/javascript" src="view/javascript/summernote/summernote.js"></script>
			<link href="view/javascript/summernote/summernote.css" rel="stylesheet" />
			<script type="text/javascript" src="view/javascript/summernote/summernote-image-attributes.js"></script> 
			<script type="text/javascript" src="view/javascript/summernote/opencart.js"></script>';
		}
		if(substr(VERSION,0,3)=='4.0') { 
			$html[] = '<script type="text/javascript" src="view/javascript/ckeditor/ckeditor.js"></script>
			<script type="text/javascript" src="view/javascript/ckeditor/adapters/jquery.js"></script>
			<script type="text/javascript">$(\'textarea[data-oc-toggle="ckeditor"]\').ckeditor();</script>';
		}
		
		$data['form_html_data'] = join($html);
		
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		$this->response->setOutput($this->load->view($this->modtpl_form, $data));
	}
	public function setpostval($name, $val, $defval) {
		if (isset($this->request->post[$name])) {
			return $this->request->post[$name];
		} elseif (!empty($val) || $val == 0) {
			return $val;
		} else {
			return $defval;
		}		
	}
 	protected function validateForm() {
		if (!$this->user->hasPermission('modify', $this->modpath)) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
		
		if ($this->error && !isset($this->error['warning'])) {
			$this->error['warning'] = $this->language->get('error_warning');
		}
		
		return !$this->error;
	}
	protected function validateDelete() {
		if (!$this->user->hasPermission('modify', $this->modpath)) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}
}