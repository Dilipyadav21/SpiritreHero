<?php
include_once 'model/sa_payouts_mdl.php';
include_once $path . '/libraries/Aws3.php';
class sa_payouts_ctl extends sa_payouts_mdl
{
	public $TempSession = "";

	function __construct(){	
		if(parent::isGET() || parent::isPOST()){
			$this->SITE_ACCESS_KEY = parent::getVal("stkn");
		}

		if(isset($_REQUEST['action'])){
			$action = $_REQUEST['action'];
			if($action=='update_payout_chequeno'){
				$this->update_payout_chequeno();exit;
			}else if($action=='export_payouts'){
				$this->export_payouts();exit;
			}
		}

		common::CheckLoginSession();
	}
	
	function addPayout(){
		if(parent::isPOST()){
			if(!empty(parent::getVal("method")) && parent::getVal("method") == "allPayout"){
				
				$checked_store = parent::getVal("checked_store");	
				
				$masterBulkInsertStr ="";
				
				$created_on =  @date('Y-m-d H:i:s');
				$created_on_ts = time();
		
				foreach($checked_store as $objProd)
				{
					$masterBulkInsertStr .= "(".$objProd["store_master_id"].",".$objProd["store_due_amount"].",'".$created_on."','".$created_on_ts."'),";
				}
				
				$tempmasterBulkInsertStr = trim($masterBulkInsertStr, ",");
				
				if($masterBulkInsertStr != "")
				{
					$resArray = parent::addPayout_f_mdl($tempmasterBulkInsertStr);
					if($resArray['isSuccess'] == '1'){	
						foreach($checked_store as $objProd){
							$storeMasterId = $objProd["store_master_id"];
							$store_due_amount = $objProd["store_due_amount"];
							$this->sendPayoutEmail($storeMasterId,$store_due_amount);
						}
					}	
				}
			}
		}
	}
	
	function manualPayout(){
		if(parent::isPOST()){
			if(!empty(parent::getVal("method")) && parent::getVal("method") == "manualPayout"){
				
				$storeMasterId = parent::getVal("store_master_id");
				$store_due_amount = parent::getVal("store_due_amount");
				
				$resArray = parent::manualPayout_f_mdl($storeMasterId,$store_due_amount);
				
				if($resArray['isSuccess'] == '1'){
					$this->sendPayoutEmail($storeMasterId,$store_due_amount);
				}
			}
		}
	}
	
