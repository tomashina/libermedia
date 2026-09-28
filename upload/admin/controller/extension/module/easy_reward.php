<?php
class ControllerExtensionModuleEasyReward extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/module/easy_reward');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('module_easy_reward', $this->request->post);

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
			'href' => $this->url->link('extension/module/easy_reward', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/module/easy_reward', 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

		$this->load->model('customer/customer_group');

		$data['customer_groups'] = $this->model_customer_customer_group->getCustomerGroups();

		if (isset($this->request->post['module_easy_reward_points'])) {
			$data['module_easy_reward_points'] = $this->request->post['module_easy_reward_points'];
		} else {
			$data['module_easy_reward_points'] = $this->config->get('module_easy_reward_points');
		}

		if (isset($this->request->post['module_easy_reward_rewards'])) {
			$data['module_easy_reward_rewards'] = $this->request->post['module_easy_reward_rewards'];
		} else {
			$data['module_easy_reward_rewards'] = $this->config->get('module_easy_reward_rewards');
		}

		if (isset($this->request->post['module_easy_reward_minimum'])) {
			$data['module_easy_reward_minimum'] = $this->request->post['module_easy_reward_minimum'];
		} else {
			$data['module_easy_reward_minimum'] = $this->config->get('module_easy_reward_minimum');
		}

		if (isset($this->request->post['module_easy_reward_maximum'])) {
			$data['module_easy_reward_maximum'] = $this->request->post['module_easy_reward_maximum'];
		} else {
			$data['module_easy_reward_maximum'] = $this->config->get('module_easy_reward_maximum');
		}

		if (isset($this->request->post['module_easy_reward_auto'])) {
			$data['module_easy_reward_auto'] = $this->request->post['module_easy_reward_auto'];
		} else {
			$data['module_easy_reward_auto'] = $this->config->get('module_easy_reward_auto');
		}

		if (isset($this->request->post['module_easy_reward_notify'])) {
			$data['module_easy_reward_notify'] = $this->request->post['module_easy_reward_notify'];
		} else {
			$data['module_easy_reward_notify'] = $this->config->get('module_easy_reward_notify');
		}

		if (isset($this->request->post['module_easy_reward_status'])) {
			$data['module_easy_reward_status'] = $this->request->post['module_easy_reward_status'];
		} else {
			$data['module_easy_reward_status'] = $this->config->get('module_easy_reward_status');
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/easy_reward', $data));
	}

	public function install() {
		$this->load->model('setting/event');

		$this->model_setting_event->addEvent('easy_reward', 'catalog/model/checkout/order/addOrderHistory/after', 'extension/module/easy_reward');
	}

	public function uninstall() {
		$this->load->model('setting/event');

		$this->model_setting_event->deleteEventByCode('easy_reward');
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/module/easy_reward')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}
}