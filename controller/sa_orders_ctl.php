<?php
include_once 'model/sa_orders_mdl.php';
$path = preg_replace('/controller(?!.*controller).*/','',__DIR__);
include_once $path . 'libraries/Aws3.php';
$login_user_email="";
if(isset($_SESSION['user_email']) && $_SESSION['user_email'] != "") {
	$login_user_email=trim($_SESSION['user_email']);
}
class sa_orders_ctl extends sa_orders_mdl
{
	public $TempSession = "";

	function __construct(){	
		if(parent::isGET() || parent::isPOST()){
			$this->SITE_ACCESS_KEY = parent::getVal("stkn");
		}

		if(isset($_REQUEST['action'])){
			$action = $_REQUEST['action'];
			if($action=='approveOrder'){
				$this->approveOrder();exit;
			}elseif($action=='orderDetailsPage'){
				$this->orderDetailsPage();exit;
			}elseif($action=='approveOrderQuickbuys'){
				$this->approveOrderQuickBuy();exit;
			}elseif($action=='quickbuyorderDetailsPage'){
				$this->quickbuyOrderDetailsPage();exit;
			}elseif($action=='deleteManualData'){
				$this->deleteManualData();exit;
			}elseif($action=='deleteQuickbuyData'){
				$this->deleteQuickbuyData();exit;
			}elseif($action=='deleteManualOrderAfterApprove'){
				$this->deleteManualOrderAfterApprove();exit;
			}elseif($action=='deleteQuickbuyOrderAfterApprove'){
				$this->deleteQuickbuyOrderAfterApprove();exit;
			}elseif($action=='updateOrderTrackingNumber'){
				$this->updateOrderTrackingNumber();exit;
			}
		}
		
		common::CheckLoginSession();
	}
	
	function getStoreDropdownInfo(){
		return parent::getStoreDropdownInfo_f_mdl();
	}

	function getStoreCloseDropdownInfo(){

		
		return parent::getStoreCloseDropdownInfo_f_mdl();
	}
	
	function updateCancelOrderFlag(){
		global $login_user_email;
		if(parent::isPOST()){
			if(!empty(parent::getVal("method")) && parent::getVal("method") == "cancel_order") {
				$id = parent::getVal("id");
				$store_master_id = parent::getVal("store_master_id");

				$soim_update_data = [
                    'buyStatus' => '1'
                ];
                parent::updateTable_f_mdl('store_order_items_master',$soim_update_data,'store_orders_master_id="'.$id.'"');
				return parent::updateCancelOrderFlag_f_mdl($id,$store_master_id,$login_user_email);				
			}
		}
	}
	
