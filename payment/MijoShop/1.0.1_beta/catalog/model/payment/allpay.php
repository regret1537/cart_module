<?php 
class ModelPaymentAllPay extends Model {
    
    public function getMethod($address) {
        $this->load->language('payment/allpay');
        
        $sql = 'SELECT * from ' . DB_PREFIX . 'zone_to_geo_zone';
        $sql .= ' WHERE `geo_zone_id` = "' . (int)$this->config->get('allpay_geo_zone_id') . '"';
        $sql .= ' AND `country_id` = "' . (int)$address['country_id'] . '"';
        $sql .= ' AND (`zone_id` = "' . (int)$address['zone_id'] . '" OR `zone_id` = "0")';
        
        $query = $this->db->query($sql);
        
        $status = (!$this->config->get('alertpay_geo_zone_id') || $query->num_rows) ? true : false;
                
        $method_data = array();
                
        if ($status) {
            $method_data = array(
                'code' => 'allpay',
                'title' => $this->language->get('text_title'),
                'sort_order' => $this->config->get('allpay_sort_order')
            );
        }
         
        return $method_data;
    }

    public function getPaymentDesc() {
        $this->load->language('payment/allpay');
        $payment_desc = array();
        if ($this->config->get('allpay_payment_credit') == 'on') {
            $payment_desc['Credit'] = $this->language->get('des_payment_credit');
        }
        if ($this->config->get('allpay_payment_credit_03') == 'on') {
            $payment_desc['Credit_03'] = $this->language->get('des_payment_credit_03');
        }
        if ($this->config->get('allpay_payment_credit_06') == 'on') {
            $payment_desc['Credit_06'] = $this->language->get('des_payment_credit_06');
        }
        if ($this->config->get('allpay_payment_credit_12') == 'on') {
            $payment_desc['Credit_12'] = $this->language->get('des_payment_credit_12');
        }
        if ($this->config->get('allpay_payment_credit_18') == 'on') {
            $payment_desc['Credit_18'] = $this->language->get('des_payment_credit_18');
        }
        if ($this->config->get('allpay_payment_credit_24') == 'on') {
            $payment_desc['Credit_24'] = $this->language->get('des_payment_credit_24');
        }
        if ($this->config->get('allpay_payment_webatm') == 'on') {
            $payment_desc['WebATM'] = $this->language->get('des_payment_webatm');
        }
        if ($this->config->get('allpay_payment_atm') == 'on') {
            $payment_desc['ATM'] = $this->language->get('des_payment_atm');
        }
        if ($this->config->get('allpay_payment_cvs') == 'on') {
            $payment_desc['CVS'] = $this->language->get('des_payment_cvs');
        }
        if ($this->config->get('allpay_payment_barcode') == 'on') {
            $payment_desc['BARCODE'] = $this->language->get('des_payment_barcode');
        }
        if ($this->config->get('allpay_payment_alipay') == 'on') {
            $payment_desc['Alipay'] = $this->language->get('des_payment_alipay');
        }
        if ($this->config->get('allpay_payment_tenpay') == 'on') {
            $payment_desc['Tenpay'] = $this->language->get('des_payment_tenpay');
        }
        if ($this->config->get('allpay_payment_topupused') == 'on') {
            $payment_desc['TopUpUsed'] = $this->language->get('des_payment_topupused');
        }
        
        return $payment_desc;
    }
    
    public function invokeExt($ext_dir) {
        $sdk_res = include_once($ext_dir . 'AllPay.Payment.Integration.php');
        $ext_res = include_once($ext_dir . 'allpay_cart_ext.php');
        return ($sdk_res and $ext_res);
    }
    
    public function logMsg($msg, $append = false) {
        $log_file_name = DIR_LOGS . 'allpay_return_url_log.txt';
        $log_msg = date('Y-m-d H:i:s') . ' - ' . $msg . "\n";
        if (!$append) {
            file_put_contents($log_file_name, $log_msg);
        } else {
            file_put_contents($log_file_name, $log_msg , FILE_APPEND);
        }
    }
}
?>