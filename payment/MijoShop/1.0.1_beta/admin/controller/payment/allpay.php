<?php
	class ControllerPaymentAllpay extends Controller
	{
		
		/**
		 * @method 付款方式設定頁面
		 */
		public function index()
		{
      # 取得語言資訊
			$this->load->model('localisation/language');
			$this->load->language('payment/allpay');
			
			# 取得地理位置資訊
			$this->load->model('localisation/geo_zone');
			$this->data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();
			
			# 取得訂單資訊
			$this->load->model('localisation/order_status');
      $this->data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

      $token = $this->session->data['token'];

      # 載入相關 Model
      $this->load->model('setting/setting');
			if (($this->request->server['REQUEST_METHOD'] == 'POST') and $this->validate())
			{
				# 更新模組設定
				$this->model_setting_setting->editSetting('allpay', $this->request->post);
				
				$this->session->data['success'] = $this->language->get('text_success');
				
				$this->redirect($this->url->link('extension/payment', 'token=' . $token, 'SSL'));
			}
			
			# header 設定
			$this->data['heading_title'] = $this->language->get('heading_title');			
			
			# 欄位說明設定
			$this->data['des_merchant_id'] = $this->language->get('des_merchant_id');
			$this->data['des_hash_key'] = $this->language->get('des_hash_key');
			$this->data['des_hash_iv'] = $this->language->get('des_hash_iv');
			$this->data['des_payment_method'] = $this->language->get('des_payment_method');
			$this->data['des_order_status'] = $this->language->get('des_order_status');
			$this->data['des_paid_status'] = $this->language->get('des_paid_status');
			$this->data['des_unpaid_status'] = $this->language->get('des_unpaid_status');
			$this->data['des_geo_zone'] = $this->language->get('des_geo_zone');
			$this->data['des_round_method'] = $this->language->get('des_round_method');
			$this->data['des_payment_status'] = $this->language->get('des_payment_status');
			$this->data['des_sort_order'] = $this->language->get('des_sort_order');
			
			# 欄位內容設定
      $this->data['payment_methods'] = array(
        'allpay_payment_credit' => $this->language->get('des_payment_credit'),
        'allpay_payment_credit_03' => $this->language->get('des_payment_credit_03'),
        'allpay_payment_credit_06' => $this->language->get('des_payment_credit_06'),
        'allpay_payment_credit_12' => $this->language->get('des_payment_credit_12'),
        'allpay_payment_credit_18' => $this->language->get('des_payment_credit_18'),
        'allpay_payment_credit_24' => $this->language->get('des_payment_credit_24'),
        'allpay_payment_webatm' => $this->language->get('des_payment_webatm'),
        'allpay_payment_atm' => $this->language->get('des_payment_atm'),
        'allpay_payment_cvs' => $this->language->get('des_payment_cvs'),
        'allpay_payment_barcode' => $this->language->get('des_payment_barcode'),
        'allpay_payment_alipay' => $this->language->get('des_payment_alipay'),
        'allpay_payment_tenpay' => $this->language->get('des_payment_tenpay'),
        'allpay_payment_topupused' => $this->language->get('des_payment_topupused')
      );
      $this->data['round_methods'] = array(
        $this->language->get('des_round_round'),
        $this->language->get('des_round_ceil'),
        $this->language->get('des_round_floor')
      );
      
      
			$this->data['text_enabled'] = $this->language->get('text_enabled');
			$this->data['text_disabled'] = $this->language->get('text_disabled');
			$this->data['text_all_zones'] = $this->language->get('text_all_zones');
					
			# 按鈕內容設定
			$this->data['button_save'] = $this->language->get('button_save');
			$this->data['button_cancel'] = $this->language->get('button_cancel');
			
			# 錯誤訊息設定
			$error_list = array('permission', 'merchant_id', 'hash_key', 'hash_iv');
			foreach($error_list as $tmp_piece)
			{
				$tmp_name = 'error_' . $tmp_piece;
				if (isset($this->error[$tmp_name]))
				{
					$this->data[$tmp_name] = $this->error[$tmp_name];
				}
			}
			unset($error_list);
			unset($tmp_name);
			
			# 設定網頁路徑
			$this->data['breadcrumbs'] = array(
				array(
					'text' => $this->language->get('text_home')
					, 'href' => $this->url->link('common/home', 'token=' . $token, 'SSL')
					, 'separator' => false
				)
				, array(
					'text' => $this->language->get('text_payment')
					, 'href' => $this->url->link('extension/payment', 'token=' . $token, 'SSL')
					, 'separator' => ' :: '
				)
				, array(
					'text' => $this->language->get('heading_title')
					, 'href' => $this->url->link('payment/allpay', 'token=' . $token, 'SSL')
					, 'separator' => ' :: '
				)	
			);
			
			# 設定按下按鍵導向頁面
			$this->data['action'] = $this->url->link('payment/allpay', 'token=' . $token, 'SSL');
			$this->data['cancel'] = $this->url->link('extension/payment', 'token=' . $token, 'SSL');
			unset($token);
			
			# 取得傳入POST參數
			$languages = $this->model_localisation_language->getLanguages();
			$this->data['languages'] = $languages;
			foreach ($languages as $language) 
			{
				$tmp_name = 'allpay_description_' . $language['language_id'];
				if (isset($this->request->post[$tmp_name]))
				{
					$tmp_val = $this->request->post[$tmp_name];
				}
				else
				{
					$tmp_val = $this->config->get($tmp_name);
				}
				$this->data[$tmp_name] = $tmp_val;
			}
			unset($languages);
			unset($tmp_name);
			unset($tmp_val);
			
			$tmp_var_list = array(
        'merchant_id',
        'hash_key',
        'hash_iv',
        'payment_credit',
        'payment_credit_03',
        'payment_credit_06',
        'payment_credit_12',
        'payment_credit_18',
        'payment_credit_24',
        'payment_webatm',
        'payment_atm',
        'payment_cvs',
        'payment_barcode',
        'payment_alipay',
        'payment_tenpay',
        'payment_topupused',
        'order_status_id',
        'paid_status_id',
        'unpaid_status_id',
        'round_method',
        'geo_zone_id',
        'status',
        'sort_order'
			);
			foreach ($tmp_var_list as $tmp_piece)
			{
				$tmp_name = 'allpay_' . $tmp_piece;
				if (isset($this->request->post[$tmp_name]))
				{
					$tmp_val = $this->request->post[$tmp_name];
				}
				else
				{
					$tmp_val = $this->config->get($tmp_name);
				}
				$this->data[$tmp_name] = $tmp_val;
			}
			unset($tmp_var_list);
			unset($tmp_val);
			
			# 設定顯示頁面
			$this->template = 'payment/allpay.tpl';
			$this->children = array(
				'common/header',
				'common/footer',
			);
			
			# 顯示頁面
			$this->response->setOutput($this->render());
		}
		
		/**
		 * @method 權限與參數檢查
		 * @return boolean 檢查結果
		 */
		private function validate()
		{
			# 權限檢查
			if (!$this->user->hasPermission('modify', 'payment/allpay'))
			{
				$this->error['error_permission'] = $this->language->get('error_permission');
			}
			
			# 必填欄位檢查
			$key_list = array('merchant_id', 'hash_key', 'hash_iv');
			foreach ($key_list as $tmp_name)
			{
				if (!$this->request->post['allpay_' . $tmp_name])
				{
					$tmp_error_name = 'error_' . $tmp_name;
					$this->error[$tmp_error_name] = $this->language->get($tmp_error_name);
				}
			}
			
			return (empty($this->error) ? true : false);
		}
	}
