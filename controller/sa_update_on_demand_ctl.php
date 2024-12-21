<?php
include 'model/sa_stores_mdl.php';
$path = preg_replace('/controller(?!.*controller).*/','',__DIR__);
class sa_update_on_demand_ctl extends sa_stores_mdl
{
	function updateOnDemandPrice($storeId=0)
	{
		$sql = 'SELECT id,front_side_ink_colors,back_side_ink_colors,store_fulfillment_type,is_fundraising,ct_fundraising_price,is_bulk_pricing,store_fulfillment_type FROM `store_master`
		WHERE id="'.$storeId.'" limit 1';			
		$storeDetails = parent::selectTable_f_mdl($sql);
		//echo "<pre>";print_r($storeDetails);
		$updateVariantOnDemandPrice = array();
		$productNotFound            = array();
		foreach ($storeDetails as $row) 
		{
			if($row['id'])
			{
				$sql = 'SELECT id,store_product_master_id from store_owner_product_master where store_master_id="'.$row['id'].'"';
				$storeOwnerProductsMaster = parent::selectTable_f_mdl($sql);
				//echo "<pre>";print_r($storeOwnerProductsMaster);
				foreach ($storeOwnerProductsMaster as $row2) 
				{
					if($row2['id'])
					{
						$sql = 'SELECT id,store_owner_product_master_id,store_product_variant_master_id,price,price_on_demand from store_owner_product_variant_master where store_owner_product_master_id="'.$row2['id'].'"';
						$storeOwnerProductsVarientMaster = parent::selectTable_f_mdl($sql);
						//echo "<pre>";print_r($storeOwnerProductsVarientMaster);
						foreach ($storeOwnerProductsVarientMaster as $row3) 
						{
							if($row3['store_product_variant_master_id'])
							{
								$sql = 'SELECT price,price_on_demand from store_product_variant_master where id="'.$row3['store_product_variant_master_id'].'"';
								$storeProductVariantMaster = parent::selectTable_f_mdl($sql);
								
								/*
								* Front-side and back-side price only added with on-demand store
								* Add front-side as per color catridge price into base price
								*/ 
								$add_cost = 0;
								if(isset($row['front_side_ink_colors']) && !empty($row['front_side_ink_colors'])){
									$add_cost += intval($row['front_side_ink_colors'])-1;
								}

								//Add back-side as per color catridge price into base price
								if(isset($row['back_side_ink_colors']) && !empty($row['back_side_ink_colors'])){
									$add_cost += common::ADD_COST_BACK_SIDE_INK_COLOR+intval($row['back_side_ink_colors'])-1;
								}
 
								/*
								* ADD_COST_STORE_FULFILLMENT_TYPE_2 means add $2 in base price on flash sale case
								* ADD_COST_STORE_FULFILLMENT_TYPE_3 means add $6 in base price on flash sale case
								*/
								$fullfillmentsql = 'SELECT * FROM `general_settings_master` WHERE id=1';
								$getFullfilmentPrice= parent::selectTable_f_mdl($fullfillmentsql);
								$fullfilment_silver_price='0';
								$fullfilment_gold_price='0';
								$fullfilment_platinum_price='0';
								if(!empty($getFullfilmentPrice)){
									$fullfilment_silver_price   = $getFullfilmentPrice[0]['fullfilment_silver_price'];
									$fullfilment_gold_price   = $getFullfilmentPrice[0]['fullfilment_gold_price'];
									$fullfilment_platinum_price   = $getFullfilmentPrice[0]['fullfilment_platinum_price'];
								}
								$fullfilment_type_price = 0;
								if(isset($row['store_fulfillment_type']) && $row['store_fulfillment_type']=='SHIP_1_LOCATION_SORT'){
									$fullfilment_type_price = $fullfilment_gold_price;
									//$fullfilment_type_price = common::ADD_COST_STORE_FULFILLMENT_TYPE_2;
								}
								else if(isset($row['store_fulfillment_type']) && $row['store_fulfillment_type']=='SHIP_EACH_FAMILY_HOME'){
									$fullfilment_type_price = $fullfilment_platinum_price;
									//$fullfilment_type_price = common::ADD_COST_STORE_FULFILLMENT_TYPE_3;
								}else if(isset($row['store_fulfillment_type']) && $row['store_fulfillment_type']=='SHIP_1_LOCATION_NOT_SORT'){
									$fullfilment_type_price = $fullfilment_silver_price;
								}
								
								//To do add bussiness login for fullfilmemnt type & fundrising
								if(isset($storeProductVariantMaster[0]['price']) && $storeProductVariantMaster[0]['price_on_demand'])
								{
									$updateStoreOwnerProductVariant = [
										'price'           => (floatval($storeProductVariantMaster[0]['price'])+$add_cost+$fullfilment_type_price),
										'price_on_demand' => (floatval($storeProductVariantMaster[0]['price_on_demand'])),
									];
								}
								else
								{
									$productNotFound[]  = $row3['store_product_variant_master_id'];
								}
							
								parent::updateTable_f_mdl('store_owner_product_variant_master',$updateStoreOwnerProductVariant,'id="'.$row3['id'].'"');
								//Update data into store_owner_product_variant_master
								$updateVariantOnDemandPrice[]= $row['id']."_".$row3['id'];
							}
						}
					}
				}
			}
		}

		if(count($updateVariantOnDemandPrice)>0)
		{
			echo "<pre> Update Price & on Demand Price";print_r($updateVariantOnDemandPrice);
		}
		else{
			echo "already updated";
		}

		echo "<pre> product not found";print_r($productNotFound);
	}

