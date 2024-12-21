<?php
include_once 'model/sa_stores_mdl.php';
include_once('helpers/createStoreHelper.php');
include_once('helpers/storeHelper.php');

$path = preg_replace('/controller(?!.*controller).*/', '', __DIR__);
include_once $path . 'libraries/Aws3.php';
$s3Obj = new Aws3;
$login_user_email="";
if(isset($_SESSION['user_email']) && $_SESSION['user_email'] != "") {
	$login_user_email=trim($_SESSION['user_email']);
}
class sa_analytical_reports_ctl extends sa_stores_mdl
{
	public $TempSession = "";

	function __construct()
	{
		if (parent::isGET() || parent::isPOST()) {
			if (parent::getVal("method")) {
				$this->checkRequestProcess(parent::getVal("method"));
			} else {
				$this->SITE_ACCESS_KEY = parent::getVal("stkn");
			}
			//$this->SITE_ACCESS_KEY = parent::getVal("stkn");
		}

		common::CheckLoginSession();
	}

	function checkRequestProcess($requestFor)
	{
		if ($requestFor != "") {
			switch ($requestFor) {
				case "getReportsByDateFilter":
					$this->getReportsByDateFilter();
				break;
				case "export_analytical_report":
					$this->export_analytical_report();
				break;
			}
		}
	}

	public function getOrdercountdetail()
	{
		$sql = 'SELECT count(id) as total_orders FROM store_orders_master WHERE is_order_cancel="0" ORDER BY id DESC';
		$ordercount = parent::selectTable_f_mdl($sql);
		return $ordercount;
	}

	public function getOrderItemscountdetail()
	{
		$sql = 'SELECT SUM(soim.quantity) as total_quantity,SUM(soim.fundraising_amount) as total_fundraising,
		(select sum(total_price) from store_orders_master  WHERE is_order_cancel="0" ) as total_amount,
		SUM(soim.price) as total_item_amount FROM store_order_items_master as soim INNER JOIN store_orders_master as som ON som.id=soim.store_orders_master_id WHERE som.is_order_cancel="0" AND soim.is_deleted="0" ';
		$ordercount = parent::selectTable_f_mdl($sql);
		return $ordercount;
	}

	public function getTopSellingSKUGlobal()
	{
		// $start_date = date('Y-m-d', strtotime($s_date));
        // $end_date = date('Y-m-d', strtotime($e_date));
		
		$sql = "SELECT soim.sku, SUM(soim.quantity) AS total_quantity_sold,
		SUM(CASE WHEN som.store_sale_type = 'Flash Sale' THEN soim.quantity ELSE 0 END) AS flash_orderItem_sold,
    	SUM(CASE WHEN som.store_sale_type = 'On-Demand' THEN soim.quantity ELSE 0 END) AS ondemand_orderItem_sold
		FROM store_order_items_master AS soim
		INNER JOIN store_orders_master AS som ON som.id = soim.store_orders_master_id
		WHERE som.is_order_cancel = '0' AND soim.is_deleted = '0'
		GROUP BY soim.sku
		ORDER BY total_quantity_sold DESC
		";
		$globalSellingSku = parent::selectTable_f_mdl($sql);
		
		return $globalSellingSku;
	}
	public function getTopSellingSizeGlobal()
	{
		$sql = "SELECT sopvm.size,SUM(soim.quantity) AS quantity,
		SUM(CASE WHEN som.store_sale_type = 'Flash Sale' THEN soim.quantity ELSE 0 END) AS flash_orderItem_sold,
    	SUM(CASE WHEN som.store_sale_type = 'On-Demand' THEN soim.quantity ELSE 0 END) AS ondemand_orderItem_sold
		FROM store_order_items_master AS soim
		INNER JOIN store_orders_master AS som ON som.id = soim.store_orders_master_id
		INNER JOIN store_owner_product_variant_master AS sopvm ON sopvm.id = soim.store_owner_product_variant_master_id
		INNER JOIN store_product_colors_master as spcm ON spcm.product_color=sopvm.color
		WHERE som.is_order_cancel = '0' AND soim.is_deleted = '0'
		GROUP BY sopvm.size
		ORDER BY quantity DESC 
		";
		return parent::selectTable_f_mdl($sql);
	}
	public function getTopSellingColorGlobal()
	{
		$sql = "SELECT sopvm.color,spcm.product_color_name,SUM(soim.quantity) AS quantity,
		SUM(CASE WHEN som.store_sale_type = 'Flash Sale' THEN soim.quantity ELSE 0 END) AS flash_orderItem_sold,
    	SUM(CASE WHEN som.store_sale_type = 'On-Demand' THEN soim.quantity ELSE 0 END) AS ondemand_orderItem_sold
		FROM store_order_items_master AS soim
		INNER JOIN store_orders_master AS som ON som.id = soim.store_orders_master_id
		INNER JOIN store_owner_product_variant_master AS sopvm ON sopvm.id = soim.store_owner_product_variant_master_id
		INNER JOIN store_product_colors_master as spcm ON spcm.product_color=sopvm.color
		WHERE som.is_order_cancel = '0' AND soim.is_deleted = '0' 
		GROUP BY sopvm.color
		ORDER BY quantity DESC 
		";
		return parent::selectTable_f_mdl($sql);
	}

