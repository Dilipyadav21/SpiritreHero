<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include_once 'model/customer_order_view_mdl.php';

class customer_order_view_ctl extends customer_order_view_mdl
{
	public $TempSession = "";

	function __construct(){	
		if(parent::isGET() || parent::isPOST()){
			$this->SITE_ACCESS_KEY = parent::getVal("stkn");
		}
		
		common::CheckLoginSession();
	}
	
	function updateOrderSortListInfo(){
		if(!empty(parent::getVal("method")) && parent::getVal("method") == "update_sort_list_info"){

			$student_name = parent::getVal("student_name");
			$select_sort_list = parent::getVal("select_sort_list");
			$store_order_id = parent::getVal("oid");
			$email = parent::getVal("email");
			$shop_order_number = parent::getVal("shop_order_number");

			parent::updateOrderSortListInfo_f_mdl($store_order_id,$select_sort_list,$student_name,$email,$shop_order_number);
		}
	}

	public function get_student_sort_list_info(){
		if(!empty(parent::getVal("method")) && parent::getVal("method") == "get_student_sort_list_info"){
			
			$store_master_id = $_POST['store_master_id'];
			$sortlist_info = $_POST['sort_list_info'];
			$student_name = $_POST['student_name'];

			$respArray = parent::getSortListNameInfo_f_mdl($store_master_id,$sortlist_info);
				
			$divHtml = '';
			if(isset($respArray) && !empty($respArray)){
				$divHtml .= '<div class="row">';
				$divHtml .= '<div class="form-row col-md-12">';
				$divHtml .= '<div class="form-group col-md-6">
								<label>Select Sort List Name</label>
								<select name="select_sort_list" id="select_sort_list" value="select_sort_list" class="form-control">';
								
								$divHtml .= $respArray;
								
								$divHtml .= '</select>';
				$divHtml .= '</div>';
				$divHtml .= '<div class="form-group col-md-6">
								<label>Student Name</label>
								<input type="text" name ="student_name" class="form-control" id="student_name" value="'.$_POST['student_name'].'"/>';
				$divHtml .= '</div>';
				$divHtml .= '</div>';
				
				$divHtml .= '</div>';
				$divHtml .= '</div>';
			}else{
				$divHtml .= '<div style="text-align: center">No data available.</div>';
			}

			$res['SUCCESS'] = 'TRUE';
			$res['MESSAGE'] = '';
			$res['divHtml'] = $divHtml;
			
			common::sendJson($res);
		}
	}

	function getOrderViewInfo(){
		if (!empty(parent::getVal("oid")) && !empty(parent::getVal("email")) && !empty(parent::getVal("shop_order_number"))) 
		{
			$this->orderId = parent::getVal("oid");
			$this->emailId = parent::getVal("email");
			$this->orderNo = parent::getVal("shop_order_number");
			
			$orderData = $this->get_order_details($this->orderId,$this->emailId,$this->orderNo);
			
			return $orderData;
		}
	}
	function get_order_details($store_orders_master_id,$emailId,$orderNo){
		$sql = 'SELECT `store_orders_master`.* FROM `store_orders_master`
		LEFT JOIN `store_master` ON `store_master`.id = `store_orders_master`.store_master_id
		WHERE `store_orders_master`.id="'.$store_orders_master_id.'" AND `store_orders_master`.cust_email="'.$emailId.'" AND `store_orders_master`.shop_order_number="'.$orderNo.'"
		';
		$list_data = parent::selectTable_f_mdl($sql);
		if(!empty($list_data)){
			$sql = 'SELECT `store_order_items_master`.id,store_order_items_master.shop_product_id, `store_order_items_master`.store_owner_product_master_id,`store_order_items_master`.shop_variant_id, `store_order_items_master`.title, `store_order_items_master`.quantity, `store_order_items_master`.price, `store_order_items_master`.sku,`store_order_items_master`.variant_title,store_owner_product_variant_master.image
			FROM `store_order_items_master`
			LEFT JOIN store_owner_product_variant_master ON store_owner_product_variant_master.id = `store_order_items_master`.store_owner_product_variant_master_id
			WHERE `store_order_items_master`.store_orders_master_id = "'.$store_orders_master_id.'"
			';

			$var_data = parent::selectTable_f_mdl($sql);

			$list_data[0]['var_data'] = $var_data;
			return $list_data;
		}else{
			header('location:index.php');
		}
	}

