<?php
    /*
    *   需搭配 SDK(AllPay.Payment.Integration.php) 使用
    */
    abstract class URLType {
        const CREATE_ORDER = 0;
        const QUERY_ORDER = 1;
    }
    
    abstract class ServiceURL {
        const PROD_CO = 'https://payment.allpay.com.tw/Cashier/AioCheckOut';
        const STAGE_CO = 'https://payment-stage.allpay.com.tw/Cashier/AioCheckOut';
        const PROD_QO = 'https://payment.allpay.com.tw/Cashier/QueryTradeInfo';
        const STAGE_QO = 'http://payment-stage.allpay.com.tw/Cashier/QueryTradeInfo';
    }
    
    abstract class RoundMethod {
        const MHD_ROUND = '0';
        const MHD_CEIL = '1';
        const MHD_FLOOR = '2';
    }
    
    abstract class LogMsg {
        const RESP_DES = 'Recive allPay response.';
        const RESP_RES = 'Processed result: %s';
    }
    
    abstract class ErrorMsg {
        const EXT_MISS = 'allPay extension missed.';
        const C_FD_EMPTY = 'allPay checkout feedback is empty.';
        const Q_FD_EMPTY = 'allPay query feedback is empty.';
        const AMT_DIFF = 'Amount error.';
        const PAY_DIFF = 'Payment method error.';
        const UPD_ODR = 'Order is modified.';
    }
	
	abstract class OrderComment {
		const GC_ATM = 'Bank Code : %s, Virtual Account : %s, Payment Deadline : %s.';
		const GC_BARCODE = 'Payment Deadline : %s, BARCODE 1 : %s, BARCODE 2 : %s, BARCODE 3 : %s.';
		const GC_CVS = 'Trade Code : %s, Payment Deadline : %s.';
		const SUCC = 'Paid Succeed.';
		const FAIL = 'Paid Failed, Exception(%s).';
	}
    
    abstract class ReturnCode {
        const PAID = 1;
        const DELV_PAID = 800;
        const GC_ATM = 2;
        const GC_BARCODE = 10100073;
        const GC_CVS = 10100073;
    }
	
	abstract class TradeStatus {
		const UNPAID = 0;
		const PAID = 1;
	}
    
    class AllpayCartExt {
        private $merchant_id = '';
        private $test_mode = false;
        public function __construct($merchant_id) {
            if (empty($merchant_id)) {
                throw new Exception('merchant_id missed.');
            }
            $this->merchant_id = $merchant_id;
            $this->test_mode = $this->isTestMode($this->merchant_id);
        }
        
        public function isTestMode() {
            return ($this->merchant_id == '2000132' or $this->merchant_id == '2000214');
        }
        
        public function getServiceURL($action = URLType::CREATE_ORDER) {
            if ($this->test_mode) {
                switch ($action) {
                    case URLType::CREATE_ORDER:
                        return ServiceURL::STAGE_CO;
                        break;
                    case URLType::QUERY_ORDER:
                        return ServiceURL::STAGE_QO;
                        break;
                    default:
                }
                
            } else {
                switch ($action) {
                    case URLType::CREATE_ORDER:
                        return ServiceURL::PROD_CO;
                        break;
                    case URLType::QUERY_ORDER:
                        return ServiceURL::PROD_QO;
                        break;
                    default:
                }
            }
            return '';
        }
        
        public function getMerchantTradeNo($order_id) {
            if ($this->test_mode) {
                return (date('ymdHis') . $order_id);
            } else {
                return $order_id;
            }
        }
        
		public function parsePayment($payment_type) {
			$type_pieces = explode('_', $payment_type);
            return $type_pieces[0];
		}
		
        public function getCartOrderID($order_id) {
            if ($this->test_mode) {
                return (substr($order_id, 12));
            } else {
                return $order_id;
            }
        }
        
        public function roundAmt($amt, $round_method = '0') {
            $round_amt = '';
            switch($round_method) {
                case RoundMethod::MHD_ROUND:
                    $round_amt = round($amt, 0);
                    break;
                case RoundMethod::MHD_CEIL:
                    $round_amt = ceil($amt);
                    break;
                case RoundMethod::MHD_FLOOR:
                    $round_amt = floor($amt);
                    break;
                default:
                    $round_amt = $amt;
            }
            return $round_amt;
        }
        
        public function setSendExt($payment, $params) {
            $send_ext = array();
            $atm_min_exp_dt = 1;
            $atm_max_exp_dt = 60;
            $params = array(
                'Installment' => 0,# 信用卡分期用
                'TotalAmount' => 0,# 信用卡分期用
                'ExpireDate' => 3,# ATM用
                'ReturnURL' => '',# ATM/CVS/BARCODE用
                'Email' => '-',# Alipay用
                'PhoneNo' => '-',# Alipay用
                'UserName' => '-',# Alipay用
            );
            foreach ($params as $name => $value) {
                if (isset($data[$name])) {
                    $data[$name] = $value;
                }
            }
            if (class_exists('PaymentMethod')) {
                switch ($payment) {
                    case PaymentMethod::WebATM:
                    case PaymentMethod::TopUpUsed:
                        break;
                    case PaymentMethod::Credit:
                        # 預設不支援銀聯卡
                        $send_ext['UnionPay'] = false;
                        
                        # 信用卡分期參數
                        if (!empty($choose_installment)) {
                            $send_ext['CreditInstallment'] = $data['Installment'];
                            $send_ext['InstallmentAmount'] = $data['TotalAmount'];
                            $send_ext['Redeem'] = false;
                        }
                        break;
                    case PaymentMethod::ATM:
                        if ($data['ExpireDate'] < $atm_min_exp_dt or $data['ExpireDate'] > $atm_max_exp_dt) {
                            throw new Exception('ATM ExpireDate from ' . $atm_min_exp_dt . ' to ' . $atm_max_exp_dt . '.');
                        }
                        $send_ext['ExpireDate'] = $data['ExpireDate'];
                        $send_ext['PaymentInfoURL'] = $data['ReturnURL'];
                        break;
                    case PaymentMethod::CVS:
                    case PaymentMethod::BARCODE:
                        $send_ext['Desc_1'] = '';
                        $send_ext['Desc_2'] = '';
                        $send_ext['Desc_3'] = '';
                        $send_ext['Desc_4'] = '';
                        $send_ext['PaymentInfoURL'] = $data['ReturnURL'];
                        break;
                    case PaymentMethod::Alipay:
                        $send_ext['Email'] = $data['Email'];
                        $send_ext['PhoneNo'] = $data['PhoneNo'];
                        $send_ext['UserName'] = $data['UserName'];
                        break;
                    case PaymentMethod::Tenpay:
                        $send_ext['ExpireTime'] = date('Y/m/d H:i:s', strtotime('+3 days'));
                        break;
                    default:
                        throw new Exception('Undefine payment method.');
                        break;
                }
            }
            return $send_ext;
        }
        
        public function validAmount($cart_amt, $rtn_amt, $query_amt) {
            if ($cart_amt != $rtn_amt or $cart_amt != $query_amt) {
                throw new Exception(ErrorMsg::AMT_DIFF);
            }
        }
        
        public function validPayment($rtn_payment, $query_payment) {
			if ($rtn_payment != $query_payment) {
                throw new Exception(ErrorMsg::PAY_DIFF);
			}
        }
        
        public function validStatus($order_status, $create_status) {
            if ($order_status != $create_status) {
                throw new Exception(ErrorMsg::UPD_ODR);
            }
        }
		
		public function getCommentTpl($payment, $trade_status, $rtn_code) {
			// V3
			$comment_tpl = '';
			$gc_code = array(
				PaymentMethod::ATM => ReturnCode::GC_ATM,
				PaymentMethod::BARCODE => ReturnCode::GC_BARCODE,
				PaymentMethod::CVS => ReturnCode::GC_CVS
			);
			$ext_comment = array(
				PaymentMethod::ATM => OrderComment::GC_ATM,
				PaymentMethod::BARCODE => OrderComment::GC_BARCODE,
				PaymentMethod::CVS => OrderComment::GC_CVS
			);
			switch ($payment) {
				case PaymentMethod::Credit:
                case PaymentMethod::WebATM:
                case PaymentMethod::Alipay:
                case PaymentMethod::Tenpay:
                case PaymentMethod::TopUpUsed:
					if ($trade_status == TradeStatus::PAID) {
						$comment_tpl = $this->getSuccTpl($rtn_code);
					} else {
						throw new Exception('None');
					}
                    break;
                case PaymentMethod::ATM:
				case PaymentMethod::BARCODE:
				case PaymentMethod::CVS:
					switch ($trade_status) {
						case TradeStatus::PAID:
							$comment_tpl = $this->getSuccTpl($rtn_code);
							break;
						case TradeStatus::UNPAID:
							if ($rtn_code == $gc_code[$payment]) {
								$comment_tpl = $ext_comment[$payment];
							} else {
								throw new Exception('None');
							}
							break;
						default:
					}
                    break;
                default:
			}
			return $comment_tpl;
		}
		
		private function getSuccTpl($rtn_code) {
			$comment = '';
			if ($rtn_code == ReturnCode::PAID or $rtn_code == ReturnCode::DELV_PAID) {
				$comment = OrderComment::SUCC;
			}
			return $comment;
		}
		
		public function getComment($payment, $tpl, $feedback) {
			$comment = '';
			switch ($payment) {
                case PaymentMethod::ATM:
					$comment = sprintf(
						$tpl,
						$feedback['BankCode'],
						$feedback['vAccount'],
						$feedback['ExpireDate']
					);
                    break;
				case PaymentMethod::BARCODE:
					$comment = sprintf(
						$tpl,
						$feedback['ExpireDate'],
						$feedback['Barcode1'],
						$feedback['Barcode2'],
						$feedback['Barcode3']
					);
                    break;
				case PaymentMethod::CVS:
					$comment = sprintf(
						$tpl,
						$feedback['PaymentNo'],
						$feedback['ExpireDate']
					);
                    break;
                default:
					$comment = $tpl;
			}
			return $comment;
		}
		
		
		public function getFailComment($msg) {
			$comment = sprintf(
				OrderComment::FAIL,
				$msg
			);
			return $comment;
		}
		
        public function getProccessRes($result) {
			return (sprintf(LogMsg::RESP_RES, $result));
		}
        
        
    }
?>