	function get_order_list_post(){
		if(empty($_REQUEST['order_type'])){

			if(parent::isPOST()){
				if(parent::getVal("hdn_method") == "orders_pagination")
				{	
					$record_count=0;
					$page=0;
					$current_page=1;
					$rows='10';
					$keyword='';
					
					if( (isset($_REQUEST['rows']))&&(!empty($_REQUEST['rows'])) ){
						$rows=$_REQUEST['rows'];
					}
					if( (isset($_REQUEST['keyword']))&&(!empty($_REQUEST['keyword'])) ){
						$keyword=$_REQUEST['keyword'];
					}
					if( (isset($_REQUEST['current_page']))&&(!empty($_REQUEST['current_page'])) ){
						$current_page=$_REQUEST['current_page'];
					}
					$start=($current_page-1)*$rows;
					$end=$rows;
					$sort_field = '';
					if(isset($_POST['sort_field']) && !empty($_POST['sort_field'])){
						$sort_field = $_POST['sort_field'];
					}
					$sort_type = '';
					if(isset($_POST['sort_type']) && !empty($_POST['sort_type'])){
						$sort_type = $_POST['sort_type'];
					}
					//end fixed, no change for any module

					$cond_keyword = '';
					if(isset($keyword) && !empty($keyword)){
						$cond_keyword = "AND (
								som.shop_order_id = '".trim($keyword)."' OR
								som.shop_order_number = '".trim($keyword)."' OR
								som.cust_email LIKE '%".trim($keyword)."%' OR
								som.cust_phone LIKE '%".trim($keyword)."%' OR
								sm.store_name LIKE '%".trim($keyword)."%' OR
								som.fe_order_id LIKE '%".trim($keyword)."%' OR
								som.cust_name LIKE '%".trim($keyword)."%'
							)";
					}
					$cond_order = 'ORDER BY som.id DESC';
					if(!empty($sort_field)){
						$cond_order = 'ORDER BY '.$sort_field.' '.$sort_type;
					}

					$trackingStatus = "";
					if ((isset($_REQUEST['tracking_status'])) && (!empty($_REQUEST['tracking_status']))) {
						if($_REQUEST['tracking_status']=='in_production'){
							$trackingStatus = 'AND ssrm.tracking_status IS NULL AND (som.fe_order_id !="" OR som.fe_order_id IS NOT NULL) AND (DATEDIFF(CURDATE(), som.created_on) <= 7) ';
						}elseif($_REQUEST['tracking_status']=='in_production_delayed'){
							$trackingStatus = 'AND ssrm.tracking_status IS NULL AND (som.fe_order_id !="" OR som.fe_order_id IS NOT NULL) AND (DATEDIFF(CURDATE(), som.created_on) > 7) ';
						}else{
							if($_REQUEST['tracking_status']=='in_transit_delayed'){
								$trackingStatus = 'AND ssrm.tracking_status = "in_transit" AND (DATEDIFF(CURDATE(), ssrm.ship_date) > 10) ';
							}else{
								$trackingStatus = 'AND ssrm.tracking_status = "'.$_REQUEST['tracking_status'].'"';
							}
						}	
					}

					$selectedStore = "";
					if( (isset($_REQUEST['select_store']))&&(!empty($_REQUEST['select_store'])) ){
						$selectedStore = 'AND sm.id = "'.$_REQUEST['select_store'].'"';
					}
					
					$storeSaleType = "";
					if( (isset($_REQUEST['store_sale_type_master_id']))&&(!empty($_REQUEST['store_sale_type_master_id'])) ){
						$storeSaleType = 'AND sm.store_sale_type_master_id = "'.$_REQUEST['store_sale_type_master_id'].'"';
					}

					$vendor_order_status1 = "";
					if( (isset($_REQUEST['vendor_order_status']))&&(!empty($_REQUEST['vendor_order_status'])) ){
						if(trim($_REQUEST['vendor_order_status'])=='sent_to_vendor_fe'){
							$vendor_order_status1 = 'AND (som.fe_order_id !="" OR som.fe_order_id IS NOT NULL) ';
						}else if($_REQUEST['vendor_order_status']=='sent_to_vendor_customCat'){
							$vendor_order_status1 = 'AND (som.customcat_order_id !="" OR som.customcat_order_id IS NOT NULL)';
						}else if($_REQUEST['vendor_order_status']=='manually_ordered'){
							$vendor_order_status1 = 'AND soim.mark_as_manually_ordered_vendor="1" ';
						}else if($_REQUEST['vendor_order_status']=='need_to_order_manually'){
							$vendor_order_status1 = 'AND (soim.mark_as_manually_ordered_vendor="0" AND (som.fe_order_id ="" OR som.fe_order_id IS NULL) AND (som.customcat_order_id ="" OR som.customcat_order_id IS NULL))';
						} 
					}
					
					$sql="
						SELECT count(som.id) as count
						FROM `store_orders_master` as som
						LEFT JOIN `store_master` as sm ON sm.id = som.store_master_id
						LEFT JOIN store_order_items_master as soim ON soim.store_orders_master_id=som.id
						LEFT JOIN fe_webhook_master as fwm ON fwm.fe_order_id=som.fe_order_id
						LEFT JOIN shipengine_shipping_rates_master as ssrm ON ssrm.tracking_number=fwm.fe_tracker_number
						WHERE 1 AND som.order_type = 1 AND som.is_order_cancel = '0'
						$cond_keyword
						$selectedStore
						$storeSaleType
						$vendor_order_status1
						$trackingStatus
					";
					$all_count = parent::selectTable_f_mdl($sql);

					$sql1="
						SELECT DISTINCT 
							sm.store_name,
							som.id, 
							som.store_master_id,
							som.shop_order_id, som.shop_order_number, som.total_price, 
							som.cust_email, 
							som.cust_name,
							som.is_order_cancel,
							som.order_cancelled_by,
							som.fe_order_id,
							som.customcat_order_id,
							som.customcat_tracker_number,
							som.customcat_tracking_url,
							som.created_on, 
							som.created_on_ts,
							som.manual_tracking_number,
							fwm.fe_tracker_number,
							fwm.fe_tracking_url,
							ssrm.tracking_status,
							ssrm.ship_date
						FROM `store_orders_master` as som
						LEFT JOIN `store_master` as sm ON sm.id = som.store_master_id
						LEFT JOIN store_order_items_master as soim ON soim.store_orders_master_id=som.id
						LEFT JOIN fe_webhook_master as fwm ON fwm.fe_order_id=som.fe_order_id
						LEFT JOIN shipengine_shipping_rates_master as ssrm ON ssrm.tracking_number=fwm.fe_tracker_number
						WHERE 1 AND som.order_type = 1 AND som.is_order_cancel = '0'
						$cond_keyword
						$selectedStore
						$storeSaleType
						$vendor_order_status1
						$trackingStatus
						$cond_order
						LIMIT $start,$end
					";
					$all_list = parent::selectTable_f_mdl($sql1);

					if( (isset($all_count[0]['count']))&&(!empty($all_count[0]['count'])) ){
						$record_count=$all_count[0]['count'];
						$page=$record_count/$rows;
						$page=ceil($page);
					}
					$sr_start=0;
					if($record_count>=1){
						$sr_start=(($current_page-1)*$rows)+1;
					}
					$sr_end=($current_page)*$rows;
					if($record_count<=$sr_end){
						$sr_end=$record_count;
					}

					if(isset($_POST['pagination_export']) && $_POST['pagination_export']=='Y'){
						/*if(isset($all_list) && !empty($all_list)){
							$date_formate=Config::get('constant.DATE_FORMATE');
							$file_full_path = public_path().Config::get('constant.DOWNLOAD_TABLE_LOCATION')."downloaded_table_".time().".csv";
							$file_full_url = asset(Config::get('constant.DOWNLOAD_TABLE_LOCATION')."downloaded_table_".time().".csv");
							$file_for_download_data = fopen($file_full_path,"w");
							fputcsv($file_for_download_data,array('#','Name','Email','Mobile','Add Date'));
							$i=$sr_start;
							foreach ($all_list as $single){
								if($single->add_date!=''){
									$add_date = date($date_formate, $single->add_date);
								}else{
									$add_date = '';
								}
								fputcsv($file_for_download_data,array(
									$i,
									$single->first_name.' '.$single->last_name,
									$single->email,
									$single->mobile,
									$add_date
								));
								$i++;
							}
							fclose($file_for_download_data);
							$this->param['SUCCESS']='TRUE';
							$this->param['file_full_url']=$file_full_url;
						}else{
							$this->param['SUCCESS']='FALSE';
						}
						echo json_encode($this->param,1);*/
					}else{
						$html = '';
						$html .= '<div class="row">';
						$html .= '<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">';
						$html .= '<div class="table-responsive">';
						$html .= '<table class="table table-bordered table-hover">';

						$html .= '<thead>';
						$html .= '<tr>';
						$html .= '<th>#</th>';
						$html .= '<th class="sort_th" data-sort_field="store_name">Store Name</th>';
						//$html .= '<th>Shop Order Id</th>';// class="sort_th" data-sort_field="shop_order_id"
						$html .= '<th class="sort_th" data-sort_field="shop_order_number">Order Number</th>';
						$html .= '<th>Price</th>';
						$html .= '<th>Customer Name</th>';
						$html .= '<th>Email</th>';
						$html .= '<th class="sort_th" data-sort_field="created_on">Order Date</th>';
						$html .= '<th>Vendor Order Status</th>';
						$html .= '<th>Vendor Order Id</th>';
						$html .= '<th>FE Tracking</th>';
						$html .= '<th>CustomCat Tracking</th>';
						$html .= '<th colspan="2" style="text-align: center;">Action</th>';
						$html .= '</tr>';
						$html .= '</thead>';

						$html .= '<tbody>';

						if(!empty($all_list)){
							$sr = $sr_start;
							foreach($all_list as $single){

								$sqldata = "SELECT svm.vendor_name, SUM(soim.quantity) as total_quantity, som.is_sent_customcat, som.fe_order_id, soim.mark_as_manually_ordered_vendor
						            FROM store_order_items_master as soim
						            LEFT JOIN store_owner_product_variant_master as sopvm ON sopvm.id = soim.store_owner_product_variant_master_id
						            LEFT JOIN store_owner_product_master as sopm ON sopm.id = soim.store_owner_product_master_id
						            LEFT JOIN store_product_master as spm ON spm.id = sopm.store_product_master_id
						            LEFT JOIN store_vendors_master as svm ON svm.id = spm.vendor_id
						            LEFT JOIN store_orders_master as som ON som.id = soim.store_orders_master_id
						            WHERE soim.store_orders_master_id = '".$single['id']."' AND soim.is_deleted='0'
						            GROUP BY svm.vendor_name, som.is_sent_customcat, som.fe_order_id, soim.mark_as_manually_ordered_vendor";
								$orderStatusData = parent::selectTable_f_mdl($sqldata);

								$statusQuantities = [
								    'Need to order manually' => 0,
								    'Sent to vendor (FE)' => 0,
								    'Sent to vendor (CustomCat)' => 0,
								    'Manually ordered' => 0
								];

								foreach($orderStatusData as $singleItem) {
								    if ($singleItem['mark_as_manually_ordered_vendor'] == 1) {
								        $statusQuantities['Manually ordered'] += $singleItem['total_quantity'];
								    } else {
								        if ($singleItem['vendor_name'] == 'CustomCat') {
								            if ($singleItem['is_sent_customcat'] == '2') {
								                $statusQuantities['Sent to vendor (CustomCat)'] += $singleItem['total_quantity'];
								            } else {
								                $statusQuantities['Need to order manually'] += $singleItem['total_quantity'];
								            }
								        } elseif ($singleItem['vendor_name'] == 'FulfillEngine') {
								            if (!empty($singleItem['fe_order_id'])) {
								                $statusQuantities['Sent to vendor (FE)'] += $singleItem['total_quantity'];
								            } else {
								                $statusQuantities['Need to order manually'] += $singleItem['total_quantity'];
								            }
								        } else {
								            $statusQuantities['Need to order manually'] += $singleItem['total_quantity'];
								        }
								    }
								}

								$orderItemStatus = '';
								foreach ($statusQuantities as $status => $quantity) {
								    if ($quantity > 0) {
								        $badgeClass = '';
								        if ($status == 'Need to order manually') {
								            $badgeClass = 'background-color: #dc3545; color:#fff;';
								        } elseif ($status == 'Manually ordered') {
								            $badgeClass = 'background-color: #007bff; color:#fff;';
								        } else {
								            $badgeClass = 'badge-success';
								        }
								        $orderItemStatus .= '<span class="badge ' . (($status == 'Need to order manually' || $status == 'Manually ordered') ? '' : 'badge-success') . '" style="' . $badgeClass . '">' . $status . ' - ' . $quantity . ' qty</span><br>';
								    }
								}

								$create_date='';
								if(!empty($single["created_on"])){
									$create_date=date('m/d/Y h:i A', strtotime($single["created_on"]));
								}
								$html .= '<tr>';
								
								$html .= '<td>'.$sr.'</td>';
								$html .= '<td>'.$single['store_name'].'</td>';
								
								$html .= '<td><a href="sa-order-view.php?stkn='.parent::getVal("stkn").'&oid='.$single['id'].'">'.$single['shop_order_number'].'</a></td>';
								
								$html .= '<td>$'.number_format((double)$single['total_price'],2).'</td>';
								$html .= '<td>'.$single['cust_name'].'</td>';
								$html .= '<td>'.$single['cust_email'].'</td>';
								$html .= '<td>'.$create_date.'</td>';
								$html .= '<td>'.$orderItemStatus.'</td>';
								
								$feOrderUrl ='';
								if (!empty($single['fe_order_id'])) {
									if(isset($_SERVER['HTTP_HOST']) && ($_SERVER['HTTP_HOST']=="localhost") || ($_SERVER['HTTP_HOST']=="spirithero-rds.bitcotapps.com")){
										$feOrderUrl = '<span>FulfillEngine - <a target="_blank" href="https://app.fulfillengine.com/user-view/accounts/act-8330840/orders/'.$single['fe_order_id'].'">'.$single['fe_order_id'].'</a></span><br>';
									}else{
										$feOrderUrl = '<span>FulfillEngine - <a target="_blank" href="https://app.fulfillengine.com/user-view/accounts/act-9113822/orders/'.$single['fe_order_id'].'">'.$single['fe_order_id'].'</a></span><br>';
									}
									
								}
								$html .= '<td>'.$feOrderUrl.'</td>';
								$html .= '<td>';
									$currentDate = new DateTime();
									$currentDateFormatted = $currentDate->format('m/d/Y h:i A');
									$shipdate = new DateTime($single['ship_date']);
									$shipDateFormatted = $shipdate->format('m/d/Y h:i A');
									// Calculate the difference in days
									$interval = $currentDate->diff($shipdate);
									$daysDiff = $interval->days;

									$createddate = new DateTime($single["created_on"]);
									$createdateinterval = $currentDate->diff($createddate);
									$createdate_daysDiff = $createdateinterval->days;
			
									if ($single['tracking_status'] == 'in_transit') {
										if ($daysDiff > 10) {
											$html .= '<a target="_blank" href="'.$single['fe_tracking_url'].'" class="btn btn-xs in_transit_btn_'.$single['id'].'" style="color:#fff;background-color:#ff0000;display:block;">
													<i class="site-menu-icon icon fa-truck" aria-hidden="true" style="color: #fff;"></i> '.$single['tracking_status'].' (Delayed)
												</a><br>
												<p class="card-text">
													<input type="text" value="" order_id="'.$single['id'].'" class="tracking_input_'.$single['id'].'" id="tracking_input" name="tracking_input" placeholder="Enter tracking number" autocomplete="off" style="display: none;">
													<button class="btn btn-primary btn-round btn-sm edit_tracking_save_'.$single['id'].' waves-effect waves-classic" data-toggle="tooltip" data-placement="top" data-trigger="hover" order_id="'.$single['id'].'" id="edit_tracking_save_'.$single['id'].'" fe_order_id="'.$single['fe_order_id'].'" data-original-title="Save" title="" style="float: right; display:none;"><i class="fa fa-check"></i></button>
													<button class="btn btn-primary btn-round btn-sm edit_trackingnumber_'.$single['id'].' waves-effect waves-classic" data-toggle="tooltip" data-placement="top" data-trigger="hover" order_id="'.$single['id'].'" id="edit_trackingnumber_'.$single['id'].'" fe_order_id="'.$single['fe_order_id'].'" data-original-title="Edit" title="" style="float: right;display:block;"><i class="fa fa-edit"></i></button><br>
												</p>
											';
										} else {
											$html .= '<a target="_blank" href="'.$single['fe_tracking_url'].'" class="btn btn-xs in_transit_btn_'.$single['id'].'" style="color:#fff;background-color:#ffc107; display:block;">
														<i class="site-menu-icon icon fa-truck" aria-hidden="true" style="color: #fff;"></i> '.$single['tracking_status'].'
												</a><br>
												<p class="card-text">
													<input type="text" value="" order_id="'.$single['id'].'" class="tracking_input_'.$single['id'].'" id="tracking_input" name="tracking_input" placeholder="Enter tracking number" autocomplete="off" style="display: none;">
													<button class="btn btn-primary btn-round btn-sm edit_tracking_save_'.$single['id'].' waves-effect waves-classic" data-toggle="tooltip" data-placement="top" data-trigger="hover" order_id="'.$single['id'].'" fe_order_id="'.$single['fe_order_id'].'"
														id="edit_tracking_save_'.$single['id'].'" data-original-title="Save" title="" style="float: right; display:none;"><i class="fa fa-check"></i></button>
													<button class="btn btn-primary btn-round btn-sm edit_trackingnumber_'.$single['id'].' waves-effect waves-classic" data-toggle="tooltip" data-placement="top" data-trigger="hover" order_id="'.$single['id'].'" id="edit_trackingnumber_'.$single['id'].'" fe_order_id="'.$single['fe_order_id'].'" data-original-title="Edit" title="" style="float: right;display:block;"><i class="fa fa-edit"></i></button><br>
												</p>
											';
										}
										
									} elseif ($single['tracking_status'] == 'delivered') {
										$html .= '<a target="_blank" href="'.$single['fe_tracking_url'].'" class="btn btn-xs" style="color:#fff;background-color:#28a745"><i class="site-menu-icon icon fa-check" aria-hidden="true" style="color: #fff;"></i> '.$single['tracking_status'].'</a><br>';
									} else {
										if (!empty($single['fe_order_id'])) {
											if ($createdate_daysDiff > 7) {
												if(empty($single['manual_tracking_number'])){
													$html .= '<a class="btn btn-xs in_transit_btn_'.$single['id'].'" style="color:#fff;background-color:#330166;display:block;">
															<i class="site-menu-icon icon fa fa-file-text-o" aria-hidden="true" style="color: #fff;"></i>  In Production (Delayed)
														</a><br>
														<p class="card-text">
															<input type="text" value="" order_id="'.$single['id'].'" class="tracking_input_'.$single['id'].'" id="tracking_input" name="tracking_input" placeholder="Enter tracking number" autocomplete="off" style="display: none;">
															<button class="btn btn-primary btn-round btn-sm edit_tracking_save_'.$single['id'].' waves-effect waves-classic" data-toggle="tooltip" data-placement="top" data-trigger="hover" order_id="'.$single['id'].'" id="edit_tracking_save_'.$single['id'].'" fe_order_id="'.$single['fe_order_id'].'" data-original-title="Save" title="" style="float: right; display:none;"><i class="fa fa-check"></i></button>
															<button class="btn btn-primary btn-round btn-sm edit_trackingnumber_'.$single['id'].' waves-effect waves-classic" data-toggle="tooltip" data-placement="top" data-trigger="hover" order_id="'.$single['id'].'" id="edit_trackingnumber_'.$single['id'].'" fe_order_id="'.$single['fe_order_id'].'" data-original-title="Edit" title="" style="float: right;display:block;"><i class="fa fa-edit"></i></button><br>
														</p>
													';
											    }else{
													$html .= '<a class="btn btn-xs" style="color:#fff;background-color:#330166" target="_blank" href="https://tools.usps.com/go/TrackConfirmAction?tLabels='.$single['manual_tracking_number'].'" ><i class="site-menu-icon icon fa fa-file-text-o" aria-hidden="true" style="color: #fff;"></i> In Production ('.$single['manual_tracking_number'].')</a><br>';
												}

												//$html .= '<a class="btn btn-xs" style="color:#fff;background-color:#330166"><i class="site-menu-icon icon fa fa-file-text-o" aria-hidden="true" style="color: #fff;"></i> In Production (Delayed)</a><br>';
											} else {
												if(empty($single['manual_tracking_number'])){

													$html .= '<a class="btn btn-xs in_transit_btn_'.$single['id'].'" style="color:#fff;background-color:#330166; display:block;">
																<i class="site-menu-icon icon fa fa-file-text-o" aria-hidden="true" style="color: #fff;"></i> In Production
														</a><br>
														<p class="card-text">
															<input type="text" value="" order_id="'.$single['id'].'" class="tracking_input_'.$single['id'].'" id="tracking_input" name="tracking_input" placeholder="Enter tracking number" autocomplete="off" style="display: none;">
															<button class="btn btn-primary btn-round btn-sm edit_tracking_save_'.$single['id'].' waves-effect waves-classic" data-toggle="tooltip" data-placement="top" data-trigger="hover" order_id="'.$single['id'].'" fe_order_id="'.$single['fe_order_id'].'"
																id="edit_tracking_save_'.$single['id'].'" data-original-title="Save" title="" style="float: right; display:none;"><i class="fa fa-check"></i></button>
															<button class="btn btn-primary btn-round btn-sm edit_trackingnumber_'.$single['id'].' waves-effect waves-classic" data-toggle="tooltip" data-placement="top" data-trigger="hover" order_id="'.$single['id'].'" id="edit_trackingnumber_'.$single['id'].'" fe_order_id="'.$single['fe_order_id'].'" data-original-title="Edit" title="" style="float: right;display:block;"><i class="fa fa-edit"></i></button><br>
														</p>
													';
												}else{
													$html .= '<a class="btn btn-xs" style="color:#fff;background-color:#330166" target="_blank" href="https://tools.usps.com/go/TrackConfirmAction?tLabels='.$single['manual_tracking_number'].'"><i class="site-menu-icon icon fa fa-file-text-o" aria-hidden="true" style="color: #fff;"></i> In Production ('.$single['manual_tracking_number'].')</a><br>';
												}
												//$html .= '<a class="btn btn-xs" style="color:#fff;background-color:#330166"><i class="site-menu-icon icon fa fa-file-text-o" aria-hidden="true" style="color: #fff;"></i> In Production</a><br>';
											}
											
										}else{
											$html .= $single['tracking_status'];
										}
									}
								$html .= '</td>';
								if (!empty($single['customcat_tracker_number']) ) {
									$html .= '<td><a target="_blank" href="'.$single['customcat_tracking_url'].'" class="btn btn-xs" style="color:#fff;background-color:#28a745"><i class="site-menu-icon icon fa-check" aria-hidden="true" style="color: #fff;"></i> '.$single['customcat_tracker_number'].'</a><br></td>';
								}else{
									$html .= '<td></td>';
								}
								//$html .= '<td><a target="_blank" href="'.$single['fe_tracking_url'].'">'.$single['tracking_status'].'</a><br></td>';
								if($single["is_order_cancel"] == '0')
								{
									$html .= '<td style="text-align:center;"><button data-href="sa-orders.php?stkn='.parent::getVal("stkn").'&oid='.$single["id"].'" data-id = "'.$single["id"].'" data-store_master_id = "'.$single["store_master_id"].'" type="button" class="btn btn-danger btn-cancel-order">Cancel</button></td>';
								}
								else if($single["is_order_cancel"] == '1')
								{
									$html .= '<td style="text-align:center;">Cancelled</td>';
								}
								
								$html .= '<td style="text-align:center;"><button data-href="sa-order-view.php?stkn='.parent::getVal("stkn").'&oid='.$single["id"].'" type="button" class="btn btn-info btn-edit-order">Edit</button></td>';

								$html .= '</tr>';
								$sr++;
							}
						}else{
							$html .= '<tr>';
							$html .= '<td colspan="7" align="center">No Record Found</td>';
							$html .= '</tr>';
						}

						$html .= '</tbody>';
						$html .= '</table>';
						$html .= '</div>';
						$html .= '</div>';
						$html .= '</div>';

						$res['DATA'] = $html;
						$res['page_count'] = $page;
						$res['record_count']=$record_count;
						$res['sr_start']=$sr_start;
						$res['sr_end']=$sr_end;
						echo json_encode($res,1);
						exit;
					}
				}
			}
		}else if($_REQUEST['order_type']=='4'){
			if(parent::isPOST()){
				if(parent::getVal("hdn_method") == "orders_pagination")
				{	
					$record_count=0;
					$page=0;
					$current_page=1;
					$rows='10';
					$keyword='';
					
					if( (isset($_REQUEST['rows']))&&(!empty($_REQUEST['rows'])) ){
						$rows=$_REQUEST['rows'];
					}
					if( (isset($_REQUEST['keyword']))&&(!empty($_REQUEST['keyword'])) ){
						$keyword=$_REQUEST['keyword'];
					}
					if( (isset($_REQUEST['current_page']))&&(!empty($_REQUEST['current_page'])) ){
						$current_page=$_REQUEST['current_page'];
					}
					$start=($current_page-1)*$rows;
					$end=$rows;
					$sort_field = '';
					if(isset($_POST['sort_field']) && !empty($_POST['sort_field'])){
						$sort_field = $_POST['sort_field'];
					}
					$sort_type = '';
					if(isset($_POST['sort_type']) && !empty($_POST['sort_type'])){
						$sort_type = $_POST['sort_type'];
					}
					//end fixed, no change for any module

					$cond_keyword = '';
					if(isset($keyword) && !empty($keyword)){
						$cond_keyword = "AND (
								som.shop_order_id = '".trim($keyword)."' OR
								som.shop_order_number = '".trim($keyword)."' OR
								som.cust_email LIKE '%".trim($keyword)."%' OR
								som.cust_phone LIKE '%".trim($keyword)."%' OR
								sm.store_name LIKE '%".trim($keyword)."%' OR
								som.fe_order_id LIKE '%".trim($keyword)."%' OR
								som.cust_name LIKE '%".trim($keyword)."%'
							)";
					}
					$cond_order = 'ORDER BY som.id DESC';
					if(!empty($sort_field)){
						$cond_order = 'ORDER BY '.$sort_field.' '.$sort_type;
					}

					$trackingStatus = "";
					if ((isset($_REQUEST['tracking_status'])) && (!empty($_REQUEST['tracking_status']))) {
						if($_REQUEST['tracking_status']=='in_production'){
							$trackingStatus = 'AND ssrm.tracking_status IS NULL AND (som.fe_order_id !="" OR som.fe_order_id IS NOT NULL) AND (DATEDIFF(CURDATE(), som.created_on) <= 7) ';
						}elseif($_REQUEST['tracking_status']=='in_production_delayed'){
							$trackingStatus = 'AND ssrm.tracking_status IS NULL AND (som.fe_order_id !="" OR som.fe_order_id IS NOT NULL) AND (DATEDIFF(CURDATE(), som.created_on) > 7) ';
						}else{
							if($_REQUEST['tracking_status']=='in_transit_delayed'){
								$trackingStatus = 'AND ssrm.tracking_status = "in_transit" AND (DATEDIFF(NOW(), ssrm.ship_date) > 10) ';
							}else{
								$trackingStatus = 'AND ssrm.tracking_status = "'.$_REQUEST['tracking_status'].'"';
							}
						}	
					}

					$selectedStore = "";
					if( (isset($_REQUEST['select_store']))&&(!empty($_REQUEST['select_store'])) ){
						$selectedStore = 'AND sm.id = "'.$_REQUEST['select_store'].'"';
					}
					
					$storeSaleType = "";
					if( (isset($_REQUEST['store_sale_type_master_id']))&&(!empty($_REQUEST['store_sale_type_master_id'])) ){
						$storeSaleType = 'AND sm.store_sale_type_master_id = "'.$_REQUEST['store_sale_type_master_id'].'"';
					}

					$vendor_order_status1 = "";
					if( (isset($_REQUEST['vendor_order_status']))&&(!empty($_REQUEST['vendor_order_status'])) ){
						if(trim($_REQUEST['vendor_order_status'])=='sent_to_vendor_fe'){
							$vendor_order_status1 = 'AND (som.fe_order_id !="" OR som.fe_order_id IS NOT NULL) ';
						}else if($_REQUEST['vendor_order_status']=='sent_to_vendor_customCat'){
							$vendor_order_status1 = 'AND (som.customcat_order_id !="" OR som.customcat_order_id IS NOT NULL)';
						}else if($_REQUEST['vendor_order_status']=='manually_ordered'){
							$vendor_order_status1 = 'AND soim.mark_as_manually_ordered_vendor="1" ';
						}else if($_REQUEST['vendor_order_status']=='need_to_order_manually'){
							$vendor_order_status1 = 'AND (soim.mark_as_manually_ordered_vendor="0" AND (som.fe_order_id ="" OR som.fe_order_id IS NULL) AND (som.customcat_order_id ="" OR som.customcat_order_id IS NULL))';
						} 
					}
					
					$sql="
						SELECT count(DISTINCT som.id) as count
						FROM `store_orders_master` as som
						LEFT JOIN `store_master` as sm ON sm.id = som.store_master_id
						LEFT JOIN store_order_items_master as soim ON soim.store_orders_master_id=som.id
						LEFT JOIN fe_webhook_master as fwm ON fwm.fe_order_id=som.fe_order_id
						LEFT JOIN shipengine_shipping_rates_master as ssrm ON ssrm.tracking_number=fwm.fe_tracker_number
						WHERE 1 AND som.order_type = 1 AND som.is_order_cancel = '1'
						$cond_keyword
						$selectedStore
						$storeSaleType
						$vendor_order_status1
						$trackingStatus
					";
					$all_count = parent::selectTable_f_mdl($sql);

					$sql1="
						SELECT DISTINCT 
							sm.store_name,
							som.id, 
							som.store_master_id,
							som.shop_order_id, som.shop_order_number, som.total_price, 
							som.cust_email, 
							som.cust_name,
							som.is_order_cancel,
							som.order_cancelled_by,
							som.fe_order_id,
							som.customcat_order_id,
							som.customcat_tracker_number,
							som.customcat_tracking_url,
							som.created_on, 
							som.created_on_ts,
							som.manual_tracking_number,
							fwm.fe_tracker_number,
							fwm.fe_tracking_url,
							ssrm.tracking_status,
							ssrm.ship_date
						FROM `store_orders_master` as som
						LEFT JOIN `store_master` as sm ON sm.id = som.store_master_id
						LEFT JOIN store_order_items_master as soim ON soim.store_orders_master_id=som.id
						LEFT JOIN fe_webhook_master as fwm ON fwm.fe_order_id=som.fe_order_id
						LEFT JOIN shipengine_shipping_rates_master as ssrm ON ssrm.tracking_number=fwm.fe_tracker_number
						WHERE 1 AND som.order_type = 1 AND som.is_order_cancel = '1'
						$cond_keyword
						$selectedStore
						$storeSaleType
						$vendor_order_status1
						$trackingStatus
						$cond_order
						LIMIT $start,$end
					";
					$all_list = parent::selectTable_f_mdl($sql1);

					if( (isset($all_count[0]['count']))&&(!empty($all_count[0]['count'])) ){
						$record_count=$all_count[0]['count'];
						$page=$record_count/$rows;
						$page=ceil($page);
					}
					$sr_start=0;
					if($record_count>=1){
						$sr_start=(($current_page-1)*$rows)+1;
					}
					$sr_end=($current_page)*$rows;
					if($record_count<=$sr_end){
						$sr_end=$record_count;
					}

					if(isset($_POST['pagination_export']) && $_POST['pagination_export']=='Y'){
						/*if(isset($all_list) && !empty($all_list)){
							$date_formate=Config::get('constant.DATE_FORMATE');
							$file_full_path = public_path().Config::get('constant.DOWNLOAD_TABLE_LOCATION')."downloaded_table_".time().".csv";
							$file_full_url = asset(Config::get('constant.DOWNLOAD_TABLE_LOCATION')."downloaded_table_".time().".csv");
							$file_for_download_data = fopen($file_full_path,"w");
							fputcsv($file_for_download_data,array('#','Name','Email','Mobile','Add Date'));
							$i=$sr_start;
							foreach ($all_list as $single){
								if($single->add_date!=''){
									$add_date = date($date_formate, $single->add_date);
								}else{
									$add_date = '';
								}
								fputcsv($file_for_download_data,array(
									$i,
									$single->first_name.' '.$single->last_name,
									$single->email,
									$single->mobile,
									$add_date
								));
								$i++;
							}
							fclose($file_for_download_data);
							$this->param['SUCCESS']='TRUE';
							$this->param['file_full_url']=$file_full_url;
						}else{
							$this->param['SUCCESS']='FALSE';
						}
						echo json_encode($this->param,1);*/
					}else{
						$html = '';
						$html .= '<div class="row">';
						$html .= '<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">';
						$html .= '<div class="table-responsive">';
						$html .= '<table class="table table-bordered table-hover">';

						$html .= '<thead>';
						$html .= '<tr>';
						$html .= '<th>#</th>';
						$html .= '<th class="sort_th" data-sort_field="store_name">Store Name</th>';
						//$html .= '<th>Shop Order Id</th>';// class="sort_th" data-sort_field="shop_order_id"
						$html .= '<th class="sort_th" data-sort_field="shop_order_number">Order Number</th>';
						$html .= '<th>Price</th>';
						$html .= '<th>Customer Name</th>';
						$html .= '<th>Email</th>';
						$html .= '<th class="sort_th" data-sort_field="created_on">Order Date</th>';
						$html .= '<th>Vendor Order Status</th>';
						$html .= '<th>Vendor Order Id</th>';
						$html .= '<th>FE Tracking</th>';
						$html .= '<th>CustomCat Tracking</th>';
						$html .= '<th colspan="2" style="text-align: center;">Action</th>';
						$html .= '</tr>';
						$html .= '</thead>';

						$html .= '<tbody>';

						if(!empty($all_list)){
							$sr = $sr_start;
							foreach($all_list as $single){

								$sqldata = "SELECT svm.vendor_name, SUM(soim.quantity) as total_quantity, som.is_sent_customcat, som.fe_order_id, soim.mark_as_manually_ordered_vendor
						            FROM store_order_items_master as soim
						            LEFT JOIN store_owner_product_variant_master as sopvm ON sopvm.id = soim.store_owner_product_variant_master_id
						            LEFT JOIN store_owner_product_master as sopm ON sopm.id = soim.store_owner_product_master_id
						            LEFT JOIN store_product_master as spm ON spm.id = sopm.store_product_master_id
						            LEFT JOIN store_vendors_master as svm ON svm.id = spm.vendor_id
						            LEFT JOIN store_orders_master as som ON som.id = soim.store_orders_master_id
						            WHERE soim.store_orders_master_id = '".$single['id']."' AND soim.is_deleted='0'
						            GROUP BY svm.vendor_name, som.is_sent_customcat, som.fe_order_id, soim.mark_as_manually_ordered_vendor";
								$orderStatusData = parent::selectTable_f_mdl($sqldata);

								$statusQuantities = [
								    'Need to order manually' => 0,
								    'Sent to vendor (FE)' => 0,
								    'Sent to vendor (CustomCat)' => 0,
								    'Manually ordered' => 0
								];

								foreach($orderStatusData as $singleItem) {
								    if ($singleItem['mark_as_manually_ordered_vendor'] == 1) {
								        $statusQuantities['Manually ordered'] += $singleItem['total_quantity'];
								    } else {
								        if ($singleItem['vendor_name'] == 'CustomCat') {
								            if ($singleItem['is_sent_customcat'] == '2') {
								                $statusQuantities['Sent to vendor (CustomCat)'] += $singleItem['total_quantity'];
								            } else {
								                $statusQuantities['Need to order manually'] += $singleItem['total_quantity'];
								            }
								        } elseif ($singleItem['vendor_name'] == 'FulfillEngine') {
								            if (!empty($singleItem['fe_order_id'])) {
								                $statusQuantities['Sent to vendor (FE)'] += $singleItem['total_quantity'];
								            } else {
								                $statusQuantities['Need to order manually'] += $singleItem['total_quantity'];
								            }
								        } else {
								            $statusQuantities['Need to order manually'] += $singleItem['total_quantity'];
								        }
								    }
								}

								$orderItemStatus = '';
								foreach ($statusQuantities as $status => $quantity) {
								    if ($quantity > 0) {
								        $badgeClass = '';
								        if ($status == 'Need to order manually') {
								            $badgeClass = 'background-color: #dc3545; color:#fff;';
								        } elseif ($status == 'Manually ordered') {
								            $badgeClass = 'background-color: #007bff; color:#fff;';
								        } else {
								            $badgeClass = 'badge-success';
								        }
								        $orderItemStatus .= '<span class="badge ' . (($status == 'Need to order manually' || $status == 'Manually ordered') ? '' : 'badge-success') . '" style="' . $badgeClass . '">' . $status . ' - ' . $quantity . ' qty</span><br>';
								    }
								}

								$create_date='';
								if(!empty($single["created_on"])){
									$create_date=date('m/d/Y h:i A', strtotime($single["created_on"]));
								}
								$html .= '<tr>';
								
								$html .= '<td>'.$sr.'</td>';
								$html .= '<td>'.$single['store_name'].'</td>';
								
								$html .= '<td><a href="sa-order-view.php?stkn='.parent::getVal("stkn").'&oid='.$single['id'].'">'.$single['shop_order_number'].'</a></td>';
								
								$html .= '<td>$'.number_format((double)$single['total_price'],2).'</td>';
								$html .= '<td>'.$single['cust_name'].'</td>';
								$html .= '<td>'.$single['cust_email'].'</td>';
								$html .= '<td>'.$create_date.'</td>';
								$html .= '<td>'.$orderItemStatus.'</td>';
								
								$feOrderUrl ='';
								if (!empty($single['fe_order_id'])) {
									if(isset($_SERVER['HTTP_HOST']) && ($_SERVER['HTTP_HOST']=="localhost") || ($_SERVER['HTTP_HOST']=="spirithero-rds.bitcotapps.com")){
										$feOrderUrl = '<span>FulfillEngine - <a target="_blank" href="https://app.fulfillengine.com/user-view/accounts/act-8330840/orders/'.$single['fe_order_id'].'">'.$single['fe_order_id'].'</a></span><br>';
									}else{
										$feOrderUrl = '<span>FulfillEngine - <a target="_blank" href="https://app.fulfillengine.com/user-view/accounts/act-9113822/orders/'.$single['fe_order_id'].'">'.$single['fe_order_id'].'</a></span><br>';
									}
									
								}
								$html .= '<td>'.$feOrderUrl.'</td>';
								$html .= '<td>';
									$currentDate = new DateTime();
									$currentDateFormatted = $currentDate->format('m/d/Y h:i A');
									$shipdate = new DateTime($single['ship_date']);
									$shipDateFormatted = $shipdate->format('m/d/Y h:i A');
									// Calculate the difference in days
									$interval = $currentDate->diff($shipdate);
									$daysDiff = $interval->days;

									$createddate = new DateTime($single["created_on"]);
									$createdateinterval = $currentDate->diff($createddate);
									$createdate_daysDiff = $createdateinterval->days;
			
									if ($single['tracking_status'] == 'in_transit') {
										if ($daysDiff > 10) {
											$html .= '<a target="_blank" href="'.$single['fe_tracking_url'].'" class="btn btn-xs in_transit_btn_'.$single['id'].'" style="color:#fff;background-color:#ff0000;display:block;">
													<i class="site-menu-icon icon fa-truck" aria-hidden="true" style="color: #fff;"></i> '.$single['tracking_status'].' (Delayed)
												</a><br>
												<p class="card-text">
													<input type="text" value="" order_id="'.$single['id'].'" class="tracking_input_'.$single['id'].'" id="tracking_input" name="tracking_input" placeholder="Enter tracking number" autocomplete="off" style="display: none;">
													<button class="btn btn-primary btn-round btn-sm edit_tracking_save_'.$single['id'].' waves-effect waves-classic" data-toggle="tooltip" data-placement="top" data-trigger="hover" order_id="'.$single['id'].'" id="edit_tracking_save_'.$single['id'].'" fe_order_id="'.$single['fe_order_id'].'" data-original-title="Save" title="" style="float: right; display:none;"><i class="fa fa-check"></i></button>
													<button class="btn btn-primary btn-round btn-sm edit_trackingnumber_'.$single['id'].' waves-effect waves-classic" data-toggle="tooltip" data-placement="top" data-trigger="hover" order_id="'.$single['id'].'" id="edit_trackingnumber_'.$single['id'].'" fe_order_id="'.$single['fe_order_id'].'" data-original-title="Edit" title="" style="float: right;display:block;"><i class="fa fa-edit"></i></button><br>
												</p>
											';
										} else {
											$html .= '<a target="_blank" href="'.$single['fe_tracking_url'].'" class="btn btn-xs in_transit_btn_'.$single['id'].'" style="color:#fff;background-color:#ffc107; display:block;">
														<i class="site-menu-icon icon fa-truck" aria-hidden="true" style="color: #fff;"></i> '.$single['tracking_status'].'
												</a><br>
												<p class="card-text">
													<input type="text" value="" order_id="'.$single['id'].'" class="tracking_input_'.$single['id'].'" id="tracking_input" name="tracking_input" placeholder="Enter tracking number" autocomplete="off" style="display: none;">
													<button class="btn btn-primary btn-round btn-sm edit_tracking_save_'.$single['id'].' waves-effect waves-classic" data-toggle="tooltip" data-placement="top" data-trigger="hover" order_id="'.$single['id'].'" fe_order_id="'.$single['fe_order_id'].'"
														id="edit_tracking_save_'.$single['id'].'" data-original-title="Save" title="" style="float: right; display:none;"><i class="fa fa-check"></i></button>
													<button class="btn btn-primary btn-round btn-sm edit_trackingnumber_'.$single['id'].' waves-effect waves-classic" data-toggle="tooltip" data-placement="top" data-trigger="hover" order_id="'.$single['id'].'" id="edit_trackingnumber_'.$single['id'].'" fe_order_id="'.$single['fe_order_id'].'" data-original-title="Edit" title="" style="float: right;display:block;"><i class="fa fa-edit"></i></button><br>
												</p>
											';
										}
										
									} elseif ($single['tracking_status'] == 'delivered') {
										$html .= '<a target="_blank" href="'.$single['fe_tracking_url'].'" class="btn btn-xs" style="color:#fff;background-color:#28a745"><i class="site-menu-icon icon fa-check" aria-hidden="true" style="color: #fff;"></i> '.$single['tracking_status'].'</a><br>';
									} else {
										if (!empty($single['fe_order_id'])) {
											if ($createdate_daysDiff > 7) {
												if(empty($single['manual_tracking_number'])){
													$html .= '<a class="btn btn-xs in_transit_btn_'.$single['id'].'" style="color:#fff;background-color:#330166;display:block;">
															<i class="site-menu-icon icon fa fa-file-text-o" aria-hidden="true" style="color: #fff;"></i>  In Production (Delayed)
														</a><br>
														<p class="card-text">
															<input type="text" value="" order_id="'.$single['id'].'" class="tracking_input_'.$single['id'].'" id="tracking_input" name="tracking_input" placeholder="Enter tracking number" autocomplete="off" style="display: none;">
															<button class="btn btn-primary btn-round btn-sm edit_tracking_save_'.$single['id'].' waves-effect waves-classic" data-toggle="tooltip" data-placement="top" data-trigger="hover" order_id="'.$single['id'].'" id="edit_tracking_save_'.$single['id'].'" fe_order_id="'.$single['fe_order_id'].'" data-original-title="Save" title="" style="float: right; display:none;"><i class="fa fa-check"></i></button>
															<button class="btn btn-primary btn-round btn-sm edit_trackingnumber_'.$single['id'].' waves-effect waves-classic" data-toggle="tooltip" data-placement="top" data-trigger="hover" order_id="'.$single['id'].'" id="edit_trackingnumber_'.$single['id'].'" fe_order_id="'.$single['fe_order_id'].'" data-original-title="Edit" title="" style="float: right;display:block;"><i class="fa fa-edit"></i></button><br>
														</p>
													';
												}else{
													$html .= '<a class="btn btn-xs" style="color:#fff;background-color:#330166" target="_blank" href="'.$single['manual_tracking_number'].'"><i class="site-menu-icon icon fa fa-file-text-o" aria-hidden="true" style="color: #fff;"></i> In Production ('.$single['manual_tracking_number'].')</a><br>';
												}

												// $html .= '<a class="btn btn-xs" style="color:#fff;background-color:#330166"><i class="site-menu-icon icon fa fa-file-text-o" aria-hidden="true" style="color: #fff;"></i> In Production (Delayed)</a><br>';
											} else {
												if(empty($single['manual_tracking_number'])){
													$html .= '<a class="btn btn-xs in_transit_btn_'.$single['id'].'" style="color:#fff;background-color:#330166; display:block;">
																<i class="site-menu-icon icon fa fa-file-text-o" aria-hidden="true" style="color: #fff;"></i> In Production
														</a><br>
														<p class="card-text">
															<input type="text" value="" order_id="'.$single['id'].'" class="tracking_input_'.$single['id'].'" id="tracking_input" name="tracking_input" placeholder="Enter tracking number" autocomplete="off" style="display: none;">
															<button class="btn btn-primary btn-round btn-sm edit_tracking_save_'.$single['id'].' waves-effect waves-classic" data-toggle="tooltip" data-placement="top" data-trigger="hover" order_id="'.$single['id'].'" fe_order_id="'.$single['fe_order_id'].'"
																id="edit_tracking_save_'.$single['id'].'" data-original-title="Save" title="" style="float: right; display:none;"><i class="fa fa-check"></i></button>
															<button class="btn btn-primary btn-round btn-sm edit_trackingnumber_'.$single['id'].' waves-effect waves-classic" data-toggle="tooltip" data-placement="top" data-trigger="hover" order_id="'.$single['id'].'" id="edit_trackingnumber_'.$single['id'].'" fe_order_id="'.$single['fe_order_id'].'" data-original-title="Edit" title="" style="float: right;display:block;"><i class="fa fa-edit"></i></button><br>
														</p>
													';
												}else{
													$html .= '<a class="btn btn-xs" style="color:#fff;background-color:#330166" target="_blank" href="'.$single['manual_tracking_number'].'"><i class="site-menu-icon icon fa fa-file-text-o" aria-hidden="true" style="color: #fff;"></i> In Production ('.$single['manual_tracking_number'].')</a><br>';
												}

												// $html .= '<a class="btn btn-xs" style="color:#fff;background-color:#330166"><i class="site-menu-icon icon fa fa-file-text-o" aria-hidden="true" style="color: #fff;"></i> In Production</a><br>';
											}
											
										}else{
											$html .= $single['tracking_status'];
										}
									}
								$html .= '</td>';
								if (!empty($single['customcat_tracker_number']) ) {
									$html .= '<td><a target="_blank" href="'.$single['customcat_tracking_url'].'" class="btn btn-xs" style="color:#fff;background-color:#28a745"><i class="site-menu-icon icon fa-check" aria-hidden="true" style="color: #fff;"></i> '.$single['customcat_tracker_number'].'</a><br></td>';
								}else{
									$html .= '<td></td>';
								}
								//$html .= '<td><a target="_blank" href="'.$single['fe_tracking_url'].'">'.$single['tracking_status'].'</a><br></td>';
								if($single["is_order_cancel"] == '0')
								{
									$html .= '<td style="text-align:center;"><button data-href="sa-orders.php?stkn='.parent::getVal("stkn").'&oid='.$single["id"].'" data-id = "'.$single["id"].'" data-store_master_id = "'.$single["store_master_id"].'" type="button" class="btn btn-danger btn-cancel-order">Cancel</button></td>';
								}
								else if($single["is_order_cancel"] == '1')
								{
									$html .= '<td style="text-align:center;">Cancelled ('.$single["order_cancelled_by"].')</td>';
								}
								
								$html .= '<td style="text-align:center;"><button data-href="sa-order-view.php?stkn='.parent::getVal("stkn").'&oid='.$single["id"].'" type="button" class="btn btn-info btn-edit-order">Edit</button></td>';

								$html .= '</tr>';
								$sr++;
							}
						}else{
							$html .= '<tr>';
							$html .= '<td colspan="7" align="center">No Record Found</td>';
							$html .= '</tr>';
						}

						$html .= '</tbody>';
						$html .= '</table>';
						$html .= '</div>';
						$html .= '</div>';
						$html .= '</div>';

						$res['DATA'] = $html;
						$res['page_count'] = $page;
						$res['record_count']=$record_count;
						$res['sr_start']=$sr_start;
						$res['sr_end']=$sr_end;
						echo json_encode($res,1);
						exit;
					}
				}
			}
		}else if($_REQUEST['order_type']=='1'){
			if(parent::isPOST()){
				$array = array();
				$result = array();
				if(parent::getVal("hdn_method") == "orders_pagination")
				{
					$record_count=0;
					$page=0;
					$current_page=1;
					$rows='10';
					$keyword='';

					if( (isset($_REQUEST['rows']))&&(!empty($_REQUEST['rows'])) ){
						$rows=$_REQUEST['rows'];
					}
					if( (isset($_REQUEST['keyword']))&&(!empty($_REQUEST['keyword'])) ){
						$keyword=$_REQUEST['keyword'];
					}
					if( (isset($_REQUEST['current_page']))&&(!empty($_REQUEST['current_page'])) ){
						$current_page=$_REQUEST['current_page'];
					}
					$start=($current_page-1)*$rows;
					$end=$rows;
					$sort_field = '';
					if(isset($_POST['sort_field']) && !empty($_POST['sort_field'])){
						$sort_field = $_POST['sort_field'];
					}
					$sort_type = '';
					if(isset($_POST['sort_type']) && !empty($_POST['sort_type'])){
						$sort_type = $_POST['sort_type'];
					}
					//end fixed, no change for any module

					$cond_keyword = '';

					if(isset($keyword) && !empty($keyword)){
						$cond_keyword = " AND (
								ac.orderId = '".trim($keyword)."' OR
								store_master.store_name LIKE '%".trim($keyword)."%' OR
								sodm.first_name LIKE '%".trim($keyword)."%' OR
								sodm.last_name LIKE '%".trim($keyword)."%' OR
								sodm.email LIKE '%".trim($keyword)."%'
							)";
					}

					$cond_order = 'ORDER BY ac.created_at DESC';
					if(!empty($sort_field)){
						$cond_order = 'ORDER BY '.$sort_field.' '.$sort_type;
					}

					$selectedStore = "";
					if( (isset($_REQUEST['select_store']))&&(!empty($_REQUEST['select_store'])) ){
						$selectedStore = 'AND store_master.id = "'.$_REQUEST['select_store'].'"';
					}


					$sql = "SELECT COUNT(ac.id) as count 
					FROM store_master 
					INNER JOIN store_owner_details_master AS sodm ON sodm.id = store_master.store_owner_details_master_id 
					INNER JOIN add_to_cart as ac ON store_master.id = ac.store_master_id WHERE ac.status IN (2,3) $selectedStore $cond_keyword GROUP BY ac.orderId $cond_order ";
					
					$all_count = parent::selectTable_f_mdl($sql);


					$sql1 = "SELECT ac.id, ac.orderId, ac.status, store_master.store_name, 0 as qtyTotal, 0 as priceTotal, ac.created_at, sodm.first_name, sodm.last_name, sodm.email 
					FROM store_master 
					INNER JOIN store_owner_details_master AS sodm ON sodm.id = store_master.store_owner_details_master_id 
					INNER JOIN add_to_cart as ac ON store_master.id = ac.store_master_id WHERE ac.status IN (2,3) $cond_keyword $selectedStore GROUP BY ac.orderId $cond_order LIMIT $start,$end ";
					
					$all_list = parent::selectTable_f_mdl($sql1);


					if(!empty($all_list)){
						
						foreach ($all_list as $key => $value) {
							$priceQty = "SELECT ac.qty as qtyTotal, ac.price as priceTotal 
							FROM add_to_cart as ac WHERE ac.orderId = '".$value['orderId']."'";

							$priceQty_list = parent::selectTable_f_mdl($priceQty);
							// print_r($priceQty_list);
							// die();
							$price = 0;
							$qty = 0;
							foreach ($priceQty_list as $key => $value1) {
								
								$price = $price + ($value1['priceTotal'] * $value1['qtyTotal']);
								$qty =  $qty +  $value1['qtyTotal'];	
							}
							

							$value['qtyTotal'] = $qty;
							$value['priceTotal'] = $price;
							$result[] = $value;
						}
					}

					
					if( (isset($all_count[0]['count']))&&(!empty($all_count[0]['count'])) ){
						$record_count=count($all_count);
						$page=$record_count/$rows;
						$page=ceil($page);
					}
					$sr_start=0;
					if($record_count>=1){
						$sr_start=(($current_page-1)*$rows)+1;
					}
					$sr_end=($current_page)*$rows;
					if($record_count<=$sr_end){
						$sr_end=$record_count;
					}

					if(isset($_POST['pagination_export']) && $_POST['pagination_export']=='Y'){
						
					}else{
						$html1 = '';
						$html1 .= '<div class="row">';
						$html1 .= '<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">';
						$html1 .= '<div class="table-responsive">';
						$html1 .= '<table class="table table-bordered table-hover">';

						$html1 .= '<thead>';
						$html1 .= '<tr>';
						$html1 .= '<th><input type="checkbox" id="ckbCheckAll"></th>';
						//$html1 .= '<th>#</th>';
						$html1 .= '<th>Store Name</th>';
						//$html1 .= '<th>Shop Order Id</th>';// class="sort_th" data-sort_field="shop_order_id"
						$html1 .= '<th>Order Number</th>';
						$html1 .= '<th>Price</th>';
						$html1 .= '<th>QTY</th>';
						$html1 .= '<th>Owner Name</th>';
						$html1 .= '<th>Owner Email</th>';
						$html1 .= '<th>Order Date</th>';
						$html1 .= '<th colspan="2" style="text-align: center;">Action</th>';
						$html1 .= '</tr>';
						$html1 .= '</thead>';

						$html1 .= '<tbody>';
						if(!empty($result)){
							$sr = $sr_start;
							foreach($result as $single){
								$created_at='';
								if(!empty($single["created_at"])){
									$created_at=date('m/d/Y h:i A', strtotime($single["created_at"]));
								}

								$html1 .= '<tr>';
								$html1 .= '<td><input type="checkbox" value='.$single["orderId"].' class="checkBoxClass"></td>';
								$html1 .= '<td>'.$single['store_name'].'</td>';
								
								$html1 .= '<td><a href="manualOrderDetails.php?stkn='.parent::getVal("stkn").'&oid='.$single['orderId'].'">MO-'.$single['orderId'].'</a></td>';
								
								$html1 .= '<td>$'.number_format((double)$single['priceTotal'],2).'</td>';
								$html1 .= '<td>'.$single['qtyTotal'].'</td>';
								$html1 .= '<td>'.$single['first_name'].' '.$single['last_name'].'</td>';
								$html1 .= '<td>'.$single['email'].'</td>';
								$html1 .= '<td>'.$created_at.'</td>';
								
								if($single['status'] == 2){
									$html1 .= '<td class="td-action" style="text-align:center;"><button type="button" class="btn btn-primary btn-approve-order action-button" onclick="approveData('.$single['orderId'].')">Under Review</button></td>';								
									$html1 .= '<td class="td-action" style="text-align:center;"><button type="button" class="btn btn-danger btn-delete-order action-button" onclick="deleteManualOrder('.$single['orderId'].')">Delete</button></td>';
								}else{
									$html1 .= '<td style="text-align:center;"><span  class=" text-success ">Approved</span></td>';
									$html1 .= '<td style="text-align:center;"><button type="button" id="delete_manual_order_approve" data-id="'.$single['orderId'].'" class="btn btn-danger btn-delete-order action-button">Delete</button></td>';
								}
								
								$html1 .= '</tr>';
								$sr++;
							}

						}else{
							$html1 .= '<tr>';
							$html1 .= '<td colspan="8" align="center">No Record Found</td>';
							$html1 .= '</tr>';
						}
						

						$html1 .= '</tbody>';
						$html1 .= '</table>';
						$html1 .= '</div>';
						$html1 .= '</div>';
						$html1 .= '</div>';

						$array['DATA'] = $html1;
						$array['page_count'] = $page;
						$array['record_count']=$record_count;
						$array['sr_start']=$sr_start;
						$array['sr_end']=$sr_end;

						echo json_encode($array,1);
						exit;
					}


				// die($_REQUEST['order_type']);
				}
			}
		}else if($_REQUEST['order_type']=='2'){
			if(parent::isPOST()){
				$array = array();
				$result = array();
				if(parent::getVal("hdn_method") == "orders_pagination")
				{
					$record_count=0;
					$page=0;
					$current_page=1;
					$rows='10';
					$keyword='';

					if( (isset($_REQUEST['rows']))&&(!empty($_REQUEST['rows'])) ){
						$rows=$_REQUEST['rows'];
					}
					if( (isset($_REQUEST['keyword']))&&(!empty($_REQUEST['keyword'])) ){
						$keyword=$_REQUEST['keyword'];
					}
					if( (isset($_REQUEST['current_page']))&&(!empty($_REQUEST['current_page'])) ){
						$current_page=$_REQUEST['current_page'];
					}
					$start=($current_page-1)*$rows;
					$end=$rows;
					$sort_field = '';
					if(isset($_POST['sort_field']) && !empty($_POST['sort_field'])){
						$sort_field = $_POST['sort_field'];
					}
					$sort_type = '';
					if(isset($_POST['sort_type']) && !empty($_POST['sort_type'])){
						$sort_type = $_POST['sort_type'];
					}
					//end fixed, no change for any module

					$cond_keyword = '';

					if(isset($keyword) && !empty($keyword)){
						$cond_keyword = " AND (
								ac.orderId = '$keyword' OR
								store_master.store_name LIKE '%$keyword%' OR
								sodm.first_name LIKE '%$keyword%' OR
								sodm.last_name LIKE '%$keyword%' OR
								sodm.email LIKE '%$keyword%'
							)";
					}

					$cond_order = 'ORDER BY ac.created_at DESC';
					if(!empty($sort_field)){
						$cond_order = 'ORDER BY '.$sort_field.' '.$sort_type;
					}

					$selectedStore = "";
					if( (isset($_REQUEST['select_store']))&&(!empty($_REQUEST['select_store'])) ){
						$selectedStore = 'AND store_master.id = "'.$_REQUEST['select_store'].'"';
					}


					$sql = "SELECT COUNT(ac.id) as count 
					FROM store_master 
					INNER JOIN store_owner_details_master AS sodm ON sodm.id = store_master.store_owner_details_master_id 
					INNER JOIN add_to_cart_quickbuy as ac ON store_master.id = ac.store_master_id WHERE ac.status IN (2,3) $selectedStore $cond_keyword GROUP BY ac.orderId $cond_order ";
					
					$all_count = parent::selectTable_f_mdl($sql);


					$sql1 = "SELECT ac.id, ac.orderId, ac.status, store_master.store_name, 0 as qtyTotal, 0 as priceTotal, ac.created_at, sodm.first_name, sodm.last_name, sodm.email 
					FROM store_master 
					INNER JOIN store_owner_details_master AS sodm ON sodm.id = store_master.store_owner_details_master_id 
					INNER JOIN add_to_cart_quickbuy as ac ON store_master.id = ac.store_master_id WHERE ac.status IN (2,3) $cond_keyword $selectedStore GROUP BY ac.orderId $cond_order LIMIT $start,$end ";
					
					$all_list = parent::selectTable_f_mdl($sql1);

					if(!empty($all_list)){
						
						foreach ($all_list as $key => $value) {
							$priceQty = "SELECT ac.qty as qtyTotal, ac.price as priceTotal 
							FROM add_to_cart_quickbuy as ac WHERE ac.orderId = '".$value['orderId']."'";

							$priceQty_list = parent::selectTable_f_mdl($priceQty);
							// print_r($priceQty_list);
							// die();
							$price = 0;
							$qty = 0;
							foreach ($priceQty_list as $key => $value1) {
								
								$price = $price + ($value1['priceTotal'] * $value1['qtyTotal']);
								$qty =  $qty +  $value1['qtyTotal'];	
							}
							

							$value['qtyTotal'] = $qty;
							$value['priceTotal'] = $price;
							$result[] = $value;
						}
					}

					
					if( (isset($all_count[0]['count']))&&(!empty($all_count[0]['count'])) ){
						$record_count=count($all_count);
						$page=$record_count/$rows;
						$page=ceil($page);
					}
					$sr_start=0;
					if($record_count>=1){
						$sr_start=(($current_page-1)*$rows)+1;
					}
					$sr_end=($current_page)*$rows;
					if($record_count<=$sr_end){
						$sr_end=$record_count;
					}

					if(isset($_POST['pagination_export']) && $_POST['pagination_export']=='Y'){
						
					}else{
						$html1 = '';
						$html1 .= '<div class="row">';
						$html1 .= '<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">';
						$html1 .= '<div class="table-responsive">';
						$html1 .= '<table class="table table-bordered table-hover">';

						$html1 .= '<thead>';
						$html1 .= '<tr>';
						$html1 .= '<th><input type="checkbox" id="ckbCheckAll"></th>';
						//$html1 .= '<th>#</th>';
						$html1 .= '<th>Store Name</th>';
						//$html1 .= '<th>Shop Order Id</th>';// class="sort_th" data-sort_field="shop_order_id"
						$html1 .= '<th>Order Number</th>';
						$html1 .= '<th>Price</th>';
						$html1 .= '<th>QTY</th>';
						$html1 .= '<th>Owner Name</th>';
						$html1 .= '<th>Owner Email</th>';
						$html1 .= '<th>Order Date</th>';
						$html1 .= '<th colspan="2" style="text-align: center;">Action</th>';
						$html1 .= '</tr>';
						$html1 .= '</thead>';

						$html1 .= '<tbody>';
						if(!empty($result)){
							$sr = $sr_start;
							foreach($result as $single){
								$created_at='';
								if(!empty($single["created_at"])){
									$created_at=date('m/d/Y h:i A', strtotime($single["created_at"]));
								}
								$html1 .= '<tr>';
								$html1 .= '<td><input type="checkbox" value='.$single["orderId"].' class="checkBoxClass"></td>';
								$html1 .= '<td>'.$single['store_name'].'</td>';
								
								$html1 .= '<td><a href="QuickBuyOrderDetails.php?stkn='.parent::getVal("stkn").'&oid='.$single['orderId'].'">QB-'.$single['orderId'].'</a></td>';
								
								$html1 .= '<td>$'.number_format((double)$single['priceTotal'],2).'</td>';
								$html1 .= '<td>'.$single['qtyTotal'].'</td>';
								$html1 .= '<td>'.$single['first_name'].' '.$single['last_name'].'</td>';
								$html1 .= '<td>'.$single['email'].'</td>';
								$html1 .= '<td>'.$created_at.'</td>';
								
								if($single['status'] == 2){
									$html1 .= '<td class="td-action" style="text-align:center;" ><button type="button" class="btn btn-primary btn-approve-order_quickbuy action-button" onclick="approveDataQuickbuy('.$single['orderId'].')" style="color: white;">Under Review</button></td>';
									$html1 .= '<td class="td-action" style="text-align:center;" ><button type="button" class="btn btn-danger btn-delete-order_quickbuy action-button" onclick="deleteQuickbuyOrder('.$single['orderId'].')">Delete</button></td>';
								}else{
									$html1 .= '<td style="text-align:center;"><span  class=" text-success ">Approved</span></td>';
									$html1 .= '<td style="text-align:center;"><button type="button" id="delete_quickbuy_order_approve" data-id="'.$single['orderId'].'" class="btn btn-danger btn-delete-order action-button" >Delete</button></td>';
								}
								
								$html1 .= '</tr>';
								$sr++;
							}

						}else{
							$html1 .= '<tr>';
							$html1 .= '<td colspan="8" align="center">No Record Found</td>';
							$html1 .= '</tr>';
						}
						

						$html1 .= '</tbody>';
						$html1 .= '</table>';
						$html1 .= '</div>';
						$html1 .= '</div>';
						$html1 .= '</div>';

						$array['DATA'] = $html1;
						$array['page_count'] = $page;
						$array['record_count']=$record_count;
						$array['sr_start']=$sr_start;
						$array['sr_end']=$sr_end;

						echo json_encode($array,1);
						exit;
					}


				// die($_REQUEST['order_type']);
				}
			}
			
		}else if($_REQUEST['order_type']=='3'){
			if(parent::isPOST()){
				$array = array();
				$result = array();
				if(parent::getVal("hdn_method") == "orders_pagination")
				{
					$record_count=0;
					$page=0;
					$current_page=1;
					$rows='10';
					$keyword='';

					if( (isset($_REQUEST['rows']))&&(!empty($_REQUEST['rows'])) ){
						$rows=$_REQUEST['rows'];
					}
					if( (isset($_REQUEST['keyword']))&&(!empty($_REQUEST['keyword'])) ){
						$keyword=$_REQUEST['keyword'];
					}
					if( (isset($_REQUEST['current_page']))&&(!empty($_REQUEST['current_page'])) ){
						$current_page=$_REQUEST['current_page'];
					}
					$start=($current_page-1)*$rows;
					$end=$rows;
					$sort_field = '';
					if(isset($_POST['sort_field']) && !empty($_POST['sort_field'])){
						$sort_field = $_POST['sort_field'];
					}
					$sort_type = '';
					if(isset($_POST['sort_type']) && !empty($_POST['sort_type'])){
						$sort_type = $_POST['sort_type'];
					}
					//end fixed, no change for any module

					$cond_keyword = '';
					if(isset($keyword) && !empty($keyword)){
						$cond_keyword = " AND (
							spoh.place_order_number_sa = '$keyword' OR
							spoh.po_number LIKE '%$keyword%' OR
							spoh.ship_email LIKE '%$keyword%' OR
							spodh.order_numbers LIKE '%$keyword%' OR
							spodh.store_names LIKE '%$keyword%' OR
							spoh.ship_to LIKE '%$keyword%'
						)";
					}

					$cond_order = 'ORDER BY spoh.create_on DESC';
					if(!empty($sort_field)){
						$cond_order = 'ORDER BY '.$sort_field.' '.$sort_type;
					}

					$sql = "SELECT  count(DISTINCT spoh.place_order_number_sa) as count FROM sanmar_place_order_history as spoh INNER JOIN
					sanmar_place_order_details_history AS spodh ON spoh.place_order_number_sa = spodh.place_order_number_sa WHERE  1 $cond_keyword  GROUP BY spoh.place_order_number_sa $cond_order  ";
					$all_count = parent::selectTable_f_mdl($sql);


					$sql1 = "SELECT * FROM sanmar_place_order_history as spoh INNER JOIN
					sanmar_place_order_details_history AS spodh ON spoh.place_order_number_sa = spodh.place_order_number_sa WHERE  1 $cond_keyword  GROUP BY spoh.place_order_number_sa $cond_order LIMIT $start,$end ";
					$all_list = parent::selectTable_f_mdl($sql1);

					if( (isset($all_count[0]['count']))&&(!empty($all_count[0]['count'])) ){
						$record_count=count($all_count);
						$page=$record_count/$rows;
						$page=ceil($page);
					}
					$sr_start=0;
					if($record_count>=1){
						$sr_start=(($current_page-1)*$rows)+1;
					}
					$sr_end=($current_page)*$rows;
					if($record_count<=$sr_end){
						$sr_end=$record_count;
					}

					if(isset($_POST['pagination_export']) && $_POST['pagination_export']=='Y'){
						
					}else{
						$html1 = '';
						$html1 .= '<div class="row">';
						$html1 .= '<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">';
						$html1 .= '<div class="table-responsive">';
						$html1 .= '<table class="table table-bordered table-hover">';

						$html1 .= '<thead>';
						$html1 .= '<tr>';
						//$html1 .= '<th><input type="checkbox" id="ckbCheckAll"></th>';
						$html1 .= '<th>#</th>';
						$html1 .= '<th>Ship To</th>';
						$html1 .= '<th>Order Number</th>';
						$html1 .= '<th>Attention</th>';
						$html1 .= '<th>PO Number</th>';
						$html1 .= '<th>Ship Email</th>';
						$html1 .= '<th>Order Date</th>';
						$html1 .= '<th>Order Placed By</th>';
						$html1 .= '</tr>';
						$html1 .= '</thead>';

						$html1 .= '<tbody>';
						if(!empty($all_list)){
							$sr = $sr_start;
							foreach($all_list as $single){
								$create_on='';
								if(!empty($single["create_on"])){
									$create_on=date('m/d/Y h:i A', strtotime($single["create_on"]));
								}
								$html1 .= '<tr>';
								//$html1 .= '<td><input type="checkbox" value='.$single["place_order_number_sa"].' class="checkBoxClass"></td>';
								$html1 .= '<td>'.$sr.'</td>';
								$html1 .= '<td>'.$single['ship_to'].'</td>';
								$html1 .= '<td><a href="sa-place-order-sanmar-details.php?stkn='.parent::getVal("stkn").'&oid='.$single['place_order_number_sa'].'">'.$single['place_order_number_sa'].'</a></td>';
								$html1 .= '<td>'.$single['attention'].'</td>';
								$html1 .= '<td>'.$single['po_number'].'</td>';
								$html1 .= '<td>'.$single['ship_email'].'</td>';
								$html1 .= '<td>'.$create_on.'</td>';
								$html1 .= '<td>'.$single['order_placed_by'].'</td>';
								$html1 .= '</tr>';
								$sr++;
							}

						}else{
							$html1 .= '<tr>';
							$html1 .= '<td colspan="8" align="center">No Record Found</td>';
							$html1 .= '</tr>';
						}
						

						$html1 .= '</tbody>';
						$html1 .= '</table>';
						$html1 .= '</div>';
						$html1 .= '</div>';
						$html1 .= '</div>';

						$array['DATA'] = $html1;
						$array['page_count'] = $page;
						$array['record_count']=$record_count;
						$array['sr_start']=$sr_start;
						$array['sr_end']=$sr_end;

						echo json_encode($array,1);
						exit;
					}


				// die($_REQUEST['order_type']);
				}
			}
			
		}
	}

	/* Task 118 start*/
	public function approveOrder(){
		$post = $_POST;
		
		$status = false;
		if(!empty($post['orderId'])){
			$status = parent::approveOrder_f_mdl('add_to_cart', $post['orderId'], 3);
		}

		if($status){
			$sql = 'SELECT ac.id, ac.store_master_id,ac.created_at FROM add_to_cart as ac INNER JOIN store_owner_product_master as sopm ON ac.store_owner_product_master_id = sopm.id WHERE ac.status = 3 AND ac.orderId = "'.$post['orderId'].'"';
			$data = parent::selectTable_f_mdl($sql);

			$result = array();

			if(!empty($data[0])){
				$grandTotalPrice = 0;
						
				$storeTypeData = 'SELECT store_master.id,sale_type,store_master.store_name FROM store_sale_type_master INNER JOIN store_master ON store_master.store_sale_type_master_id = store_sale_type_master.id WHERE store_master.id = "'.$data[0]['store_master_id'].'" ';
				$storeTypeData = parent::selectTable_f_mdl($storeTypeData);	

				$productSql = 'SELECT ac.id, ac.store_master_id, ac.store_owner_product_master_id, ac.color, ac.qty as qtyTotal, ac.store_owner_product_variant_master_id, IF(sopm.group_name = "", "Others", sopm.group_name) as group_name, sopm.product_title, sopvm.size, sopvm.sku, sopvm.image, sopvm.id as vid, sopvm.fundraising_price, ac.price as priceTotal, pc.product_color_name  FROM add_to_cart as ac INNER JOIN store_owner_product_master as sopm ON ac.store_owner_product_master_id = sopm.id INNER JOIN store_owner_product_variant_master as sopvm ON ac.store_owner_product_variant_master_id = sopvm.id INNER JOIN store_product_colors_master as pc on pc.product_color = ac.color WHERE ac.status = 3 AND sopm.status = 1 AND ac.store_master_id = "'.$data[0]['store_master_id'].'" AND ac.orderId = "'.$post['orderId'].'" ';
				$productData = parent::selectTable_f_mdl($productSql);

				$storeName = !empty($storeTypeData)?$storeTypeData[0]['store_name'] : '';

				if(!empty($productData)){
						$price = 0;
						$qty = 0;
						$fundraisingTotal = 0;
						foreach ($productData as $key => $value1) {
							
							$price = $price + ($value1['priceTotal'] * $value1['qtyTotal']);
							$qty =  $qty +  $value1['qtyTotal'];
							$fundraisingTotal = $fundraisingTotal + (double)$value1['fundraising_price'];	
						}
						

						$data[0]['qtyTotal'] = $qty;
						$data[0]['priceTotal'] = $price;
				}



				$value['productData'] = $productData;

				// $result[] = $value;
				

				$array = array(
                    'store_master_id' => $data[0]['store_master_id'],
                    'store_sale_type' => !empty($storeTypeData)?$storeTypeData[0]['sale_type']:'',
                    'manual_order_number' => $post['orderId'],
                    'order_type'	=> 2,
                    'total_price' => $data[0]['priceTotal'],
                    'total_fundraising_amount' => "0.00",
                   // 'created_on' => @date('Y-m-d H:i:s'),
                    'created_on' => $data[0]['created_at'],
                    'created_on_ts' => time(),
                );

                $som_arr = parent::insertTable_f_mdl('store_orders_master',$array);

                if(isset($som_arr['insert_id']) && !empty($som_arr['insert_id'])){

                	$total_fundraising_amount = 0;

                    //now insert item data
                    foreach($productData as $single_item){
                    	
                    		$soim_insert_data = [
                                    'store_master_id' => $single_item['store_master_id'],
                                    'store_owner_product_master_id' => $single_item['store_owner_product_master_id'],
                                    'store_owner_product_variant_master_id' => $single_item['store_owner_product_variant_master_id'],
                                    'store_orders_master_id' => $som_arr['insert_id'],
                                    'title' => $storeName."-".$single_item['product_title'],
                                    'quantity' => $single_item['qtyTotal'],
                                    'price' => $single_item['priceTotal'],
                                    'sku' => $single_item['sku'],
                                    'vendor' => 'SpiritHero.com',
                                    'variant_title' => $single_item['size'].' / '.$single_item['product_color_name'],
                                    'shop_variant_id' => rand($som_arr['insert_id'].$single_item['store_master_id'].time(),10),
                                    'created_on' => @date('Y-m-d H:i:s'),
                                    'created_on_ts' => time(),
                            ];
                            parent::insertTable_f_mdl('store_order_items_master',$soim_insert_data);
                    }
                }

                //update total_fundraising_amount in main order
                $som_update_data = [
                    'total_fundraising_amount' => $total_fundraising_amount
                ];
                parent::updateTable_f_mdl('store_orders_master',$som_update_data,'id="'.$som_arr['insert_id'].'"');

                /*==============Profit margin Auto update Manual Order===============*/
                $store_master_id=$data[0]['store_master_id'];
              	$total_sale         = 0.00;
              	$fundraising_amount = 0.00;
              	if (isset($store_master_id) && !empty($store_master_id)) {
                  	$saleSql = 'SELECT soim.id,soim.store_master_id,sm.store_name AS title,soim.is_deleted,SUM(soim.quantity * soim.price) AS total_sale,som.store_sale_type FROM store_order_items_master AS soim INNER JOIN store_orders_master AS som ON soim.store_orders_master_id = som.id INNER JOIN store_master AS sm ON soim.store_master_id = sm.id WHERE 1 AND soim.is_deleted = 0 AND som.is_order_cancel = "0"  AND som.order_type="1" AND som.store_master_id = '.$store_master_id.' ';
                  	$saleData = parent::selectTable_f_mdl($saleSql);

                   	$saleSql2 = 'SELECT soim.id,soim.store_master_id,sm.store_name AS title,soim.is_deleted,SUM(soim.quantity * soim.price) AS total_sale,som.store_sale_type FROM store_order_items_master AS soim INNER JOIN store_orders_master AS som ON soim.store_orders_master_id = som.id INNER JOIN store_master AS sm ON soim.store_master_id = sm.id WHERE 1 AND soim.is_deleted = 0 AND som.is_order_cancel = "0" AND som.order_type="2" AND som.store_master_id = '.$store_master_id.' ';
                  	$saleData2 = parent::selectTable_f_mdl($saleSql2);
                  	$total_sale_manual='';
                  	if(!empty($saleData2)){
                    	$total_sale_manual = $saleData2[0]['total_sale'];
                  	}

                  	$saleSql3 = 'SELECT soim.id,soim.store_master_id,sm.store_name AS title,soim.is_deleted,SUM(soim.quantity * soim.price) AS total_sale,som.store_sale_type FROM store_order_items_master AS soim INNER JOIN store_orders_master AS som ON soim.store_orders_master_id = som.id INNER JOIN store_master AS sm ON soim.store_master_id = sm.id WHERE 1 AND soim.is_deleted = 0 AND som.is_order_cancel = "0" AND som.order_type="3" AND som.store_master_id = '.$store_master_id.' ';
                  	$saleData3 = parent::selectTable_f_mdl($saleSql3);
                  	$total_sale_quickbuy='';
                  	if(!empty($saleData3)){
                    	$total_sale_quickbuy = $saleData3[0]['total_sale'];
                  	}

                  	if(!empty($saleData)){
                      	$total_sale = $saleData[0]['total_sale'];
                      	$fundSql = 'SELECT IFNULL(SUM(total_fundraising_amount),0) as total_fundraising_amount FROM `store_orders_master` WHERE `store_orders_master`.store_master_id = '.$store_master_id.' and `store_orders_master`.is_order_cancel = "0"';
                      	$fundData = parent::selectTable_f_mdl($fundSql);
                      	$fundraising_amount = $fundData[0]['total_fundraising_amount'];
                      	$total_sale         =number_format((float)$total_sale, 2);
                      	$total_sale = str_replace(",","",$total_sale);

                      	$total_sale_manual = number_format((float)$total_sale_manual, 2);
                      	$total_sale_manual = str_replace(",","",$total_sale_manual);

                      	$total_sale_quickbuy = number_format((float)$total_sale_quickbuy, 2);
                      	$total_sale_quickbuy = str_replace(",","",$total_sale_quickbuy);

                      	$total_gross_sale = $total_sale+$total_sale_manual+$total_sale_quickbuy;
                      	$total_gross_sale = number_format((float)$total_gross_sale, 2);
                      	$total_gross_sale = str_replace(",","",$total_gross_sale);

                      	$fundraising_amount =number_format((float)$fundraising_amount, 2);
                      	$fundraising_amount = str_replace(",","",$fundraising_amount);

                  	}
              	}

              	$profitSql  = 'SELECT *, "0" AS profit_value FROM `profit_cost_details` ';
              	$profitDataAll = parent::selectTable_f_mdl($profitSql);

              	$item_sql="
                  SELECT sm.ct_fundraising_price,sm.id,sm.store_name,sm.is_fundraising,
                  (SELECT IFNULL(SUM(oim.quantity),0) FROM `store_orders_master` as om INNER JOIN `store_order_items_master` as oim on om.id = oim.store_orders_master_id WHERE 1 AND om.is_order_cancel = 0 AND oim.is_deleted = 0 and oim.store_master_id = sm.id) as totalItem_sold,
                  (SELECT IFNULL(SUM(oim.quantity),0) FROM `store_orders_master` as om INNER JOIN `store_order_items_master` as oim on om.id = oim.store_orders_master_id WHERE 1 AND om.is_order_cancel = 0 AND oim.is_deleted = 0 and oim.store_master_id = sm.id and om.order_type=1) as actual_orderItem_sold,
                  (SELECT count(id) FROM store_orders_master WHERE store_master_id = sm.id AND is_order_cancel = 0) as total_order, 
                  (SELECT count(id) FROM store_orders_master WHERE store_master_id = sm.id AND is_order_cancel = 0 AND order_type=1) as total_actual_order,
                  (SELECT IFNULL(SUM(total_fundraising_amount),0) FROM store_orders_master WHERE store_master_id = sm.id AND is_order_cancel = 0) as total_fund_amount
                  FROM store_master sm INNER JOIN store_owner_details_master sodm ON sm.store_owner_details_master_id = sodm.id INNER JOIN store_sale_type_master sstm ON sm.store_sale_type_master_id = sstm.id 
                  LEFT JOIN store_organization_type_master as org ON sm.store_organization_type_master_id = org.id WHERE sm.id = ".$store_master_id."
              	";
              	$item_sql_data = parent::selectTable_f_mdl($item_sql);
              	$label_values = 0.00;
              	$printCost=0.00;
              	$checked_lable_cost=0.00;
              	$unchecked_lable_cost=0.00;
              	if(!empty($profitDataAll))
              	{
                  	foreach($profitDataAll as $value)
                  	{
                      	$profitSql  = 'SELECT store_profit.profit_value,profit_cost_details.cost_label,profit_cost_details.id,profit_cost_details.cost_slug,profit_cost_details.is_checked FROM store_profit LEFT JOIN profit_cost_details ON store_profit.profit_label_id = profit_cost_details.id where store_profit.store_master_id = "'.$store_master_id.'" AND profit_cost_details.id =  "'.$value['id'].'" ';
                      	$profitData = parent::selectTable_f_mdl($profitSql);
                      	if(!empty($profitData))
                      	{
                          	$label_values += str_replace(",","",$profitData[0]['profit_value']);
                          	$label_values = str_replace(",","",$label_values);
                          	$cost_slug=$profitData[0]['cost_slug'];
                          	$is_checked=$profitData[0]['is_checked'];
                          	$totalItem_sold=$item_sql_data[0]['totalItem_sold'];
                          	$actual_orderItem_sold=$item_sql_data[0]['actual_orderItem_sold'];
                          	$profit_id=$profitData[0]['id'];
                          	if($is_checked=='1'){
                              	$printCostLabel =str_replace(",","",$profitData[0]['profit_value']) * $totalItem_sold;
                              	$printCostLabel = str_replace(",","",$printCostLabel);
                              	$printCost = number_format((float)($printCostLabel-str_replace(",","",$profitData[0]['profit_value'])), 2);
                              	$checked_lable_cost +=$printCostLabel;
                              	$checked_lable_cost = str_replace(",","",$checked_lable_cost);
                          	}else{ 
                              	$unchecked_lable_cost += str_replace(",","",$profitData[0]['profit_value']);
                              	$unchecked_lable_cost = str_replace(",","",$unchecked_lable_cost);
                              	$total_card_fee=0.00;
                              	if($profit_id=='12'){
                                  	$total_order_amount = str_replace(",","",$total_gross_sale);
                                  	$total_order_get = str_replace(",","",$item_sql_data[0]['total_order']);
                                  	$card_fee= $total_order_amount*2.9/100;
                                  	$card_fee = str_replace(",","",$card_fee);
                                  	$no_of_order_fee=$total_order_get * 0.30;
                                 	$total_card_fee=$card_fee + $no_of_order_fee;
                                  	$total_card_fee=number_format((float)$total_card_fee, 2);
                                  	$total_card_fee = str_replace(",","",$total_card_fee);
                              	}
                          	}
                      	}
                        else{
                          	$label_values += str_replace(",","",$value['lable_profit']);
                          	$label_values = str_replace(",","",$label_values);
                          	$cost_slug=$value['cost_slug'];
                          	$is_checked=$value['is_checked'];
                          	$totalItem_sold=str_replace(",","",$item_sql_data[0]['totalItem_sold']);
                          	$actual_orderItem_sold=str_replace(",","",$item_sql_data[0]['total_order']);
                          	$profit_id=$value['id'];
                          	if($is_checked=='1'){
                              	$printCostLabel =str_replace(",","",$value['lable_profit']) * $totalItem_sold;
                              	$printCostLabel = str_replace(",","",$printCostLabel);
                              	$printCost = number_format((float)($printCostLabel-str_replace(",","",$value['lable_profit'])), 2);
                              	$checked_lable_cost +=$printCostLabel;
                              	$checked_lable_cost = str_replace(",","",$checked_lable_cost);
                          	}else{ 
                              	$unchecked_lable_cost += str_replace(",","",$value['lable_profit']);
                              	$unchecked_lable_cost = str_replace(",","",$unchecked_lable_cost);
                              	$total_card_fee=0.00;
                              	if($profit_id=='12'){
                                  	$total_order_amount = str_replace(",","",$total_gross_sale);
                                  	$total_order_get = str_replace(",","",$item_sql_data[0]['total_order']);
                                  	$card_fee= $total_order_amount*2.9/100;
                                  	$card_fee = str_replace(",","",$card_fee);
                                  	$no_of_order_fee=$total_order_get * 0.30;
                                  	$total_card_fee=$card_fee + $no_of_order_fee;
                                  	$total_card_fee=number_format((float)$total_card_fee, 2);
                                  	$total_card_fee = str_replace(",","",$total_card_fee);
                              	}
                          	}
                      	}
                  	}
              	}

               	$total_lable_price = ($checked_lable_cost+$unchecked_lable_cost + $fundraising_amount +$total_card_fee);
               	$lablrprice   = number_format( (float)str_replace(",","",$total_lable_price), 2, '.', '');
               	$total_profit= (float)$total_gross_sale-str_replace(",","",$lablrprice);
               	$totalProfit  = (float)$total_profit;
               	$total_profit = str_replace(",","",$total_profit);
               	$gross_sale=$total_gross_sale-str_replace(",","",$fundraising_amount);
               	$gross_sale = str_replace(",","",$gross_sale);
               	if($gross_sale=='0'){
               		$profitmargin='0';
               	}else{
               		$profitmargin= ($total_profit/$gross_sale)*100;
               	}
               	
               	$profitmargin  = number_format((float)$profitmargin, 2);
               	$profitmargin = str_replace(",","",$profitmargin);
               	$newProfit = $totalProfit;
               	$totalProData = [
                  'total_profit'  => $newProfit,
                  'profit_margin' => $profitmargin
               	];
              	parent::updateTable_f_mdl('store_master',$totalProData,'id="'.$store_master_id.'"');
                /*==========Profit margin Auto update Manual order========================*/

			}	
		}

		echo $status;die;

		// print_r($array);

		// echo $status;
	}

	Public function orderDetailsPage(){
		global $s3Obj;

		$sqlStoreName = 'SELECT COUNT(ac.id), ac.orderId, ac.created_at, ac.store_master_id, ac.status, store_master.store_name FROM add_to_cart as ac INNER JOIN store_master ON ac.store_master_id = store_master.id WHERE ac.status IN (2,3) AND ac.orderId = "'.$_GET['oid'].'" ';
		$dataStoreName = parent::selectTable_f_mdl($sqlStoreName);


		$sql = 'SELECT ac.id, ac.store_master_id, SUM(ac.qty) as qtyTotal, IF(sopm.group_name = "", "Others", sopm.group_name) as group_name FROM add_to_cart as ac INNER JOIN store_owner_product_master as sopm ON ac.store_owner_product_master_id = sopm.id WHERE ac.status IN (2,3) AND ac.orderId = "'.$_GET['oid'].'" GROUP BY sopm.group_name';
		$data = parent::selectTable_f_mdl($sql);

		$result = array();

		if(!empty($data)){
			foreach ($data as $key => $value) {
					if($value['group_name'] == 'Others'){
						$group_name = '';
					}else{
						$group_name = $value['group_name'];
					}

					$groupSql = 'SELECT minimum_group_value FROM minimum_group_product WHERE product_group = "'.$group_name.'" ';
					$groupDetails = parent::selectTable_f_mdl($groupSql);
					if(!empty($groupDetails)){
						$value['limit'] = (int)$groupDetails[0]['minimum_group_value'];
					}else{
						$value['limit'] = 0;
					}

					$productSql = 'SELECT ac.id, ac.store_master_id, ac.color, ac.qty, ac.store_owner_product_variant_master_id, IF(sopm.group_name = "", "Others", sopm.group_name) as group_name, sopm.product_title, sopvm.size, sopvm.sku ,sopvm.image, sopvm.id as vid, ac.price, pc.product_color_name,slmm.image as mockup_image FROM add_to_cart as ac INNER JOIN store_owner_product_master as sopm ON ac.store_owner_product_master_id = sopm.id INNER JOIN store_owner_product_variant_master as sopvm ON ac.store_owner_product_variant_master_id = sopvm.id INNER JOIN store_product_colors_master as pc on pc.product_color = ac.color  LEFT JOIN `store_logo_mockups_master` as slmm ON slmm.store_owner_product_variant_master_id = ac.store_owner_product_variant_master_id WHERE ac.status IN (2,3) AND sopm.status = 1 AND ac.orderId = "'.$_GET['oid'].'" AND sopm.group_name = "'.$group_name.'" ';
					$productData = parent::selectTable_f_mdl($productSql);

					// if(!empty($value['image'])){
					// 	$value['image'] = $s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH.$value['image']);
					// }

					$value['productData'] = $productData;

				$result[] = $value;
			}


		}

		if(!empty($result)){
			return array('result' => $result, 'storeNameData' =>$dataStoreName);
		}else{
			// header("location:purchase-minimum-products.php?stkn=&store_master_id='".$_GET['store_master_id']."'");
			
		}
	}
	/* Task 118 end*/

	public function approveOrderQuickBuy(){
		$post = $_POST;
		$status = false;
		if(!empty($post['orderId'])){
			$status = parent::approveOrder_f_mdl('add_to_cart_quickbuy', $post['orderId'], 3);
		}
		if($status){
			$sql = 'SELECT ac.id, ac.store_master_id,ac.customer_name,ac.sort_list_name,ac.created_at FROM add_to_cart_quickbuy as ac INNER JOIN store_owner_product_master as sopm ON ac.store_owner_product_master_id = sopm.id WHERE ac.status = 3 AND ac.orderId = "'.$post['orderId'].'"';
			$data = parent::selectTable_f_mdl($sql);

			$result = array();

			if(!empty($data[0])){
				$grandTotalPrice = 0;
						
				$storeTypeData = 'SELECT store_master.id,sale_type,store_master.store_name FROM store_sale_type_master INNER JOIN store_master ON store_master.store_sale_type_master_id = store_sale_type_master.id WHERE store_master.id = "'.$data[0]['store_master_id'].'" ';
				$storeTypeData = parent::selectTable_f_mdl($storeTypeData);	

				$productSql = 'SELECT ac.id, ac.store_master_id, ac.store_owner_product_master_id, ac.color, ac.qty as qtyTotal, ac.store_owner_product_variant_master_id, IF(sopm.group_name = "", "Others", sopm.group_name) as group_name, sopm.product_title, sopvm.size, sopvm.sku, sopvm.image, sopvm.id as vid, sopvm.fundraising_price, ac.price as priceTotal, pc.product_color_name  FROM add_to_cart_quickbuy as ac INNER JOIN store_owner_product_master as sopm ON ac.store_owner_product_master_id = sopm.id INNER JOIN store_owner_product_variant_master as sopvm ON ac.store_owner_product_variant_master_id = sopvm.id INNER JOIN store_product_colors_master as pc on pc.product_color = ac.color WHERE ac.status = 3 AND sopm.status = 1 AND ac.store_master_id = "'.$data[0]['store_master_id'].'" AND ac.orderId = "'.$post['orderId'].'" ';
				$productData = parent::selectTable_f_mdl($productSql);
				

				$storeName = !empty($storeTypeData)?$storeTypeData[0]['store_name'] : '';

				if(!empty($productData)){
						$price = 0;
						$qty = 0;
						$fundraisingTotal = 0;
						foreach ($productData as $key => $value1) {
							
							$price = $price + ($value1['priceTotal'] * $value1['qtyTotal']);
							$qty =  $qty +  $value1['qtyTotal'];
							$fundraisingTotal = $fundraisingTotal + (double)$value1['fundraising_price'];	
						}
						

						$data[0]['qtyTotal'] = $qty;
						$data[0]['priceTotal'] = $price;
				}



				$value['productData'] = $productData;

				// $result[] = $value;
				
				$array = array(
                    'store_master_id' => $data[0]['store_master_id'],
                    'store_sale_type' => !empty($storeTypeData)?$storeTypeData[0]['sale_type']:'',
                    'quickbuy_order_number' => $post['orderId'],
                    'order_type'	=> 3,
                    'total_price' => $data[0]['priceTotal'],
                    'total_fundraising_amount' => "0.00",
                    'student_name' => $data[0]['customer_name'],
                    'sortlist_info' => $data[0]['sort_list_name'],
                    //'created_on' => @date('Y-m-d H:i:s'),
                    'created_on' => $data[0]['created_at'],
                    'created_on_ts' => time(),
                );
                $som_arr = parent::insertTable_f_mdl('store_orders_master',$array);
                if(isset($som_arr['insert_id']) && !empty($som_arr['insert_id'])){
                	$total_fundraising_amount = 0;
                    //now insert item data
                    foreach($productData as $single_item){
                    		$soim_insert_data = [
                                    'store_master_id' => $single_item['store_master_id'],
                                    'store_owner_product_master_id' => $single_item['store_owner_product_master_id'],
                                    'store_owner_product_variant_master_id' => $single_item['store_owner_product_variant_master_id'],
                                    'store_orders_master_id' => $som_arr['insert_id'],
                                    'title' => $storeName."-".$single_item['product_title'],
                                    'quantity' => $single_item['qtyTotal'],
                                    'price' => $single_item['priceTotal'],
                                    'sku' => $single_item['sku'],
                                    'vendor' => 'SpiritHero.com',
                                    'variant_title' => $single_item['size'].' / '.$single_item['product_color_name'],
                                    'shop_variant_id' => rand($som_arr['insert_id'].$single_item['store_master_id'].time(),10),
                                    'created_on' => @date('Y-m-d H:i:s'),
                                    'created_on_ts' => time(),
                            ];
                            parent::insertTable_f_mdl('store_order_items_master',$soim_insert_data);
                    }
                }

                //update total_fundraising_amount in main order
                $som_update_data = [
                    'total_fundraising_amount' => $total_fundraising_amount
                ];
                parent::updateTable_f_mdl('store_orders_master',$som_update_data,'id="'.$som_arr['insert_id'].'"');
				
                /*=============profit margin auto update===============*/
                $store_master_id=$data[0]['store_master_id'];
              	$total_sale         = 0.00;
              	$fundraising_amount = 0.00;
              	if (isset($store_master_id) && !empty($store_master_id)) {
                  	$saleSql = 'SELECT soim.id,soim.store_master_id,sm.store_name AS title,soim.is_deleted,SUM(soim.quantity * soim.price) AS total_sale,som.store_sale_type FROM store_order_items_master AS soim INNER JOIN store_orders_master AS som ON soim.store_orders_master_id = som.id INNER JOIN store_master AS sm ON soim.store_master_id = sm.id WHERE 1 AND soim.is_deleted = 0 AND som.is_order_cancel = "0"  AND som.order_type="1" AND som.store_master_id = '.$store_master_id.' ';
                  	$saleData = parent::selectTable_f_mdl($saleSql);

                   	$saleSql2 = 'SELECT soim.id,soim.store_master_id,sm.store_name AS title,soim.is_deleted,SUM(soim.quantity * soim.price) AS total_sale,som.store_sale_type FROM store_order_items_master AS soim INNER JOIN store_orders_master AS som ON soim.store_orders_master_id = som.id INNER JOIN store_master AS sm ON soim.store_master_id = sm.id WHERE 1 AND soim.is_deleted = 0 AND som.is_order_cancel = "0" AND som.order_type="2" AND som.store_master_id = '.$store_master_id.' ';
                  	$saleData2 = parent::selectTable_f_mdl($saleSql2);
                  	$total_sale_manual='';
                  	if(!empty($saleData2)){
                    	$total_sale_manual = $saleData2[0]['total_sale'];
                  	}

                  	$saleSql3 = 'SELECT soim.id,soim.store_master_id,sm.store_name AS title,soim.is_deleted,SUM(soim.quantity * soim.price) AS total_sale,som.store_sale_type FROM store_order_items_master AS soim INNER JOIN store_orders_master AS som ON soim.store_orders_master_id = som.id INNER JOIN store_master AS sm ON soim.store_master_id = sm.id WHERE 1 AND soim.is_deleted = 0 AND som.is_order_cancel = "0" AND som.order_type="3" AND som.store_master_id = '.$store_master_id.' ';
                  	$saleData3 = parent::selectTable_f_mdl($saleSql3);
                  	$total_sale_quickbuy='';
                  	if(!empty($saleData3)){
                    	$total_sale_quickbuy = $saleData3[0]['total_sale'];
                  	}

                  	if(!empty($saleData)){
                      	$total_sale = $saleData[0]['total_sale'];
                      	$fundSql = 'SELECT IFNULL(SUM(total_fundraising_amount),0) as total_fundraising_amount FROM `store_orders_master` WHERE `store_orders_master`.store_master_id = '.$store_master_id.' and `store_orders_master`.is_order_cancel = "0"';
                      	$fundData = parent::selectTable_f_mdl($fundSql);
                      	$fundraising_amount = $fundData[0]['total_fundraising_amount'];
                      	$total_sale         =number_format((float)$total_sale, 2);
                      	$total_sale = str_replace(",","",$total_sale);

                      	$total_sale_manual = number_format((float)$total_sale_manual, 2);
                      	$total_sale_manual = str_replace(",","",$total_sale_manual);

                      	$total_sale_quickbuy = number_format((float)$total_sale_quickbuy, 2);
                      	$total_sale_quickbuy = str_replace(",","",$total_sale_quickbuy);

                      	$total_gross_sale = $total_sale+$total_sale_manual+$total_sale_quickbuy;
                      	$total_gross_sale = number_format((float)$total_gross_sale, 2);
                      	$total_gross_sale = str_replace(",","",$total_gross_sale);

                      	$fundraising_amount =number_format((float)$fundraising_amount, 2);
                      	$fundraising_amount = str_replace(",","",$fundraising_amount);

                  	}
              	}

              	$profitSql  = 'SELECT *, "0" AS profit_value FROM `profit_cost_details` ';
              	$profitDataAll = parent::selectTable_f_mdl($profitSql);

              	$item_sql="
                  SELECT sm.ct_fundraising_price,sm.id,sm.store_name,sm.is_fundraising,
                  (SELECT IFNULL(SUM(oim.quantity),0) FROM `store_orders_master` as om INNER JOIN `store_order_items_master` as oim on om.id = oim.store_orders_master_id WHERE 1 AND om.is_order_cancel = 0 AND oim.is_deleted = 0 and oim.store_master_id = sm.id) as totalItem_sold,
                  (SELECT IFNULL(SUM(oim.quantity),0) FROM `store_orders_master` as om INNER JOIN `store_order_items_master` as oim on om.id = oim.store_orders_master_id WHERE 1 AND om.is_order_cancel = 0 AND oim.is_deleted = 0 and oim.store_master_id = sm.id and om.order_type=1) as actual_orderItem_sold,
                  (SELECT count(id) FROM store_orders_master WHERE store_master_id = sm.id AND is_order_cancel = 0) as total_order, 
                  (SELECT count(id) FROM store_orders_master WHERE store_master_id = sm.id AND is_order_cancel = 0 AND order_type=1) as total_actual_order,
                  (SELECT IFNULL(SUM(total_fundraising_amount),0) FROM store_orders_master WHERE store_master_id = sm.id AND is_order_cancel = 0) as total_fund_amount
                  FROM store_master sm INNER JOIN store_owner_details_master sodm ON sm.store_owner_details_master_id = sodm.id INNER JOIN store_sale_type_master sstm ON sm.store_sale_type_master_id = sstm.id 
                  LEFT JOIN store_organization_type_master as org ON sm.store_organization_type_master_id = org.id WHERE sm.id = ".$store_master_id."
              	";
              	$item_sql_data = parent::selectTable_f_mdl($item_sql);
              	$label_values = 0.00;
              	$printCost=0.00;
              	$checked_lable_cost=0.00;
              	$unchecked_lable_cost=0.00;
              	if(!empty($profitDataAll))
              	{
					$craditcardfee=0.00;
                  	foreach($profitDataAll as $value)
                  	{
                      	$profitSql  = 'SELECT store_profit.profit_value,profit_cost_details.cost_label,profit_cost_details.id,profit_cost_details.cost_slug,profit_cost_details.is_checked FROM store_profit LEFT JOIN profit_cost_details ON store_profit.profit_label_id = profit_cost_details.id where store_profit.store_master_id = "'.$store_master_id.'" AND profit_cost_details.id =  "'.$value['id'].'" ';
                      	$profitData = parent::selectTable_f_mdl($profitSql);
                      	if(!empty($profitData))
                      	{
                          	$label_values += number_format((float)str_replace(",","",$profitData[0]['profit_value']),2);
                          	$label_values = str_replace(",","",$label_values);
                          	$cost_slug=$profitData[0]['cost_slug'];
                          	$is_checked=$profitData[0]['is_checked'];
                          	$totalItem_sold=$item_sql_data[0]['totalItem_sold'];
                          	$actual_orderItem_sold=$item_sql_data[0]['actual_orderItem_sold'];
                          	$profit_id=$profitData[0]['id'];
                          	if($is_checked=='1'){
                              	$printCostLabel =str_replace(",","",$profitData[0]['profit_value']) * $totalItem_sold;
                              	$printCostLabel = str_replace(",","",$printCostLabel);
                              	$printCost = number_format((float)($printCostLabel-str_replace(",","",$profitData[0]['profit_value'])), 2);
                              	$checked_lable_cost +=$printCostLabel;
                              	$checked_lable_cost = str_replace(",","",$checked_lable_cost);
                          	}else{ 
                              	$unchecked_lable_cost += number_format((float)str_replace(",","",$profitData[0]['profit_value']), 2);
                              	$unchecked_lable_cost = str_replace(",","",$unchecked_lable_cost);
                              	$total_card_fee=0.00;
                              	if($profit_id=='12'){
                                  	$total_order_amount = str_replace(",","",$total_gross_sale);
                                  	$total_order_get = str_replace(",","",$item_sql_data[0]['total_order']);
                                  	$card_fee= $total_order_amount*2.9/100;
                                  	$card_fee = str_replace(",","",$card_fee);
                                  	$no_of_order_fee=$total_order_get * 0.30;
                                 	$total_card_fee=$card_fee + $no_of_order_fee;
                                  	$total_card_fee=number_format((float)$total_card_fee, 2);
                                  	$total_card_fee = str_replace(",","",$total_card_fee);
									$craditcardfee=$total_card_fee;
                              	}
                          	}
                      	}
                        else{
                          	$label_values += number_format((float)str_replace(",","",$value['lable_profit']),2);
                          	$label_values = str_replace(",","",$label_values);
                          	$cost_slug=$value['cost_slug'];
                          	$is_checked=$value['is_checked'];
                          	$totalItem_sold=str_replace(",","",$item_sql_data[0]['totalItem_sold']);
                          	$actual_orderItem_sold=str_replace(",","",$item_sql_data[0]['total_order']);
                          	$profit_id=$value['id'];
                          	if($is_checked=='1'){
                              	$printCostLabel =str_replace(",","",$value['lable_profit']) * $totalItem_sold;
                              	$printCostLabel = str_replace(",","",$printCostLabel);
                              	$printCost = number_format((float)($printCostLabel-str_replace(",","",$value['lable_profit'])), 2);
                              	$checked_lable_cost +=$printCostLabel;
                              	$checked_lable_cost = str_replace(",","",$checked_lable_cost);
                          	}else{ 
                              	$unchecked_lable_cost += number_format((float)str_replace(",","",$value['lable_profit']), 2);
                              	$unchecked_lable_cost = str_replace(",","",$unchecked_lable_cost);
                              	$total_card_fee=0.00;
                              	if($profit_id=='12'){
                                  	$total_order_amount = str_replace(",","",$total_gross_sale);
                                  	$total_order_get = str_replace(",","",$item_sql_data[0]['total_order']);
                                  	$card_fee= $total_order_amount*2.9/100;
                                  	$card_fee = str_replace(",","",$card_fee);
                                  	$no_of_order_fee=$total_order_get * 0.30;
                                  	$total_card_fee=$card_fee + $no_of_order_fee;
                                  	$total_card_fee=number_format((float)$total_card_fee, 2);
                                  	$total_card_fee = str_replace(",","",$total_card_fee);
									$craditcardfee=$total_card_fee;
                              	}
                          	}
                      	}
                  	}
              	}

               	$total_lable_price = ($checked_lable_cost+$unchecked_lable_cost + $fundraising_amount +$craditcardfee);
               	$lablrprice   = number_format( (float)str_replace(",","",$total_lable_price), 2, '.', '');
               	$total_profit= (float)$total_gross_sale-str_replace(",","",$lablrprice);
               	$totalProfit  = (float)$total_profit;
               	$total_profit = str_replace(",","",$total_profit);
               	$gross_sale=$total_gross_sale-str_replace(",","",$fundraising_amount);
               	$gross_sale = str_replace(",","",$gross_sale);
               	if($gross_sale=='0'){
               		$profitmargin='0';
               	}else{
               		$profitmargin= ($total_profit/$gross_sale)*100;
               	}
               	
               	$profitmargin  = number_format((float)$profitmargin, 2);
               	$profitmargin = str_replace(",","",$profitmargin);
               	$newProfit = $totalProfit;
               	$totalProData = [
                  'total_profit'  => $newProfit,
                  'profit_margin' => $profitmargin
               	];
              	parent::updateTable_f_mdl('store_master',$totalProData,'id="'.$store_master_id.'"');
                /*==============profit margin auto update end====================*/
			}	
		}
		
		echo $status;die;
	}

	Public function quickbuyOrderDetailsPage(){
		global $s3Obj;

		$sqlStoreName = 'SELECT COUNT(ac.id), ac.orderId, ac.created_at, ac.store_master_id, ac.status, store_master.store_name FROM add_to_cart_quickbuy as ac INNER JOIN store_master ON ac.store_master_id = store_master.id WHERE ac.status IN (2,3) AND ac.orderId = "'.$_GET['oid'].'" ';
		$dataStoreName = parent::selectTable_f_mdl($sqlStoreName);


		$sql = 'SELECT ac.id, ac.store_master_id, SUM(ac.qty) as qtyTotal, IF(sopm.group_name = "", "Others", sopm.group_name) as group_name FROM add_to_cart_quickbuy as ac INNER JOIN store_owner_product_master as sopm ON ac.store_owner_product_master_id = sopm.id WHERE ac.status IN (2,3) AND ac.orderId = "'.$_GET['oid'].'" GROUP BY sopm.group_name';
		$data = parent::selectTable_f_mdl($sql);

		$result = array();

		if(!empty($data)){
			foreach ($data as $key => $value) {
					if($value['group_name'] == 'Others'){
						$group_name = '';
					}else{
						$group_name = $value['group_name'];
					}

					$groupSql = 'SELECT minimum_group_value FROM minimum_group_product WHERE product_group = "'.$group_name.'" ';
					$groupDetails = parent::selectTable_f_mdl($groupSql);
					if(!empty($groupDetails)){
						$value['limit'] = (int)$groupDetails[0]['minimum_group_value'];
					}else{
						$value['limit'] = 0;
					}

					$productSql = 'SELECT ac.id, ac.store_master_id, ac.color, ac.qty, ac.store_owner_product_variant_master_id, IF(sopm.group_name = "", "Others", sopm.group_name) as group_name, sopm.product_title, sopvm.size, sopvm.sku, sopvm.image, sopvm.id as vid, ac.price, pc.product_color_name,slmm.image as mockup_image FROM add_to_cart_quickbuy as ac INNER JOIN store_owner_product_master as sopm ON ac.store_owner_product_master_id = sopm.id INNER JOIN store_owner_product_variant_master as sopvm ON ac.store_owner_product_variant_master_id = sopvm.id INNER JOIN store_product_colors_master as pc on pc.product_color = ac.color LEFT JOIN `store_logo_mockups_master` as slmm ON slmm.store_owner_product_variant_master_id = ac.store_owner_product_variant_master_id WHERE ac.status IN (2,3) AND sopm.status = 1 AND ac.orderId = "'.$_GET['oid'].'" AND sopm.group_name = "'.$group_name.'" ';
					$productData = parent::selectTable_f_mdl($productSql);

					// if(!empty($value['image'])){
					// 	$value['image'] = $s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH.$value['image']);
					// }

					$value['productData'] = $productData;

				$result[] = $value;
			}


		}

		if(!empty($result)){
			return array('result' => $result, 'storeNameData' =>$dataStoreName);
		}else{
			// header("location:purchase-minimum-products.php?stkn=&store_master_id='".$_GET['store_master_id']."'");
			
		}
	}

	public function deleteManualData()
	{	
		$res = [];
		if(!empty($_POST['orderId'])){
			$data = parent::deleteTable_f_mdl('add_to_cart', 'orderId ='.$_POST['orderId']." AND status = 2");
			echo json_encode($data);
		}
		die();
	}

	public function deleteQuickbuyData()
	{	
		$res = [];
		if(!empty($_POST['orderId'])){
			$data = parent::deleteTable_f_mdl('add_to_cart_quickbuy', 'orderId ='.$_POST['orderId']." AND status = 2");
			echo json_encode($data);
		}
		die();
	}

	public function deleteManualOrderAfterApprove()
	{	
		$res = [];
		if(!empty($_POST['orderId'])){
			
			parent::deleteTable_f_mdl('add_to_cart', 'orderId ='.$_POST['orderId']." AND status = 3");
			$orderSql = 'SELECT id  FROM store_orders_master WHERE manual_order_number="'.$_POST['orderId'].'" AND order_type = "2" ';
			$OrderData = parent::selectTable_f_mdl($orderSql);

			$order_id='';
			if(!empty($OrderData)){
				$order_id= $OrderData[0]['id'];
			}

			parent::deleteTable_f_mdl('store_orders_master', 'manual_order_number ='.$_POST['orderId']." AND order_type = 2");
			
			$data = parent::deleteTable_f_mdl('store_order_items_master','store_orders_master_id="'.$order_id.'"');
			
			echo json_encode($data);
		}
		die();
	}

	public function deleteQuickbuyOrderAfterApprove()
	{	
		$res = [];
		if(!empty($_POST['orderId'])){
			
			parent::deleteTable_f_mdl('add_to_cart_quickbuy', 'orderId ='.$_POST['orderId']." AND status = 3");
			$orderSql = 'SELECT id  FROM store_orders_master WHERE quickbuy_order_number="'.$_POST['orderId'].'" AND order_type = "3" ';
			$OrderData = parent::selectTable_f_mdl($orderSql);

			$order_id='';
			if(!empty($OrderData)){
				$order_id= $OrderData[0]['id'];
			}

			parent::deleteTable_f_mdl('store_orders_master', 'quickbuy_order_number ='.$_POST['orderId']." AND order_type = '3'");
			
			$data = parent::deleteTable_f_mdl('store_order_items_master','store_orders_master_id="'.$order_id.'"');
			
			echo json_encode($data);
		}
		die();
	}

	function exportOrders(){
		global $s3Obj;
		if(parent::isPOST()){
			if(!empty(parent::getVal("method")) && parent::getVal("method") == "export_orders"){
				$order_id = parent::getVal("order_ids");
				$order_type = parent::getVal("order_type");
				$resultArray = array();
				$export_file = time() . '-export.csv';
				$export_file_path = 'image_uploads/_export/' . $export_file;
				$export_file_url = common::IMAGE_UPLOAD_URL.'_export/' . $export_file;
				$file_for_export_data = fopen($export_file_path,"w");
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
				header('Content-Disposition: attachment; filename='.$export_file_url);

				if($order_type=='1'){

					fputcsv($file_for_export_data,
						['Order #','Order Date','Group Name','Product Title','Color','Size','SKU','Quantity','Price','Total Price','Order Status',]
					);
				}else{
					fputcsv($file_for_export_data,
						['Order #','Order Date','Group Name','Product Title','Color','Size','SKU','Quantity','Price','Total Price','Order Status','Student Name','Teacher Name']
					);
				}
				foreach($order_id as $values){

					if($order_type=='1'){
						$manual_order_sql = 'SELECT ac.id, ac.orderId,ac.store_owner_product_variant_master_id, ac.status,ac.price,ac.color,ac.price,ac.qty, store_master.store_name, ac.created_at, sodm.first_name, sodm.last_name, sodm.email,sopvm.size,sopvm.sku,sopvm.color as sopvm_color,IF(sopm.group_name = "", "Others", sopm.group_name) as group_name, sopm.product_title,sopm.product_description,pc.product_color_name FROM store_master 
						INNER JOIN store_owner_details_master AS sodm ON sodm.id = store_master.store_owner_details_master_id 
						INNER JOIN add_to_cart as ac ON store_master.id = ac.store_master_id
						INNER JOIN store_owner_product_master as sopm ON ac.store_owner_product_master_id = sopm.id
						LEFT JOIN store_owner_product_variant_master AS sopvm ON sopvm.id = ac.store_owner_product_variant_master_id
						INNER JOIN store_product_colors_master as pc on pc.product_color = ac.color
						WHERE ac.orderId='.$values.' ';
						$manual_order_data = parent::selectTable_f_mdl($manual_order_sql);
						if(!empty($manual_order_data)){
							
							foreach($manual_order_data as $single_item){
								$order_status="Under Review";
								if($single_item['status']=='3'){
									$order_status='Approved';
								}
								$total_price_sql=" SELECT SUM(qty * price) AS total_price FROM add_to_cart WHERE id=".$single_item['id']." ";
								$total_price_data = parent::selectTable_f_mdl($total_price_sql);
								$price = number_format((float)$single_item['price'], 2);
								$total_price = number_format((float)$total_price_data[0]['total_price'], 2);
								fputcsv($file_for_export_data,
									[
										trim("MO-".$single_item['orderId']),
										trim($single_item['created_at']),
										trim($single_item['group_name']),
										trim($single_item['product_title']),
										trim($single_item['product_color_name']),
										trim($single_item['size']),
										trim($single_item['sku']),
										trim($single_item['qty']),
										trim("$".$price),
										trim("$".$total_price),
										trim($order_status)
									]
								);
							}
						}
					}else if($order_type=='2'){
						$quick_order_sql = 'SELECT acq.id, acq.orderId,acq.store_owner_product_variant_master_id, acq.status,acq.price,acq.color,acq.price,acq.qty,acq.customer_name,acq.sort_list_name, store_master.store_name, acq.created_at, sodm.first_name, sodm.last_name, sodm.email,sopvm.size,sopvm.sku,sopvm.color as sopvm_color,IF(sopm.group_name = "", "Others", sopm.group_name) as group_name, sopm.product_title,sopm.product_description,pc.product_color_name FROM store_master 
						INNER JOIN store_owner_details_master AS sodm ON sodm.id = store_master.store_owner_details_master_id 
						INNER JOIN add_to_cart_quickbuy as acq ON store_master.id = acq.store_master_id
						INNER JOIN store_owner_product_master as sopm ON acq.store_owner_product_master_id = sopm.id
						LEFT JOIN store_owner_product_variant_master AS sopvm ON sopvm.id = acq.store_owner_product_variant_master_id
						INNER JOIN store_product_colors_master as pc on pc.product_color = acq.color
						WHERE acq.orderId='.$values.' ';
						$quick_order_data = parent::selectTable_f_mdl($quick_order_sql);
						if(!empty($quick_order_data)){

							foreach($quick_order_data as $single_item){
								$order_status="Under Review";
								if($single_item['status']=='3'){
									$order_status='Approved';
								}
								$total_price_sql=" SELECT SUM(qty * price) AS total_price FROM add_to_cart_quickbuy WHERE id=".$single_item['id']." ";
								$total_price_data = parent::selectTable_f_mdl($total_price_sql);

								$price = number_format((float)$single_item['price'], 2);
								$total_price = number_format((float)$total_price_data[0]['total_price'], 2);

								fputcsv($file_for_export_data,
									[
										trim("QB-".$single_item['orderId']),
										trim($single_item['created_at']),
										trim($single_item['group_name']),
										trim($single_item['product_title']),
										trim($single_item['product_color_name']),
										trim($single_item['size']),
										trim($single_item['sku']),
										trim($single_item['qty']),
										trim("$".$price),
										trim("$".$total_price),
										trim($order_status),
										trim($single_item['customer_name']),
										trim($single_item['sort_list_name'])
									]
								);
							}
						}
					}
				}
				$status = true;
				if($status == true){
					fclose($file_for_export_data);
					$documentURL = $export_file_url;
					$resultArray['SUCCESS']='TRUE';
					$resultArray['MESSAGE']='';
					$resultArray['EXPORT_URL']=$documentURL; // Task 59
				}else{
					$resultArray['SUCCESS'] = 'FALSE';
					$resultArray['MESSAGE'] = 'Records are not found.';
				}	
				common::sendJson($resultArray);
			}
		}
	}

	Public function placeOrderHistoryPage(){

		$sql = "SELECT id,place_order_number_sa,attention,po_number,ship_to,ship_email,sku,color,size,quantity,create_on,
		(SELECT IFNULL( SUM(quantity) ,0) FROM `sanmar_place_order_history` WHERE place_order_number_sa ='".$_GET['oid']."' ) as total_qty
		FROM sanmar_place_order_history WHERE place_order_number_sa ='".$_GET['oid']."' ORDER BY sku ";
		$data = parent::selectTable_f_mdl($sql);
		return $data;
	}

	Public function placeorderHistoryDetailsData($order_id){

		$sql = "SELECT * FROM sanmar_place_order_details_history WHERE place_order_number_sa='".$order_id."' ";
		$historydetailsdata = parent::selectTable_f_mdl($sql);
		return $historydetailsdata;
	}

	public function getStoreSaleTypeList(){
		$sql = 'SELECT id, sale_type, sale_short_code FROM `store_sale_type_master` WHERE status=1';
		return parent::selectTable_f_mdl($sql);
	}

	public function updateOrderTrackingNumber(){
		$resultArray = [];
		if(!empty($_POST['order_id'])){
			$fe_order_id  =trim($_POST['fe_order_id']);
			$order_id     =trim($_POST['order_id']);
			$tracking_num =trim($_POST['tracking_num']);

			

			if(!empty($fe_order_id)){

				$sql = 'SELECT fwm.fe_tracker_number,fwm.fe_tracking_url,ssrm.tracking_status,ssrm.ship_date FROM fe_webhook_master as fwm LEFT JOIN shipengine_shipping_rates_master as ssrm ON ssrm.tracking_number=fwm.fe_tracker_number
					WHERE  fwm.fe_order_id="'.$fe_order_id.'" ';
				$tracking_data=parent::selectTable_f_mdl($sql);
				if(empty($tracking_data)){
					$som_update_data = [
						'manual_tracking_number'=>$tracking_num
					];
					parent::updateTable_f_mdl('store_orders_master',$som_update_data,'fe_order_id="'.$fe_order_id.'"');

				}else{
					$som_update_data = [
						'fe_tracker_number' => $tracking_num,
						'fe_tracking_url'=>'https://tools.usps.com/go/TrackConfirmAction?tLabels='.$tracking_num
					];
					parent::updateTable_f_mdl('fe_webhook_master',$som_update_data,'fe_order_id="'.$fe_order_id.'"');
				}

	            $resultArray["isSuccess"] = "1";
				$resultArray['msg'] 	  = 'Tracking number updated successfully.';
			}else{
				$resultArray['isSuccess'] = "0";
				$resultArray['msg']       = 'Error while updating tracking number. Please check and try again after some time.';
			}
			common::sendJson($resultArray);die;
		}

	}

}
?>
