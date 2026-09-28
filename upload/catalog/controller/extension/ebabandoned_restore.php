<?php
class ControllerExtensionEbabandonedRestore extends Controller {
	
	public function index(){
		$this->load->model('setting/setting');
		$this->load->model('extension/ebcart');
		
		if(isset($this->request->get['ebcart_id'])){
			$ebcart_id = base64_decode($this->request->get['ebcart_id']);
		}else{
			$ebcart_id = 0;
		}
		
		$cart_info = $this->model_extension_ebcart->getebcart($ebcart_id);
		if($cart_info){
			if($cart_info['customer_id']){
				$this->session->data['customer_id'] = $cart_info['customer_id'];
				$cart_info = $this->model_extension_ebcart->checkcartbycustomerid($cart_info['customer_id']);
				if(!$cart_info){
					$this->restore($ebcart_id);
				}else{
					$this->response->redirect($this->url->link('checkout/cart', '', true));
				}
			}else{
				$this->restore($ebcart_id);
			}
		}else{
			$this->session->data['warning'] = 'Something went wrong!'; 
			$this->response->redirect($this->url->link('account/login', '', true));
		}
	}
	
	public function restore($ebcart_id){
		$carts = $this->model_extension_ebcart->getcartforrestore($ebcart_id);
		if($carts){
			$this->cart->clear();
			foreach($carts as $cart){
				$this->add($cart['product_id'], $cart['quantity'], json_decode($cart['option']), 0);
			}
			$this->response->redirect($this->url->link('checkout/cart', '', true));
		}else{
			$this->session->data['warning'] = 'Something went wrong!'; 
			$this->response->redirect($this->url->link('account/login', '', true));
		}
	}

	public function add($product_id, $quantity = 1, $option = array(), $recurring_id = 0) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "cart WHERE api_id = '" . (isset($this->session->data['api_id']) ? (int)$this->session->data['api_id'] : 0) . "' AND customer_id = '" . (int)$this->customer->getId() . "' AND session_id = '" . $this->db->escape($this->session->getId()) . "' AND product_id = '" . (int)$product_id . "' AND recurring_id = '" . (int)$recurring_id . "' AND `option` = '" . $this->db->escape(json_encode($option)) . "'");

		if (!$query->row['total']) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "cart SET api_id = '" . (isset($this->session->data['api_id']) ? (int)$this->session->data['api_id'] : 0) . "', customer_id = '" . (int)$this->customer->getId() . "', session_id = '" . $this->db->escape($this->session->getId()) . "', product_id = '" . (int)$product_id . "', recurring_id = '" . (int)$recurring_id . "', `option` = '" . $this->db->escape(json_encode($option)) . "', quantity = '" . (int)$quantity . "', date_added = NOW()");
		} else {
			$this->db->query("UPDATE " . DB_PREFIX . "cart SET quantity = (quantity + " . (int)$quantity . ") WHERE api_id = '" . (isset($this->session->data['api_id']) ? (int)$this->session->data['api_id'] : 0) . "' AND customer_id = '" . (int)$this->customer->getId() . "' AND session_id = '" . $this->db->escape($this->session->getId()) . "' AND product_id = '" . (int)$product_id . "' AND recurring_id = '" . (int)$recurring_id . "' AND `option` = '" . $this->db->escape(json_encode($option)) . "'");
		}
	}

}