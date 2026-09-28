<?php
//lib
require_once(DIR_SYSTEM.'library/tmd/system.php');
//lib
class ControllerExtensionModuleCustom extends Controller {
	private $error = array();
public function install()
	{
	$this->load->model('extension/custom');
	$this->model_extension_custom->install();
	}	
	public function uninstall()
	{
	$this->load->model('extension/custom');
	$this->model_extension_custom->uninstall();
	}
	public function index() {
		$this->registry->set('tmd', new TMD($this->registry));
		$keydata=array(
		'code'=>'tmdkey_custom',
		'eid'=>'MzM3MzE=',
		'route'=>'extension/module/custom',
		);
		$custom=$this->tmd->getkey($keydata['code']);
		$data['getkeyform']=$this->tmd->loadkeyform($keydata);
		$this->load->language('extension/module/custom');

		$this->document->setTitle($this->language->get('heading_title1'));
		
		$data['heading_title'] = $this->language->get('heading_title');
		$data['text_extension'] = $this->language->get('text_extension');
		$data['entry_status'] = $this->language->get('entry_status');
		$data['text_success'] = $this->language->get('text_success');
		$data['text_edit'] = $this->language->get('text_edit');
		$data['error_permission'] = $this->language->get('error_permission');
		$data['text_disabled'] = $this->language->get('text_disabled');
		$data['text_enabled'] = $this->language->get('text_enabled');
		

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('module_custom', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/module/custom', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/module/custom', 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

		if (isset($this->request->post['module_custom_status'])) {
			$data['module_custom_status'] = $this->request->post['module_custom_status'];
		} else {
			$data['module_custom_status'] = $this->config->get('module_custom_status');
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/custom', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/module/custom')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
		
		$custom=$this->config->get('tmdkey_custom');
		if (empty(trim($custom))) {			
		$this->error['warning'] ='Module will Work after add License key!';
		}
		return !$this->error;
	}
	
	public function keysubmit() {
		$json = array(); 
		
      	if (($this->request->server['REQUEST_METHOD'] == 'POST')) {
			$keydata=array(
			'code'=>'tmdkey_custom',
			'eid'=>'MjYxMjQ=',
			'route'=>'extension/module/custom',
			'moduledata_key'=>$this->request->post['moduledata_key'],
			);
			$this->registry->set('tmd', new TMD($this->registry));
            $json=$this->tmd->matchkey($keydata);       
		} 
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
