<?php
include_once 'model/sa_addedit_coupon_code_mdl.php';
$path = preg_replace('/controller(?!.*controller).*/','',__DIR__);
include_once $path . '/libraries/Aws3.php';


class sa_addedit_coupon_code_ctl extends sa_addedit_coupon_code_mdl
{
	public $TempSession = "";
	function __construct()
	{
		if (parent::isGET() || parent::isPOST()) {
			if(parent::getVal("method")){
				$this->checkRequestProcess(parent::getVal("method"));
			}else{
				$this->SITE_ACCESS_KEY = parent::getVal("stkn");
			}
			//$this->SITE_ACCESS_KEY = parent::getVal("stkn");
		}
		common::CheckLoginSession();
	}

    function checkRequestProcess($requestFor){
        if($requestFor != ""){
            switch($requestFor){
				case "add_edit_coupon_code":
					$this->addEditCouponCode();
                break;
                case "coupon_code_check":
					$this->coupon_code_check();
                break;
				case "coupon_code_limit_check":
					$this->coupon_code_limit_check();
                break;
			}
        }
    }
	
	function getCouponCodeInfo($id){
		return parent::getCouponCodeInfo_f_mdl($id);
	}

	public function getCouponCodeCollectionStores($coupon_code_id){
		 $sql = 'SELECT cccm.store_master_id,sm.store_name,sm.shop_collection_id,sm.shop_collection_handle FROM `coupon_code_collection_master` as cccm INNER JOIN store_master as sm ON cccm.store_master_id=sm.id WHERE cccm.coupon_code_master_id="'.$coupon_code_id.'"  ORDER BY sm.store_name ASC';
		return parent::selectTable_f_mdl($sql);
	}

    public function getCouponCodeProd($coupon_code_id){
		 $sql = 'SELECT cccm.store_owner_product_master_id,sopm.shop_product_id,sopm.product_title,sm.store_name FROM coupon_code_collection_master as cccm INNER JOIN  `store_owner_product_master`as sopm ON cccm.store_owner_product_master_id=sopm.id  INNER JOIN store_master as sm ON sm.id=sopm.store_master_id WHERE cccm.coupon_code_master_id="'.$coupon_code_id.'" ORDER BY sm.store_name ASC ';
		return parent::selectTable_f_mdl($sql);
	}


    public function getCollectionInfo(){
		 $sql = 'SELECT id,store_name,shop_collection_id,shop_collection_handle FROM `store_master` WHERE is_collection_created="1" AND status="1" ORDER BY store_name ASC';
		return parent::selectTable_f_mdl($sql);
	}

    public function getProductInfo(){
		$sql = 'SELECT spm.id,spm.shop_product_id,spm.product_title,sm.store_name,sm.product_name_identifier FROM `store_owner_product_master`as spm INNER JOIN store_master as sm ON sm.id=spm.store_master_id WHERE sm.status="1" AND spm.is_soft_deleted="0" AND (spm.shop_product_id !="" OR spm.shop_product_id IS NOT NULL) ORDER BY sm.store_name ASC';
		return parent::selectTable_f_mdl($sql);
	}

    public function coupon_code_check(){
		if (parent::isPOST()) {
			$res='';
			if (!empty(parent::getVal("method")) && parent::getVal("method") == "coupon_code_check") {
				$discount_code= parent::getVal('discount_code');
				$id= parent::getVal('id');
				$status=0;
				if(!empty($id)){
					$sql = 'SELECT discount_code from coupon_code_master where discount_code = "'.$discount_code.'" AND id!="'.$id.'" ';
				}else{
					$sql = 'SELECT discount_code from coupon_code_master where discount_code ="'.$discount_code.'" ';	
				}
				$list_data = parent::selectTable_f_mdl($sql);

				if(!empty($list_data)){
                    $status=1;
                }else{
                    $status=0;
                }
				
				echo $status;
			}
			die;
		}
	}

	public function coupon_code_limit_check(){
		if (parent::isPOST()) {
			$res='';
			if (!empty(parent::getVal("method")) && parent::getVal("method") == "coupon_code_limit_check") {
				$discount_code_limit= parent::getVal('discount_code_limit');
				$discount_code_id= parent::getVal('id');
				
				$sql='SELECT DISTINCT coupon_code from coupon_code_series_master  where coupon_code_master_id="'.$discount_code_id.'" ';
				$discountseriesdata = parent::selectTable_f_mdl($sql);
				$discountcodes = implode(',', $discountseriesdata);
				if(!empty($discountcodes)){
					$sql='SELECT count(som.shop_order_number) as count FROM store_orders_master as som where som.discount_code IN ('.$discountcodes.') ';
					$orderdata = parent::selectTable_f_mdl($sql);
					$status=0;
					if(!empty($orderdata)){
						if(!empty($discount_code_limit) && $discount_code_limit<=$orderdata[0]['count']){
							$status=1;
						}
					}
				}else{
					$sql='SELECT count(som.shop_order_number) as count FROM store_orders_master as som INNER JOIN coupon_code_master as ccm ON ccm.discount_code=som.discount_code where ccm.id="'.$discount_code_id.'" ';
					$orderdata = parent::selectTable_f_mdl($sql);
					$status=0;
					if(!empty($orderdata)){
						if(!empty($discount_code_limit) && $discount_code_limit<=$orderdata[0]['count']){
							$status=1;
						}
					}
				}
				echo $status;
			}
			die;
		}
	}