	public function getSortListMissingDetails()
	{
		$sql = 'SELECT store_orders_master.id,shop_order_id, store_orders_master.shop_order_number, store_orders_master.total_price,store_orders_master.cust_email, store_orders_master.cust_name, store_orders_master.created_on, CONCAT("https://app.spirithero.com/customer-order-view.php?stkn=", "&oid=", store_orders_master.id, "&email=", cust_email, "&shop_order_number=", shop_order_number) AS link FROM store_orders_master INNER JOIN store_master ON store_orders_master.store_master_id = store_master.id where (sortlist_info = "" OR student_name = "") and store_sale_type="Flash Sale" AND store_master.store_fulfillment_type!="SHIP_EACH_FAMILY_HOME" AND store_orders_master.created_on BETWEEN "2021-06-01" and "2021-09-31" ';
		
		$list_data['sort_list'] = parent::selectTable_f_mdl($sql);
		return $list_data;
	}

	/* Task 94 start */
	public function getCollectionStatus($collection_id)
	{
		$sql1       = 'SELECT price_limit_shipping_on_demand,free_shipping_text,away_from_shipping_text,congrate_shipping_text,text_color,color,is_enable_ship_bar,font_size,font_weight,text_align FROM general_settings_master';
		$list_data1 = parent::selectTable_f_mdl($sql1);
		$price_limit_shipping_on_demand = '';
		$free_shipping_text             = '';
		$away_from_shipping_text        = '';
		$congrate_shipping_text         = '';
		$text_color                     = '';
		$color                          = '';
		$is_enable_ship_bar             = '';
		if(!empty($list_data1)){
			$price_limit_shipping_on_demand = $list_data1[0]['price_limit_shipping_on_demand'];
			$free_shipping_text             = $list_data1[0]['free_shipping_text'];
			$away_from_shipping_text        = $list_data1[0]['away_from_shipping_text'];
			$congrate_shipping_text         = $list_data1[0]['congrate_shipping_text'];
			$text_color                     = $list_data1[0]['text_color'];
			$color                          = $list_data1[0]['color'];
			$is_enable_ship_bar             = (int)$list_data1[0]['is_enable_ship_bar'];
			$font_size                      = $list_data1[0]['font_size'];
			$font_weight                    = $list_data1[0]['font_weight'];
			$text_align                     = $list_data1[0]['text_align'];
		}
		if(isset($_REQUEST['collection_id']) && !empty($_REQUEST['collection_id'])){
			$sql       = 'SELECT store_sale_type_master_id FROM store_master WHERE shop_collection_id = '.$collection_id.' ';
			$list_data = parent::selectTable_f_mdl($sql);
			$respArray = array();
			if(!empty($list_data)){
				$store_sale_type_master_id = $list_data[0]['store_sale_type_master_id'];
				if($store_sale_type_master_id == 2 ){
					$respArray['store_sale_type']         = 'On-Demand';
					$respArray['price_limit_on_demand']   = $price_limit_shipping_on_demand;
					$respArray['free_shipping_text']      = $free_shipping_text;
					$respArray['away_from_shipping_text'] = $away_from_shipping_text;
					$respArray['congrate_shipping_text']  = $congrate_shipping_text;
					$respArray['text_color']              = $text_color;
					$respArray['color']                   = $color;
					$respArray['is_enable_ship_bar']      = $is_enable_ship_bar;
					$respArray['font_size']               = $font_size;
					$respArray['font_weight']             = $font_weight;
					$respArray['text_align']              = $text_align;
				}
				else{
					$respArray['store_sale_type'] = 'Flash Sale';
				}
				$respArray['status'] = 1;
			}
			else{
				$respArray['status']                = 0;
			}
		}
		elseif(isset($_REQUEST['free_ship_cart_prod_id']) && !empty($_REQUEST['free_ship_cart_prod_id'])){
			$productSql  = 'SELECT store_master_id FROM store_owner_product_master where shop_product_id = '.$_REQUEST['free_ship_cart_prod_id'].' ';
			$productData = parent::selectTable_f_mdl($productSql);
			$store_master_id ='';
			if(!empty($productData)){
				$store_master_id = $productData[0]['store_master_id'];
				$sql       = 'SELECT store_sale_type_master_id FROM store_master WHERE id = '.$store_master_id.' ';
				$list_data = parent::selectTable_f_mdl($sql);
				$respArray = array();
				if(!empty($list_data)){
					$store_sale_type_master_id = $list_data[0]['store_sale_type_master_id'];
					if($store_sale_type_master_id == 2 ){
						$respArray['store_sale_type']         = 'On-Demand';
						$respArray['price_limit_on_demand']   = $price_limit_shipping_on_demand;
						$respArray['price_limit_on_demand']   = $price_limit_shipping_on_demand;
						$respArray['free_shipping_text']      = $free_shipping_text;
						$respArray['away_from_shipping_text'] = $away_from_shipping_text;
						$respArray['congrate_shipping_text']  = $congrate_shipping_text;
						$respArray['text_color']              = $text_color;
						$respArray['color']                   = $color;
						$respArray['is_enable_ship_bar']      = $is_enable_ship_bar;
					}
					else{
						$respArray['store_sale_type'] = 'Flash Sale';
					}
					$respArray['status'] = 1;
				}
				else{
					$respArray['status']                = 0;
				}
			}
		}
		common::sendJson($respArray);	
	}
	/* Task 94 end */