	public function updateStoreDecription($offset,$limit)
	{	global $path;
		$sql          = 'SELECT id,store_fulfillment_type,store_sale_type_master_id,shop_collection_id FROM `store_master` WHERE status = 1 LIMIT '.$offset.','.$limit.'';			
		$storeDetails = parent::selectTable_f_mdl($sql);
		if(!empty($storeDetails)){
			foreach($storeDetails as $value){
				$store_description = '';
				if($value['store_sale_type_master_id'] == 1){
					if(isset($value['store_fulfillment_type']) && $value['store_fulfillment_type']=='SHIP_1_LOCATION_NOT_SORT'){
						$store_description = COMMON::DESCRIPTION_FOR_OPEN_FLASH_STORE;
					}else if(isset($value['store_fulfillment_type']) && $value['store_fulfillment_type']=='SHIP_1_LOCATION_SORT'){
						$store_description = COMMON::DEFAULT_DESCRIPTION_FOR_SILVER_GOLD_FULFILLMENT;
					}else if(isset($value['store_fulfillment_type']) && $value['store_fulfillment_type']=='SHIP_EACH_FAMILY_HOME'){
						$store_description = COMMON::DEFAULT_DESCRIPTION_FOR_PLATINUM_FULFILLMENT;
					}
				}else{
					$store_description = common::DESCRIPTION_FOR_OPEN_ONDEMAND_STORE;
				}

				$updateDescription = [
					'store_description' => $store_description
				];

				// print_r($updateDescription);
				parent::updateTable_f_mdl('store_master',$updateDescription,'id="'.$value['id'].'"');
				require_once($path.'lib/class_graphql.php');

				$shop      = common::PARENT_STORE_NAME;
				$shop_sql  = "SELECT shop_name, token FROM `shop_management` WHERE shop_name='".$shop."'";
				$shop_data = parent::selectTable_f_mdl($shop_sql);
				if(!empty($shop_data)) {
					require_once($path.'lib/class_graphql.php');

					$shop = $shop_data[0]['shop_name'];
					$token = $shop_data[0]['token'];

					$headers = array(
						'X-Shopify-Access-Token' => $token
					);
					$graphql = new Graphql($shop, $headers);

					$mutationData = 'mutation collectionUpdate($input: CollectionInput!) {
					  collectionUpdate(input: $input) {
						collection {
						  id
						}
						job {
						  id
						}
						userErrors {
						  field
						  message
						}
					  }
					}';
					$inputData = '{
					  "input": {
						  "id":"gid://shopify/Collection/'.$value['shop_collection_id'].'",
						  "descriptionHtml":"'.trim($store_description).'"
					  }
					}';
					$graphql->runByMutation($mutationData,$inputData);
				}
			}
		}
	}

	public function getAllProductsNotUsable($since_id)
	{	
		global $path;
	    // $storeInfo = parent::getStoreInfo_f_mdl();
	    #region - Initialize Shopify Class Object
		require_once($path.'lib/shopify.php');
		require_once($path.'lib/functions.php');
		
		$since_id = (!empty($_GET['since_id']))?$_GET['since_id']:0;
		$shop      = common::PARENT_STORE_NAME;
		$shop_sql  = "SELECT shop_name, token FROM `shop_management` WHERE shop_name='".$shop."'";
		$storeInfo = parent::selectTable_f_mdl($shop_sql);
		$shopifyObject = new ShopifyClient($storeInfo[0]["shop_name"], $storeInfo[0]["token"], common::SHOPIFY_API_KEY, common::SHOPIFY_SECRET);
		echo "<pre>";print_r($shopifyObject);
		$getProductsShopify = '';
		if(!empty($storeInfo)) {
			$getProductsShopify = $shopifyObject->call('GET', '/admin/api/2023-04/products.json?limit=250&since_id='.$since_id);
		}
		$shopIdGroup = array();
		$NotExistInSA = array();
		$existInSA = array();
		$title = array();
		if (!empty($getProductsShopify)) {
		   	foreach ($getProductsShopify as $value) {
		   		$shop_product_id = $value['id'];
		   		$shopIdGroup[]=$shop_product_id;
		   		$productSql  = 'SELECT shop_product_id FROM store_owner_product_master WHERE shop_product_id = '.$shop_product_id.'';
		    	$productData = parent::selectTable_f_mdl($productSql);
		    	if (!empty($productData)) {
		    		$existInSA[] = $productData[0]['shop_product_id'];
		    	}
		    	else{
		    		$NotExistInSA[] = $shop_product_id;
		    		$title[] = $value['title'];
		    	}
		   	}
		}   	

		echo "Exist in SA";print_r($existInSA);
		echo "Not Exist in SA";print_r(array_values($NotExistInSA));
		echo "Product title Not Exist in SA";print_r(array_values($title));
	   	echo "All shop_ids";print_r($shopIdGroup);
	}

	public function findCollectionOfDuplicateProduct($store_master_id)
	{
		global $path;
		
	    #region - Initialize Shopify Class Object
		require_once($path.'lib/shopify.php');
		require_once($path.'lib/functions.php');
		$shop      = common::PARENT_STORE_NAME;
		$shop_sql  = "SELECT shop_name, token FROM `shop_management` WHERE shop_name='".$shop."'";
		$storeInfo = parent::selectTable_f_mdl($shop_sql);

	    $shopifyObject      = new ShopifyClient($storeInfo[0]["shop_name"], $storeInfo[0]["token"], common::SHOPIFY_API_KEY, common::SHOPIFY_SECRET);
	    $storeSql           = 'SELECT shop_collection_id,id FROM store_master where id = '.$store_master_id.'';
	    $StoreDATA          = parent::selectTable_f_mdl($storeSql);
	    $storeMasterId      = $StoreDATA[0]['id'];
	    $shop_collection_id = $StoreDATA[0]['shop_collection_id'];

	    $productSql  = 'SELECT * FROM store_owner_product_master where store_master_id = '.$storeMasterId.'';
	    $productCount  = parent::selectTable_f_mdl($productSql);
	    
	    $getcollectionCount = $shopifyObject->call('GET', 'admin/api/2023-04/products/count.json?collection_id='.$shop_collection_id);
		echo "<br>".$count1 = $getcollectionCount;
		echo "<br>".$count2 = count($productCount);
		if ($count2==$count1) {
			echo "<br>Match=".$collection_idMath = $shop_collection_id;
		}
		else{
			echo "<br>NotMatch=".$collectionNotMatch = $shop_collection_id;
		}
	}
}
