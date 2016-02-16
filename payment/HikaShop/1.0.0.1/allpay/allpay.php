<?php
	/**
	 * @package		AllPay for Joomla Hikashop
	 * @version		1.0.0
	 * @author		Shawn Chang
	 * @copyright	Copyright 2013-2014 AllPay Financial Information Service Co., Ltd. All rights reserved.
	 * @license		GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
	 */
	defined('_JEXEC') or die('Restricted access');
	
	class plgHikashoppaymentAllpay extends hikashopPaymentPlugin
	{
		# 支付插件名稱
		var $name = 'allpay';
	
		# 接受的幣別
		var $accepted_currencies = array(
			'EUR', 'USD', 'GBP', 'HKD', 'SGD', 'JPY', 'CAD', 'AUD', 'CHF'
			, 'DKK',  'SEK', 'NOK', 'ILS', 'MYR', 'NZD', 'TRY', 'AED', 'MAD'
			, 'QAR', 'SAR', 'TWD', 'THB', 'CZK', 'HUF', 'SKK', 'EEK', 'BGN'
			, 'PLN', 'ISK', 'INR', 'LVL', 'KRW', 'ZAR', 'RON', 'HRK', 'LTL'
			, 'JOD', 'OMR', 'RSD', 'TND', 'CNY'
		);
		
		# 多重插件設定, 通常被設為 true
		var $multiple = true;
		
		var $pluginConfig = array(
			'allpay_test_mode'   => array('測試模式', 'boolean','0')
			, 'allpay_merchant_id'    => array('商店代號', 'input')
			, 'allpay_hash_key'    => array('金鑰', 'input')
			, 'allpay_hash_iv'     => array('向量', 'input')
			, 'allpay_pay_success'    => array('付款成功狀態', 'orderstatus')
			, 'allpay_pay_fail'      => array('付款失敗狀態', 'orderstatus')
		);
		
		/**
		 * @method hikashop 原生功能: 訂單確認後觸發
		 * @param object $order 訂單資訊
		 * @param array $methods 方法資訊
		 * @param int $method_id 方法 ID
		 * @return boolean 執行結果
		 */
		public function onAfterOrderConfirm(&$order,&$methods,$method_id) 
		{
			if (!class_exists('AllInOne')) 
			{
				require_once('AllPay.Payment.Integration.php');
			}
			
			try
			{
				# 設定服務參數
				$aio = new AllInOne();
				$allpay_param = $methods[$order->order_payment_id]->payment_params;
				$order_info = (isset($order->cart) ? $order->cart : $order);
				if ($allpay_param->allpay_test_mode)
				{
					$service_url = 'http://payment-stage.allpay.com.tw/Cashier/AioCheckOut';
					$merchant_trade_no = date('is') . $order_info->order_number;
				}
				else
				{
					$service_url = 'https://payment.allpay.com.tw/Cashier/AioCheckOut';
					$merchant_trade_no = $order_info->order_number;
				}
				$aio->MerchantID = $allpay_param->allpay_merchant_id;
				$aio->HashKey = $allpay_param->allpay_hash_key;
				$aio->HashIV = $allpay_param->allpay_hash_iv;
				$aio->ServiceURL = $service_url;
				unset($allpay_param);
				
				# 設定基本參數
				$aio->Send['ReturnURL'] = JURI::root() . 'plugins/hikashoppayment/allpay/response.php';
				$aio->Send['ClientBackURL'] = JURI::root() . 'index.php/hikashop-menu-for-products-listing/order';
				$aio->Send['MerchantTradeNo'] = $merchant_trade_no;
				$aio->Send['MerchantTradeDate'] = date('Y/m/d H:i:s');
				
				# 設定訂單商品資訊
                $order_total = round($order_info->full_total->prices[0]->price_value_with_tax, 0);
                $aio->Send['TotalAmount'] = $order_total;
                array_push($aio->Send['Items'], array(
                    'Name' => '網路商品一批'
                    , 'Price' => $order_total
                    , 'Currency' => 'NTD'
                    , 'Quantity' => 1
                ));
				unset($order_info);
				$aio->Send['TradeDesc'] = 'Hikashop_Payment_Plugin_AllPay';
				
				# 產生訂單 HTML Code
				$szHtml = $aio->CheckOutString(null);
				unset($aio);
				
				echo $szHtml;
				unset($szHtml);
			}
			catch(Exception $e)
			{
				JFactory::getApplication()->enqueueMessage('付款失敗<br />' . $e->getMessage(), 'error');
				
				return false;
			}
			
			return true;
		}
	}
?>