	public function verifyCouponCodeOld($custom_coupon_code)
	{
		require_once('lib/class_graphql.php');
		require_once('lib/shopify.php');
		require_once('lib/php-shopify-sdk/vendor/autoload.php');
		$res = [];
		$shop_data = parent::getShopCredentials_f_mdl(common::PARENT_STORE_NAME,true);
		if(!empty($shop_data)) {
			$shop = $shop_data[0]['shop_name'];
			$token = $shop_data[0]['token'];

			$config = array(
				'ShopUrl' => $shop,
				'AccessToken' => $token,
			);
			
			$shopify = new PHPShopify\ShopifySDK($config);
			if(isset($custom_coupon_code) && !empty($custom_coupon_code)){
				$code = $custom_coupon_code;
				$descountURL = $shopify->getAdminUrl() . 'discount_codes/lookup.json?code=' . urlencode($code);
				$httpHeaders['X-Shopify-Access-Token'] = $token;
				$curl = curl_init();
					curl_setopt_array($curl, array(
					CURLOPT_URL => $descountURL,
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_ENCODING => '',
					CURLOPT_MAXREDIRS => 10,
					CURLOPT_TIMEOUT => 0,
					CURLOPT_FOLLOWLOCATION => true,
					CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
					CURLOPT_CUSTOMREQUEST => 'GET',
					CURLOPT_HTTPHEADER => array(
						'X-Shopify-Access-Token: '.$token.''
					),
				));
				$responseUrl = curl_exec($curl);
				$result    =  json_decode($responseUrl,true);

				if(isset($result['errors']) && !empty($result['errors'])){
					$res['MESSAGE'] = $result['errors'];
					$res['STATUS'] = false;
				}else{
					if (!empty($result) && is_array($result)) {
						$priceRule = new \PHPShopify\PriceRule($result['discount_code']['price_rule_id']);
						$priceRuleRes = $priceRule->get();
						// echo "<pre>";print_r($priceRuleRes);
						$finalRes = [];
						if (!empty($priceRuleRes) && is_array($priceRuleRes)) {
							$startDate = date('Y-m-d');
							if(isset($priceRuleRes['starts_at'])){
								$stdateArr = explode('T', $priceRuleRes['starts_at']);
								$startDate = $stdateArr[0];
							}

							$endDate = date('Y-m-d');
							if(isset($priceRuleRes['ends_at'])){
								$dateArr = explode('T', $priceRuleRes['ends_at']);
								$endDate = $dateArr[0];
							}

							$date = date('Y-m-d');
							if($date > $endDate){
								$res['MESSAGE'] = "Invalid coupon";
								$res['STATUS'] = false;
							}else{
								$finalRes = [
									'code' => $result['discount_code']['code'],
									'value' => ltrim($priceRuleRes['value'], '-'),
									'type' => $priceRuleRes['value_type'],
									'starts_at'=>$startDate,
									'ends_at'=>$endDate,
									'target_selection'=>$priceRuleRes['target_selection'],
									'usage_limit'=>$priceRuleRes['usage_limit'],
									'usage_count'=>$result['discount_code']['usage_count'],
									'once_per_customer' =>$priceRuleRes['once_per_customer'],
									'customer_selection' =>$priceRuleRes['customer_selection'],
									'target_type' =>$priceRuleRes['target_type'],
									'target_selection' =>$priceRuleRes['target_selection'],
									'allocation_method' =>$priceRuleRes['allocation_method'],
									'allocation_limit' =>$priceRuleRes['allocation_limit'],  
									'entitled_product_ids'=>$priceRuleRes['entitled_product_ids'],
									'entitled_variant_ids'=>$priceRuleRes['entitled_variant_ids'],
									'entitled_collection_ids'=>$priceRuleRes['entitled_collection_ids'],
									'prerequisite_customer_ids'=>$priceRuleRes['prerequisite_customer_ids'],
									'prerequisite_subtotal_range'=>$priceRuleRes['prerequisite_subtotal_range'],
									'prerequisite_quantity_range'=>$priceRuleRes['prerequisite_quantity_range'],
									'prerequisite_to_entitlement_quantity_ratio'=>$priceRuleRes['prerequisite_to_entitlement_quantity_ratio'],
									'prerequisite_to_entitlement_purchase'=>$priceRuleRes['prerequisite_to_entitlement_purchase']
								];

								$res['MESSAGE'] = "Coupon code is valid";
								$res['STATUS'] = true;
								$res['RESULT'] =$finalRes;
							}	
						}
					}
				}
			}	
		}else{
			$res['MESSAGE'] = "Invalid token";
			$res['STATUS'] = true;
		}
		echo json_encode($res);die();	
	}