	function addEditCouponCode(){
		$res=[];
		if(parent::isPOST()){
			if(!empty(parent::getVal("method")) && parent::getVal("method") == "add_edit_coupon_code"){
				$id = parent::getVal("id");
				$discount_type 				= parent::getVal("discount_type");
				$discount_code      		= parent::getVal("discount_code");
                $discount_value       		= parent::getVal("discount_value");
                $discount_price       		= parent::getVal("discount_price");
                $minimum_purchase       	= parent::getVal("minimum_purchase");
                $minimum_purchase_value 	= parent::getVal("minimum_purchase_value");
                $discount_limit_type    	= parent::getVal("discount_limit_type");
                $discount_code_limit    	= parent::getVal("discount_code_limit");
                $apply_for       			= parent::getVal("apply_for");
                $discount_code_start_date  	= parent::getVal("discount_code_start_date");
                $discount_code_end_date  	= parent::getVal("discount_code_end_date");
                $discount_series_name  	    = parent::getVal("discount_series_name");
                $apply_once_per_order  	    = parent::getVal("apply_once_per_order");
                $apply_storetype  	    	= parent::getVal("apply_storetype");
                $store_type_chk  	    	= parent::getVal("store_type_chk");
                
                if($apply_for=='0'){
                	$collectionName       		= parent::getVal("apply_for_val");
                	$collection_name = explode(',', $collectionName);
                }else{
                	$productName       		= parent::getVal("apply_for_val");
                	$product_name = explode(',', $productName);
                }

                if (!empty($discount_code_end_date)) {
				    $discount_code_end_date = date('Y-m-d H:i:s', strtotime($discount_code_end_date));
				}else{
					$discount_code_end_date ='null';
				}
				
                $discount_code_start_date = date('Y-m-d H:i:s', strtotime($discount_code_start_date));

				
				if(!empty($id)){
					if(!empty($discount_code)){
						if($discount_limit_type=='0'){
							$sql='SELECT count(shop_order_number) as count FROM store_orders_master where discount_code="'.$discount_code.'" ';
							$orderdata = parent::selectTable_f_mdl($sql);
							if(!empty($orderdata)){
								if(!empty($discount_code_limit) && $discount_code_limit<=$orderdata[0]['count']){
									$res['SUCCESS'] = 'FALSE';
									$res['MESSAGE'] = 'Discount limit exceeds the already utilized numbers.';
									common::sendJson($res);die;
								}
							}

						}else{

							$sql = 'SELECT DISTINCT store_master_id FROM store_orders_master WHERE discount_code="'.$discount_code.'"';
							$orderstoredata = parent::selectTable_f_mdl($sql);
							if (!empty($orderstoredata)) {
								foreach ($orderstoredata as $singlestore) {
									// Count the number of orders for each store using the discount
									$sql = 'SELECT count(shop_order_number) as count FROM store_orders_master WHERE store_master_id="'.$singlestore['store_master_id'].'" AND discount_code="'.$discount_code.'"';
									$orderdata = parent::selectTable_f_mdl($sql);
									if (!empty($orderdata) && $discount_code_limit <= $orderdata[0]['count']) {
										$res['SUCCESS'] = 'FALSE';
										$res['MESSAGE'] = 'Discount limit exceeds the already utilized numbers.';
										common::sendJson($res);die;
									}
								}
							}
						}
						
					}

					$updated_at = date('Y-m-d H:i:s');

					$update_data = [
						//'discount_type' =>trim($discount_type),
						'discount_code' =>trim($discount_code),
						'discount_series_name' =>trim($discount_series_name),
						'discount_value' =>trim($discount_value),
						'discount_price' =>trim($discount_price),
						'minimum_purchase' =>trim($minimum_purchase),
						'minimum_purchase_value' =>trim($minimum_purchase_value),
						'discount_code_limit_type' =>trim($discount_limit_type),
						'discount_code_limit' =>trim($discount_code_limit),
						'apply_for' =>trim($apply_for),
						'apply_once_per_order' =>trim($apply_once_per_order),
						'discount_code_start_date' =>trim($discount_code_start_date),
						'discount_code_end_date' =>trim($discount_code_end_date),
						'apply_storetype' =>trim($apply_storetype),
						'applied_store_checkbox' =>trim($store_type_chk),
						'updated_on' => date('Y-m-d H:i:s')
					];
					$som_arr =parent::updateTable_f_mdl('coupon_code_master',$update_data,'id="'.$id.'"');
					
					if($discount_type=='1'){
						parent::deleteTable_f_mdl('coupon_code_collection_master', 'coupon_code_master_id ="'. $id.'"');
						if(trim($apply_for)=='0'){
							foreach($collection_name as $singleval){
								$insertData = [
									'coupon_code_master_id' 		=>trim($id),
									'store_master_id' 				=>trim($singleval),
									'created_on'            		=> date('Y-m-d H:i:s'),
									'updated_on'            		=> date('Y-m-d H:i:s')
								];
								$insertData_arr =parent::insertTable_f_mdl('coupon_code_collection_master',$insertData);
							}
						}else if(trim($apply_for)=='1'){
							foreach($product_name as $singlprod){
								$insertData = [
									'coupon_code_master_id' 		=>trim($id),
									'store_owner_product_master_id' =>trim($singlprod),
									'created_on'            		=> date('Y-m-d H:i:s'),
									'updated_on'            		=> date('Y-m-d H:i:s')
								];
								$insertData_arr =parent::insertTable_f_mdl('coupon_code_collection_master',$insertData);
							}
						}
					}

					$res['SUCCESS'] = 'TRUE';
					$res['MESSAGE'] = 'Discount code updated successfully.';

				}else{
					if(!empty($discount_code)){
						$sql = 'SELECT discount_code from coupon_code_master where discount_code ="'.$discount_code.'" ';
						$Cdata = parent::selectTable_f_mdl($sql);
						if(!empty($Cdata)){
							$res['SUCCESS'] = 'FALSE';
							$res['MESSAGE'] = 'Discount code already exist.';
							common::sendJson($res);die;
						}else{

							$sql = 'SELECT coupon_code from coupon_code_series_master where coupon_code ="'.$discount_code.'" ';
							$ccsdata = parent::selectTable_f_mdl($sql);
							if(!empty($ccsdata)){
								$res['SUCCESS'] = 'FALSE';
								$res['MESSAGE'] = 'Discount code already exist.';
								common::sendJson($res);die;
							}else{

								if(empty($discount_code_end_date)){
									$insertData = [
										'discount_type' 			=>trim($discount_type),
										'discount_code' 			=>trim($discount_code),
										'discount_series_name' 		=>trim($discount_series_name),
										'discount_value' 			=>trim($discount_value),
										'discount_price' 			=>trim($discount_price),
										'minimum_purchase' 			=>trim($minimum_purchase),
										'minimum_purchase_value' 	=>trim($minimum_purchase_value),
										'discount_code_limit_type' 	=>trim($discount_limit_type),
										'discount_code_limit' 		=>trim($discount_code_limit),
										'apply_for' 				=>trim($apply_for),
										'apply_once_per_order' 		=>trim($apply_once_per_order),
										'discount_code_start_date' 	=>$discount_code_start_date,
										'apply_storetype' 			=>trim($apply_storetype),
										'applied_store_checkbox' 	=>trim($store_type_chk),
										'created_on'            	=> date('Y-m-d H:i:s')
									];
								}else{
									$insertData = [
										'discount_type' 			=>trim($discount_type),
										'discount_code' 			=>trim($discount_code),
										'discount_value' 			=>trim($discount_value),
										'discount_series_name' 		=>trim($discount_series_name),
										'discount_price' 			=>trim($discount_price),
										'minimum_purchase' 			=>trim($minimum_purchase),
										'minimum_purchase_value' 	=>trim($minimum_purchase_value),
										'discount_code_limit_type' 	=>trim($discount_limit_type),
										'discount_code_limit' 		=>trim($discount_code_limit),
										'apply_for' 				=>trim($apply_for),
										'apply_once_per_order' 		=>trim($apply_once_per_order),
										'discount_code_start_date' 	=>$discount_code_start_date,
										'discount_code_end_date' 	=>$discount_code_end_date,
										'apply_storetype' 			=>trim($apply_storetype),
										'applied_store_checkbox' 	=>trim($store_type_chk),
										'created_on'            	=> date('Y-m-d H:i:s')
									];
			
								}
								$insertData_arr =parent::insertTable_f_mdl('coupon_code_master',$insertData);
							}
						}
					}else{
						if(empty($discount_code_end_date)){
							$insertData = [
								'discount_type' 			=>trim($discount_type),
								'discount_code' 			=>trim($discount_code),
								'discount_series_name' 		=>trim($discount_series_name),
								'discount_value' 			=>trim($discount_value),
								'discount_price' 			=>trim($discount_price),
								'minimum_purchase' 			=>trim($minimum_purchase),
								'minimum_purchase_value' 	=>trim($minimum_purchase_value),
								'discount_code_limit_type' 	=>trim($discount_limit_type),
								'discount_code_limit' 		=>trim($discount_code_limit),
								'apply_for' 				=>trim($apply_for),
								'apply_once_per_order' 		=>trim($apply_once_per_order),
								'discount_code_start_date' 	=>$discount_code_start_date,
								'apply_storetype' 			=>trim($apply_storetype),
								'applied_store_checkbox' 	=>trim($store_type_chk),
								'created_on'            	=> date('Y-m-d H:i:s')
							];
						}else{
							$insertData = [
								'discount_type' 			=>trim($discount_type),
								'discount_code' 			=>trim($discount_code),
								'discount_value' 			=>trim($discount_value),
								'discount_series_name' 		=>trim($discount_series_name),
								'discount_price' 			=>trim($discount_price),
								'minimum_purchase' 			=>trim($minimum_purchase),
								'minimum_purchase_value' 	=>trim($minimum_purchase_value),
								'discount_code_limit_type' 	=>trim($discount_limit_type),
								'discount_code_limit' 		=>trim($discount_code_limit),
								'apply_for' 				=>trim($apply_for),
								'apply_once_per_order' 		=>trim($apply_once_per_order),
								'discount_code_start_date' 	=>$discount_code_start_date,
								'discount_code_end_date' 	=>$discount_code_end_date,
								'apply_storetype' 			=>trim($apply_storetype),
								'applied_store_checkbox' 	=>trim($store_type_chk),
								'created_on'            	=> date('Y-m-d H:i:s')
							];
	
						}
						$insertData_arr =parent::insertTable_f_mdl('coupon_code_master',$insertData);
					}

					if(isset($insertData_arr['insert_id']) && !empty($insertData_arr['insert_id'])){
						$coupon_code_id=$insertData_arr['insert_id'];
						if($discount_type=='1'){

							if(trim($apply_for)=='0'){
								foreach($collection_name as $singleval){
									$insertData = [
										'coupon_code_master_id' 		=>trim($coupon_code_id),
										'store_master_id' 				=>trim($singleval),
										'created_on'            		=> date('Y-m-d H:i:s'),
										'updated_on'            		=> date('Y-m-d H:i:s')
									];
									$insertData_arr =parent::insertTable_f_mdl('coupon_code_collection_master',$insertData);
								}
							}else if(trim($apply_for)=='1'){
								foreach($product_name as $singlprod){
									$insertData = [
										'coupon_code_master_id' 		=>trim($coupon_code_id),
										'store_owner_product_master_id' =>trim($singlprod),
										'created_on'            		=> date('Y-m-d H:i:s'),
										'updated_on'            		=> date('Y-m-d H:i:s')
									];
									$insertData_arr =parent::insertTable_f_mdl('coupon_code_collection_master',$insertData);
								}
							}
						}

						if(isset($_FILES['dis_csv_file']) && !empty($_FILES['dis_csv_file'])){

							$mimeType 			= $_FILES['dis_csv_file']['type'];
							$fileName 			= $_FILES['dis_csv_file']['name'];
							$tempfileName 		= $_FILES['dis_csv_file']['tmp_name'];
							$fileExt 			= strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
							if($_FILES["dis_csv_file"]["size"] > 0){
								$file = fopen($tempfileName, "r");
								$codeCount = 0;
								while (($csvData = fgetcsv($file, 10000, ",")) !== FALSE)
								{	

									if($csvData[0] != ''){
										$couponCode = trim($csvData[0]);
										$couponsql = 'SELECT discount_code from coupon_code_master where discount_code ="'.$couponCode.'" ';
										$cc_data = parent::selectTable_f_mdl($couponsql);
										if(empty($cc_data)){
											$sql = 'SELECT coupon_code from coupon_code_series_master where coupon_code ="'.$couponCode.'" ';
											$ccs_data = parent::selectTable_f_mdl($sql);
											if(empty($ccs_data)){
												$insertCSVData = [
													'coupon_code' 			=> $couponCode,
													'coupon_code_master_id' =>trim($coupon_code_id),
													'create_on'             => date('Y-m-d H:i:s')
												];
												parent::insertTable_f_mdl('coupon_code_series_master', $insertCSVData);
											}
										}
										$codeCount++;
									}
								}
								fclose($file);
							}
						}
					}
					$res['SUCCESS'] = 'TRUE';
					$res['MESSAGE'] = 'Discount code added successfully.';
				}
			}
		}
		 common::sendJson($res);
	}
}
?>