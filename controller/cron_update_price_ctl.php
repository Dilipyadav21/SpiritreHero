<?php
include_once 'model/cron_update_price_mdl.php';

class cron_update_price_ctl extends cron_update_price_mdl
{
	function __construct(){
		$this->startUpdatePriceJob();
	}
	
	function startUpdatePriceJob(){

		//Check email only testing purpose
		/*require_once("lib/class_aws.php");
	    $objAWS = new Aws(common::AWS_ACCESS_KEY,common::AWS_SECRET_KEY,common::AWS_REGION);
	    $mailSendStatus = $objAWS->sendEmail(["sanjay@bitcot.com"], "startUpdatePriceJob", "Message: startUpdatePriceJob", "Message: startUpdatePriceJob");*/
	    //end Check email..

		$storeInfoArray = parent::getPriceUpdateStoreInfo_f_mdl();

		if(!empty($storeInfoArray)){
			$shop_data = parent::getStoreInfo_f_mdl();
			#region - Init Shopify Class Object
			require_once('lib/shopify.php');

			$shopifyObject = new ShopifyClient($shop_data[0]["shop_name"], $shop_data[0]["token"], common::SHOPIFY_API_KEY, common::SHOPIFY_SECRET);
			#endregion

			foreach($storeInfoArray as $objStore)
			{
				$store_price_data = parent::getStorePriceData_f_mdl($objStore['store_master_id']);

				if($objStore['store_sale_type_master_id'] == 2){
					#region - For On-Demand Price
					foreach($store_price_data as $objPrice)
					{
						$onDemandPrice = $objPrice['price_on_demand'] + $objPrice['fundraising_price'];
						#region - Get Product Json
						try
						{
							$variantIdArray = json_decode('{"variant": {"id": '.$objPrice['shop_variant_id'].',"price": "'.$onDemandPrice.'"}}');
							
							$getApiInfo = $shopifyObject->call('PUT', '/admin/api/2023-04/variants/'.$objPrice['shop_variant_id'].'.json',$variantIdArray);
							
							sleep(0.5);
						}
						catch (ShopifyApiException $e)
						{
						}
						catch (ShopifyCurlException $e)
						{  
						}
						#endregion

						#region - Update Price Pending Status = 0
						parent::updatePriceStatusToDB_f_mdl($objPrice['id']);
						#endregion
					}
					#endregion
				}else{
					#region - For Flash Sale Price
					foreach($store_price_data as $objPrice)
					{
						$salePrice = $objPrice['price'] + $objPrice['fundraising_price'];
						#region - Get Product Json
						try
						{
							$variantIdArray = json_decode('{"variant": {"id": '.$objPrice['shop_variant_id'].',"price": "'.$salePrice.'"}}');
							
							$getApiInfo = $shopifyObject->call('PUT', '/admin/api/2023-04/variants/'.$objPrice['shop_variant_id'].'.json',$variantIdArray);
							
							sleep(0.5);
						}
						catch (ShopifyApiException $e)
						{
						}
						catch (ShopifyCurlException $e)
						{  
						}
						#endregion

						#region - Update Price Pending Status = 0
						parent::updatePriceStatusToDB_f_mdl($objPrice['id']);
						#endregion
					}
				}
			}
		}else{
			echo "Pending Data Not Available";
		}
	}
}
?>