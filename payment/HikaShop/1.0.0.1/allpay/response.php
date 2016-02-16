<?php
	/**
	 * @package		AllPay for Joomla Hikashop
	 * @version		1.0.0
	 * @author		Shawn Chang
	 * @copyright	Copyright 2013-2014 AllPay Financial Information Service Co., Ltd. All rights reserved.
	 * @license		GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
	 */

	# 設定內部字元編碼
	mb_internal_encoding('utf-8');
	
	# 設定回應訊息
	$respose_msg = '1|OK';
	
	try
	{
		/**
		 * @method 插入檔案
		 * @param string $class_name 檔案的class名稱
		 * @param string $file_path 檔案路徑(可使用相對路徑)
		 * @throws Exception
		 */
		function _requireFile($class_name, $file_path)
		{
			if (!class_exists($class_name))
			{
				if (!file_exists($file_path))
				{
					throw new Exception('Required File ' . basename($file_path) . ' Not Found.');
				}
				else
				{
					require($file_path);
				}
			}
		}
		
		# 取得 MySQL連線相關設定
		_requireFile('JConfig', '../../../configuration.php');
		$joomla_config = new JConfig();
		$tmp_host = $joomla_config->host;
		$tmp_user = $joomla_config->user;
		$tmp_password = $joomla_config->password;
		$tmp_default_db = $joomla_config->db;
		$tmp_encode = 'utf8';
		$table_prefix = $joomla_config->dbprefix;
		unset($joomla_config);
		
		# 判斷是否支援 mysqli
		$mysql_method = (function_exists('mysqli_connect') ? 'mysqli' : 'mysql');
		
		# 建立MySQL連線
		$mysql_conn = null;
		switch ($mysql_method)
		{
			case 'mysql':
				$mysql_conn = mysql_connect($tmp_host, $tmp_user, $tmp_password);
				mysql_select_db($tmp_default_db, $mysql_conn);
				mysql_set_charset($tmp_encode);
				break;
			case 'mysqli':
				$mysql_conn = mysqli_connect($tmp_host, $tmp_user, $tmp_password, $tmp_default_db);
				mysqli_set_charset($mysql_conn, $tmp_encode);
				break;
			default:
				break;
		}
		unset($tmp_host);
		unset($tmp_user);
		unset($tmp_password);
		unset($tmp_default_db);
		unset($tmp_encode);
		
		/**
		 * @method 取得資料庫中的第1筆資料
		 * @param string $mysql_method php MySQL函數類別
		 * @param object $mysql_conn MySQL連線物件
		 * @param string $select_sql 查詢 SQL
		 * @return array 資料陣列
		 */
		function _mysqlQueryRow($mysql_method, $mysql_conn, $select_sql)
		{
			$tmp_row = null;
			switch ($mysql_method)
			{
				case 'mysql':
					$tmp_row = mysql_fetch_row(mysql_query($select_sql, $mysql_conn));
					break;
				case 'mysqli':
					$tmp_row = mysqli_fetch_row(mysqli_query($mysql_conn, $select_sql));
					break;
				default:
					break;
			}
		
			return $tmp_row;
		}
		
		# 從資料庫取得後台金流參數
		$sql_get_pay_param = 'SELECT `payment_params` FROM `' . $table_prefix . 'hikashop_payment`';
		$sql_get_pay_param .= ' WHERE `payment_type` = "allpay";';
		$row_pay_param = _mysqlQueryRow($mysql_method, $mysql_conn, $sql_get_pay_param);
		unset($sql_get_pay_param);

		# 去除大括號前端字串
		$split_brace = strstr($row_pay_param[0], '{');
		unset($row_pay_param);

		# 以「:"」切割參數
		$split_colon_quote = explode(':"', $split_brace);
		unset($split_brace);

		# 解析後台金流參數
		$payment_config = array();
		if (!empty($split_colon_quote))
		{
			# 取得「";」之前的內容設定屬性名稱跟屬性值
			for ($i = 1 ; $i < (count($split_colon_quote) - 1) ; $i += 2)
			{
				$payment_config[strstr($split_colon_quote[$i], '";', true)] = strstr($split_colon_quote[$i + 1], '";', true);
			}
			unset($i);
		}
		unset($split_colon_quote);
		
		if (empty($payment_config))
		{
			throw new Exception('Payment Config Error.');
		}
		else
		{
			# 取得整合金流 SDK
			_requireFile('AllInOne', 'AllPay.Payment.Integration.php');
			
			# 設定金流 SDK必要參數
			$aio = new AllInOne();
			$aio->HashKey = $payment_config['allpay_hash_key'];
			$aio->HashIV = $payment_config['allpay_hash_iv'];
			$aio->MerchantID = $payment_config['allpay_merchant_id'];
			
			# 取得付款結果
			$pay_result = $aio->CheckOutFeedback();
			unset($aio);
			
			if(count($pay_result) < 1)
			{
				throw new Exception('Get Allpay Feedback Failed.');
			}
			else
			{
				# 訂單編號檢查, 並設定查詢 SQL
				$order_table = $table_prefix . 'hikashop_order';
                if ($payment_config['allpay_test_mode']) {
                    $order_no = substr($pay_result['MerchantTradeNo'], 4);
                } else {
                    $order_no = $pay_result['MerchantTradeNo'];
                }
				
				$sql_get_order_info = '';
				if (preg_match('/^[A-Z0-9]+$/', $order_no))
				{
					#	order_id: 訂單 ID
					#	order_full_price: 訂單金額
					#	order_number: 訂單編號
					$sql_get_order_info = 'SELECT `order_id`, `order_full_price`, `order_ip` FROM `' . $order_table . '`';
					$sql_get_order_info .= ' WHERE `order_number` = "' . $order_no . '";';
				}
				
				# 取得對應訂單資訊
				$row_order_info = _mysqlQueryRow($mysql_method, $mysql_conn, $sql_get_order_info);
				unset($sql_get_order_info);
				if (empty($row_order_info))
				{
					throw new Exception('Order(' . $order_no . ') Not Found In Hikashop.');
				}
				else
				{
					# 取得訂單 ID
					$order_id = $row_order_info[0];
					
					# 取得訂單金額
					$order_full_price = $row_order_info[1];
					
					# 取得下單 IP
					$order_ip = $row_order_info[2];
					
					# 核對訂單金額
					if ($pay_result['TradeAmt'] != $order_full_price)
					{
						throw new Exception('Order(' . $order_no . ') Amount Are Not Identical.');
					}
					else
					{
						# 檢查訂單回傳狀態
						$return_code = $pay_result['RtnCode'];
						$history_table = $table_prefix . 'hikashop_history';
						$now_time = time();
						if ($return_code != 1 and $return_code != 800)
						{
							$order_status = $payment_config['allpay_pay_fail'];
							$history_reason = '訂單未付款';
							$respose_msg = '0|Order ' . $order_no . ' Exception.(' . $return_code . ': ' . $pay_result['RtnMsg'] . ')';
						}
						else
						{
							$order_status = $payment_config['allpay_pay_success'];
							$history_reason = '訂單已付款';
						}
						unset($return_code);
						
						# 更新訂單狀態
						#	order_status: 訂單狀態
						#	order_modified: 訂單修改時間
						$sql_update_order = 'UPDATE `' . $order_table . '`';
						$sql_update_order .= ' SET `order_status` = "' . $order_status . '"';
						$sql_update_order .= ', `order_modified` = ' . $now_time;
						$sql_update_order .= ' WHERE `order_id` = ' . $order_id . ';';
						/**
						 * @method 執行 SQL
						 * @param string $mysql_method php MySQL函數類別
						 * @param object $mysql_conn MySQL連線物件
						 * @param string $sql SQL
						 */
						function _mysqlQuery($mysql_method, $mysql_conn, $sql)
						{
							switch($mysql_method)
							{
								case 'mysql':
									mysql_query($sql, $mysql_conn);
									break;
								case 'mysqli':
									mysqli_query($mysql_conn, $sql);
									break;
								default:
									break;
							}
						}
						_mysqlQuery($mysql_method, $mysql_conn, $sql_update_order);
						unset($sql_update_order);
							
						# 新增異動記錄
						# history_order_id: 記錄訂單 ID(用以對應訂單資料)
						# history_created: 記錄產生時間
						# history_ip: 產生記錄 IP
						# history_new_status: 狀態記錄
						# history_reason: 記錄原因(若有常需帶錯誤訊息)
						# history_type: 記錄類別
						# history_data: 記錄內容
						$sql_insert_history = 'INSERT INTO `' . $history_table . '`';
						$sql_insert_history .= ' SET `history_order_id` = ' . $order_id;
						$sql_insert_history .= ', `history_created` = ' . $now_time;
						$sql_insert_history .= ', `history_ip` = "allpay / ' . $order_ip . '"';
						$sql_insert_history .= ', `history_new_status` = "' . $order_status . '"';
						$sql_insert_history .= ', `history_reason` = "' . $history_reason . '"';
						$sql_insert_history .= ', `history_type` = "creation"';
						
						$payment_alias = array(
								'Credit' => '信用卡'
								, 'WebATM' => '網路 ATM'
								, 'ATM' => '自動櫃員機'
								, 'CVS' => '超商代碼'
								, 'BARCODE' => '超商條碼'
								, 'Alipay' => '海外支付-支付寶'
								, 'Tenpay' => '海外支付-財付通'
								, 'TopUpUsed' => '儲值消費'
						);
						$sql_insert_history .= ', `history_data` = "' . $payment_alias[$pay_result['PaymentType']] . '付款";';
						_mysqlQuery($mysql_method, $mysql_conn, $sql_insert_history);

						unset($history_table);
						unset($now_time);
						unset($order_status);
						unset($history_reason);
						unset($payment_alias);
						unset($sql_insert_history);
					}
					unset($order_id);
					unset($order_full_price);
					unset($order_ip);
				}
				unset($row_order_info);
				unset($order_table);
				unset($order_no);
			}
			unset($pay_result);
		}
		unset($mysql_method);
		unset($payment_config);
		unset($table_prefix);
		
		# 關閉 MySQL 連線
		if (!empty($mysql_conn))
		{
			switch ($mysql_method)
			{
				case 'mysql':
					mysql_close($mysql_conn);
					break;
				case 'mysqli':
					mysqli_close($mysql_conn);
					break;
				default:
					break;
			}
		}
		unset($mysql_conn);
	}
	catch(Exception $e)
	{
		$respose_msg = '0|' . $e->getMessage();
	}
	
	echo $respose_msg;
?>