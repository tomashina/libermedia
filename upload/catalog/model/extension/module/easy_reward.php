<?php
class ModelExtensionModuleEasyReward extends Model {
	public function getRewardsByOrderId($order_id) {
		$query = $this->db->query("SELECT SUM(reward) AS total FROM " . DB_PREFIX . "order_product WHERE order_id = '" . (int)$order_id . "'");

		return $query->row['total'];
	}

	public function getTotalCustomerRewardsByOrderId($order_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "customer_reward WHERE order_id = '" . (int)$order_id . "' AND points > 0");

		return $query->row['total'];
	}

	public function addReward($customer_id, $description = '', $points = '', $order_id = 0) {
		$this->db->query("INSERT INTO " . DB_PREFIX . "customer_reward SET customer_id = '" . (int)$customer_id . "', order_id = '" . (int)$order_id . "', points = '" . (int)$points . "', description = '" . $this->db->escape($description) . "', date_added = NOW()");

		if ($this->config->get('module_easy_reward_notify')) {
			$this->load->model('account/customer');

			$customer_info = $this->model_account_customer->getCustomer($customer_id);

			if ($customer_info) {
				$this->load->language('extension/module/easy_reward');

				$store_name = $this->config->get('config_name');

				$text = sprintf($this->language->get('text_received'), $points) . "\n\n";
				$text .= sprintf($this->language->get('text_total'), $this->getRewardTotal($customer_id));

				$mail = new Mail($this->config->get('config_mail_engine'));
				$mail->protocol = $this->config->get('config_mail_protocol');
				$mail->parameter = $this->config->get('config_mail_parameter');
				$mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
				$mail->smtp_username = $this->config->get('config_mail_smtp_username');
				$mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
				$mail->smtp_port = $this->config->get('config_mail_smtp_port');
				$mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');

				$mail->setTo($customer_info['email']);
				$mail->setFrom($this->config->get('config_email'));
				$mail->setSender(html_entity_decode($store_name, ENT_QUOTES, 'UTF-8'));
				$mail->setSubject(sprintf($this->language->get('text_subject'), html_entity_decode($store_name, ENT_QUOTES, 'UTF-8')));
				$mail->setText($text);
				$mail->send();
			}
		}
	}

	public function deleteReward($order_id) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "customer_reward WHERE order_id = '" . (int)$order_id . "' AND points > 0");
	}

	public function getRewardTotal($customer_id) {
		$query = $this->db->query("SELECT SUM(points) AS total FROM " . DB_PREFIX . "customer_reward WHERE customer_id = '" . (int)$customer_id . "'");

		return $query->row['total'];
	}
}
