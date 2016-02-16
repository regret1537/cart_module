<?php

class ControllerPaymentAllPay extends Controller {
    
	public function index() {
        $this->load->language('payment/allpay');
        $this->data['allpay_form_action'] = $this->url->link('payment/allpay/redirect', '', 'SSL');
        $this->data['allpay_button_confirm'] = $this->language->get('button_confirm');
        $this->data['allpay_title'] = $this->language->get('text_title');
        $this->data['allpay_payment_desc'] = $this->language->get('des_payment_method');
        
        # 設定有效付款方式
        $this->load->model('payment/allpay');
        $this->data['allpay_payment_methods'] = $this->model_payment_allpay->getPaymentDesc();
        
        if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/allpay.tpl')) {
            $this->template = $this->config->get('config_template') . '/template/payment/allpay.tpl';
        } else {
            $this->template = 'default/template/payment/allpay.tpl';
        }
        $this->render();
	}
	
    public function redirect() {
		$this->load->model('checkout/order');
		$this->load->model('payment/allpay');
		$this->load->language('payment/allpay');
		$this->model_payment_allpay->invokeExt(DIR_CATALOG . 'model/payment/');
        try {
            $merchant_id = $this->config->get('allpay_merchant_id');
            $choose_installment = 0;
            $AIO = new AllInOne();
            $ACE = new AllpayCartExt($merchant_id);
            
            # 取得購物車資訊
            $checkout_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
            $order_id = $checkout_info['order_id'];
          
            # 檢查付款方式是否合法
            $payment_type = $this->request->post['allpay_choose_payment'];
            $allpay_payment = $this->model_payment_allpay->getPaymentDesc();
            if (!in_array($payment_type, $allpay_payment)) {
                throw new Exception($this->language->get('error_invalid_payment'));
            }
            
            # 設定串接allPay參數
            $AIO->MerchantID = $merchant_id;
            $AIO->ServiceURL = $ACE->getServiceURL(URLType::CREATE_ORDER);
            $AIO->Send['MerchantTradeNo'] = $ACE->getMerchantTradeNo($order_id);
            $AIO->HashKey = $this->config->get('allpay_hash_key');
            $AIO->HashIV = $this->config->get('allpay_hash_iv');
            $AIO->Send['ReturnURL'] = $this->url->link('payment/allpay/response', '', 'SSL');
            $AIO->Send['ClientBackURL'] = $this->url->link('common/home', '', 'SSL');
            $AIO->Send['MerchantTradeDate'] = date('Y/m/d H:i:s');
            $AIO->Send['TradeDesc'] = 'allpay_module_mijoshop_1_0_2';
            
            # 小數點金額處理
            $order_total = $ACE->roundAmt($checkout_info['total'], $this->config->get('allpay_round_method'));
            
            # 設定商品資訊
            $AIO->Send['TotalAmount'] = $order_total;
            $AIO->Send['Items'] = array();
            array_push(
                $AIO->Send['Items'],
                array(
                    'Name' => $this->language->get('des_product_name'),
                    'Price' => $AIO->Send['TotalAmount'],
                    'Currency' => $checkout_info['currency_code'],
                    'Quantity' => 1,
                    'URL' => ''
                )
            );
            unset($checkout_info);
            
            # 取得付款方式(與分期期數)
            $type_pieces = explode('_', $payment_type);
            $AIO->Send['ChoosePayment'] = $type_pieces[0];
            if (isset($type_pieces[1])) {
                $choose_installment = $type_pieces[1];
            }
            
            # 設定串接allPay參數
            $params = array(
                'Installment' => $type_pieces[1],# 信用卡分期用
                'TotalAmount' => $AIO->Send['TotalAmount'],# 信用卡分期用
                'ReturnURL' => $AIO->Send['ReturnURL']# ATM/CVS/BARCODE用
            );
            $AIO->SendExtend = $ACE->setSendExt($AIO->Send['ChoosePayment'], $params);
            
            # 取得歐付寶轉導頁
            $red_html = $AIO->CheckOutString();
            
            # 清空購物車
            $this->cart->clear();
            
            # 建立訂單
            $this->model_checkout_order->confirm($order_id, $this->config->get('allpay_order_status_id'), true);
          
            # 輸出歐付寶轉導頁
            echo $red_html;
            exit;
        } catch(Exception $e) {
            $this->data['heading_title'] = $this->language->get('text_title');
            $this->data['text_warning'] = $e->getMessage();
            if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/allpay_error.tpl')) {
                $this->template = $this->config->get('config_template') . '/template/payment/allpay_error.tpl';
            } else {
                $this->template = 'default/template/payment/allpay_error.tpl';
            }
            $this->children = array(
				'common/column_left',
				'common/column_right',
				'common/content_top',
				'common/content_bottom',
				'common/footer',
				'common/header'
			);
            $this->response->setOutput($this->render());
        }
    }
  
	public function response() {
		$this->load->model('checkout/order');
		$this->load->model('payment/allpay');
		$this->model_payment_allpay->invokeExt(DIR_CATALOG . 'model/payment/');
        $this->model_payment_allpay->logMsg(LogMsg::RESP_DES);
        $this->model_payment_allpay->logMsg(print_r($_POST, true), true);
		$AIO = null;
		$ACE = null;
		$upd_order_comment = 'Unknown.';
        try {
			$res_msg = '1|OK';
            $merchant_id = $this->config->get('allpay_merchant_id');
            $crt_order_status = $this->config->get('allpay_order_status_id');
			$upd_order_status = $this->config->get('allpay_paid_status_id');
            $AIO = new AllInOne();
            $ACE = new AllpayCartExt($merchant_id);
            
            # 取得付款結果
			$AIO->MerchantID = $merchant_id;
			$AIO->HashKey = $this->config->get('allpay_hash_key');
			$AIO->HashIV = $this->config->get('allpay_hash_iv');
            $checkout_feedback = $AIO->CheckOutFeedback();
            if (empty($checkout_feedback)) {
                throw new Exception(ErrorMsg::C_FD_EMPTY);
            }
            $rtn_code = $checkout_feedback['RtnCode'];
            $rtn_msg = $checkout_feedback['RtnMsg'];
            $type_pieces = explode('_', $checkout_feedback['PaymentType']);
            $payment_method = $type_pieces[0];
            
            # 取得購物車訂單明細
            $merchant_trade_no = $checkout_feedback['MerchantTradeNo'];
            $cart_order_id = $ACE->getCartOrderID($merchant_trade_no);
            $order_info = $this->model_checkout_order->getOrder($cart_order_id);
            $cart_order_total = $ACE->roundAmt($order_info['total'], $this->config->get('allpay_round_method'));
            
            # 反查歐付寶訂單明細
            $AIO->ServiceURL = $ACE->getServiceURL(URLType::QUERY_ORDER);
            $AIO->Query['MerchantTradeNo'] = $merchant_trade_no;
            $query_feedback = $AIO->QueryTradeInfo();
            if (empty($query_feedback)) {
                throw new Exception(ErrorMsg::Q_FD_EMPTY);
            }
			$trade_status = $query_feedback['TradeStatus'];
            
            # 金額檢查
            $ACE->validAmount($cart_order_total, $checkout_feedback['TradeAmt'], $query_feedback['TradeAmt']);
            
            # 付款方式檢查
			$query_payment = $ACE->parsePayment($query_feedback['PaymentType']);
            $ACE->validPayment($payment_method, $query_payment);
            
            # 訂單狀態檢查
            $ACE->validStatus($crt_order_status, $order_info['order_status_id']);
			
			# 取得訂單備註
			$comment_tpl = $ACE->getCommentTpl($payment_method, $trade_status, $rtn_code);
			$upd_order_comment = $ACE->getComment($payment_method, $comment_tpl, $checkout_feedback);
        } catch (Exception $e) {
			$exception_msg = $e->getMessage();
            $res_msg = '0|' . $exception_msg;
			if (!empty($ACE)) {
				$upd_order_comment = $ACE->getFailComment($exception_msg);
			}
			$upd_order_status = $this->config->get('allpay_unpaid_status_id');
        }
		
		# 更新訂單，並通知客戶
		$this->model_checkout_order->update($cart_order_id, $upd_order_status, $upd_order_comment, true);
		
		$this->model_payment_allpay->logMsg($res_msg, true);
		
		# 印出回應訊息
        echo $res_msg;
		exit;
	}
	
}
?>