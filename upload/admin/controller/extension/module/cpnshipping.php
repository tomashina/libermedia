<?php
class ControllerExtensionModuleCpnshipping extends Controller {
	private $error = array(); 
	

	
	public function index() {   
		$this->language->load('extension/module/cpnshipping');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('module_cpnshipping', $this->request->post);		
			
			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
			
		}


		$data['heading_title'] = $this->language->get('heading_title');

		$data['entry_client_number'] = $this->language->get('entry_client_number');
		$data['entry_api_user'] = $this->language->get('entry_api_user');
		$data['entry_api_key'] = $this->language->get('entry_api_key');

		$data['text_info'] = $this->language->get('text_info');

		$data['entry_sender_name'] = $this->language->get('entry_sender_name');
		$data['entry_sender_address'] = $this->language->get('entry_sender_address');
		$data['entry_sender_zipcode'] = $this->language->get('entry_sender_zipcode');
		$data['entry_sender_city'] = $this->language->get('entry_sender_city');
		$data['entry_sender_country'] = $this->language->get('entry_sender_country');

		
		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');

		$data['action'] = $this->url->link('extension/module/cpnshipping', 'user_token=' . $this->session->data['user_token'], 'SSL');

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

 		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['image'])) {
			$data['error_image'] = $this->error['image'];
		} else {
			$data['error_image'] = array();
		}

  		$data['breadcrumbs'] = array();

   		$data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('text_home'),
			'href'      => $this->url->link('common/home', 'user_token=' . $this->session->data['user_token'], 'SSL'),
      		'separator' => false
   		);

   		$data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('text_module'),
			'href'      => $this->url->link('extension/module', 'user_token=' . $this->session->data['user_token'], 'SSL'),
      		'separator' => ' :: '
   		);

   		$data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('heading_title'),
			'href'      => $this->url->link('extension/module/cpnshipping', 'user_token=' . $this->session->data['user_token'], 'SSL'),
      		'separator' => ' :: '
   		);


		if (isset($this->request->post['module_cpnshipping_data'])) {
			$data['module_cpnshipping_data'] = $this->request->post['module_cpnshipping_data'];
		} elseif ($this->config->get('module_cpnshipping_data')) { 
			$data['module_cpnshipping_data'] = $this->config->get('module_cpnshipping_data');
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/cpnshipping', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/module/cpnshipping')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}	

		if (!$this->error) {
			return true;
		} else {
			return false;
		}	
	}

		public function import(){
		$json = array();

            	// load data from order_id
            	$order_id = $this->request->get['order_id'];

            		$pcount 	= $this->request->get['pcount'];
					$brojracuna = $this->request->get['order_id'];
					$iznos 		= $this->request->get['iznos'];
            		$this->load->model('sale/order');
            		$order_info = $this->model_sale_order->getOrder($order_id);




		if($order_info['payment_code'] =='cod'){
			if ($iznos=='') {
				$mani = $order_info['total'];
				$mani =  number_format((float)$mani, 2, '.', '');
			} else {
				$mani = $iznos;
			}
		} else {
			$mani=0;
		}


				try 
					{
						$module_data = (array)$this->config->get('module_cpnshipping_data');
						$clientNumber = isset($module_data['client_number']) ? trim($module_data['client_number']) : '';
						$username = isset($module_data['api_user']) ? trim($module_data['api_user']) : '';
						$pwd = isset($module_data['api_key']) ? $module_data['api_key'] : '';

						if ($clientNumber === '' || $username === '' || $pwd === '') {
							throw new Exception('GLS pristupni podaci nisu podešeni u modulu CPN Shipping.');
						}

						$password = hash('sha512', $pwd, true);

					$parcels = [];
					$parcel = new StdClass();
					$parcel->ClientNumber = $clientNumber;
					$parcel->ClientReference = "Liber Media";
					$parcel->CODAmount = $mani;
					$parcel->CODReference = $brojracuna;
					$parcel->Content = "Liber Media";
					$parcel->Count = 1;
					$deliveryAddress = new StdClass();
					$deliveryAddress->ContactEmail = $order_info['email'];
					$deliveryAddress->ContactName = $order_info['shipping_firstname'] . ' ' . $order_info['shipping_lastname'];
					$deliveryAddress->ContactPhone = $order_info['telephone'];
					$deliveryAddress->Name = $order_info['shipping_firstname'] . ' ' . $order_info['shipping_lastname'];
					$deliveryAddress->Street = $order_info['shipping_address_1'];
					$deliveryAddress->HouseNumber = $order_info['shipping_address_2'];
					$deliveryAddress->City = $order_info['shipping_city'];
					$deliveryAddress->ZipCode = $order_info['shipping_postcode'];
					$deliveryAddress->CountryIsoCode = "HR";
					$deliveryAddress->HouseNumberInfo = "/b";
					$parcel->DeliveryAddress = $deliveryAddress;
					$pickupAddress = new StdClass();
					$pickupAddress->ContactName = "Liber Media";
					$pickupAddress->ContactPhone = "+385953888535";
					$pickupAddress->ContactEmail = "info@liber-media.hr";
					$pickupAddress->Name = "Liber Media";
					$pickupAddress->Street = "Davorina Bazjanca";
					$pickupAddress->HouseNumber = "17";
					$pickupAddress->City = "Zadar";
					$pickupAddress->ZipCode = "23000";
					$pickupAddress->CountryIsoCode = "HR";
					$pickupAddress->HouseNumberInfo = "/a";
					$parcel->PickupAddress = $pickupAddress;
					$parcel->PickupDate = date('Y-m-d');
					$service1 = new StdClass();
					$service1->Code = "FDS";
					$parameter1 = new StdClass();
					$parameter1->StringValue = $order_info['email'];
					$service1->FDSParameter = $parameter1;
					$service2 = new StdClass();
					$service2->Code = "DPV";
					$parameter2 = new StdClass();
					$parameter2->StringValue = "Liber Media";
					$parameter2->DecimalValue = "500";
					$service2->DPVParameter = $parameter2;



					$services = [];
					$services[] = $service1;
					$services[] = $service2;
					$parcel->ServiceList = $services;

					$parcels[] = $parcel;

					//The service URL:
					$wsdl = "https://api.mygls.hr/ParcelService.svc?singleWsdl";

                    $soapOptions = array('soap_version'   => SOAP_1_1
                    , 'stream_context' => stream_context_create(array('ssl' => array('cafile' => 'cacert.pem'))));





					$this->PrepareLabels($username,$password,$parcels,$wsdl,$soapOptions,$order_id);
				}
				catch (Exception $e)
				{
				    echo $e->getMessage();
				}














	}

    public function PrepareLabels($username,$password,$parcels,$wsdl,$soapOptions,$order_id)
    {
        //Test request:
        $prepareLabelsRequest = array('Username' => $username,
            'Password' => $password,
            'ParcelList' => $parcels);


        $this->log->write($prepareLabelsRequest);

        $request = array ("prepareLabelsRequest" => $prepareLabelsRequest);

        //Service client creation:
        $client = new SoapClient($wsdl,$soapOptions);



        //Service calling:
        $response = $client->PrepareLabels($request);


     //   print_r($response);

        $parcelIdList = [];
        if($response != null && count((array)$response->PrepareLabelsResult->PrepareLabelsError) == 0 && count((array)$response->PrepareLabelsResult->ParcelInfoList) > 0)
        {
            $parcelIdList[] = $response->PrepareLabelsResult->ParcelInfoList->ParcelInfo->ParcelId;

            //agmedia
            $this->db->query("UPDATE `" . DB_PREFIX . "order` SET printed = 1 WHERE order_id = '" . (int)$order_id . "'");

            //$this->db->query("UPDATE `" . DB_PREFIX . "order` SET printed = 1 WHERE order_id = '9'");
        }

        //Test request:
        $getPrintedLabelsRequest = array('Username' => $username,
            'Password' => $password,
            'ParcelIdList' => $parcelIdList,
            'PrintPosition' => 1,
            'ShowPrintDialog' => 0);

        return $getPrintedLabelsRequest;
    }

}
?>
