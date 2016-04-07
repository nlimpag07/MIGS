<?php

class ModelPaymentMigs extends Model {

	public function install() {
		$this->db->query("
			CREATE TABLE `" . DB_PREFIX . "migs_order` (
				`migs_order_id` INT(11) NOT NULL AUTO_INCREMENT,
				`order_id` int(11) NOT NULL,
				`migs_transaction_id` varchar(255) NOT NULL,
				`date_added` DATETIME NOT NULL,
				`modified` DATETIME NOT NULL,
				`refund_status` INT(1) DEFAULT NULL,
				`currency_code` CHAR(3) NOT NULL,
				`total` DECIMAL( 10, 2 ) NOT NULL,
				KEY `migs_transaction_id` (`migs_transaction_id`),
				PRIMARY KEY `migs_order_id` (`migs_order_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
		");

		$this->db->query("
			CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "migs_order_transaction` (
			  `migs_order_transaction_id` INT(11) NOT NULL AUTO_INCREMENT,
			  `migs_order_id` INT(11) NOT NULL,
			  `date_added` DATETIME NOT NULL,
			  `type` ENUM('payment', 'refund') DEFAULT NULL,
			  `amount` DECIMAL( 10, 2 ) NOT NULL,
			  PRIMARY KEY (`migs_order_transaction_id`)
			) ENGINE=MyISAM DEFAULT COLLATE=utf8_general_ci;
			");
	}

	public function uninstall() {
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "migs_order`;");
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "migs_order_transaction`;");
	}

	public function getOrder($order_id) {

		$qry = $this->db->query("SELECT * FROM `" . DB_PREFIX . "migs_order` WHERE `order_id` = '" . (int)$order_id . "' LIMIT 1");

		if ($qry->num_rows) {
			$order = $qry->row;
			$order['transactions'] = $this->getTransactions($order['migs_order_id'], $qry->row['currency_code']);
			return $order;
		} else {
			return false;
		}
	}

	public function getTotalReleased($migs_order_id) {
		$query = $this->db->query("SELECT SUM(`amount`) AS `total` FROM `" . DB_PREFIX . "migs_order_transaction` WHERE `migs_order_id` = '" . (int)$migs_order_id . "' AND (`type` = 'payment' OR `type` = 'refund')");

		return (double)$query->row['total'];
	}

	public function refund($migs_order, $amount) {
		if (!empty($migs_order) && $migs_order['refund_status'] != 1) {
			if ($this->config->get('migs_environment') == 1) {
				$url = 'https://pay.g2a.com/rest/transactions/' . $migs_order['migs_transaction_id'];
			} else {
				$url = 'https://www.test.pay.g2a.com/rest/transactions/' . $migs_order['migs_transaction_id'];
			}

			$refunded_amount = round($amount, 2);

			$string = $migs_order['migs_transaction_id'] . $migs_order['order_id'] . round($migs_order['total'], 2) . $refunded_amount . html_entity_decode($this->config->get('migs_secret'));
			$hash = hash('sha256', $string);

			$fields = array(
				'action' => 'refund',
				'amount' => $refunded_amount,
				'hash' => $hash,
			);

			return $this->sendCurl($url, $fields);
		} else {
			return false;
		}
	}

	public function updateRefundStatus($migs_order_id, $status) {
		$this->db->query("UPDATE `" . DB_PREFIX . "migs_order` SET `refund_status` = '" . (int)$status . "' WHERE `migs_order_id` = '" . (int)$migs_order_id . "'");
	}

	private function getTransactions($migs_order_id, $currency_code) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "migs_order_transaction` WHERE `migs_order_id` = '" . (int)$migs_order_id . "'");

		$transactions = array();
		if ($query->num_rows) {
			foreach ($query->rows as $row) {
				$row['amount'] = $this->currency->format($row['amount'], $currency_code, true, true);
				$transactions[] = $row;
			}
			return $transactions;
		} else {
			return false;
		}
	}

	public function addTransaction($migs_order_id, $type, $total) {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "migs_order_transaction` SET `migs_order_id` = '" . (int)$migs_order_id . "',`date_added` = now(), `type` = '" . $this->db->escape($type) . "', `amount` = '" . (double)$total . "'");
	}

	public function getTotalRefunded($migs_order_id) {
		$query = $this->db->query("SELECT SUM(`amount`) AS `total` FROM `" . DB_PREFIX . "migs_order_transaction` WHERE `migs_order_id` = '" . (int)$migs_order_id . "' AND 'refund'");

		return (double)$query->row['total'];
	}

	public function sendCurl($url, $fields) {
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
		curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($fields));

		$auth_hash = hash('sha256', $this->config->get('migs_api_hash') . $this->config->get('migs_username') . html_entity_decode($this->config->get('migs_secret')));
		$authorization = $this->config->get('migs_api_hash') . ";" . $auth_hash;
		curl_setopt(
				$curl, CURLOPT_HTTPHEADER, array(
			"Authorization: " . $authorization
				)
		);

		$response = json_decode(curl_exec($curl));

		curl_close($curl);
		if (is_object($response)) {
			return (string)$response->status;
		} else {
			return str_replace('"', "", $response);
		}
	}

	public function logger($message) {
		if ($this->config->get('migs_debug') == 1) {
			$log = new Log('migs.log');
			$backtrace = debug_backtrace();
			$log->write('Origin: ' . $backtrace[1]['class'] . '::' . $backtrace[1]['function']);
			$log->write(print_r($message, 1));
		}
	}

}