	public function getTopSellingSKUByColorSize()
	{
		$sql = "SELECT soim.sku,sopvm.color,spcm.product_color_name,sopvm.size,SUM(soim.quantity) AS total_quantity_sold,
		SUM(CASE WHEN som.store_sale_type = 'Flash Sale' THEN soim.quantity ELSE 0 END) AS flash_orderItem_sold,
    	SUM(CASE WHEN som.store_sale_type = 'On-Demand' THEN soim.quantity ELSE 0 END) AS ondemand_orderItem_sold
		FROM store_order_items_master AS soim
		INNER JOIN store_orders_master AS som ON som.id = soim.store_orders_master_id
		INNER JOIN store_owner_product_variant_master AS sopvm ON sopvm.id = soim.store_owner_product_variant_master_id
		INNER JOIN store_product_colors_master as spcm ON spcm.product_color=sopvm.color
		WHERE som.is_order_cancel = '0' AND soim.is_deleted = '0' 
		GROUP BY soim.sku, sopvm.size, sopvm.color
		ORDER BY total_quantity_sold DESC
		";
		return parent::selectTable_f_mdl($sql);
	}

	public function getTopSellingvendorSkuGlobal()
	{
		$sql = "SELECT soim.sku,SUM(soim.quantity) AS total_quantity_sold,svm.vendor_name,
		SUM(CASE WHEN som.store_sale_type = 'Flash Sale' THEN soim.quantity ELSE 0 END) AS flash_orderItem_sold,
    	SUM(CASE WHEN som.store_sale_type = 'On-Demand' THEN soim.quantity ELSE 0 END) AS ondemand_orderItem_sold
		FROM store_order_items_master AS soim
		INNER JOIN store_orders_master AS som ON som.id = soim.store_orders_master_id
		INNER JOIN store_owner_product_variant_master AS sopvm ON sopvm.id = soim.store_owner_product_variant_master_id
		INNER JOIN store_owner_product_master as sopm ON sopm.id = soim.store_owner_product_master_id
		INNER JOIN store_product_master as spm ON spm.id = sopm.store_product_master_id
		INNER JOIN store_vendors_master as svm ON svm.id = spm.vendor_id
		WHERE som.is_order_cancel = '0' AND soim.is_deleted = '0'
		GROUP BY soim.sku,svm.vendor_name 
		ORDER BY total_quantity_sold DESC
		";
		return parent::selectTable_f_mdl($sql);
	}

	public function getTopSellingvendorProdGlobal()
	{
		$sql = "SELECT svm.vendor_name, SUM(soim.quantity) AS total_quantity_sold,
		SUM(CASE WHEN som.store_sale_type = 'Flash Sale' THEN soim.quantity ELSE 0 END) AS flash_orderItem_sold,
    	SUM(CASE WHEN som.store_sale_type = 'On-Demand' THEN soim.quantity ELSE 0 END) AS ondemand_orderItem_sold
		FROM store_order_items_master AS soim
		INNER JOIN store_orders_master AS som ON som.id = soim.store_orders_master_id
		INNER JOIN store_owner_product_variant_master AS sopvm ON sopvm.id = soim.store_owner_product_variant_master_id
		INNER JOIN store_owner_product_master as sopm ON sopm.id = soim.store_owner_product_master_id
		INNER JOIN store_product_master as spm ON spm.id = sopm.store_product_master_id
		INNER JOIN store_vendors_master as svm ON svm.id = spm.vendor_id
		WHERE som.is_order_cancel = '0' AND soim.is_deleted = '0'
		GROUP BY svm.vendor_name
		ORDER BY total_quantity_sold DESC
		";
		return parent::selectTable_f_mdl($sql);
	}

