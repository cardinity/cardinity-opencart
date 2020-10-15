<?php
class ControllerExtensionPaymentCardinity extends Controller
{
	public function index()
	{
		$this->load->language('extension/payment/cardinity');

		// SameSite cookie temporary hot fix
		$this->setSameSiteCookie();

		/**
		 * Check if external payment option is available,
		 * if so, then proceed with external checkout options.
		 */
		if ($this->config->get('cardinity_external') == 1) {
			$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
			if ($order_info) {
				$this->load->model('extension/payment/cardinity');
				$data['amount'] = number_format($order_info['total'], 2, '.', '');
				$data['currency'] = $order_info['currency_code'];
				$data['country'] = $order_info['shipping_iso_code_2'];
				$data['order_id'] = $this->session->data['order_id'];
				$data['description'] = 'OC' . $this->session->data['order_id'];
				$data['return_url'] = $this->url->link('extension/payment/cardinity/externalPaymentCallback');
				$attributes = $this->model_extension_payment_cardinity->createExternalPayment($this->config->get('cardinity_project_key'), $this->config->get('cardinity_project_secret'), $data);
				return $this->load->view('extension/payment/cardinity_external', $attributes);
			}
			return $this->load->view('extension/payment/cardinity_external_error');
		}


		$data['entry_holder'] = $this->language->get('entry_holder');
		$data['entry_pan'] = $this->language->get('entry_pan');
		$data['entry_expires'] = $this->language->get('entry_expires');
		$data['entry_expiration_month'] = $this->language->get('entry_expiration_month');
		$data['entry_expiration_year'] = $this->language->get('entry_expiration_year');
		$data['entry_cvc'] = $this->language->get('entry_cvc');

		$data['button_confirm'] = $this->language->get('button_confirm');


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

		return $this->load->view('extension/payment/cardinity', $data);
	}

	public function setSameSiteCookie()
	{
		$name = 'SameSite';
		$value = $this->session->getId();
		$expire = time() + 60 * 30;
		$path = ini_get('session.cookie_path');
		if($path == null){
			$path = '';
		}
		$samesite = 'Lax';//'none';
		$domain = ini_get('session.cookie_domain');
		if($domain == null){
			$domain = '';
		}
		$httponly = true;
		$secure = false; //true;

		if (PHP_VERSION_ID < 70300) {
			setcookie(
				$name,
				$value,
				$expire,
				//"$path; SameSite=$samesite",
				$path, //testing wtihtou https
				$domain,
				$secure,
				$httponly
			);
		} else {
			setcookie($name, $value, [
				'expires' => $expire,
				'path' => $path,
				'domain' => $domain,
				'samesite' => $samesite,
				'secure' => $secure,
				'httponly' => $httponly,
			]);
		}

		$this->testLog(print_r($_COOKIE, true));
	}

	public function externalPaymentCallback()
	{
		$this->load->language('extension/payment/cardinity');

		//restore session from cookie
		$this->session->start('SameSite',$_COOKIE['SameSite']);


		$message = '';
		ksort($_POST);

		foreach ($_POST as $key => $value) {
			if ($key == 'signature') continue;
			$message .= $key . $value;
		}

		$signature = hash_hmac('sha256', $message, $this->config->get('cardinity_project_secret'));

		if ($signature == $_POST['signature'] && $_POST['status'] == 'approved') {
			$this->finalizeOrder($_POST);
			$this->response->redirect($this->url->link('checkout/success', '', true));
		} else {

			$this->testLog($this->language->get("error_payment_declined"));
			$this->failedOrder("Card was declined",$this->language->get("error_payment_declined"));
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
			);

			try {
				$payment = $this->model_extension_payment_cardinity->createPayment($this->config->get('cardinity_key'), $this->config->get('cardinity_secret'), $payment_data);
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
						//3ds
						$authorization_information = $payment->getAuthorizationInformation();

						$encryption_data = array(
							'order_id' => $this->session->data['order_id'],
							'secret'   => $this->config->get('cardinity_secret')
						);

						$hash = $this->encryption->encrypt(json_encode($encryption_data));

						$json['3ds'] = array(
							'url'     => $authorization_information->getUrl(),
							'PaReq'   => $authorization_information->getData(),
							'TermUrl' => $this->url->link('extension/payment/cardinity/threeDSecureCallback', '', true),
							'hash'    => $hash
						);
					} elseif ($payment->getStatus() == 'approved') {
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
			'secret'   => $this->config->get('cardinity_secret')
		);

		$hash = $this->encryption->encrypt(json_encode($encryption_data));

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
		$this->load->model('extension/payment/cardinity');

		$this->load->language('extension/payment/cardinity');

		$success = false;

		$error = '';

		//restore session from cookie
		$this->session->start('SameSite',$_COOKIE['SameSite']);
		$this->testLog("Cookie samesite value :".$_COOKIE['SameSite']);

		$encryption_data = array(
			'order_id' => $this->session->data['order_id'],
			'secret'   => $this->config->get('cardinity_secret')
		);

		$hash = $this->encryption->encrypt(json_encode($encryption_data));

		if (hash_equals($hash, $this->request->post['MD'])) {

			if($this->request->post['PaRes'] == '3d-fail'){
				//3ds attempted but authentication failed
				//process as failed order
				$this->testLog("3ds auth failed");//TODO add lang

				$this->failedOrder($this->language->get('error_3ds_failed'),$this->language->get('error_3ds_failed'));

				$error = $this->language->get('error_3ds_failed');
				$json['error'] = $error;
				$json['redirect'] = $this->url->link('checkout/checkout', '', true);
				$this->response->addHeader('Content-Type: application/json');
				$this->response->setOutput(json_encode($json));

			}else{
				//3ds success
				$order = $this->model_extension_payment_cardinity->getOrder($encryption_data['order_id']);

				if ($order && $order['payment_id']) {
					$payment = $this->model_extension_payment_cardinity->finalizePayment($this->config->get('cardinity_key'), $this->config->get('cardinity_secret'), $order['payment_id'], $this->request->post['PaRes']);

					if ($payment && $payment->getStatus() == 'approved') {
						$success = true;
					} else {
						$error = $this->language->get('error_finalizing_payment');
					}
				} else {
					$error = $this->language->get('error_unknown_order_id');
				}
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

		$this->load->language('extension/payment/cardinity');

		$this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $this->config->get('cardinity_order_status_id'));

		$this->model_extension_payment_cardinity->log($this->language->get('text_payment_success'));
		$this->model_extension_payment_cardinity->log($payment);
	}

	private function failedOrder($log = null, $alert = null)
	{

		$this->load->language('extension/payment/cardinity');

		$this->model_extension_payment_cardinity->log($this->language->get('text_payment_failed'));

		if ($log) {
			$this->model_extension_payment_cardinity->log($log);
		}

		if ($alert != null) {
			$this->session->data['error'] = $alert;
		} else if($log !=null){
			$this->session->data['error'] = $log;
		}else{
			$this->session->data['error'] = $this->language->get('error_process_order');
		}

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

	public function testLog($string){
		$this->load->model('extension/payment/cardinity');
		$this->model_extension_payment_cardinity->log( $string."");
	}
}