	public function verifyCouponCode($custom_coupon_code,$shop_collection_id)
	{
		$res = $entitled_product_ids = $entitled_collection_ids = [];
		
		if(isset($custom_coupon_code) && !empty($custom_coupon_code)){
			$code = $custom_coupon_code;

			$sql='SELECT * FROM `coupon_code_series_master` WHERE coupon_code="'.$code.'" ';
    		$ccsm_data = parent::selectTable_f_mdl($sql);
    		if(!empty($ccsm_data)){
				$coupon_code_master_id=$ccsm_data[0]['coupon_code_master_id'];
				$sql='SELECT id,discount_type,discount_code,discount_value,discount_price,minimum_purchase,minimum_purchase_value,discount_code_limit_type,discount_code_limit,apply_for,apply_once_per_order,discount_code_start_date,discount_code_end_date,discount_status,apply_storetype,applied_store_checkbox FROM `coupon_code_master` WHERE discount_status="0" AND id="'.$coupon_code_master_id.'" ';
    			$ccm_data = parent::selectTable_f_mdl($sql);
			}else{
				$sql='SELECT id,discount_type,discount_code,discount_value,discount_price,minimum_purchase,minimum_purchase_value,discount_code_limit_type,discount_code_limit,apply_for,apply_once_per_order,discount_code_start_date,discount_code_end_date,discount_status,apply_storetype,applied_store_checkbox FROM `coupon_code_master` WHERE discount_status="0" AND discount_code="'.$code.'" ';
    			$ccm_data = parent::selectTable_f_mdl($sql);
			}
			//$result    =  json_decode($responseUrl,true);

			if(isset($ccm_data) && empty($ccm_data)){
				$res['MESSAGE'] = 'Not Found';
				$res['STATUS'] = false;
			}else{
				if (!empty($ccm_data)) {
					$discount_code_id=$ccm_data[0]['id'];

					$discount_type=$ccm_data[0]['discount_type'];
					$discount_code=$ccm_data[0]['discount_code'];
					$discount_value=$ccm_data[0]['discount_value'];
					$discount_price=$ccm_data[0]['discount_price'];
					$minimum_purchase=$ccm_data[0]['minimum_purchase'];
					$minimum_purchase_value=$ccm_data[0]['minimum_purchase_value'];
					$discount_code_limit_type=$ccm_data[0]['discount_code_limit_type'];
					$discount_code_limit=$ccm_data[0]['discount_code_limit'];
					$discount_code_start_date=$ccm_data[0]['discount_code_start_date'];
					$discount_code_end_date=$ccm_data[0]['discount_code_end_date'];
					$apply_once_per_order=$ccm_data[0]['apply_once_per_order'];
					$apply_storetype=$ccm_data[0]['apply_storetype'];
					$applied_store_checkbox=$ccm_data[0]['applied_store_checkbox'];
					if($discount_value=='0'){
						$discount_type='percentage';
					}else{
						$discount_type='fixed_amount';
					}
					$prerequisite_subtotal_range = null;
					$prerequisite_quantity_range = null;
					if($minimum_purchase=='1'){
						$prerequisite_subtotal_range=array('greater_than_or_equal_to'=>$minimum_purchase_value);

						// $prerequisite_subtotal_range=$minimum_purchase_value;
					}elseif($minimum_purchase=='2'){
						$prerequisite_quantity_range=array('greater_than_or_equal_to'=>$minimum_purchase_value);
					}

					$sqlprod='SELECT cccm.store_master_id,cccm.store_owner_product_master_id,sm.shop_collection_id,sopm.shop_product_id FROM coupon_code_collection_master as cccm LEFT JOIN store_master as sm ON sm.id=cccm.store_master_id LEFT JOIN store_owner_product_master as sopm ON sopm.id=cccm.store_owner_product_master_id  WHERE  cccm.coupon_code_master_id="'.$discount_code_id.'" AND (cccm.store_owner_product_master_id !="" OR  cccm.store_owner_product_master_id IS NOT NULL) ';
					$sqlproddata = parent::selectTable_f_mdl($sqlprod);
					if(!empty($sqlproddata)){
						foreach($sqlproddata as $proddata_val){
							$entitled_product_ids[]=$proddata_val['shop_product_id'];
						}
					}

					$sqlstore='SELECT cccm.store_master_id,cccm.store_owner_product_master_id,sm.shop_collection_id,sopm.shop_product_id FROM coupon_code_collection_master as cccm LEFT JOIN store_master as sm ON sm.id=cccm.store_master_id LEFT JOIN store_owner_product_master as sopm ON sopm.id=cccm.store_owner_product_master_id  WHERE  cccm.coupon_code_master_id="'.$discount_code_id.'" AND (cccm.store_master_id !="" OR  cccm.store_master_id IS NOT NULL) ';
					$sqlstoredata = parent::selectTable_f_mdl($sqlstore);
					if(!empty($sqlstoredata)){
						foreach($sqlstoredata as $storedata_val){
							$entitled_collection_ids[]=$storedata_val['shop_collection_id'];
						}
					}

					$startDate = date('Y-m-d H:i:s');
					if(isset($discount_code_start_date)){
						$startDate = date('Y-m-d H:i:s', strtotime($discount_code_start_date));
					}

					$endDate = date('Y-m-d H:i:s');
					if(isset($discount_code_end_date)){
						$endDate = date('Y-m-d H:i:s', strtotime($discount_code_end_date));;
					}

					if($discount_code_limit_type=='0'){
						$sql='SELECT count(shop_order_number) as count FROM store_orders_master where discount_code="'.$code.'"  order by id desc';
						$orderdata = parent::selectTable_f_mdl($sql);
					}else if($discount_code_limit_type=='1'){
						$sql = 'SELECT id FROM store_master WHERE shop_collection_id="'.$shop_collection_id.'" ';
						$storedata = parent::selectTable_f_mdl($sql);
						if (!empty($storedata)) {
							$sql = 'SELECT count(shop_order_number) as count FROM store_orders_master WHERE store_master_id="'.$storedata[0]['id'].'" AND discount_code="'.$code.'"';
							$orderdata = parent::selectTable_f_mdl($sql);
						}
					}

					if($applied_store_checkbox=="true"){
						$sql = 'SELECT id,store_sale_type_master_id FROM store_master WHERE shop_collection_id="'.$shop_collection_id.'" ';
						$storeMasterdata = parent::selectTable_f_mdl($sql);
						if($apply_storetype == $storeMasterdata[0]['store_sale_type_master_id']){
						}else{
							$res['MESSAGE'] = "Invalid coupon: Coupon is not valid for this collection";
							$res['STATUS'] = false;
							echo json_encode($res);die();
						}
					}

					$orderCount='0';
					if(!empty($orderdata)){
						$orderCount=$orderdata[0]['count'];
					}

					$date = date('Y-m-d H:i:s');

					$currTimestamp = strtotime($date);
					$startTimestamp = strtotime($startDate);
					$endTimestamp = strtotime($endDate);

					if ($currTimestamp > $endTimestamp) {
					    $res['MESSAGE'] = "Invalid coupon: Coupon has expired";
					    $res['STATUS'] = false;
					} else if ($currTimestamp < $startTimestamp) {
					    $res['MESSAGE'] = "Invalid coupon: Coupon is not yet active";
					    $res['STATUS'] = false;
					}else if (!empty($discount_code_limit) && $discount_code_limit <= $orderCount) {
					    $res['MESSAGE'] = "Invalid coupon: Coupon usage limit exceeded";
					    $res['STATUS'] = false;
					}else{
						$finalRes = [
							'code' => $code,
							'value' => trim($discount_price),
							'type' => $discount_type,
							'starts_at'=>$startDate,
							'ends_at'=>$endDate,
							'usage_limit'=>$discount_code_limit,
							'apply_once_per_order'=>$apply_once_per_order,
							//'usage_count'=>'',
							'once_per_customer' =>false,
							'customer_selection' =>'all',
							'target_type' =>'line_item',
							'target_selection' =>'all',
							'allocation_method' =>'across',
							'allocation_limit' =>'',  
							'entitled_product_ids'=>$entitled_product_ids,
							'entitled_variant_ids'=>[],
							'entitled_collection_ids'=>$entitled_collection_ids,
							'prerequisite_customer_ids'=>[],
							'prerequisite_subtotal_range'=>$prerequisite_subtotal_range,
							'prerequisite_quantity_range'=>$prerequisite_quantity_range,
							'prerequisite_to_entitlement_quantity_ratio'=>'',
							'prerequisite_to_entitlement_purchase'=>'{prerequisite_amount: null}',
							'apply_storetype'=>$apply_storetype,
							'applied_store_checkbox'=>$applied_store_checkbox
						];

						$res['MESSAGE'] = "Coupon code is valid";
						$res['STATUS'] = true;
						$res['RESULT'] =$finalRes;
					}
				}
			}
		}	

		echo json_encode($res);die();	
	}

