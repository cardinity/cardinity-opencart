<?php
class ControllerExtensionPaymentCardinity extends Controller
{

	public function index()
	{

		$this->setSessionIdInCookie();

		$this->load->language('extension/payment/cardinity');
		/**
		 * Check if external payment option is available,
		 * if so, then proceed with external checkout options.
		 */
		if ($this->config->get('payment_cardinity_external') == 1) {
			$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

			//for external callback
			//$this->setSessionIdInCookie();

			if ($order_info) {
				$this->load->model('extension/payment/cardinity');
				$data['amount'] = number_format($order_info['total'], 2, '.', '');
				$data['currency'] = $order_info['currency_code'];
				$data['country'] = $order_info['shipping_iso_code_2'];
				//our gateway wont accept id less than 3 digit
				$orderId = $this->session->data['order_id'];
				if ($orderId < 100) {
					$formattedOrderId = str_pad($orderId, 3, '0', STR_PAD_LEFT);
				} else {
					$formattedOrderId = $orderId;
				}
				$data['order_id'] = $formattedOrderId; //$this->session->data['order_id'];
				$data['description'] = 'OC' . $this->session->data['order_id'];
				$data['return_url'] = $this->url->link('extension/payment/cardinity/externalPaymentCallback', '', true);
				$attributes = $this->model_extension_payment_cardinity->createExternalPayment($this->config->get('payment_cardinity_project_key'), $this->config->get('payment_cardinity_project_secret'), $data);

				//these two are for website not for api
				$attributes['button_confirm'] = $this->language->get('button_confirm');
				$attributes['text_loading'] = $this->language->get('text_loading');

				return $this->load->view('extension/payment/cardinity_external', $attributes);
			}
			return $this->load->view('extension/payment/cardinity_external_error');
		}

		$data['months'] = array();

		for ($i = 1; $i <= 12; $i++) {
			$data['months'][] = array(
				'text'  => strftime('%B', mktime(0, 0, 0, $i, 1, 2000)),
				'value' => sprintf('%02d', $i)
			);
		}

		$today = getdate();

		$data['years'] = array();

		for ($i = $today['year']; $i < $today['year'] + 11; $i++) {
			$data['years'][] = array(
				'text'  => strftime('%Y', mktime(0, 0, 0, 1, 1, $i)),
				'value' => strftime('%Y', mktime(0, 0, 0, 1, 1, $i))
			);
		}

		//these two are for website not for api
		$data['button_confirm'] = $this->language->get('button_confirm');
		$data['text_loading'] = $this->language->get('text_loading');

		return $this->load->view('extension/payment/cardinity', $data);
	}

	public function setSessionIdInCookie()
	{

		$expire = time() + (10 * 60); // 10 min from now
		$name = 'cardinitySessionId';
		$value = $this->session->getId();

		$path = ini_get('session.cookie_path') ?? '/';
		$domain = ini_get('session.cookie_domain') ?? '';

		$secure = true;
		$samesite = 'None';

		//$secure = false;
		//$samesite = 'Lax';

		//only usin post 7.3 syntax as opencart 3 minimum requirement is 7.3
		setcookie($name, $value, [
			'expires' => $expire,
			'path' => $path,
			'domain' => $domain,
			'secure' => $secure,
			'httponly' => false,
			'samesite' => $samesite,
		]);

		$this->testLog($this->session->getId());
		$this->testLog(print_r($_COOKIE, true));
	}

	public function externalPaymentCallback()
	{
		$this->load->language('extension/payment/cardinity');		
		$this->load->model('extension/payment/cardinity');

		//restore session based on sessionId from cookie
		$this->session->start($_COOKIE['cardinitySessionId']);

		$message = '';
		ksort($_POST);

		foreach ($_POST as $key => $value) {
			if ($key == 'signature') continue;
			$message .= $key . $value;
		}

		error_reporting(null);
		$signature = hash_hmac('sha256', $message, $this->config->get('payment_cardinity_project_secret'));
		error_reporting(E_ALL);

		if ($signature == $_POST['signature'] && $_POST['status'] == 'approved') {


			$this->model_extension_payment_cardinity->addOrder(array(
				'order_id'   => $_POST['order_id'],
				'payment_id' =>$_POST['id'],
			));
			$this->model_extension_payment_cardinity->updateOrder(array(
				'payment_status' => 'approved_external',
				'payment_id' =>$_POST['id'],
			));

			$this->logTransaction(array(
				'orderId' => $_POST['order_id'],
				'transactionId' =>  $_POST['id'],
				'3dsVersion' => 'unknown (external)',
				'amount' => $_POST['amount'] ." ".$_POST['currency'],
				'status' => 'approved'
			));
			
			$this->finalizeOrder($_POST);
			$this->response->redirect($this->url->link('checkout/success', '', true));
		} else {

			$this->model_extension_payment_cardinity->addOrder(array(
				'order_id'   => $_POST['order_id'],
				'payment_id' =>$_POST['id'],
			));
			$this->model_extension_payment_cardinity->updateOrder(array(
				'payment_status' => 'failed_external',
				'payment_id' =>$_POST['id'],
			));

			$this->failedOrder("Card was declined", $this->language->get("error_payment_declined"));
			$this->response->redirect($this->url->link('checkout/checkout', '', true));
		}
	}