	public function getReportsByDateFilter()
	{
		$resultArray = array();
		if (!empty(parent::getVal("method")) && parent::getVal("method") == "getReportsByDateFilter") {
			$from_date = '';
			$to_date = '';
			$resultArray=[];
			if ((isset($_POST['start_date'])) && $_POST['start_date'] != '') {
        		$from_date = date('Y-m-d', strtotime($_POST['start_date']));
			}

			if ((isset($_POST['end_date'])) && $_POST['end_date'] != '') {
        		$to_date = date('Y-m-d', strtotime($_POST['end_date'].' 23:59:59'));
			}

			$sql = 'SELECT count(id) as total_orders FROM store_orders_master WHERE is_order_cancel="0" AND date(created_on) BETWEEN "'.$from_date.'" AND "'.$to_date.'" ORDER BY id DESC';
			$ordercount = parent::selectTable_f_mdl($sql);
			if(empty($ordercount)){
				$ordercount[0]['total_orders']='0';
			}
			$resultArray["total_order_count"]=number_format($ordercount[0]['total_orders']);

			$sql = 'SELECT SUM(soim.quantity) as total_quantity,SUM(soim.fundraising_amount) as total_fundraising,
			(select sum(total_price) from store_orders_master  WHERE is_order_cancel="0" AND date(created_on) BETWEEN "'.$from_date.'" AND "'.$to_date.'") as total_amount,
			SUM(soim.price) as total_item_amount FROM store_order_items_master as soim INNER JOIN store_orders_master as som ON som.id=soim.store_orders_master_id WHERE som.is_order_cancel="0" AND soim.is_deleted="0" AND date(som.created_on) BETWEEN "'.$from_date.'" AND "'.$to_date.'" ';
			$orderitemcount = parent::selectTable_f_mdl($sql);

			$total_quantity		=(($orderitemcount[0]['total_quantity']=='')? '0':number_format($orderitemcount[0]['total_quantity']));
			$total_amount=(!empty($orderitemcount[0]['total_amount']) ? number_format((float)$orderitemcount[0]['total_amount'] ,2) : '0.00');
			$total_fundraising=(!empty($orderitemcount[0]['total_fundraising']) ? number_format((float)$orderitemcount[0]['total_fundraising'] ,2) : '0.00');
			
			$resultArray["total_quantity"]    =$total_quantity;
			$resultArray["total_fundraising"] =$total_fundraising;
			$resultArray["total_amount"]      =$total_amount;


			$sql = "SELECT soim.sku, SUM(soim.quantity) AS total_quantity_sold,
			SUM(CASE WHEN som.store_sale_type = 'Flash Sale' THEN soim.quantity ELSE 0 END) AS flash_orderItem_sold,
    		SUM(CASE WHEN som.store_sale_type = 'On-Demand' THEN soim.quantity ELSE 0 END) AS ondemand_orderItem_sold
			FROM store_order_items_master AS soim
			INNER JOIN store_orders_master AS som ON som.id = soim.store_orders_master_id
			WHERE som.is_order_cancel = '0' AND soim.is_deleted = '0'  AND date(som.created_on) BETWEEN '".$from_date."' AND '".$to_date."'
			GROUP BY soim.sku
			ORDER BY total_quantity_sold DESC
			";
			$globalSellingSku = parent::selectTable_f_mdl($sql);
			
			$globalskuhtml = '';
			$globalskuhtml .= '<table class="table table-bordered table-hover"> ';
			$globalskuhtml .= '<thead>';
			$globalskuhtml .= '<tr>';
			$globalskuhtml .= '<th>SKU</th>';
			$globalskuhtml .= '<th>Total Sold Quantity</th>';
			$globalskuhtml .= '<th>Flash Sale</th>';
			$globalskuhtml .= '<th>On-Demand</th>';
			$globalskuhtml .= '</tr>';
			$globalskuhtml .= '</thead>';
			$globalskuhtml .= '<tbody>';
			if(!empty($globalSellingSku)){
				foreach($globalSellingSku as $globalsku){
			$globalskuhtml .= '<tr>';
			$globalskuhtml .= '<td>'.$globalsku['sku'].'</td>';
			$globalskuhtml .= '<td>'.number_format($globalsku['total_quantity_sold']).'</td>';
			$globalskuhtml .= '<td>'.number_format($globalsku['flash_orderItem_sold']).'</td>';
			$globalskuhtml .= '<td>'.number_format($globalsku['ondemand_orderItem_sold']).'</td>';
			$globalskuhtml .= '</tr>';
				} 
			}else{
				$globalskuhtml .= '<tr>';
				$globalskuhtml .= '<td colspan="5" align="center">No Record Found</td>';
				$globalskuhtml .= '</tr>';
			}
			$globalskuhtml .= '</tbody>';
			$globalskuhtml .= '</table>';
			$resultArray['globalskuhtml'] = $globalskuhtml;


			$sql = "SELECT sopvm.size,SUM(soim.quantity) AS quantity,
			SUM(CASE WHEN som.store_sale_type = 'Flash Sale' THEN soim.quantity ELSE 0 END) AS flash_orderItem_sold,
    		SUM(CASE WHEN som.store_sale_type = 'On-Demand' THEN soim.quantity ELSE 0 END) AS ondemand_orderItem_sold
			FROM store_order_items_master AS soim
			INNER JOIN store_orders_master AS som ON som.id = soim.store_orders_master_id
			INNER JOIN store_owner_product_variant_master AS sopvm ON sopvm.id = soim.store_owner_product_variant_master_id
			INNER JOIN store_product_colors_master as spcm ON spcm.product_color=sopvm.color
			WHERE som.is_order_cancel = '0' AND soim.is_deleted = '0'  AND date(som.created_on) BETWEEN '".$from_date."' AND '".$to_date."'
			GROUP BY sopvm.size
			ORDER BY quantity DESC 
			";
			$globalSellingSize = parent::selectTable_f_mdl($sql);
			$globalsizehtml = '';
			$globalsizehtml .= '<table class="table table-bordered table-hover"> ';
			$globalsizehtml .= '<thead>';
			$globalsizehtml .= '<tr>';
			$globalsizehtml .= '<th>Size</th>';
			$globalsizehtml .= '<th>Total Sold Quantity</th>';
			$globalsizehtml .= '<th>Flash Sale</th>';
			$globalsizehtml .= '<th>On-Demand</th>';
			$globalsizehtml .= '</tr>';
			$globalsizehtml .= '</thead>';
			$globalsizehtml .= '<tbody>';				
			if(!empty($globalSellingSize)){
				foreach($globalSellingSize as $globalsize){
					$globalsizehtml .= '<tr>';
					$globalsizehtml .= '<td>'.$globalsize['size'].'</td>';
					$globalsizehtml .= '<td>'.number_format($globalsize['quantity']).'</td>';
					$globalsizehtml .= '<td>'.number_format($globalsize['flash_orderItem_sold']).'</td>';
					$globalsizehtml .= '<td>'.number_format($globalsize['ondemand_orderItem_sold']).'</td>';
					$globalsizehtml .= '</tr>';
				} 
			}else{
				$globalsizehtml .= '<tr>';
				$globalsizehtml .= '<td colspan="5" align="center">No Record Found</td>';
				$globalsizehtml .= '</tr>';
			}
			$globalsizehtml .= '</tbody>';
			$globalsizehtml .= '</table>';
			$resultArray['globalsizehtml'] = $globalsizehtml;


			$sql = "SELECT sopvm.color,spcm.product_color_name,SUM(soim.quantity) AS quantity,
			SUM(CASE WHEN som.store_sale_type = 'Flash Sale' THEN soim.quantity ELSE 0 END) AS flash_orderItem_sold,
    		SUM(CASE WHEN som.store_sale_type = 'On-Demand' THEN soim.quantity ELSE 0 END) AS ondemand_orderItem_sold
			FROM store_order_items_master AS soim
			INNER JOIN store_orders_master AS som ON som.id = soim.store_orders_master_id
			INNER JOIN store_owner_product_variant_master AS sopvm ON sopvm.id = soim.store_owner_product_variant_master_id
			INNER JOIN store_product_colors_master as spcm ON spcm.product_color=sopvm.color
			WHERE som.is_order_cancel = '0' AND soim.is_deleted = '0' AND date(som.created_on) BETWEEN '".$from_date."' AND '".$to_date."'
			GROUP BY sopvm.color
			ORDER BY quantity DESC 
			";
			$globalSellingColor = parent::selectTable_f_mdl($sql);
			$globalcolorhtml = '';
			$globalcolorhtml .= '<table class="table table-bordered table-hover"> ';
			$globalcolorhtml .= '<thead>';
			$globalcolorhtml .= '<tr>';
			$globalcolorhtml .= '<th>Color Name</th>';
			$globalcolorhtml .= '<th>Total Sold Quantity</th>';
			$globalcolorhtml .= '<th>Flash Sale</th>';
			$globalcolorhtml .= '<th>On-Demand</th>';
			$globalcolorhtml .= '</tr>';
			$globalcolorhtml .= '</thead>';
			$globalcolorhtml .= '<tbody>';				
			if(!empty($globalSellingColor)){
				foreach($globalSellingColor as $globalcolor){
					$globalcolorhtml .= '<tr>';
					$globalcolorhtml .= '<td>'.$globalcolor['product_color_name'].'</td>';
					$globalcolorhtml .= '<td>'.number_format($globalcolor['quantity']).'</td>';
					$globalcolorhtml .= '<td>'.number_format($globalcolor['flash_orderItem_sold']).'</td>';
					$globalcolorhtml .= '<td>'.number_format($globalcolor['ondemand_orderItem_sold']).'</td>';
					$globalcolorhtml .= '</tr>';
				} 
			}else{
				$globalcolorhtml .= '<tr>';
				$globalcolorhtml .= '<td colspan="5" align="center">No Record Found</td>';
				$globalcolorhtml .= '</tr>';
			}
			$globalcolorhtml .= '</tbody>';
			$globalcolorhtml .= '</table>';
			$resultArray['globalcolorhtml'] = $globalcolorhtml;

			$sql = "SELECT soim.sku,sopvm.color,spcm.product_color_name,sopvm.size,SUM(soim.quantity) AS total_quantity_sold,
			SUM(CASE WHEN som.store_sale_type = 'Flash Sale' THEN soim.quantity ELSE 0 END) AS flash_orderItem_sold,
    		SUM(CASE WHEN som.store_sale_type = 'On-Demand' THEN soim.quantity ELSE 0 END) AS ondemand_orderItem_sold
			FROM store_order_items_master AS soim
			INNER JOIN store_orders_master AS som ON som.id = soim.store_orders_master_id
			INNER JOIN store_owner_product_variant_master AS sopvm ON sopvm.id = soim.store_owner_product_variant_master_id
			INNER JOIN store_product_colors_master as spcm ON spcm.product_color=sopvm.color
			WHERE som.is_order_cancel = '0' AND soim.is_deleted ='0' AND date(som.created_on) BETWEEN '".$from_date."' AND '".$to_date."'
			GROUP BY soim.sku, sopvm.size, sopvm.color
			ORDER BY total_quantity_sold DESC
			";
			$globalSellingSkuColorSize = parent::selectTable_f_mdl($sql);
			$topskucolorsizehtml = '';
			$topskucolorsizehtml .= '<table class="table table-bordered table-hover"> ';
			$topskucolorsizehtml .= '<thead>';
			$topskucolorsizehtml .= '<tr>';
			$topskucolorsizehtml .='<th>SKU</th>';
			$topskucolorsizehtml .='<th>Color</th>';
			$topskucolorsizehtml .='<th>Size</th>';
			$topskucolorsizehtml .='<th>Total Sold Quantity</th>';
			$topskucolorsizehtml .= '<th>Flash Sale</th>';
			$topskucolorsizehtml .= '<th>On-Demand</th>';
			$topskucolorsizehtml .= '</tr>';
			$topskucolorsizehtml .= '</thead>';
			$topskucolorsizehtml .= '<tbody>';				
			if(!empty($globalSellingSkuColorSize)){
				foreach($globalSellingSkuColorSize as $topskucolorsize){
					$topskucolorsizehtml .= '<tr>';
					$topskucolorsizehtml .= '<td>'.$topskucolorsize['sku'].'</td>';
					$topskucolorsizehtml .= '<td>'.$topskucolorsize['product_color_name'].'</td>';
					$topskucolorsizehtml .= '<td>'.$topskucolorsize['size'].'</td>';
					$topskucolorsizehtml .= '<td>'.number_format($topskucolorsize['total_quantity_sold']).'</td>';
					$topskucolorsizehtml .= '<td>'.number_format($topskucolorsize['flash_orderItem_sold']).'</td>';
					$topskucolorsizehtml .= '<td>'.number_format($topskucolorsize['ondemand_orderItem_sold']).'</td>';
					$topskucolorsizehtml .= '</tr>';
				} 
			}else{
				$topskucolorsizehtml .= '<tr>';
				$topskucolorsizehtml .= '<td colspan="7" align="center">No Record Found</td>';
				$topskucolorsizehtml .= '</tr>';
			}
			$topskucolorsizehtml .= '</tbody>';
			$topskucolorsizehtml .= '</table>';
			$resultArray['topskucolorsizehtml'] = $topskucolorsizehtml;

			$sql = "SELECT svm.vendor_name, SUM(soim.quantity) AS total_quantity_sold,
			SUM(CASE WHEN som.store_sale_type = 'Flash Sale' THEN soim.quantity ELSE 0 END) AS flash_orderItem_sold,
    		SUM(CASE WHEN som.store_sale_type = 'On-Demand' THEN soim.quantity ELSE 0 END) AS ondemand_orderItem_sold
			FROM store_order_items_master AS soim
			INNER JOIN store_orders_master AS som ON som.id = soim.store_orders_master_id
			INNER JOIN store_owner_product_variant_master AS sopvm ON sopvm.id = soim.store_owner_product_variant_master_id
			INNER JOIN store_owner_product_master as sopm ON sopm.id = soim.store_owner_product_master_id
			INNER JOIN store_product_master as spm ON spm.id = sopm.store_product_master_id
			INNER JOIN store_vendors_master as svm ON svm.id = spm.vendor_id
			WHERE som.is_order_cancel = '0' AND soim.is_deleted = '0' AND date(som.created_on) BETWEEN '".$from_date."' AND '".$to_date."'
			GROUP BY svm.vendor_name
			ORDER BY total_quantity_sold DESC
			";
			$globalSellingVendorProduct = parent::selectTable_f_mdl($sql);
			$globalVendorProdhtml = '';
			$globalVendorProdhtml .= '<table class="table table-bordered table-hover"> ';
			$globalVendorProdhtml .= '<thead>';
			$globalVendorProdhtml .= '<tr>';
			$globalVendorProdhtml .= '<th>Vendor Name</th>';
			$globalVendorProdhtml .= '<th>Total Sold Quantity</th>';
			$globalVendorProdhtml .= '<th>Flash Sale</th>';
			$globalVendorProdhtml .= '<th>On-Demand</th>';
			$globalVendorProdhtml .= '</tr>';
			$globalVendorProdhtml .= '</thead>';
			$globalVendorProdhtml .= '<tbody>';				
			if(!empty($globalSellingVendorProduct)){
				foreach($globalSellingVendorProduct as $globalvendorProd){
					$globalVendorProdhtml .= '<tr>';
					$globalVendorProdhtml .= '<td>'.$globalvendorProd['vendor_name'].'</td>';
					$globalVendorProdhtml .= '<td>'.number_format($globalvendorProd['total_quantity_sold']).'</td>';
					$globalVendorProdhtml .= '<td>'.number_format($globalvendorProd['flash_orderItem_sold']).'</td>';
					$globalVendorProdhtml .= '<td>'.number_format($globalvendorProd['ondemand_orderItem_sold']).'</td>';
					$globalVendorProdhtml .= '</tr>';
				} 
			}else{
				$globalVendorProdhtml .= '<tr>';
				$globalVendorProdhtml .= '<td colspan="5" align="center">No Record Found</td>';
				$globalVendorProdhtml .= '</tr>';
			}
			$globalVendorProdhtml .= '</tbody>';
			$globalVendorProdhtml .= '</table>';
			$resultArray['globalVendorProdhtml'] = $globalVendorProdhtml;

			$sql = "SELECT soim.sku,SUM(soim.quantity) AS total_quantity_sold,svm.vendor_name,
			SUM(CASE WHEN som.store_sale_type = 'Flash Sale' THEN soim.quantity ELSE 0 END) AS flash_orderItem_sold,
    		SUM(CASE WHEN som.store_sale_type = 'On-Demand' THEN soim.quantity ELSE 0 END) AS ondemand_orderItem_sold
			FROM store_order_items_master AS soim
			INNER JOIN store_orders_master AS som ON som.id = soim.store_orders_master_id
			INNER JOIN store_owner_product_variant_master AS sopvm ON sopvm.id = soim.store_owner_product_variant_master_id
			INNER JOIN store_owner_product_master as sopm ON sopm.id = soim.store_owner_product_master_id
			INNER JOIN store_product_master as spm ON spm.id = sopm.store_product_master_id
			INNER JOIN store_vendors_master as svm ON svm.id = spm.vendor_id
			WHERE som.is_order_cancel = '0' AND soim.is_deleted = '0' AND date(som.created_on) BETWEEN '".$from_date."' AND '".$to_date."'
			GROUP BY soim.sku,svm.vendor_name 
			ORDER BY total_quantity_sold DESC
			";
			$globalSellingVendorSku = parent::selectTable_f_mdl($sql);
			$globalVendorSkuhtml = '';
			$globalVendorSkuhtml .= '<table class="table table-bordered table-hover"> ';
			$globalVendorSkuhtml .= '<thead>';
			$globalVendorSkuhtml .= '<tr>';
			$globalVendorSkuhtml .= '<th>Vendor Name</th>';
			$globalVendorSkuhtml .= '<th>SKU</th>';
			$globalVendorSkuhtml .= '<th>Total Sold Quantity</th>';
			$globalVendorSkuhtml .= '<th>Flash Sale</th>';
			$globalVendorSkuhtml .= '<th>On-Demand</th>';
			$globalVendorSkuhtml .= '</tr>';
			$globalVendorSkuhtml .= '</thead>';
			$globalVendorSkuhtml .= '<tbody>';				
			if(!empty($globalSellingVendorSku)){
				foreach($globalSellingVendorSku as $vendorsku){
					$globalVendorSkuhtml .= '<tr>';
					$globalVendorSkuhtml .= '<td>'.$vendorsku['vendor_name'].'</td>';
					$globalVendorSkuhtml .= '<td>'.$vendorsku['sku'].'</td>';
					$globalVendorSkuhtml .= '<td>'.number_format($vendorsku['total_quantity_sold']).'</td>';
					$globalVendorSkuhtml .= '<td>'.number_format($vendorsku['flash_orderItem_sold']).'</td>';
					$globalVendorSkuhtml .= '<td>'.number_format($vendorsku['ondemand_orderItem_sold']).'</td>';
					$globalVendorSkuhtml .= '</tr>';
				} 
			}else{
				$globalVendorSkuhtml .= '<tr>';
				$globalVendorSkuhtml .= '<td colspan="5" align="center">No Record Found</td>';
				$globalVendorSkuhtml .= '</tr>';
			}
			$globalVendorSkuhtml .= '</tbody>';
			$globalVendorSkuhtml .= '</table>';
			$resultArray['globalsellingskubyvendorSKUhtml'] = $globalVendorSkuhtml;


			$resultArray["isSuccess"] = "TRUE";
			echo json_encode($resultArray,1);exit;
		}
	}