	public function checkProductStatus()
	{	
		$response = [];
		$response['status']=false;
		if(!empty(parent::getVal("method")) && parent::getVal("method") == "checkProductStatus"){
			$data = self::productInfoGraphql(parent::getVal("productHandles"));
			if (in_array("DRAFT", $data)){
				$response['status']=true;
			}else{
				$response['status']=false;
			}
		}
		echo json_encode($response);die();
	}

	public function productInfoGraphql($handles)
	{
		require_once('lib/shopify.php');
		require_once('lib/functions.php');
		$shop_data = parent::getShopCredentials_f_mdl(common::PARENT_STORE_NAME,true);
		$storeInfo = $shop_data[0];
        $shopifyObject = new ShopifyClient($storeInfo["shop_name"], $storeInfo["token"], common::SHOPIFY_API_KEY, common::SHOPIFY_SECRET);
		
		require_once('lib/class_graphql.php');
		
		$headers = array(
			'X-Shopify-Access-Token' => $storeInfo["token"]
		);
		$graphql = new Graphql($storeInfo["shop_name"], $headers);
		$productStatusArray[] = '';
		foreach ($handles as $productHandle) {
			$getProductTitleMutationQuery = '
		    {
			  productByHandle(handle: "'.$productHandle.'") {
			    id
			    status
			  }
			}';
			$productTitleResponse = $graphql->runByQuery($getProductTitleMutationQuery);
			if(isset($productTitleResponse['data']['productByHandle']) && !empty($productTitleResponse['data']['productByHandle'])){
				$productStatus = $productTitleResponse['data']['productByHandle']['status'];
				$productStatusArray[] = $productStatus;
			}
		}
		return $productStatusArray;
	}
}