	function payoutsPagination(){
		if(parent::isPOST()){
			if(parent::getVal("hdn_method") == "payouts_pagination")
			{
				$record_count=0;
				$page=0;
				$current_page=1;
				$rows='10';
				$keyword='';
				
				if( (isset($_REQUEST['rows']))&&(!empty($_REQUEST['rows'])) ){
					$rows=$_REQUEST['rows'];
				}
				if( (isset($_REQUEST['keyword']))){
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

				// Task 66 start
				$cond_keyword = '';
				if(isset($keyword) && !empty($keyword)){
					if(!is_numeric($keyword)){
						$cond_keyword = "AND (
							sm.store_name LIKE '%".trim($keyword)."%' OR 
							soam.check_payable_to_name LIKE '%".trim($keyword)."%' OR 
							sopm.notes LIKE '%".trim($keyword)."%' OR
							sopm.cheque_no LIKE '%".trim($keyword)."%' 
						)";
					}
					else{
						$cond_keyword = '';
					}
				}

				$paid_keyword = '';
				if(isset($keyword)){
					if(is_numeric($keyword)){
						$paid_keyword = "AND (sopm.paid_amount LIKE '%".trim($keyword)."%' OR 
						sopm.cheque_no LIKE '%".trim($keyword)."%')
						";
					}
				}
				// Task 66 end
				
				$cond_order = 'ORDER BY sopm.id DESC';
				if(!empty($sort_field)){
					$cond_order = 'ORDER BY '.$sort_field.' '.$sort_type;
				}

				$storeSaleType = "";
				if( (isset($_REQUEST['store_sale_type_master_id']))&&(!empty($_REQUEST['store_sale_type_master_id'])) ){
					$storeSaleType = 'AND sm.store_sale_type_master_id = "'.$_REQUEST['store_sale_type_master_id'].'"';
				}
				
				$sql="
						SELECT count(sopm.id) as count
						FROM store_owner_payouts_master sopm
						INNER JOIN store_master sm ON sopm.store_master_id = sm.id
						WHERE 1 AND sopm.is_deleted_commission = 1
						$cond_keyword
						$paid_keyword
						$storeSaleType
					";
				$all_count = parent::selectTable_f_mdl($sql);
				
				
				$sql1="
						SELECT sopm.id, sm.store_name,sm.store_sale_type_master_id, sopm.paid_amount,sopm.notes,sopm.cheque_no,sopm.created_on_ts,sopm.created_on,soam.check_payable_to_name as payable FROM store_owner_payouts_master sopm INNER JOIN store_master sm ON sopm.store_master_id = sm.id LEFT JOIN `store_owner_address_master` as soam ON soam.store_master_id = sm.id WHERE 1 AND sopm.is_deleted_commission = 1
						$cond_keyword
						$paid_keyword
						$storeSaleType
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
				}else{
					$html = '';
					$html .= '<div class="row">';
					$html .= '<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">';
					$html .= '<div class="table-responsive">';
					$html .= '<table class="table table-bordered table-hover">';

					$html .= '<thead>';
					$html .= '<tr>';
					$html .= '<th><input type="checkbox" id="ckbCheckAll"></th>';
					$html .= '<th>#</th>';
					$html .= '<th class="sort_th" data-sort_field="store_name">Store Name</th>';
					$html .= '<th style="min-width:120px;">Store Type</th>';
					$html .= '<th class="sort_th" data-sort_field="payable">Payable</th>';
					$html .= '<th class="sort_th" data-sort_field="paid_amount">Paid Amount</th>';
					$html .= '<th class="sort_th" data-sort_field="created_on_ts">Date</th>';
					$html .= '<th>Notes</th>';
					$html .= '<th>Check #</th>';
					$html .= '<th>Action</th>';
					
					$html .= '</tr>';
					$html .= '</thead>';

					$html .= '<tbody>';

					
					if(!empty($all_list))
					{
						$sr = $sr_start;
						foreach($all_list as $single){
							if(empty($single['cheque_no'])){
								$cheque_no='<td id="check_input_'.$single['id'].'"><input type="text" data-id="'.$single['id'].'"  id="cheque_no_'.$single['id'].'" class="cheque_no update_cheque_btn"></td>';
							}else{
								$cheque_no='<td id="check_input_'.$single['id'].'">
								<input type="text" id="cheque_no_'.$single['id'].'" data-id="'.$single['id'].'" class="cheque_no update_cheque_btn editcheck_no_'.$single['id'].'" value="'.trim($single['cheque_no']).'" style="display: none;">
								<span style="display: inline-block;word-break: break-all;" id="span1_'.$single['id'].'">'.$single['cheque_no'].'</span>&nbsp;
								<button class="btn btn-primary btn-round btn-sm waves-effect waves-classic update_cheque_edit_btn" data-toggle="tooltip" data-placement="top" data-trigger="hover" data-original-title="Edit" title="" id="update_cheque_edit_btn_'.$single['id'].'" data-id="'.$single['id'].'"><i class="fa fa-edit"></i></button></td>';
							}
							$html .= '<tr>';
							$html .= '<td><input type="checkbox" value=' . $single["id"] . ' class="checkBoxClass"></td>';
							$html .= '<td>'.$sr.'</td>';
							$html .= '<td>'.$single["store_name"].'</td>';
							$html .= '<td>' . ($single["store_sale_type_master_id"] == 1 ? 'Flash Sale' : 'On-Demand') . '</td>';
							$html .= '<td>'.$single["payable"].'</td>';	
							$html .= '<td>$'.$single["paid_amount"].'</td>';
							//$html .= '<td>'.date('m/d/Y',$single["created_on_ts"]).'</td>';
							$html .= '<td>'.date("m/d/Y h:i A", strtotime($single["created_on"])).'</td>';
							$html .= '<td>'.$single["notes"].'</td>';
							$html .=  $cheque_no;
							$html .= '<td><button data-href="sa-add-payout.php?stkn='.parent::getVal("stkn").'&pid='.$single["id"].'" type="button" class="btn btn-info waves-effect waves-classic btn-addedit-org">Edit</button>&nbsp;&nbsp;<button data-id="'.$single["id"].'" type="button" class="btn btn-danger waves-effect waves-classic btn-confirm-commision">Delete</button>';
							
							$html .= '</tr>';	
							$sr++;
						}
					}
					else{
						$html .= '<tr>';
						$html .= '<td colspan="6" align="center">No Record Found</td>';
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
	}
	
	function duePaidPagination(){
		if(parent::isPOST()){
			if(parent::getVal("hdn_method") == "duepaid_pagination")
			{
				$record_count=0;
				$page=0;
				$current_page=1;
				$rows='10';
				$keyword='';
				
				if( (isset($_REQUEST['rows']))&&(!empty($_REQUEST['rows'])) ){
					$rows=$_REQUEST['rows'];
				}
				if( (isset($_REQUEST['keyword']))){
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

				// Task 66 start
				$cond_keyword = '';
				if(isset($keyword) && !empty($keyword)){
					if(!is_numeric($keyword)){
						$cond_keyword = "AND (
							store_name LIKE '%".trim($keyword)."%' OR
							payable LIKE '%".trim($keyword)."%' OR
							mailing_address LIKE '%".trim($keyword)."%' OR
							notes LIKE '%".trim($keyword)."%'
						)";
					}
				}
				$paid_keyword = '';
				if(isset($_POST['duepaid']) && $_POST['duepaid'] == 'paid_comm'){
					if(isset($keyword)){
						if(is_numeric($keyword)){
							$paid_keyword = "AND (
								ROUND(paid_amount,2) LIKE '%".trim($keyword)."%' OR
								(ROUND(paid_amount,2)) REGEXP ROUND('".trim($keyword)."', 2) OR 
							 	(ROUND(earned_amount,2)-ROUND(paid_amount,2)) LIKE '%".trim($keyword)."%' OR 
							 	(ROUND(earned_amount,2)-ROUND(paid_amount,2)) REGEXP ROUND('".trim($keyword)."', 2) OR 
							 	mailing_address LIKE '%".trim($keyword)."%' OR
							 	order_count LIKE '%".trim($keyword)."%' OR
								totalItem_sold LIKE '%".trim($keyword)."%'
							)";
						}
					}
				}else if(isset($_POST['duepaid']) && $_POST['duepaid'] == 'overdue_comm'){
					if(isset($keyword)){
						if(is_numeric($keyword)){
							$paid_keyword = "AND (
								ROUND(paid_amount,2) LIKE '%".trim($keyword)."%' OR
								(ROUND(paid_amount,2)) REGEXP ROUND('".trim($keyword)."', 2) OR 
							 	(ROUND(earned_amount,2)-ROUND(paid_amount,2)) LIKE '%".trim($keyword)."%' OR 
							 	(ROUND(earned_amount,2)-ROUND(paid_amount,2)) REGEXP ROUND('".trim($keyword)."', 2) OR 
							 	mailing_address LIKE '%".trim($keyword)."%' OR
							 	order_count LIKE '%".trim($keyword)."%' OR
								totalItem_sold LIKE '%".trim($keyword)."%'
							)";
						}
					}
				}else{
					$paid_keyword = '';
					if(isset($_POST['duepaid']) && $_POST['duepaid'] == 'comm_due'){
						if(is_numeric($keyword)){
							// $paid_keyword = "AND ((earned_amount-paid_amount) LIKE '%".trim($keyword)."%' OR earned_amount LIKE '%".trim($keyword)."%') ";
							$paid_keyword = "AND (
							 	(ROUND(earned_amount,2)-ROUND(paid_amount,2)) LIKE '%".trim($keyword)."%' OR
							 	(ROUND(earned_amount,2)-ROUND(paid_amount,2)) REGEXP ROUND('".trim($keyword)."', 2) OR 
							  	ROUND(earned_amount,2) LIKE '%".trim($keyword)."%' OR
							 	(ROUND(earned_amount,2)) REGEXP ROUND('".trim($keyword)."', 2) OR 
							  	mailing_address LIKE '%".trim($keyword)."%' OR
							  	order_count LIKE '%".trim($keyword)."%' OR
								totalItem_sold LIKE '%".trim($keyword)."%'
							)";

						}
					}
				}	

				// (earned_amount-paid_amount) LIKE '%".trim($keyword)."%' OR 
				//earned_amount LIKE '%".trim($keyword)."%' OR
				//order_count LIKE '%".trim($keyword)."%' OR
				//totalItem_sold LIKE '%".trim($keyword)."%' OR
				// Task 66 end
				$cond_order = 'ORDER BY id DESC';
				if(!empty($sort_field)){
					$cond_order = 'ORDER BY '.$sort_field.' '.$sort_type;
				}

				$storeSaleType = "";
				if( (isset($_REQUEST['store_sale_type_master_id']))&&(!empty($_REQUEST['store_sale_type_master_id'])) ){
					$storeSaleType = 'AND store_master.store_sale_type_master_id = "'.$_REQUEST['store_sale_type_master_id'].'"';
				}

				$con_duepaid = '';
				if(isset($_POST['duepaid']) && $_POST['duepaid'] == 'comm_due'){
					$con_duepaid ='AND (round(earned_amount,2)-round(paid_amount,2)) > 0 ';
				}else if(isset($_POST['duepaid']) && $_POST['duepaid'] == 'paid_comm'){
					$con_duepaid ='AND  (`paid_amount` != 0.00)';
				}else if(isset($_POST['duepaid']) && $_POST['duepaid'] == 'overdue_comm'){
					$con_duepaid ='AND (round(earned_amount,2)-round(paid_amount,2)) < 0 ';
				}

				$cond_duepaid= '';
				// $cond_duepaid = 'HAVING (earned_amount-paid_amount)>0';
				// if(isset($_POST['duepaid']) && $_POST['duepaid']=='paid_comm'){
				// 	$cond_duepaid = 'HAVING (earned_amount-paid_amount)>=0';
				// }
				/*$cond_start_end = '';
				if(isset($this->start_date) && !empty($this->start_date) && isset($this->end_date) && !empty($this->end_date) ){
					$cond_start_end = "AND add_date BETWEEN ".$this->start_date." AND ".$this->end_date."";
				}*/

				$sql="
						SELECT count(*) as count
						FROM (
						SELECT `store_master`.store_name, count(`store_orders_master`.id) as order_count1, SUM(total_fundraising_amount) as earned_amount,store_owner_address_master.check_payable_to_name as payable,CONCAT(store_owner_address_master.first_name, ' ', store_owner_address_master.last_name,' ',store_owner_address_master.address_line_1,' ',store_owner_address_master.address_line_2,' ',store_owner_address_master.city,' ',store_owner_address_master.state,' ',store_owner_address_master.country,' ',store_owner_address_master.zip_code) as mailing_address,
						(
						SELECT IFNULL( SUM(paid_amount) ,0) FROM `store_owner_payouts_master`
						WHERE `store_owner_payouts_master`.store_master_id = `store_master`.id AND `store_owner_payouts_master`.is_deleted_commission = 1
						) as paid_amount,
						(SELECT IFNULL( spm.notes,'') FROM `store_owner_payouts_master` as spm
						WHERE `spm`.store_master_id = `store_master`.id AND `spm`.is_deleted_commission = 1 order by id desc limit 1
						) as notes,
						(SELECT count(`store_orders_master`.id) FROM `store_orders_master` 
						WHERE `store_orders_master`.store_master_id = `store_master`.id AND store_orders_master.is_order_cancel = 0
						) as  order_count,
						(SELECT IFNULL(SUM(oim.quantity),0) FROM `store_orders_master` as om INNER JOIN `store_order_items_master` as oim on om.id = oim.store_orders_master_id WHERE 1 AND om.is_order_cancel = 0 AND oim.is_deleted = 0 and oim.store_master_id = `store_master`.id) as totalItem_sold
						FROM `store_orders_master`
						LEFT JOIN `store_master` ON `store_master`.id = `store_orders_master`.store_master_id $storeSaleType
						LEFT JOIN `store_owner_address_master` ON `store_owner_address_master`.store_master_id = `store_orders_master`.store_master_id
						
						WHERE `store_orders_master`.is_order_cancel = 0 GROUP BY `store_orders_master`.store_master_id
						
						$cond_duepaid
						) tbl
						WHERE 1
						$con_duepaid
						$cond_keyword
						$paid_keyword
					";
					
				$all_count = parent::selectTable_f_mdl($sql);
				
				/* Task 101 add new sub sql for totalItem_sold and add where condition is_order_cancel*/
				$sql1="
						SELECT *, (round(earned_amount,2)-round(paid_amount,2)) as due_amount
						FROM (
						SELECT `store_master`.id, `store_master`.store_name,`store_master`.store_sale_type_master_id, count(`store_orders_master`.id) as order_count1, SUM(total_fundraising_amount) as earned_amount1,SUM(`store_order_items_master`.fundraising_amount) as earned_amount,store_owner_address_master.check_payable_to_name as payable,CONCAT(IFNULL(store_owner_address_master.first_name,''),' ',IFNULL(store_owner_address_master.last_name,''),' ',IFNULL(store_owner_address_master.address_line_1,''),' ',IFNULL(store_owner_address_master.address_line_2,''),' ',IFNULL(store_owner_address_master.city,''),' ',IFNULL(store_owner_address_master.state,''),' ',IFNULL(store_owner_address_master.country,''),' ',IFNULL(store_owner_address_master.zip_code,'')) AS mailing_address,
						(
						SELECT IFNULL( SUM(paid_amount) ,0.00) FROM `store_owner_payouts_master`
						WHERE `store_owner_payouts_master`.store_master_id = `store_master`.id AND `store_owner_payouts_master`.is_deleted_commission = 1
						) as paid_amount,
						(SELECT IFNULL( spm.notes,'') FROM `store_owner_payouts_master` as spm
						WHERE `spm`.store_master_id = `store_master`.id AND `spm`.is_deleted_commission = 1 order by id desc limit 1
						) as notes,
						(SELECT count(`store_orders_master`.id) FROM `store_orders_master` 
						WHERE `store_orders_master`.store_master_id = `store_master`.id AND store_orders_master.is_order_cancel = 0
						) as  order_count,
						( SELECT store_owner_payouts_master.created_on FROM `store_owner_payouts_master` WHERE `store_owner_payouts_master`.store_master_id = `store_master`.id  AND `store_owner_payouts_master`.is_deleted_commission = 1 order by id desc limit 1  ) as created_on,
						(SELECT IFNULL(SUM(oim.quantity),0) FROM `store_orders_master` as om INNER JOIN `store_order_items_master` as oim on om.id = oim.store_orders_master_id WHERE 1 AND om.is_order_cancel = 0 AND oim.is_deleted = 0 and oim.store_master_id = `store_master`.id) as totalItem_sold	
						FROM `store_orders_master`
						LEFT JOIN `store_master` ON `store_master`.id = `store_orders_master`.store_master_id $storeSaleType
						LEFT JOIN `store_owner_address_master` ON `store_owner_address_master`.store_master_id = `store_orders_master`.store_master_id
						INNER JOIN store_order_items_master ON `store_order_items_master`.store_orders_master_id=`store_orders_master`.id AND store_order_items_master.store_master_id=`store_master`.id
						
						WHERE `store_orders_master`.is_order_cancel = 0 AND `store_order_items_master`.is_deleted='0' GROUP BY `store_orders_master`.store_master_id
						$cond_duepaid
						) tbl
						WHERE 1
						$con_duepaid
						$cond_keyword
						$paid_keyword
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
				}else{
					$html = '';
					$html .= '<div class="row">';
					$html .= '<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">';
					$html .= '<div class="table-responsive">';
					$html .= '<table class="table table-bordered table-hover">';

					$html .= '<thead>';
					$html .= '<tr>';
					
					$html .= '<th><input type="checkbox" id="ckbCheckAll"></th>';
					$html .= '<th>#</th>';
					
					// if(isset($_POST['duepaid']) && $_POST['duepaid'] == 'comm_due'){
					// 	$html .= '<th>Bulk Payouts</th>';
					// }
							
					$html .= '<th class="sort_th" data-sort_field="store_name">Store Name</th>';
					$html .= '<th style="min-width:120px;">Store Type</th>';
					$html .= '<th class="sort_th" data-sort_field="payable">Payable</th>';
					$html .= '<th class="sort_th" data-sort_field="mailing_address">Mailing Address</th>';
					$html .= '<th class="sort_th" data-sort_field="order_count"># Orders</th>';/* Task 101 Add new th #of orders*/
					$html .= '<th class="sort_th" data-sort_field="totalItem_sold"># Items</th>';/* Task 101 Add new th #of item sold*/
					if(isset($_POST['duepaid']) && $_POST['duepaid'] == 'comm_due'){
						$html .= '<th class="sort_th" data-sort_field="earned_amount">Total Fund Amount</th>';
						$html .= '<th class="sort_th" data-sort_field="due_amount">Amount Due</th>';
						$html .= '<th>Action</th>';
					}
					else if(isset($_POST['duepaid']) && $_POST['duepaid'] == 'paid_comm' || isset($_POST['duepaid']) && $_POST['duepaid'] == 'overdue_comm'){
						$html .= '<th class="sort_th" data-sort_field="paid_amount">Paid Amount</th>';
						$html .= '<th class="sort_th" data-sort_field="due_amount">Amount Due</th>';
						$html .= '<th class="sort_th" data-sort_field="created_on">Paid Date</th>';
						$html .= '<th>Notes</th>';
					}
				
					$html .= '</tr>';
					$html .= '</thead>';

					$html .= '<tbody>';

					if(!empty($all_list))
					{
						$sr = $sr_start;
						foreach($all_list as $single){
							$html .= '<tr>';
							$html .= '<td><input type="checkbox" value=' . $single["id"] . ' class="checkBoxClass"></td>';
							$html .= '<td>'.$sr.'</td>';	
							
							// if(isset($_POST['duepaid']) && $_POST['duepaid'] == 'comm_due'){
							// 	$html .= '<td><input type="checkbox" data-id='.$single["id"].' data-due_amount='.$single["due_amount"].' class="due_checkbox" value=""></td>';
							// }
							
							$html .= '<td>'.$single["store_name"].'</td>';
							$html .= '<td>' . ($single["store_sale_type_master_id"] == 1 ? 'Flash Sale' : 'On-Demand') . '</td>';	
							$html .= '<td>'.$single["payable"].'</td>';
							$mailingAddressBlank = $single["mailing_address"];
							$mailingAddress = '<td></td>';
							if(!empty($mailingAddressBlank)){
								$mailingAddress = '<td>'.$single["mailing_address"].'<input type="hidden" value="'.$single["mailing_address"].'" id="myInput_'.$single["id"].'"><button data-toggle="tooltip" data-placement="top" data-trigger="hover" data-original-title="Click to copy address" data-id='.$single["id"].' type="button" class="btn btn-round btn-primary copy_clip_btn"><i title="Click to copy address" class="fa fa-copy" style="font-size:20px;"></i></button>'.'</td>';	
							}
							$html .= $mailingAddress;
							$html .= '<td>'.$single["order_count"].'</td>';
							$html .= '<td>'.$single["totalItem_sold"].'</td>';/* Task 101 Add new td totalItem_sold */

							if(isset($_POST['duepaid']) && $_POST['duepaid'] == 'comm_due'){
								$html .= '<td>$'.number_format((float)$single["earned_amount"], 2).'</td>';
								$html .= '<td>$'.number_format((float)$single["due_amount"], 2).'</td>';
								$html .= '<td><a class="btn btn-primary waves-effect waves-classic btn-manual-payout"href="sa-add-payout.php?stkn='.parent::getVal("stkn").'&mpid='.$single["id"].'&due_amount='.number_format((float)$single["due_amount"],2).'" data-id='.$single["id"].' data-due_amount='.$single["due_amount"].'>Manual Payout</a></td>';
							}
							else if(isset($_POST['duepaid']) && $_POST['duepaid'] == 'paid_comm' || isset($_POST['duepaid']) && $_POST['duepaid'] == 'overdue_comm'){
								$html .= '<td>$'.number_format((float)$single["paid_amount"], 2).'</td>';
								$html .= '<td>$'.number_format((float)$single["due_amount"], 2).'</td>';
								// $html .= '<td>'.date('m/d/Y',$single["created_on"]).'</td>';
								$html .= '<td>'.date("m/d/Y h:i A", strtotime($single["created_on"])).'</td>';
								$html .= '<td>'.$single["notes"].'</td>';
							}
							
							$html .= '</tr>';	
							$sr++;
						}
					}
					else{
						$html .= '<tr>';
						$html .= '<td colspan="12" align="center">No Record Found</td>';
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
	}
	
	function sendPayoutEmail($storeMasterId,$paidAmoount){
		$s3Obj = new Aws3;
		$ownerEmail = parent::getEmailInfo_f_mdl($storeMasterId);
		
		#region - Send Mail To Store Admin
		require_once(common::EMAIL_REQUIRE_URL);
        if(strpos(common::EMAIL_REQUIRE_URL, 'aws_ses_smtp')!==false){
            $objAWS = new aws_ses_smtp();
        }else if(strpos(common::EMAIL_REQUIRE_URL, 'sendGridEmail')!==false){
            $objAWS = new sendGridEmail();
        }else{
            $objAWS = new Aws(common::AWS_ACCESS_KEY,common::AWS_SECRET_KEY,common::AWS_REGION);
        }
		
		$emailData = parent::getEmailTemplateInfo(common::PAYOUT_EMAIL_ID);
		$logo_image = $s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH.'email-logo.png');
		$logo ='<img class="navbar-brand-logo" src="'.$logo_image.'">';
		$subject = $emailData[0]['subject'];
		$to_email = $ownerEmail['0']['email'];
		$from_email = common::AWS_ADMIN_EMAIL;
		$attachment = [];
		
		//$mailSendStatus = $objAWS::sendEmail($from_email, $to_email, $subject, str_replace(["{{PAYOUT_AMOUNT}}","{{FIRST_NAME}}","{{DASHBOARD_LINK}}"],[$paidAmoount,$ownerEmail['0']['first_name'],common::CUSTOMER_ADMIN_DASHBOARD_URL],$emailData[0]['body']), $attachment);

		$sql = 'SELECT * FROM store_master WHERE id="'.$storeMasterId.'"';
        $store_data = parent::selectTable_f_mdl($sql);

		$store_open_date=!empty($store_data[0]["store_open_date"]) ? date('m/d/Y', $store_data[0]["store_open_date"]) : '' ;
		$store_last_date=!empty($store_data[0]["store_close_date"]) ? date('m/d/Y', $store_data[0]["store_close_date"]) : '' ;


		$mailSendStatus = 1;
		if ($emailData[0]['status'] == 1) {
			$mailSendStatus = $objAWS->sendEmail([$to_email], $subject, str_replace(["{{PAYOUT_AMOUNT}}","{{FIRST_NAME}}","{{STORE_NAME}}","{{DASHBOARD_LINK}}","{{SPIRITHERO_LOGO}}","{{STORE_OPEN_DATE}}","{{STORE_LAST_DATE}}" ],[$paidAmoount,$ownerEmail['0']['first_name'],$store_data[0]['store_name'],common::CUSTOMER_ADMIN_DASHBOARD_URL,$logo,$store_open_date,$store_last_date],$emailData[0]['body']), str_replace(["{{PAYOUT_AMOUNT}}","{{FIRST_NAME}}","{{STORE_NAME}}","{{DASHBOARD_LINK}}","{{SPIRITHERO_LOGO}}","{{STORE_OPEN_DATE}}","{{STORE_LAST_DATE}}"],[$paidAmoount,$ownerEmail['0']['first_name'],$store_data[0]['store_name'],common::CUSTOMER_ADMIN_DASHBOARD_URL,$logo,$store_open_date,$store_last_date],$emailData[0]['body']));
		}
		/*send mail store manager */
		$store_owner_details_master_id = $ownerEmail['0']['store_owner_id'];
		$sql_managerData = 'SELECT email,first_name FROM `store_manager_master` WHERE status="0" AND store_owner_id="' . $store_owner_details_master_id . '"';
		$smm_data =  parent::selectTable_f_mdl($sql_managerData);
		if(!empty($smm_data)){
			foreach ($smm_data as $managerData) {
				$to_email   = $managerData['email'];
				$from_email = common::AWS_ADMIN_EMAIL;
				$attachment = [];
				if ($emailData[0]['status'] == 1) {
					$mailSendStatus = $objAWS->sendEmail([$to_email], $subject, str_replace(["{{PAYOUT_AMOUNT}}","{{FIRST_NAME}}","{{STORE_NAME}}","{{DASHBOARD_LINK}}","{{SPIRITHERO_LOGO}}","{{STORE_OPEN_DATE}}","{{STORE_LAST_DATE}}" ],[$paidAmoount,$managerData['first_name'],$store_data[0]['store_name'],common::CUSTOMER_ADMIN_DASHBOARD_URL,$logo,$store_open_date,$store_last_date],$emailData[0]['body']), str_replace(["{{PAYOUT_AMOUNT}}","{{FIRST_NAME}}","{{STORE_NAME}}","{{DASHBOARD_LINK}}","{{SPIRITHERO_LOGO}}","{{STORE_OPEN_DATE}}","{{STORE_LAST_DATE}}"],[$paidAmoount,$managerData['first_name'],$store_data[0]['store_name'],common::CUSTOMER_ADMIN_DASHBOARD_URL,$logo,$store_open_date,$store_last_date],$emailData[0]['body']));
				}	
			}
		}
		/*send mail store manager */
		#endregion
		
		$resultArray = array();
		 
		if($mailSendStatus){
			$resultArray["isSuccess"] = "1";
			$resultArray["msg"] = "Changes saved successfully.";
		}
		else{
			$resultArray["isSuccess"] = "0";
			$resultArray["msg"] = "Oops! there is some issue during insert. Please try again.";
		}
		common::sendJson($resultArray);
	}

	function deleteCommisions()
	{
		if(parent::isPOST()){
			$res = [];
			if(parent::getVal("hdn_method") == "delete_commisions")
			{	
				$payoutId = parent::getVal("payout_id");
				$res = parent::updateTable_f_mdl('store_owner_payouts_master',['is_deleted_commission'=>'0'],'id="'.$payoutId.'"');
			}
			echo common::sendJson($res,1);die();
		}	
	}

	public function update_payout_chequeno()
	{	
		if(!empty($_POST['payout_id'])){
			$updatecheque_no = [
				'cheque_no' => trim($_POST['cheque_no'])
			];
			$data =parent::updateTable_f_mdl('store_owner_payouts_master',$updatecheque_no,'id="'.$_POST['payout_id'].'"');
			
			if(trim($_POST['cheque_no']) == ''){
				$htmldata='<input type="text" data-id="'.$_POST['payout_id'].'" id="cheque_no_'.$_POST['payout_id'].'" class="cheque_no update_cheque_btn">';
			}else{
				$htmldata='
				<input type="text" id="cheque_no_'.$_POST['payout_id'].'" data-id="'.$_POST['payout_id'].'" class="cheque_no update_cheque_btn editcheck_no_'.$_POST['payout_id'].'" value="'.trim($_POST['cheque_no']).'" style="display: none;">
				<span style="display: inline-block;word-break: break-all;" id="span1_'.$_POST['payout_id'].'">'.$_POST['cheque_no'].'</span>&nbsp;
				<button class="btn btn-primary btn-round btn-sm waves-effect waves-classic update_cheque_edit_btn" data-toggle="tooltip" data-placement="top" data-trigger="hover" data-original-title="Edit" title="" id="update_cheque_edit_btn_'.$_POST['payout_id'].'" data-id="'.$_POST['payout_id'].'"><i class="fa fa-edit"></i></button>
				';
			}
			$resultArray = array();
			$resultArray["isSuccess"] = "1";
			$resultArray["htmldata"] = $htmldata;
			$resultArray["msg"] = "Updated successfully.";
			echo json_encode($resultArray);
		}
		die();
	}

	public function export_payouts(){
	    $resultArray = array();
	    if (!empty($_POST['store_master_ids'])) {

	        $payouts_for = $_POST['payouts_for'];
	        $storeMasterId = $_POST['store_master_ids'];
			if ($payouts_for == 'comm_due') { 
				$export_file = 'payouts-due-' . time() . '.csv';
			}else if($payouts_for == 'paid_comm'){
				$export_file = 'payouts-paid-' . time() . '.csv';
			}else{
				$export_file = 'payouts-all-' . time() . '.csv';
			}
	        
	        $export_file_path = 'image_uploads/_export/' . $export_file;
	        $export_file_url = common::IMAGE_UPLOAD_URL . '_export/' . $export_file;
	        $file_for_export_data = fopen($export_file_path, "w");
	        $BOM = "\xEF\xBB\xBF"; // Byte Order Mark for UTF-8

	        // Simplified headers for CSV export
	        header('Content-Encoding: UTF-8');
	        header('Content-Type: text/csv; charset=UTF-8');
	        header('Content-Disposition: attachment; filename=' . $export_file);

	        if ($payouts_for == 'comm_due') {
	            fputcsv(
	                $file_for_export_data,
	                ['Store Name', 'Store Type', 'Payable', 'Mailing Address', '# Orders', '# Items', 'Total Fund Amount', 'Total Due Amount ']
	            );
	        } elseif ($payouts_for == 'paid_comm') {
	            fputcsv(
	                $file_for_export_data,
	                ['Store Name', 'Store Type', 'Payable', 'Mailing Address', '# Orders', '# Items', 'Total Paid Amount', 'Total Due Amount ', 'Paid Date']
	            );
	        } else {
	            fputcsv(
	                $file_for_export_data,
	                ['Store Name', 'Store Type', 'Payable', 'Paid Amount', 'Paid Date', 'Check #', 'Notes ']
	            );
	        }

	        if ($payouts_for == 'comm_due' || $payouts_for == 'paid_comm') {
	            $JsoncolorArray = json_encode(array_values($storeMasterId));
	            $storeMasterIds = str_replace(['[', ']'], '', $JsoncolorArray);
	            
	            $sql = "SELECT *, (round(earned_amount, 2) - round(paid_amount, 2)) as due_amount FROM 
	                (SELECT `store_master`.id, `store_master`.store_name,`store_master`.store_sale_type_master_id, count(`store_orders_master`.id) as order_count1, 
	                SUM(total_fundraising_amount) as earned_amount1, SUM(`store_order_items_master`.fundraising_amount) as earned_amount, store_owner_address_master.check_payable_to_name as payable, 
	                CONCAT(IFNULL(store_owner_address_master.first_name,''),' ',IFNULL(store_owner_address_master.last_name,''),' ',IFNULL(store_owner_address_master.address_line_1,''),' ',
	                IFNULL(store_owner_address_master.address_line_2,''),' ',IFNULL(store_owner_address_master.city,''),' ',IFNULL(store_owner_address_master.state,''),' ',
	                IFNULL(store_owner_address_master.country,''),' ',IFNULL(store_owner_address_master.zip_code,'')) AS mailing_address, 
	                (SELECT IFNULL(SUM(paid_amount),0.00) FROM `store_owner_payouts_master` WHERE `store_owner_payouts_master`.store_master_id = `store_master`.id AND 
	                `store_owner_payouts_master`.is_deleted_commission = 1) as paid_amount, 
	                (SELECT IFNULL(spm.notes,'') FROM `store_owner_payouts_master` as spm WHERE `spm`.store_master_id = `store_master`.id AND `spm`.is_deleted_commission = 1 
	                order by id desc limit 1) as notes, 
	                (SELECT count(`store_orders_master`.id) FROM `store_orders_master` WHERE `store_orders_master`.store_master_id = `store_master`.id AND store_orders_master.is_order_cancel = 0) as order_count, 
	                (SELECT store_owner_payouts_master.created_on FROM `store_owner_payouts_master` WHERE `store_owner_payouts_master`.store_master_id = `store_master`.id 
	                AND `store_owner_payouts_master`.is_deleted_commission = 1 order by id desc limit 1) as created_on, 
	                (SELECT IFNULL(SUM(oim.quantity),0) FROM `store_orders_master` as om INNER JOIN `store_order_items_master` as oim on om.id = oim.store_orders_master_id 
	                WHERE 1 AND om.is_order_cancel = 0 AND oim.is_deleted = 0 and oim.store_master_id = `store_master`.id) as totalItem_sold 
	                FROM `store_orders_master` 
	                LEFT JOIN `store_master` ON `store_master`.id = `store_orders_master`.store_master_id AND `store_master`.id IN ($storeMasterIds)
	                LEFT JOIN `store_owner_address_master` ON `store_owner_address_master`.store_master_id = `store_orders_master`.store_master_id 
	                INNER JOIN store_order_items_master ON `store_order_items_master`.store_orders_master_id = `store_orders_master`.id AND store_order_items_master.store_master_id = `store_master`.id 
	                WHERE `store_orders_master`.is_order_cancel = 0 AND `store_order_items_master`.is_deleted='0' GROUP BY `store_orders_master`.store_master_id) tbl 
	                WHERE 1 AND (round(earned_amount, 2) - round(paid_amount, 2)) > 0 ORDER BY id DESC";
	            
	            $store_data = parent::selectTable_f_mdl($sql);

	            foreach ($store_data as $values) {
	                $store_type = ($values["store_sale_type_master_id"] == 1) ? 'Flash Sale' : 'On-Demand';

	                $earned_amount = number_format((float)$values["earned_amount"], 2);
	                $due_amount = number_format((float)$values["due_amount"], 2);
	                $paid_amount = number_format((float)$values["paid_amount"], 2);

	                if ($payouts_for == 'comm_due') {
	                    fputcsv($file_for_export_data, [
	                        trim($values['store_name']),
	                        trim($store_type),
	                        trim($values['payable']),
	                        trim($values['mailing_address'] ?: ''),
	                        trim($values['order_count']),
	                        trim($values['totalItem_sold']),
	                        trim('$' . $earned_amount),
	                        trim('$' . $due_amount)
	                    ]);
	                } else {
	                    fputcsv($file_for_export_data, [
	                        trim($values['store_name']),
	                        trim($store_type),
	                        trim($values['payable']),
	                        trim($values['mailing_address'] ?: ''),
	                        trim($values['order_count']),
	                        trim($values['totalItem_sold']),
	                        trim('$' . $paid_amount),
	                        trim('$' . $due_amount),
	                        trim(date("m/d/Y h:i A", strtotime($values["created_on"])))
	                    ]);
	                }
	            }
	            fputcsv($file_for_export_data, ['']);
	            $status = true;
	        } else {
	            // Logic for 'all_comm' payout
	            $JsoncolorArray = json_encode(array_values($storeMasterId));
	            $payoutMasterIds = str_replace(['[', ']'], '', $JsoncolorArray);

	            $sql = "SELECT sopm.id, sm.store_name, sm.store_sale_type_master_id, sopm.paid_amount, sopm.notes, sopm.cheque_no, sopm.created_on_ts, sopm.created_on, 
	                    soam.check_payable_to_name as payable 
	                    FROM store_owner_payouts_master sopm 
	                    INNER JOIN store_master sm ON sopm.store_master_id = sm.id 
	                    LEFT JOIN `store_owner_address_master` as soam ON soam.store_master_id = sm.id 
	                    WHERE sopm.is_deleted_commission = 1 AND sopm.id IN ($payoutMasterIds)";
	            
	            $store_data = parent::selectTable_f_mdl($sql);

	            foreach ($store_data as $values) {
	                $store_type = ($values["store_sale_type_master_id"] == 1) ? 'Flash Sale' : 'On-Demand';
	                fputcsv($file_for_export_data, [
	                    trim($values['store_name']),
	                    trim($store_type),
	                    trim($values['payable']),
	                    trim('$' . number_format($values['paid_amount'], 2)),
	                    trim(date("m/d/Y h:i A", strtotime($values["created_on"]))),
	                    trim($values['cheque_no']),
	                    trim($values['notes'])
	                ]);
	            }
	            $status = true;
	        }

	        fclose($file_for_export_data);
	        if ($status == true) {
	            echo json_encode(['SUCCESS' => 'TRUE', 'MESSAGE' => 'Export CSV Successfully', 'EXPORT_URL' => $export_file_url]);
	        }
	    }
	}

}
?>