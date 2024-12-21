<?php
include_once 'model/sa_vendors_mdl.php';

class sa_vendors_ctl extends sa_vendors_mdl
{
	public $TempSession = "";

	function __construct(){	
		if(parent::isGET() || parent::isPOST()){
			$this->SITE_ACCESS_KEY = parent::getVal("stkn");
		}
		
		common::CheckLoginSession();
	}
	
	function getAllVendors(){
		return parent::getAllVendors_f_mdl();
	}
	
	function deleteVendor(){
		if(parent::isPOST()){
			if(!empty(parent::getVal("method")) && parent::getVal("method") == "delete-vendor"){
				$id = parent::getVal("vId");
				
				parent::deleteVendor_f_mdl($id);
			}
		}
	}
	
	function vendorsPagination(){
		if(parent::isPOST()){
			if(parent::getVal("hdn_method") == "vendors_pagination")
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
							vendor_name LIKE '%".trim($keyword)."%'
						)";
				}
				
				$cond_order = 'ORDER BY id DESC';
				if(!empty($sort_field)){
					$cond_order = 'ORDER BY '.$sort_field.' '.$sort_type;
				}
				
				$sql="
						SELECT count(id) as count FROM store_vendors_master WHERE 1
						$cond_keyword
					";
				$all_count = parent::selectTable_f_mdl($sql);
				
				$sql1="
						SELECT id, vendor_name, status FROM store_vendors_master WHERE 1
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
					$html .= '<th>Vendor</th>';
					
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
							
							$html .= '<td>'.$single["vendor_name"].'</td>';
																		
							if($single["status"]){
								$html .= '<td>Active</td>';
							}
							else{
								$html .= '<td>Deactive</td>';
							}
							
							$html .= '<td><button type="button" class="btn btn-primary waves-effect waves-classic btn-addedit-vendor" data-href="sa-addedit-vendors.php?stkn='.parent::getVal("stkn").'&icid='.$single["id"].'">Edit</button></td>';
							// <button type="button" class="btn btn-danger waves-effect waves-classic btn-confirm-delete-vendor" data-id="'.$single["id"].'">Delete</button>			
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
}
?>