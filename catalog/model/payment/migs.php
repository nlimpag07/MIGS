<?php
class ModelPaymentMigs extends Model {
	public function getMethod($address, $total) {
		$this->language->load('payment/migs');

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$this->config->get('pp_pro_geo_zone_id') . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");

		if ($this->config->get('migs_total') > 0 && $this->config->get('migs_total') > $total) {
			$status = false;
		} elseif (!$this->config->get('migs_geo_zone_id')) {
			$status = true;
		} elseif ($query->num_rows) {
			$status = true;
		} else {
			$status = false;
		}

		$method_data = array();

		if ($status) {
			$method_data = array(
				'code'       => 'migs',
				'title'      => $this->language->get('text_title'),
				'terms'      => '',
				'sort_order' => $this->config->get('migs_sort_order')
			);
		}

		return $method_data;
	}
	public function logger($message) {
		//if ($this->config->get('migs_debug') == 1) {
			$log = new Log('migs.log');
			$backtrace = debug_backtrace();
			$log->write('Origin: ' . $backtrace[1]['class'] . '::' . $backtrace[1]['function']);
			$log->write(print_r($message, 1));
		//}
	}
}