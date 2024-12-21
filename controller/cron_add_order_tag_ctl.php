<?php 
include_once 'model/customer_order_view_mdl.php';
class cron_add_order_tag_ctl extends customer_order_view_mdl
{
	function __construct(){
		$this->addOrderTagInShipstation();
	}

	public function addOrderTagInShipstation()
	{
		$currentTime      = date("Y-m-d h:i:s");
		$sql = 'SELECT store_sale_type,shop_order_number,created_on,fe_order_id FROM `store_orders_master` WHERE created_on > DATE_SUB("'.$currentTime.'",INTERVAL 1 HOUR)';
		$list_data = parent::selectTable_f_mdl($sql);
		if (!empty($list_data)) {
			foreach ($list_data as $value) {
	            // add tag in order inside shipstation
	            $store_sale_type = $value['store_sale_type'];
	            $orderTag = '';
	            if($store_sale_type=='Flash Sale'){                                                    
	                $orderTag = common::FLASH_SALE_ORDER_TAG_ID;
	            }
	            else{
	                $orderTag = common::ON_DEMAND_ORDER_TAG_ID;
	            }
	            if(isset($value['shop_order_number'])){
	                $curl = curl_init();
	                curl_setopt_array($curl, array(
	                CURLOPT_URL => 'https://ssapi.shipstation.com//orders?orderNumber='.$value['shop_order_number'].'',
	                CURLOPT_RETURNTRANSFER => true,
	                CURLOPT_ENCODING => '',
	                CURLOPT_MAXREDIRS => 10,
	                CURLOPT_TIMEOUT => 0,
	                CURLOPT_FOLLOWLOCATION => true,
	                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	                CURLOPT_CUSTOMREQUEST => 'GET',
	                CURLOPT_HTTPHEADER => array(
	                    'Content-Type: application/json',
	                    'Authorization: '.common::SHIPSTATION_AUTH_KEY.''
	                ),
	                ));
	                $response = curl_exec($curl);
	                curl_close($curl);
	                $data    =  json_decode($response,true);
	                if (count($data['orders']) > 0) {
		                $orderId = $data['orders'][0]['orderId'];
		                $curl1 = curl_init();
		                curl_setopt_array($curl1, array(
		                    CURLOPT_URL => 'https://ssapi.shipstation.com/orders/addtag',
		                    CURLOPT_RETURNTRANSFER => true,
		                    CURLOPT_ENCODING => '',
		                    CURLOPT_MAXREDIRS => 10,
		                    CURLOPT_TIMEOUT => 0,
		                    CURLOPT_FOLLOWLOCATION => true,
		                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		                    CURLOPT_CUSTOMREQUEST => 'POST',
		                    CURLOPT_POSTFIELDS =>'{
		                    	"orderId": '.$orderId.',
		                    	"tagId": '.$orderTag.'
		                    }',
		                    CURLOPT_HTTPHEADER => array(
		                    	'Content-Type: application/json',
		                    	'Authorization: '.common::SHIPSTATION_AUTH_KEY.''
		                    ),
		                ));
		                $response1 = curl_exec($curl1);
		                curl_close($curl1);
		                // echo $response1;
		                // insert tag history
		                $authTafgSql = 'SELECT order_number FROM audit_tag_history WHERE order_number = "'.$value['shop_order_number'].'"';
						$authTagarray = parent::selectTable_f_mdl($authTafgSql);
						if (empty($authTagarray)) {
							$authTagData = [
			                    'order_number' => $value['shop_order_number'],
			                    'tag_id'       => $orderTag,
			                    'created_date' => date("Y-m-d H:i:s")
		                	];
		                	$authTadHistory = parent::insertTable_f_mdl('audit_tag_history',$authTagData);
						}

						if(!empty($value['fe_order_id'])){
							$orderTagFE= '168086'; // ON-DEMAND Automated FE Order

							$curl2 = curl_init();
							curl_setopt_array($curl2, array(
								CURLOPT_URL => 'https://ssapi.shipstation.com/orders/addtag',
								CURLOPT_RETURNTRANSFER => true,
								CURLOPT_ENCODING => '',
								CURLOPT_MAXREDIRS => 10,
								CURLOPT_TIMEOUT => 0,
								CURLOPT_FOLLOWLOCATION => true,
								CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
								CURLOPT_CUSTOMREQUEST => 'POST',
								CURLOPT_POSTFIELDS =>'{
									"orderId": '.$orderId.',
									"tagId": '.$orderTagFE.'
								}',
								CURLOPT_HTTPHEADER => array(
									'Content-Type: application/json',
									'Authorization: '.common::SHIPSTATION_AUTH_KEY.''
								),
							));
							$response1 = curl_exec($curl2);
							curl_close($curl2);
						}
					}	
	            }
	            // add tag in order inside shipstation
			}
		}else{
			echo "24 hours";
			$sql1 = 'SELECT store_sale_type,shop_order_number,created_on,fe_order_id FROM `store_orders_master` WHERE created_on > DATE_SUB("'.$currentTime.'",INTERVAL 24 HOUR)';
			$list_data1 = parent::selectTable_f_mdl($sql1);	
			foreach ($list_data1 as $value1) {
	            // add tag in order inside shipstation
	            $store_sale_type1 = $value1['store_sale_type'];
	            $orderTag1 = '';
	            if($store_sale_type1=='Flash Sale'){                                                    
	                $orderTag1 = common::FLASH_SALE_ORDER_TAG_ID;
	            }
	            else{
	                $orderTag1 = common::ON_DEMAND_ORDER_TAG_ID;
	            }
	            if(isset($value1['shop_order_number'])){
	                $curl3 = curl_init();
	                curl_setopt_array($curl3, array(
		                CURLOPT_URL => 'https://ssapi.shipstation.com//orders?orderNumber='.$value1['shop_order_number'].'',
		                CURLOPT_RETURNTRANSFER => true,
		                CURLOPT_ENCODING => '',
		                CURLOPT_MAXREDIRS => 10,
		                CURLOPT_TIMEOUT => 0,
		                CURLOPT_FOLLOWLOCATION => true,
		                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		                CURLOPT_CUSTOMREQUEST => 'GET',
		                CURLOPT_HTTPHEADER => array(
		                    'Content-Type: application/json',
		                    'Authorization: '.common::SHIPSTATION_AUTH_KEY.''
		                ),
	                ));
	                $response2 = curl_exec($curl3);
	                curl_close($curl3);
	                $data1    =  json_decode($response2,true);
	                $tagDataSearch = $data1['orders'][0]['tagIds'];
	                if (in_array($orderTag1, $tagDataSearch)) {
	                }
	                else{	
		                if (count($data1['orders']) > 0) {

			                $orderId1 = $data1['orders'][0]['orderId'];
			                $curl2 = curl_init();
			                curl_setopt_array($curl2, array(
			                    CURLOPT_URL => 'https://ssapi.shipstation.com/orders/addtag',
			                    CURLOPT_RETURNTRANSFER => true,
			                    CURLOPT_ENCODING => '',
			                    CURLOPT_MAXREDIRS => 10,
			                    CURLOPT_TIMEOUT => 0,
			                    CURLOPT_FOLLOWLOCATION => true,
			                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			                    CURLOPT_CUSTOMREQUEST => 'POST',
			                    CURLOPT_POSTFIELDS =>'{
				                    "orderId": '.$orderId1.',
				                    "tagId": '.$orderTag1.'
			                    }',
			                    CURLOPT_HTTPHEADER => array(
				                    'Content-Type: application/json',
				                    'Authorization: '.common::SHIPSTATION_AUTH_KEY.''
			                    ),
			                ));
			                $response3 = curl_exec($curl2);
			                curl_close($curl2);
			                // echo $response1;

			                // insert tag history
			                $authTafgSql1 = 'SELECT order_number FROM audit_tag_history WHERE order_number = "'.$value1['shop_order_number'].'"';
							$authTagarray1 = parent::selectTable_f_mdl($authTafgSql1);
							if (empty($authTagarray1)) {
								$authTagData1 = [
				                    'order_number' => $value1['shop_order_number'],
				                    'tag_id'       => $orderTag1,
				                    'created_date' => date("Y/m/d h:i:s")
			                	];
			                	$authTadHistory1 = parent::insertTable_f_mdl('audit_tag_history',$authTagData1);
							}
						}
					}

					if(!empty($value['fe_order_id'])){
						$orderTagFE1= '168086'; // ON-DEMAND Automated FE Order
						if (in_array($orderTag1, $tagDataSearch)) {
						}else{
							$curl2 = curl_init();
							curl_setopt_array($curl2, array(
								CURLOPT_URL => 'https://ssapi.shipstation.com/orders/addtag',
								CURLOPT_RETURNTRANSFER => true,
								CURLOPT_ENCODING => '',
								CURLOPT_MAXREDIRS => 10,
								CURLOPT_TIMEOUT => 0,
								CURLOPT_FOLLOWLOCATION => true,
								CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
								CURLOPT_CUSTOMREQUEST => 'POST',
								CURLOPT_POSTFIELDS =>'{
									"orderId": '.$orderId1.',
									"tagId": '.$orderTagFE1.'
								}',
								CURLOPT_HTTPHEADER => array(
									'Content-Type: application/json',
									'Authorization: '.common::SHIPSTATION_AUTH_KEY.''
								),
							));
							$response1 = curl_exec($curl2);
							curl_close($curl2);

						}
						
					}

	            }
	            // add tag in order inside shipstation
			}
		}
	}
}

?>