	public function send()
	{
		$this->load->model('checkout/order');
		$this->load->model('extension/payment/cardinity');

		$this->load->language('extension/payment/cardinity');

		$json = array();

		$json['error'] = $json['success'] = $json['3ds'] = '';

		$payment = false;

		$error = $this->validate();

		if (!$error) {
			$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

			if (strlen($order_info['order_id']) < 2) {
				$order_id = '0' . $order_info['order_id'];
			} else {
				$order_id = $order_info['order_id'];
			}

			if (!empty($order_info['payment_iso_code_2'])) {
				$order_country = $order_info['payment_iso_code_2'];
			} else {
				$order_country = $order_info['shipping_iso_code_2'];
			}

			$payment_data = array(
				'amount'			 => (float)$this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false),
				'currency'			 => $order_info['currency_code'],
				'order_id'			 => $order_id,
				'country'            => $order_country,
				'payment_method'     => 'card',
				'payment_instrument' => array(
					'pan'		=> preg_replace('!\s+!', '', $this->request->post['pan']),
					'exp_year'	=> (int)$this->request->post['exp_year'],
					'exp_month' => (int)$this->request->post['exp_month'],
					'cvc'		=> $this->request->post['cvc'],
					'holder'	=> $this->request->post['holder']
				),
				'threeds2_data' =>  [
                    "notification_url" => $this->url->link('extension/payment/cardinity/threeDSecureCallbackV2', '', true),
                    "browser_info" => [
                        "accept_header" => "text/html",
                        "browser_language" => $this->request->post['browser_language'],
                        "screen_width" => (int)$this->request->post['screen_width'],
                        "screen_height" => (int)$this->request->post['screen_height'],
                        'challenge_window_size' => "full-screen",
                        "user_agent" => $_SERVER['HTTP_USER_AGENT'],
                        "color_depth" => (int)$this->request->post['color_depth'],
                        "time_zone" => (int)$this->request->post['time_zone']
                    ],
                ],
			);

			try {
				$payment = $this->model_extension_payment_cardinity->createPayment($this->config->get('payment_cardinity_key'), $this->config->get('payment_cardinity_secret'), $payment_data);
			} catch (Cardinity\Exception\Declined $exception) {
				$this->failedOrder($this->language->get('error_payment_declined'), $this->language->get('error_payment_declined'));

				$json['redirect'] = $this->url->link('checkout/checkout', '', true);
			} catch (Exception $exception) {
				$this->failedOrder();

				$json['redirect'] = $this->url->link('checkout/checkout', '', true);
			}

			$successful_order_statuses = array(
				'approved',
				'pending'
			);

			if ($payment) {

				if (!in_array($payment->getStatus(), $successful_order_statuses)) {
					$this->failedOrder($payment->getStatus());

					$json['redirect'] = $this->url->link('checkout/checkout', '', true);
				} else {

					$this->model_extension_payment_cardinity->addOrder(array(
						'order_id'   => $this->session->data['order_id'],
						'payment_id' => $payment->getId()
					));

					if ($payment->getStatus() == 'pending') {

						$this->testLog("pay obj".print_r($payment, true));
						$this->testLog("is v2 ".$payment->isThreedsV2());
						//exit();

						if($payment->isThreedsV2() && !$payment->isThreedsV1()){
							//3dsv2
							$authorization_information = $payment->getThreeDS2Data();

							//setSessionIdInCookie
							$this->setSessionIdInCookie();

							$encryption_data = array(
								'order_id' => $this->session->data['order_id'],
								'secret'   => $this->config->get('payment_cardinity_secret')
							);

							error_reporting(0);
							$hash = $this->encryption->encrypt($this->config->get('config_encryption'), json_encode($encryption_data));
							error_reporting(E_ALL);

							$json['3dsv2'] = array(
								'acs_url'   => $authorization_information->getAcsUrl(),
								'creq'   	=> $authorization_information->getCreq(),
								'threeDSSessionData' => $payment->getOrderId(),
								'hash'    	=> $hash
							);

							$this->model_extension_payment_cardinity->updateOrder(array(
								'payment_status'   => 'pending_3dsv2',
								'payment_id' => $payment->getId()
							));

						}else{
							//3ds
							$authorization_information = $payment->getAuthorizationInformation();

							//setSessionIdInCookie
							$this->setSessionIdInCookie();

							$encryption_data = array(
								'order_id' => $this->session->data['order_id'],
								'secret'   => $this->config->get('payment_cardinity_secret')
							);

							error_reporting(0);
							$hash = $this->encryption->encrypt($this->config->get('config_encryption'), json_encode($encryption_data));
							error_reporting(E_ALL);

							$json['3ds'] = array(
								'url'     => $authorization_information->getUrl(),
								'PaReq'   => $authorization_information->getData(),
								'TermUrl' => $this->url->link('extension/payment/cardinity/threeDSecureCallback', '', true),
								'hash'    => $hash
							);

							$this->model_extension_payment_cardinity->updateOrder(array(
								'payment_status'   => 'pending_3dsv1',
								'payment_id' => $payment->getId()
							));
						}


					} elseif ($payment->getStatus() == 'approved') {

						$this->model_extension_payment_cardinity->updateOrder(array(
							'payment_status'   => 'approved_non3ds',
							'payment_id' => $payment->getId()
						));

						$this->logTransaction(array(
							'orderId' => $this->session->data['order_id'],
							'transactionId' =>  $payment->getId(),
							'3dsVersion' => 'none',
							'amount' => $payment->getAmount()." ".$payment->getCurrency(),
							'status' => 'approved'
						));

						
						$this->finalizeOrder($payment);

						$json['redirect'] = $this->url->link('checkout/success', '', true);
					}
				}
			}
		} else {
			$json['error'] = $error;
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function threeDSecureForm()
	{
		$this->load->model('extension/payment/cardinity');

		$this->load->language('extension/payment/cardinity');

		$success = false;
		$redirect = false;

		$encryption_data = array(
			'order_id' => $this->session->data['order_id'],
			'secret'   => $this->config->get('payment_cardinity_secret')
		);

		error_reporting(0);
		$hash = $this->encryption->encrypt($this->config->get('config_encryption'), json_encode($encryption_data));
		error_reporting(E_ALL);

		if (hash_equals($hash, $this->request->post['hash'])) {
			$success = true;

			$data['url'] = $this->request->post['url'];
			$data['PaReq'] = $this->request->post['PaReq'];
			$data['TermUrl'] = $this->request->post['TermUrl'];
			$data['MD'] = $hash;
		} else {
			$this->failedOrder($this->language->get('error_invalid_hash'));

			$redirect = $this->url->link('checkout/checkout', '', true);
		}

		$data['success'] = $success;
		$data['redirect'] = $redirect;

		$this->response->setOutput($this->load->view('extension/payment/cardinity_3ds', $data));
	}

	public function threeDSecureCallback()
	{

		//restore session based on sessionId from cookie
		$this->session->start($_COOKIE['cardinitySessionId']);

		$this->load->model('extension/payment/cardinity');
		$this->load->language('extension/payment/cardinity');

		$success = false;

		$error = '';

		$encryption_data = array(
			'order_id' => $this->session->data['order_id'],
			'secret'   => $this->config->get('payment_cardinity_secret')
		);

		error_reporting(0);
		$hash = $this->encryption->encrypt($this->config->get('config_encryption'), json_encode($encryption_data));
		error_reporting(E_ALL);

		//proper hash found on callback
		if (hash_equals($hash, $this->request->post['MD'])) {
			$order = $this->model_extension_payment_cardinity->getOrder($encryption_data['order_id']);

			if ($order && $order['payment_id']) {
				$payment = $this->model_extension_payment_cardinity->finalizePayment($this->config->get('payment_cardinity_key'), $this->config->get('payment_cardinity_secret'), $order['payment_id'], $this->request->post['PaRes']);

				if ($payment && $payment->getStatus() == 'approved') {
					$success = true;
				} else {

					$this->model_extension_payment_cardinity->updateOrder(array(
						'payment_status'   => 'failed_3dsv1',
						'payment_id' => $order['payment_id']
					));

					$error = $this->language->get('error_finalizing_payment');
				}
			} else {
				$error = $this->language->get('error_unknown_order_id');
			}
		} else {
			$error = $this->language->get('error_invalid_hash');
		}

		if ($success) {

			$this->model_extension_payment_cardinity->updateOrder(array(
				'payment_status'   => 'approved_3dsv1',
				'payment_id' => $payment->getId()
			));

			$this->logTransaction(array(
				'orderId' => $this->session->data['order_id'],
				'transactionId' =>  $payment->getId(),
				'3dsVersion' => 'v1',
				'amount' => $payment->getAmount()." ".$payment->getCurrency(),
				'status' => 'approved'
			));

			$this->finalizeOrder($payment);

			$this->response->redirect($this->url->link('checkout/success', '', true));
		} else {

			
			$this->failedOrder($error);

			$this->response->redirect($this->url->link('checkout/checkout', '', true));
		}
	}


	public function threeDSecureFormV2()
	{
		$this->load->model('extension/payment/cardinity');

		$this->load->language('extension/payment/cardinity');

		$success = false;
		$redirect = false;

		$encryption_data = array(
			'order_id' => $this->session->data['order_id'],
			'secret'   => $this->config->get('payment_cardinity_secret')
		);

		error_reporting(0);
		$hash = $this->encryption->encrypt($this->config->get('config_encryption'), json_encode($encryption_data));
		error_reporting(E_ALL);

		if (hash_equals($hash, $this->request->post['hash'])) {
			$success = true;

			$data['acs_url'] = $this->request->post['acs_url'];
			$data['creq'] = $this->request->post['creq'];
			$data['threeDSSessionData'] = $hash;
		} else {
			$this->failedOrder($this->language->get('error_invalid_hash'));

			$redirect = $this->url->link('checkout/checkout', '', true);
		}

		$data['success'] = $success;
		$data['redirect'] = $redirect;

		$this->response->setOutput($this->load->view('extension/payment/cardinity_3dsv2', $data));
	}

	public function threeDSecureCallbackV2()
	{

		//restore session based on sessionId from cookie
		$this->session->start($_COOKIE['cardinitySessionId']);

		$this->load->model('extension/payment/cardinity');
		$this->load->language('extension/payment/cardinity');

		$success = false;

		$error = '';

		$encryption_data = array(
			'order_id' => $this->session->data['order_id'],
			'secret'   => $this->config->get('payment_cardinity_secret')
		);

		error_reporting(0);
		$hash = $this->encryption->encrypt($this->config->get('config_encryption'), json_encode($encryption_data));
		error_reporting(E_ALL);


		//proper hash found on callback
		if (hash_equals($hash, $this->request->post['threeDSSessionData'])) {
			$order = $this->model_extension_payment_cardinity->getOrder($encryption_data['order_id']);

			if ($order && $order['payment_id']) {


				if($order && strpos($order['payment_status'], 'approved') !== false){
					//payment already finalized
					$success = true;	
				}else{

					$payment = $this->model_extension_payment_cardinity->finalize3dv2Payment($this->config->get('payment_cardinity_key'), $this->config->get('payment_cardinity_secret'), $order['payment_id'], $this->request->post['cres']);

					if ($payment && $payment->getStatus() == 'approved') {

						$this->model_extension_payment_cardinity->updateOrder(array(
							'payment_status'   => 'approved_3dsv2',
							'payment_id' => $payment->getId()
						));
	
						$this->logTransaction(array(
							'orderId' => $this->session->data['order_id'],
							'transactionId' =>  $payment->getId(),
							'3dsVersion' => 'v2',
							'amount' => $payment->getAmount()." ".$payment->getCurrency(),
							'status' => 'approved'
						));
	
						$success = true;
					} elseif ($payment && $payment->getStatus() == 'pending') {
						//3dsv2 failed but v1 is pending
	
						$this->model_extension_payment_cardinity->updateOrder(array(
							'payment_status'   => 'fallback_3dsv1',
							'payment_id' => $payment->getId()
						));
	
						//3ds v1 retry
						$authorization_information = $payment->getAuthorizationInformation();
	
						//setSessionIdInCookie
						$this->setSessionIdInCookie();
	
	
						/*3d sec form */
	
						$encryption_data = array(
							'order_id' => $this->session->data['order_id'],
							'secret'   => $this->config->get('payment_cardinity_secret')
						);
	
						error_reporting(0);
						$hash = $this->encryption->encrypt($this->config->get('config_encryption'), json_encode($encryption_data));
						error_reporting(E_ALL);
	
						$data['url'] = $authorization_information->getUrl();
						$data['PaReq'] = $authorization_information->getData();
						$data['TermUrl'] = $this->url->link('extension/payment/cardinity/threeDSecureCallback', '', true);
						$data['MD'] = $hash;
						$data['success'] = true;
						$data['redirect'] = false;
	
	
						echo '
						<h3>Threeds v2 validation failed, retrying for v1 in 3 seconds.</h3>
						<p>If browser does not redirect press "Proceed"</p>
						<form id="ThreeDForm" name="ThreeDForm" method="POST" action="'.$data['url'].'">
							<input type="hidden" name="PaReq" value="'.$data['PaReq'] .'" />
							<input type="hidden" name="TermUrl" value="'.$data['TermUrl'].'" />
							<input type="hidden" name="MD" value="'.$data['MD'].'" />
							<input type="submit" value="Proceed" />
						</form>
						<script type="text/javascript">
							window.onload=function(){
								window.setTimeout(document.ThreeDForm.submit.bind(document.ThreeDForm), 3000);
							};
						</script>';
						exit();
	
	
					} else  {
						$error = $this->language->get('error_finalizing_payment');
					}
				}
								
			} else {
				$error = $this->language->get('error_unknown_order_id');
			}
		} else {
			$error = $this->language->get('error_invalid_hash');
		}

		if ($success) {
			
			$this->finalizeOrder($payment);

			$this->response->redirect($this->url->link('checkout/success', '', true));
		} else {

			$this->failedOrder($error);

			$this->response->redirect($this->url->link('checkout/checkout', '', true));
		}
	}



	private function finalizeOrder($payment)
	{
		$this->load->model('checkout/order');
		$this->load->model('extension/payment/cardinity');
		$this->load->language('extension/payment/cardinity');

		$this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $this->config->get('payment_cardinity_order_status_id'));

		$this->model_extension_payment_cardinity->log($this->language->get('text_payment_success'));
		$this->model_extension_payment_cardinity->log($payment);
	}

