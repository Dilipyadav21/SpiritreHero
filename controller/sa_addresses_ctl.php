<?php
include_once 'model/sa_addresses_mdl.php';

class sa_addresses_ctl extends sa_addresses_mdl
{
	public $TempSession = "";

	function __construct(){	
		if (parent::isGET() || parent::isPOST()) {
			if(parent::getVal("method")){
				$this->checkRequestProcess(parent::getVal("method"));
			}else{
				$this->SITE_ACCESS_KEY = parent::getVal("stkn");
			}
		}
		
		common::CheckLoginSession();
	}

    function checkRequestProcess($requestFor){
        if($requestFor != ""){
            switch($requestFor){
				case "delete-addresses":
					$this->deleteAddress();
                break;
			}
        }
    }
	
	function getAllAddresses(){
		return parent::getAllAddresses_f_mdl();
	}
	
    function deleteAddress()
	{
		if (parent::isPOST()) {
			if (!empty(parent::getVal("method")) && parent::getVal("method") == "delete-addresses") {
				$id = parent::getVal("vId");
				parent::deleteTable_f_mdl('address_master', 'id =' . $id);
				$resultArray = array();
				$resultArray["isSuccess"] = "TRUE";
				$resultArray["msg"] = "Address deleted successfully.";
				common::sendJson($resultArray);
			}
		}
	}
	
	function addressesPagination(){
		if(parent::isPOST()){
			if(parent::getVal("hdn_method") == "addresses_pagination")
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
							ship_to LIKE '%".trim($keyword)."%' OR
							address_line_1 LIKE '%".trim($keyword)."%' OR
							address_line_2 LIKE '%".trim($keyword)."%' OR
							country LIKE '%".trim($keyword)."%' OR
							city LIKE '%".trim($keyword)."%' OR
							state LIKE '%".trim($keyword)."%' OR
							zip_code LIKE '%".trim($keyword)."%'
						)";
				}
				
				$cond_order = 'ORDER BY id DESC';
				if(!empty($sort_field)){
					$cond_order = 'ORDER BY '.$sort_field.' '.$sort_type;
				}
				
				$sql="
						SELECT count(id) as count FROM address_master WHERE 1
						$cond_keyword
					";
				$all_count = parent::selectTable_f_mdl($sql);
				
				$sql1="
						SELECT id, address_line_1, address_line_2,email,country,city,state,zip_code,ship_to,shipping_method,status,created_on FROM address_master WHERE 1
						$cond_keyword
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
					
					$html .= '<th>#</th>';
					$html .= '<th>Ship To</th>';
					$html .= '<th>Address</th>';
					$html .= '<th>Status</th>';
					$html .= '<th>Actions</th>';
					
					$html .= '</tr>';
					$html .= '</thead>';

					$html .= '<tbody>';

					if(!empty($all_list))
					{
						$sr = $sr_start;
							foreach($all_list as $single){
							$html .= '<tr>';
							$html .= '<td>'.$sr.'</td>';
							$html .= '<td>'.$single["ship_to"].'</td>';
							$html .= '<td>'.$single["ship_to"].','.$single["address_line_1"].','.$single["address_line_2"].','.$single["city"].','.$single["state"].','.$single["zip_code"].'</td>';
																		
							if($single["status"]=='1'){
								$html .= '<td>Active</td>';
							}
							else{
								$html .= '<td>Inactive</td>';
							}
							$html .= '<td><button type="button" class="btn btn-primary waves-effect waves-classic btn-addedit-addresses" data-href="sa-addedit-addresses.php?id='.$single["id"].'">Edit</button><button type="button" class="btn btn-danger waves-effect waves-classic btn-confirm-delete-addresses" data-id="'.$single["id"].'">Delete</button></td>';											
							$html .= '</tr>';	
							$sr++;
						}
					}
					else{
						$html .= '<tr>';
						$html .= '<td colspan="4" align="center">No Record Found</td>';
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

	function requestedStoresPagination(){
		if(parent::isPOST()){
			if(parent::getVal("hdn_method") == "requested_stores_pagination")
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
						sm.store_name LIKE '%".trim($keyword)."%'
					)";
				}
				
				$cond_order = 'ORDER BY sorpm.id DESC';
				if(!empty($sort_field)){
					$cond_order = 'ORDER BY '.$sort_field.' '.$sort_type;
				}
				
				$sql="SELECT count(sorpm.id) as count FROM store_owner_request_product_master as sorpm  INNER JOIN  store_master as sm  ON sm.id=sorpm.store_master_id
					$cond_keyword
				";
				$all_count = parent::selectTable_f_mdl($sql);
				
			 	$sql1="SELECT sorpm.id,sorpm.store_master_id,sm.store_name FROM store_owner_request_product_master as sorpm  INNER JOIN  store_master as sm  ON sm.id=sorpm.store_master_id 
					$cond_keyword 
					group By sm.store_name 
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
					$html .= '<th>#</th>';
					$html .= '<th>Store Name</th>';
					$html .= '<th>Action</th>';
					$html .= '</tr>';
					$html .= '</thead>';
					$html .= '<tbody>';
					
					if(!empty($all_list)){
						$sr = $sr_start;
						foreach($all_list as $single){
							$html .= '<tr>';
							$html .= '<td>'.$sr.'</td>';
							$html .= '<td>'.$single["store_name"].'</a></td>';
							$html .= '<td><button type="button" class="btn btn-primary btn-approve-order action-button" onclick="window.location.href=\'sa-store-view.php?stkn=&id='.$single["store_master_id"].'\';">View Request</button></td>';
							$html .= '</tr>';	
							$sr++;
						}
					}else{
						$html .= '<tr>';
						$html .= '<td colspan="3" align="center">No Record Found</td>';
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
}
?>