	public function export_analytical_report()
	{
		global $s3Obj;
		$reportData=[];
		if (parent::isPOST()) {
			if (!empty(parent::getVal("method")) && parent::getVal("method") == "export_analytical_report") {
				$csv_for = parent::getVal("csv_for");
				
				$from_date='';
				if ((isset($_POST['start_date'])) && $_POST['start_date'] != '') {
					$from_date = date('Y-m-d', strtotime($_POST['start_date']));
				}
				$to_date='';
				if ((isset($_POST['end_date'])) && $_POST['end_date'] != '') {
					$to_date = date('Y-m-d', strtotime($_POST['end_date'].' 23:59:59'));
				}

				$cond_date = '';
				if (isset($_POST['start_date'])) {
					$cond_date = "AND date(som.created_on) BETWEEN '".$from_date."' AND '".$to_date."' ";
				}

				$resultArray = array();
				$export_file = 'analytical-report-'.time().'.csv';
				$export_file_path = 'image_uploads/_export/' . $export_file;
				$export_file_url = common::IMAGE_UPLOAD_URL . '_export/' . $export_file;
				$file_for_export_data = fopen($export_file_path, "w");
				$BOM = "\xEF\xBB\xBF";
				header('Content-Encoding: UTF-8');
				header('Content-type: text/plain; charset=utf-8');
				header('Content-type: text/csv; charset=UTF-8');
				header('Content-Type: text/html; charset=utf-8');
				header('Content-Transfer-Encoding: binary');
				header('Content-type: application/csv');
				header('Content-type: application/excel');
				mb_convert_encoding($export_file_url, 'UTF-16LE', 'UTF-8');
				header("Content-type: application/vnd.ms-excel");
				header('Content-Disposition: attachment; filename=' . $export_file_url);

				if($csv_for=='export_top_selling_sku'){
					$sql = "SELECT soim.sku, SUM(soim.quantity) AS total_quantity_sold,
						SUM(CASE WHEN som.store_sale_type = 'Flash Sale' THEN soim.quantity ELSE 0 END) AS flash_orderItem_sold,
			    		SUM(CASE WHEN som.store_sale_type = 'On-Demand' THEN soim.quantity ELSE 0 END) AS ondemand_orderItem_sold
						FROM store_order_items_master AS soim
						INNER JOIN store_orders_master AS som ON som.id = soim.store_orders_master_id
						WHERE som.is_order_cancel = '0' AND soim.is_deleted = '0'  
						$cond_date 
						GROUP BY soim.sku
						ORDER BY total_quantity_sold DESC
					";
					$reportData=parent::selectTable_f_mdl($sql);
					fputcsv(
						$file_for_export_data,
						['SKU','Total Sold Quantity', 'Flash Sale Sold', 'On-Demand Sold']
					);
					foreach ($reportData as $values) {

						$total_quantity_sold  		= number_format($values['total_quantity_sold']);
						$flash_orderItem_sold  		= number_format($values['flash_orderItem_sold']);
						$ondemand_orderItem_sold  	= number_format($values['ondemand_orderItem_sold']);
						fputcsv(
							$file_for_export_data,
							[
								trim($values['sku']),
								trim($total_quantity_sold),
								trim($flash_orderItem_sold),
								trim($ondemand_orderItem_sold)
							]
						);
					}
				}else if($csv_for=='export_top_selling_color'){
					$sql = "SELECT sopvm.color,spcm.product_color_name,SUM(soim.quantity) AS total_quantity_sold,
						SUM(CASE WHEN som.store_sale_type = 'Flash Sale' THEN soim.quantity ELSE 0 END) AS flash_orderItem_sold,
			    		SUM(CASE WHEN som.store_sale_type = 'On-Demand' THEN soim.quantity ELSE 0 END) AS ondemand_orderItem_sold
						FROM store_order_items_master AS soim
						INNER JOIN store_orders_master AS som ON som.id = soim.store_orders_master_id
						INNER JOIN store_owner_product_variant_master AS sopvm ON sopvm.id = soim.store_owner_product_variant_master_id
						INNER JOIN store_product_colors_master as spcm ON spcm.product_color=sopvm.color
						WHERE som.is_order_cancel = '0' AND soim.is_deleted = '0' 
						$cond_date 
						GROUP BY sopvm.color
						ORDER BY total_quantity_sold DESC 
					";
					$reportData=parent::selectTable_f_mdl($sql);
					fputcsv(
						$file_for_export_data,
						['Color Name','Total Sold Quantity', 'Flash Sale Sold', 'On-Demand Sold']
					);
					foreach ($reportData as $values) {
						$total_quantity_sold  		= number_format($values['total_quantity_sold']);
						$flash_orderItem_sold  		= number_format($values['flash_orderItem_sold']);
						$ondemand_orderItem_sold  	= number_format($values['ondemand_orderItem_sold']);
						fputcsv(
							$file_for_export_data,
							[
								trim($values['product_color_name']),
								trim($total_quantity_sold),
								trim($flash_orderItem_sold),
								trim($ondemand_orderItem_sold)
							]
						);
					}
				}else if($csv_for=='export_top_selling_size'){
					$sql = "SELECT sopvm.size,SUM(soim.quantity) AS total_quantity_sold,
						SUM(CASE WHEN som.store_sale_type = 'Flash Sale' THEN soim.quantity ELSE 0 END) AS flash_orderItem_sold,
			    		SUM(CASE WHEN som.store_sale_type = 'On-Demand' THEN soim.quantity ELSE 0 END) AS ondemand_orderItem_sold
						FROM store_order_items_master AS soim
						INNER JOIN store_orders_master AS som ON som.id = soim.store_orders_master_id
						INNER JOIN store_owner_product_variant_master AS sopvm ON sopvm.id = soim.store_owner_product_variant_master_id
						INNER JOIN store_product_colors_master as spcm ON spcm.product_color=sopvm.color
						WHERE som.is_order_cancel = '0' AND soim.is_deleted = '0'  
						$cond_date 
						GROUP BY sopvm.size
						ORDER BY total_quantity_sold DESC 
					";
					$reportData=parent::selectTable_f_mdl($sql);
					fputcsv(
						$file_for_export_data,
						['Size','Total Sold Quantity', 'Flash Sale Sold', 'On-Demand Sold']
					);
					foreach ($reportData as $values) {
						$total_quantity_sold  		= number_format($values['total_quantity_sold']);
						$flash_orderItem_sold  		= number_format($values['flash_orderItem_sold']);
						$ondemand_orderItem_sold  	= number_format($values['ondemand_orderItem_sold']);
						fputcsv(
							$file_for_export_data,
							[
								trim($values['size']),
								trim($total_quantity_sold),
								trim($flash_orderItem_sold),
								trim($ondemand_orderItem_sold)
							]
						);
					}
				}else if($csv_for=='export_top_selling_vender_prod'){
					$sql = "SELECT svm.vendor_name, SUM(soim.quantity) AS total_quantity_sold,
						SUM(CASE WHEN som.store_sale_type = 'Flash Sale' THEN soim.quantity ELSE 0 END) AS flash_orderItem_sold,
			    		SUM(CASE WHEN som.store_sale_type = 'On-Demand' THEN soim.quantity ELSE 0 END) AS ondemand_orderItem_sold
						FROM store_order_items_master AS soim
						INNER JOIN store_orders_master AS som ON som.id = soim.store_orders_master_id
						INNER JOIN store_owner_product_variant_master AS sopvm ON sopvm.id = soim.store_owner_product_variant_master_id
						INNER JOIN store_owner_product_master as sopm ON sopm.id = soim.store_owner_product_master_id
						INNER JOIN store_product_master as spm ON spm.id = sopm.store_product_master_id
						INNER JOIN store_vendors_master as svm ON svm.id = spm.vendor_id
						WHERE som.is_order_cancel = '0' AND soim.is_deleted = '0' 
						$cond_date 
						GROUP BY svm.vendor_name
						ORDER BY total_quantity_sold DESC
					";
					$reportData=parent::selectTable_f_mdl($sql);
					fputcsv(
						$file_for_export_data,
						['Vendor Name','Total Sold Quantity', 'Flash Sale Sold', 'On-Demand Sold']
					);
					foreach ($reportData as $values) {
						$total_quantity_sold  		= number_format($values['total_quantity_sold']);
						$flash_orderItem_sold  		= number_format($values['flash_orderItem_sold']);
						$ondemand_orderItem_sold  	= number_format($values['ondemand_orderItem_sold']);
						fputcsv(
							$file_for_export_data,
							[
								trim($values['vendor_name']),
								trim($total_quantity_sold),
								trim($flash_orderItem_sold),
								trim($ondemand_orderItem_sold)
							]
						);
					}
				}else if($csv_for=='export_top_selling_vendor_sku'){
					$sql = "SELECT soim.sku,SUM(soim.quantity) AS total_quantity_sold,svm.vendor_name,
						SUM(CASE WHEN som.store_sale_type = 'Flash Sale' THEN soim.quantity ELSE 0 END) AS flash_orderItem_sold,
			    		SUM(CASE WHEN som.store_sale_type = 'On-Demand' THEN soim.quantity ELSE 0 END) AS ondemand_orderItem_sold
						FROM store_order_items_master AS soim
						INNER JOIN store_orders_master AS som ON som.id = soim.store_orders_master_id
						INNER JOIN store_owner_product_variant_master AS sopvm ON sopvm.id = soim.store_owner_product_variant_master_id
						INNER JOIN store_owner_product_master as sopm ON sopm.id = soim.store_owner_product_master_id
						INNER JOIN store_product_master as spm ON spm.id = sopm.store_product_master_id
						INNER JOIN store_vendors_master as svm ON svm.id = spm.vendor_id
						WHERE som.is_order_cancel = '0' AND soim.is_deleted = '0' 
						$cond_date 
						GROUP BY soim.sku,svm.vendor_name 
						ORDER BY total_quantity_sold DESC
					";
					$reportData=parent::selectTable_f_mdl($sql);
					fputcsv(
						$file_for_export_data,
						['Vendor Name','SKU','Total Sold Quantity', 'Flash Sale Sold', 'On-Demand Sold']
					);
					foreach ($reportData as $values) {
						$total_quantity_sold  		= number_format($values['total_quantity_sold']);
						$flash_orderItem_sold  		= number_format($values['flash_orderItem_sold']);
						$ondemand_orderItem_sold  	= number_format($values['ondemand_orderItem_sold']);
						fputcsv(
							$file_for_export_data,
							[
								trim($values['vendor_name']),
								trim($values['sku']),
								trim($total_quantity_sold),
								trim($flash_orderItem_sold),
								trim($ondemand_orderItem_sold)
							]
						);
					}
				}else if($csv_for=='export_top_selling_sku_by_colorsize'){
					$sql = "SELECT soim.sku,sopvm.color,spcm.product_color_name,sopvm.size,SUM(soim.quantity) AS total_quantity_sold,
						SUM(CASE WHEN som.store_sale_type = 'Flash Sale' THEN soim.quantity ELSE 0 END) AS flash_orderItem_sold,
						SUM(CASE WHEN som.store_sale_type = 'On-Demand' THEN soim.quantity ELSE 0 END) AS ondemand_orderItem_sold
						FROM store_order_items_master AS soim
						INNER JOIN store_orders_master AS som ON som.id = soim.store_orders_master_id
						INNER JOIN store_owner_product_variant_master AS sopvm ON sopvm.id = soim.store_owner_product_variant_master_id
						INNER JOIN store_product_colors_master as spcm ON spcm.product_color=sopvm.color
						WHERE som.is_order_cancel = '0' AND soim.is_deleted = '0'
						$cond_date 
						GROUP BY soim.sku, sopvm.size, sopvm.color
						ORDER BY total_quantity_sold DESC
					";
					$reportData=parent::selectTable_f_mdl($sql);
					fputcsv(
						$file_for_export_data,
						['SKU', 'Color', 'Size', 'Total Sold Quantity', 'Flash Sale Sold', 'On-Demand Sold']
					);
					foreach ($reportData as $values) {

						$total_quantity_sold  		= number_format($values['total_quantity_sold']);
						$flash_orderItem_sold  		= number_format($values['flash_orderItem_sold']);
						$ondemand_orderItem_sold  	= number_format($values['ondemand_orderItem_sold']);
						fputcsv(
							$file_for_export_data,
							[
								trim($values['sku']),
								trim($values['product_color_name']),
								trim($values['size']),
								trim($total_quantity_sold),
								trim($flash_orderItem_sold),
								trim($ondemand_orderItem_sold)
							]
						);
					}
				}

				fputcsv(
					$file_for_export_data,
					['']
				);
				$status = true;
				if ($status == true) {
					fclose($file_for_export_data);
					$resultArray['SUCCESS'] = 'TRUE';
					$resultArray['MESSAGE'] = '';
					$resultArray['EXPORT_URL'] = $export_file_url; // Task 59
				} else {
					$resultArray['SUCCESS'] = 'FALSE';
					$resultArray['MESSAGE'] = 'Records are not found.';
				}
				common::sendJson($resultArray);
			}
		}
	}
}
