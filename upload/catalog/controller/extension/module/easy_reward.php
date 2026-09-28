<?php
class ControllerExtensionModuleEasyReward extends Controller {
	public function index($route = '', $args = array(), $output = '') {
		if ($this->config->get('module_easy_reward_status') && $this->config->get('module_easy_reward_auto')) {
			$this->load->model('extension/module/easy_reward');

			$this->load->model('checkout/order');

			$complete_statuses = $this->config->get('config_complete_status');

			if (isset($this->session->data['order_id'])) {
				// New order from front-end
				$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

				if (!empty($order_info['customer_id']) && in_array($order_info['order_status_id'], $complete_statuses)) {
					$this->addReward($order_info);
				}
			} elseif (isset($args[0]) && isset($args[1])) {
				// Add order history from back-end
				$order_id = $args[0];
				$order_status_id = $args[1];

				$order_info = $this->model_checkout_order->getOrder($order_id);

				if (!empty($order_info['customer_id'])) {
					// If old order status is not complete but new status is complete then add rewards
					if (in_array($order_status_id, $complete_statuses)) {
						$this->addReward($order_info);
					} else {
						$this->model_extension_module_easy_reward->deleteReward($order_id);
					}
				}
			}
		}
	}

	protected function addReward($order_info) {
		$this->load->language('extension/module/easy_reward');

		$reward = $this->model_extension_module_easy_reward->getRewardsByOrderId($order_info['order_id']);

		if ($reward > 0) {
			$reward_total = $this->model_extension_module_easy_reward->getTotalCustomerRewardsByOrderId($order_info['order_id']);

			if (!$reward_total) {
				$this->model_extension_module_easy_reward->addReward($order_info['customer_id'], $this->language->get('text_order_id') . ' #' . $order_info['order_id'], $reward, $order_info['order_id']);
			}
		}
	}
}