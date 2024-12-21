<?php
//http://app.spirithero.com/cron-create-store-collections.php
//include_once 'model/get_shopify_store_data_mdl.php';
include_once 'model/index_mdl.php';

class get_shopify_store_data_ctl extends index_mdl
{
	function __construct(){
		$this->getStoreFundraisingDataFunc();
	}	

	function getStoreFundraisingDataFunc(){
			
	    //end Check email..
	    $collection_id = $_REQUEST['collection_id']?$_REQUEST['collection_id']:0;
		#region - Fetch Collection Create Request

		$stmt = "SELECT t1.enable_fundraising, t1.fundraising_amount, SUM(t2.total_fundraising_amount) as total_fundraising_amount, t3.first_name, t3.last_name, t3.address_line_1, t3.address_line_2, t3.country, t3.state, t3.city, t3.zip_code, t3.company_name
		FROM store_master t1
		LEFT JOIN store_orders_master t2
		ON t1.id = t2.store_master_id
		LEFT JOIN store_owner_silver_delivery_address_master t3
		ON t1.id = t3.store_master_id

		WHERE t1.shop_collection_id='".$collection_id."' ";

		$storeCollectionInfo = array();

		$storeCollectionInfoData = parent::selectTable_f_mdl($stmt);

		//print_r($storeCollectionInfo);

		if(!empty($storeCollectionInfoData)){			
			foreach ($storeCollectionInfoData as $key => $storeCollectionData) {
				$storeCollectionInfo = $storeCollectionData;
				//print_r($storeCollectionInfo);
			}
			
		}

		echo json_encode($storeCollectionInfo);

		die;
		
	}
}

?>