<?php 

/* Cardinity payment for OpenCart
** Cardinity API 1.0v
** Author: ELPAS LT, Ltd, Gintas Korsakas
** http://open-cart.lt
*/

class ControllerPaymentCardinity extends Controller {
	private $error = array(); 

	public function index() { 
		$this->language->load('payment/cardinity');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('cardinity', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL'));
		}

		$data['heading_title'] = $this->language->get('heading_title');

		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		$data['text_all_zones'] = $this->language->get('text_all_zones');
		$data['text_yes'] = $this->language->get('text_yes');
		$data['text_no'] = $this->language->get('text_no');

		$data['entry_order_status'] = $this->language->get('entry_order_status');		
		$data['entry_total'] = $this->language->get('entry_total');	
		$data['help_total'] = $this->language->get('help_total');	
		$data['entry_geo_zone'] = $this->language->get('entry_geo_zone');
		$data['entry_status'] = $this->language->get('entry_status');
		$data['entry_sort_order'] = $this->language->get('entry_sort_order');
		$data['entry_key'] = $this->language->get('entry_key');	
		$data['entry_secret'] = $this->language->get('entry_secret');	


		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text'      => $this->language->get('text_home'),
			'href'      => $this->url->link('common/home', 'token=' . $this->session->data['token'], 'SSL'),
			'separator' => false
		);

		$data['breadcrumbs'][] = array(
			'text'      => $this->language->get('text_payment'),
			'href'      => $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL'),
			'separator' => ' :: '
		);

		$data['breadcrumbs'][] = array(
			'text'      => $this->language->get('heading_title'),
			'href'      => $this->url->link('payment/cardinity', 'token=' . $this->session->data['token'], 'SSL'),
			'separator' => ' :: '
		);

		$data['action'] = $this->url->link('payment/cardinity', 'token=' . $this->session->data['token'], 'SSL');

		$data['cancel'] = $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL');	

		
		if (isset($this->request->post['cardinity_key'])) {
			$data['cardinity_key'] = $this->request->post['cardinity_key'];
		} else {
			$data['cardinity_key'] = $this->config->get('cardinity_key'); 
		}

		if (isset($this->request->post['cardinity_secret'])) {
			$data['cardinity_secret'] = $this->request->post['cardinity_secret'];
		} else {
			$data['cardinity_secret'] = $this->config->get('cardinity_secret'); 
		}

		if (isset($this->request->post['cardinity_total'])) {
			$data['cardinity_total'] = $this->request->post['cardinity_total'];
		} else {
			$data['cardinity_total'] = $this->config->get('cardinity_total'); 
		}

		if (isset($this->request->post['cardinity_order_status_id'])) {
			$data['cardinity_order_status_id'] = $this->request->post['cardinity_order_status_id'];
		} else {
			$data['cardinity_order_status_id'] = $this->config->get('cardinity_order_status_id'); 
		} 

		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		if (isset($this->request->post['cardinity_geo_zone_id'])) {
			$data['cardinity_geo_zone_id'] = $this->request->post['cardinity_geo_zone_id'];
		} else {
			$data['cardinity_geo_zone_id'] = $this->config->get('cardinity_geo_zone_id'); 
		} 

		$this->load->model('localisation/geo_zone');						

		$data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

		if (isset($this->request->post['cardinity_status'])) {
			$data['cardinity_status'] = $this->request->post['cardinity_status'];
		} else {
			$data['cardinity_status'] = $this->config->get('cardinity_status');
		}

		if (isset($this->request->post['cardinity_sort_order'])) {
			$data['cardinity_sort_order'] = $this->request->post['cardinity_sort_order'];
		} else {
			$data['cardinity_sort_order'] = $this->config->get('cardinity_sort_order');
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		
		$this->response->setOutput($this->load->view('payment/cardinity.tpl', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'payment/cardinity')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!$this->error) {
			return true;
		} else {
			return false;
		}	
	}

	public function install() {
		$this->db->query("
			CREATE TABLE IF NOT EXISTS `".DB_PREFIX."cardinity` (
			 `cardinity_id` bigint(20) NOT NULL AUTO_INCREMENT,
			 `order_id` bigint(20) NOT NULL,
			 `payment_id` varchar(255) NOT NULL,
			 `store_id` int(11) NOT NULL DEFAULT '0',
			 `date_added` datetime NOT NULL,
			 PRIMARY KEY (`cardinity_id`),
			 KEY `order_id` (`order_id`)
			) DEFAULT CHARSET=utf8
		");
	}


}
?>