	private function failedOrder($log = null, $alert = null)
	{
		$this->load->language('extension/payment/cardinity');
		$this->load->model('extension/payment/cardinity');
		$this->model_extension_payment_cardinity->log($this->language->get('text_payment_failed'));

		//either alert or log or seomthing general
		$this->session->data['error'] = $alert ?? $log ?? $this->language->get('error_process_order');

		//if log set use it, or use whatever error has
		$this->model_extension_payment_cardinity->log($log ?? $this->session->data['error']);

		/*
		if ($log) {
			$this->model_extension_payment_cardinity->log($log);
		}

		if ($alert) {
			$this->session->data['error'] = $alert;
		} else {
			$this->session->data['error'] = $this->language->get('error_process_order');
		}*/
	}

	private function validate()
	{
		$this->load->model('checkout/order');
		$this->load->model('extension/payment/cardinity');

		$error = array();

		if (!$this->session->data['order_id']) {
			$error['warning'] = $this->language->get('error_process_order');
		}

		if (!$error) {
			$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

			if (!$order_info) {
				$error['warning'] = $this->language->get('error_process_order');
			}
		}

		if (!in_array($order_info['currency_code'], $this->model_extension_payment_cardinity->getSupportedCurrencies())) {
			$error['warning'] = $this->language->get('error_invalid_currency');
		}

		if (!isset($this->request->post['holder']) || utf8_strlen($this->request->post['holder']) < 1 || utf8_strlen($this->request->post['holder']) > 32) {
			$error['holder'] = true;
		}

		if (!isset($this->request->post['pan']) || utf8_strlen($this->request->post['pan']) < 1 || utf8_strlen($this->request->post['pan']) > 19) {
			$error['pan'] = true;
		}

		if (!isset($this->request->post['pan']) || !is_numeric(preg_replace('!\s+!', '', $this->request->post['pan']))) {
			$error['pan'] = true;
		}

		if (!isset($this->request->post['exp_month']) || !isset($this->request->post['exp_year'])) {
			$error['expiry_date'] = true;
		} else {
			$expiry = new DateTime();
			$expiry->setDate($this->request->post['exp_year'], $this->request->post['exp_month'], '1');
			$expiry->modify('+1 month');
			$expiry->modify('-1 day');

			$now = new DateTime();

			if ($expiry < $now) {
				$error['expiry_date'] = true;
			}
		}

		if (!isset($this->request->post['cvc']) || utf8_strlen($this->request->post['cvc']) < 1 || utf8_strlen($this->request->post['cvc']) > 4) {
			$error['cvc'] = true;
		}

		return $error;
	}

	//debugging tool
	public function testLog($string)
	{
		$this->load->model('extension/payment/cardinity');
		$this->model_extension_payment_cardinity->log($string . "");
	}

	//debugging tool
	public function logTransaction($array)
	{
		$this->testLog("Logging transaction");
		$this->testLog(print_r($array, true));
		$this->load->model('extension/payment/cardinity');
		$this->model_extension_payment_cardinity->logTransaction($array);
	}
}
