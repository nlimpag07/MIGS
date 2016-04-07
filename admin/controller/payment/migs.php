<?php
class ControllerPaymentMigs extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('payment/migs');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('migs', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL'));
		}

		$data['heading_title'] = $this->language->get('heading_title');

		$data['text_edit'] = $this->language->get('text_edit');
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		$data['text_all_zones'] = $this->language->get('text_all_zones');
		$data['text_yes'] = $this->language->get('text_yes');
		$data['text_no'] = $this->language->get('text_no');
		$data['text_authorization'] = $this->language->get('text_authorization');
		$data['text_sale'] = $this->language->get('text_sale');

		$data['entry_merchant_id'] = $this->language->get('entry_merchant_id');
		$data['entry_access_code'] = $this->language->get('entry_access_code');
		$data['entry_secret_key'] = $this->language->get('entry_secret_key');
		$data['entry_test'] = $this->language->get('entry_test');
		$data['entry_payment_url'] = $this->language->get('entry_payment_url');
		$data['entry_total'] = $this->language->get('entry_total');
		$data['entry_order_status'] = $this->language->get('entry_order_status');
		$data['entry_geo_zone'] = $this->language->get('entry_geo_zone');
		$data['entry_status'] = $this->language->get('entry_status');
		$data['entry_sort_order'] = $this->language->get('entry_sort_order');

		$data['help_test'] = $this->language->get('help_test');
		$data['help_total'] = $this->language->get('help_total');

		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['merchant_id'])) {
			$data['error_merchant_id'] = $this->error['merchant_id'];
		} else {
			$data['error_merchant_id'] = '';
		}

		if (isset($this->error['access_code'])) {
			$data['error_access_code'] = $this->error['access_code'];
		} else {
			$data['error_access_code'] = '';
		}

		if (isset($this->error['secret_key'])) {
			$data['error_secret_key'] = $this->error['secret_key'];
		} else {
			$data['error_secret_key'] = '';
		}
		if (isset($this->error['payment_url'])) {
			$data['error_payment_url'] = $this->error['payment_url'];
		} else {
			$data['error_payment_url'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'token=' . $this->session->data['token'], 'SSL')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_payment'),
			'href' => $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('payment/migs', 'token=' . $this->session->data['token'], 'SSL')
		);

		$data['action'] = $this->url->link('payment/migs', 'token=' . $this->session->data['token'], 'SSL');

		$data['cancel'] = $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL');

		if (isset($this->request->post['migs_merchant_id'])) {
			$data['migs_merchant_id'] = $this->request->post['migs_merchant_id'];
		} else {
			$data['migs_merchant_id'] = $this->config->get('migs_merchant_id');
		}

		if (isset($this->request->post['migs_access_code'])) {
			$data['migs_access_code'] = $this->request->post['migs_access_code'];
		} else {
			$data['migs_access_code'] = $this->config->get('migs_access_code');
		}

		if (isset($this->request->post['migs_secret_key'])) {
			$data['migs_secret_key'] = $this->request->post['migs_secret_key'];
		} else {
			$data['migs_secret_key'] = $this->config->get('migs_secret_key');
		}

		if (isset($this->request->post['migs_test'])) {
			$data['migs_test'] = $this->request->post['migs_test'];
		} else {
			$data['migs_test'] = $this->config->get('migs_test');
		}

		if (isset($this->request->post['migs_method'])) {
			$data['migs_payment_url'] = $this->request->post['migs_payment_url'];
		} else {
			$data['migs_payment_url'] = $this->config->get('migs_payment_url');
		}

		if (isset($this->request->post['migs_total'])) {
			$data['migs_total'] = $this->request->post['migs_total'];
		} else {
			$data['migs_total'] = $this->config->get('migs_total');
		}

		if (isset($this->request->post['migs_order_status_id'])) {
			$data['migs_order_status_id'] = $this->request->post['migs_order_status_id'];
		} else {
			$data['migs_order_status_id'] = $this->config->get('migs_order_status_id');
		}

		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		if (isset($this->request->post['migs_geo_zone_id'])) {
			$data['migs_geo_zone_id'] = $this->request->post['migs_geo_zone_id'];
		} else {
			$data['migs_geo_zone_id'] = $this->config->get('migs_geo_zone_id');
		}

		$this->load->model('localisation/geo_zone');

		$data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

		if (isset($this->request->post['migs_status'])) {
			$data['migs_status'] = $this->request->post['migs_status'];
		} else {
			$data['migs_status'] = $this->config->get('migs_status');
		}

		if (isset($this->request->post['migs_sort_order'])) {
			$data['migs_sort_order'] = $this->request->post['migs_sort_order'];
		} else {
			$data['migs_sort_order'] = $this->config->get('migs_sort_order');
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('payment/migs.tpl', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'payment/migs')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!$this->request->post['migs_merchant_id']) {
			$this->error['merchant_id'] = $this->language->get('error_merchant_id');
		}

		if (!$this->request->post['migs_access_code']) {
			$this->error['access_code'] = $this->language->get('error_access_code');
		}

		if (!$this->request->post['migs_secret_key']) {
			$this->error['secret_key'] = $this->language->get('error_secret_key');
		}

		return !$this->error;
	}
}