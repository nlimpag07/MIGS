<?php
class ControllerPaymentMigs extends Controller {
	public function index() {
		$this->load->language('payment/migs');

		$data['button_confirm'] = $this->language->get('button_confirm');

		$data['action'] = $this->url->link('payment/migs/checkout', '', 'SSL');

		if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/migs.tpl')) {
			return $this->load->view($this->config->get('config_template') . '/template/payment/migs.tpl', $data);
		} else {
			return $this->load->view('default/template/payment/migs.tpl', $data);
		}
	}

	public function checkout() {
		$this->load->model('checkout/order');
		$this->load->model('account/order');
		$this->load->model('payment/migs');

		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

		$this->load->model('extension/extension');
		$results = $this->model_extension_extension->getExtensions('total');
		$order_data = array();
		$total = 0;
		$items = array();
		$taxes = $this->cart->getTaxes();

		$i = 0;
		foreach ($results as $result) {
			if ($this->config->get($result['code'] . '_status')) {
				$this->load->model('total/' . $result['code']);

				$this->{'model_total_' . $result['code']}->getTotal($order_data['totals'], $total, $taxes);

				if (isset($order_data['totals'][$i])) {
					if (strstr(strtolower($order_data['totals'][$i]['code']), 'total') === false) {
						$item = new stdClass();
						$item->sku = $order_data['totals'][$i]['code'];
						$item->name = $order_data['totals'][$i]['title'];
						$item->amount = number_format($order_data['totals'][$i]['value'], 2);
						$item->qty = 1;
						$items[] = $item;
					}
					$i++;
				}
			}
		}

		$ordered_products = $this->model_account_order->getOrderProducts($this->session->data['order_id']);

		foreach ($ordered_products as $product) {
			$item = new stdClass();
			$item->sku = $product['product_id'];
			$item->name = $product['name'];
			$item->amount = $product['price'] * $product['quantity'];
			$item->qty = $product['quantity'];
			$items[] = $item;
		}

		/*if ($this->config->get('migs_environment') == 1) {
			$url = 'https://checkout.pay.g2a.com/index/createQuote';
		} else {
			$url = 'https://checkout.test.pay.g2a.com/index/createQuote';
		}*/

		$order_total = (float)number_format($order_info['total'], 2,'','');


        $vpc_AccessCode  = $this->config->get('migs_access_code');
        $vpc_Amount      = $order_total;
        $vpc_Command     = 'pay';
        $vpc_Locale      = 'en';
        $vpc_Merchant    = $this->config->get('migs_merchant_id');
        $vpc_OrderInfo   = json_encode('Credit Card Transaction Payment');
		$vpc_ReturnURL   = $this->url->link('payment/migs/callback');
		$vpc_Version     = '1';
		$vpc_MerchTxnRef = $this->session->data['order_id'];
		$secure_secret   = $this->config->get('migs_secret_key');
		  
		$md5HashData = $secure_secret . $vpc_AccessCode . $vpc_Amount . $vpc_Command . $vpc_Locale . $vpc_MerchTxnRef . $vpc_Merchant . $vpc_OrderInfo . $vpc_ReturnURL . $vpc_Version;

		$vpcURL = $this->config->get('migs_payment_url').'?';
		$migs_secret_key = $this->config->get('migs_secret_key');

		//$order_total = number_format($order_info['total'], 2);

		//$string = $this->session->data['order_id'] . $order_total . $order_info['currency_code'] . html_entity_decode($migs_secret_key);
        
		// urlencode overkill!
		  $vpcURL .= urlencode('vpc_AccessCode') . '=' . urlencode($vpc_AccessCode) .
		    '&' . urlencode('vpc_Amount') . "=" . urlencode($vpc_Amount) .
		    '&' . urlencode('vpc_Command') . "=" . urlencode($vpc_Command) .
		    '&' . urlencode('vpc_Locale') . "=" . urlencode($vpc_Locale) .
		    '&' . urlencode('vpc_MerchTxnRef') . "=" . urlencode($vpc_MerchTxnRef) .
		    '&' . urlencode('vpc_Merchant') . "=" . urlencode($vpc_Merchant) .
		    '&' . urlencode('vpc_OrderInfo') . "=" . urlencode($vpc_OrderInfo) .
		    '&' . urlencode('vpc_ReturnURL') . "=" . urlencode($vpc_ReturnURL) .
		    '&' . urlencode('vpc_Version') . '=' . urlencode($vpc_Version) .
		    '&' . urlencode('vpc_SecureHash') . '=' . strtoupper(md5($md5HashData));

		//$response_data = $this->model_payment_migs->sendCurl($url, $fields);
        $this->response->redirect($vpcURL);
		/*$this->model_payment_migs->logger($order_total);
		$this->model_payment_migs->logger($items);
		$this->model_payment_migs->logger($fields);*/
        
        
		/*if ($response_data === false) {
			$this->response->redirect($this->url->link('payment/failure', '', 'SSL'));
		}

		if (strtolower($response_data->status) != 'ok') {
			$this->response->redirect($this->url->link('payment/failure', '', 'SSL'));
		}

		$this->model_payment_migs->addG2aOrder($order_info);

		if ($this->config->get('migs_environment') == 1) {
			$this->response->redirect('https://checkout.pay.g2a.com/index/gateway?token=' . $response_data->token);
		} else {
			$this->response->redirect('https://checkout.test.pay.g2a.com/index/gateway?token=' . $response_data->token);
		}*/
	}

	public function success() {
		$order_id = $this->session->data['order_id'];

		if (isset($this->request->post['transaction_id'])) {
			$migs_transaction_id = $this->request->post['transaction_id'];
		} elseif (isset($this->request->get['transaction_id'])) {
			$migs_transaction_id = $this->request->get['transaction_id'];
		} else {
			$migs_transaction_id = '';
		}

		$this->load->model('checkout/order');

		$order_info = $this->model_checkout_order->getOrder($order_id);

		if ($order_info) {
			$this->load->model('payment/migs');
			$migs_order_info = $this->model_payment_migs->getG2aOrder($order_id);

			$this->model_payment_migs->updateOrder($migs_order_info['migs_order_id'], $migs_transaction_id, 'payment', $order_info);

			$this->model_checkout_order->addOrderHistory($order_id, $this->config->get('migs_order_status_id'));
		}

		$this->response->redirect($this->url->link('checkout/success'));
	}

	public function callback() {
		$this->load->model('payment/migs');
		$this->model_payment_migs->logger('callback');
		$order_id = $this->session->data['order_id'];

		if (isset($this->request->post['vpc_MerchTxnRef'])) {
			$migs_transaction_id = $this->request->post['vpc_MerchTxnRef'];
		} elseif (isset($this->request->get['vpc_MerchTxnRef'])) {
			$migs_transaction_id = $this->request->get['vpc_MerchTxnRef'];
		} else {
			$migs_transaction_id = '';
		}
		if (isset($this->request->post['vpc_Merchant'])) {
			$migs_merchant_id = $this->request->post['vpc_Merchant'];
		} elseif (isset($this->request->get['vpc_Merchant'])) {
			$migs_merchant_id = $this->request->get['vpc_Merchant'];
		} else {
			$migs_merchant_id = '';
		}
		if (isset($this->request->post['vpc_TxnResponseCode'])) {
			$migs_vpc_TxnResponseCode = $this->request->post['vpc_TxnResponseCode'];
		} elseif (isset($this->request->get['vpc_TxnResponseCode'])) {
			$migs_vpc_TxnResponseCode = $this->request->get['vpc_TxnResponseCode'];
		} else {
			$migs_vpc_TxnResponseCode = '';
		}
		if (isset($this->request->post['vpc_Amount'])) {
			$migs_vpc_Amount = $this->request->post['vpc_Amount'];
		} elseif (isset($this->request->get['vpc_Amount'])) {
			$migs_vpc_Amount = $this->request->get['vpc_Amount'];
		} else {
			$migs_vpc_Amount = '';
		}
		if (isset($this->request->post['vpc_Message'])) {
			$migs_vpc_Message = $this->request->post['vpc_Message'];
		} elseif (isset($this->request->get['vpc_Message'])) {
			$migs_vpc_Message = $this->request->get['vpc_Message'];
		} else {
			$migs_vpc_Message = '';
		}
		if (isset($this->request->post['vpc_SecureHash'])) {
			$migs_vpc_SecureHash = $this->request->post['vpc_SecureHash'];
		} elseif (isset($this->request->get['vpc_SecureHash'])) {
			$migs_vpc_SecureHash = $this->request->get['vpc_SecureHash'];
		} else {
			$migs_vpc_SecureHash = '';
		}
		
		$error='';

		if ($migs_merchant_id == $this->config->get('migs_merchant_id') && $migs_transaction_id == $order_id) {
			$this->model_payment_migs->logger('Merchant Correct');

			
				$this->load->model('checkout/order');
				$migs_order = $this->model_checkout_order->getOrder($order_id);


				$vpc_AccessCode  = $this->config->get('migs_access_code');
		        $vpc_Amount      = $migs_vpc_Amount;
		        $vpc_Command     = 'pay';
		        $vpc_Locale      = 'en';
		        $vpc_Merchant    = $migs_merchant_id;
		        $vpc_OrderInfo   = json_encode('Credit Card Transaction Payment');
				$vpc_ReturnURL   = $this->url->link('payment/migs/callback');
				$vpc_Version     = '1';
				$vpc_MerchTxnRef = $order_id;
				$secure_secret   = $this->config->get('migs_secret_key');
				  
				$md5HashData = $secure_secret . $vpc_AccessCode . $migs_vpc_Amount . $vpc_Command . $vpc_Locale . $migs_transaction_id . $migs_merchant_id . $vpc_OrderInfo . $vpc_ReturnURL . $vpc_Version;
                
                if($migs_vpc_TxnResponseCode <='5') {
                    echo $migs_vpc_TxnResponseCode;
                	switch ($migs_vpc_TxnResponseCode) {
						case '0':
							$order_status_id = $this->config->get('migs_complete_status_id');
							$comment = 'Transaction Successful';
							break;
						case '1':
							$order_status_id = '10';
							$comment = 'Transaction could not be processed ';
							break;
						case '2':
							$order_status_id = '8';
							$comment = 'Transaction Declined - Contact Issuing Bank ';
							break;
						case '3':
							$order_status_id = '8';
							$comment = 'Transaction Declined - Contact Issuing Bank ';
							break;
						case '4':
							$order_status_id = '8';
							$comment = 'Transaction Declined - Contact Issuing Bank ';
							break;
						case '5':
							$order_status_id = '8';
							$comment = 'Transaction Declined - Contact Issuing Bank ';
							break;
						default:
						    $order_status_id = '1';
							$comment = 'Pending Transaction';
					}
   					if(strtoupper(md5($md5HashData)) == strtoupper($migs_vpc_SecureHash)){
					    $this->model_payment_migs->logger('ORDER Successful: Order_id:'.$migs_transaction_id.' Reason: '.$migs_vpc_Message);
					    $this->load->model('checkout/order');
					    /*$commen  = "Credit Card Order Information" . "\n\n";
						$commen .= "Order ID:".$order_id. "\n";
						$commen .= "Reciept No:".$this->request->get['vpc_ReceiptNo']. "\n";
						$commen .= "Transaction No:".$this->request->get['vpc_TransactionNo']. "\n\n";
						$commen .= "Order Status:".$comment. "\n";*/
						$this->model_checkout_order->addOrderHistory($order_id, $order_status_id);
						$this->response->redirect($this->url->link('checkout/success'));    
					} else {
						$this->model_payment_migs->logger('ORDER FAIL: An error Occured:- Order_id:'.$migs_transaction_id.' Reason: '.$migs_vpc_Message);

						/*$comment = "Transaction Failed: Security do not match, Possible tampering";
						$this->load->model('checkout/order');
					    $commen  = "Credit Card Order Information" . "\n\n";
						$commen .= "Order ID:".$order_id. "\n";
						$commen .= "Reciept No:".$this->request->get['vpc_ReceiptNo']. "\n";
						$commen .= "Transaction No:".$this->request->get['vpc_TransactionNo']. "\n\n";
						$commen .= "Order Status:".$comment. "\n";*/
						$this->model_checkout_order->addOrderHistory($order_id, $order_status_id);
						$this->response->redirect($this->url->link('checkout/failure'));
					}
				} else {
						$this->model_payment_migs->logger('ORDER FAIL: An error Occured:- Order_id:'.$migs_transaction_id.' Reason:'.$migs_vpc_TxnResponseCode.' - '.$migs_vpc_Message);
						$this->response->redirect($this->url->link('checkout/failure'));  	
                }
				
			
		} else {
			    $this->model_payment_migs->logger('ORDER FAIL: An error Occured:- Order_id:'.$migs_transaction_id.' Reason:'.$migs_vpc_TxnResponseCode.' - '.$migs_vpc_Message);
				$this->response->redirect($this->url->link('checkout/failure'));
		}
	}
}
