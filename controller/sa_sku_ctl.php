<?php
include_once 'model/sa_sku_mdl.php';

class sa_sku_ctl extends sa_sku_mdl
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
				case "delete-sku":
					$this->deleteSku();
                break;
			}
        }
    }
	
	function getAllSku(){
		return parent::getAllSku_f_mdl();
	}
	
    function deleteSku()
	{
		if (parent::isPOST()) {
			if (!empty(parent::getVal("method")) && parent::getVal("method") == "delete-sku") {
				$id = parent::getVal("vId");
				parent::deleteTable_f_mdl('varient_sku_master', 'id =' . $id);
				parent::deleteTable_f_mdl('varient_alternate_sku_master', 'varient_sku_master_id =' . $id);
				$resultArray = array();
				$resultArray["isSuccess"] = "TRUE";
				$resultArray["msg"] = "SKU deleted successfully.";
				common::sendJson($resultArray);
			}
		}
	}
	
	function skuPagination(){
		if(parent::isPOST()){
			if(parent::getVal("hdn_method") == "varient_sku_pagination")
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
				
				$cond_keyword = '';
				if(isset($keyword) && !empty($keyword)){
					$cond_keyword = "AND (
						vsm.sku LIKE '%".trim($keyword)."%' OR
						spm.product_title LIKE '%".trim($keyword)."%'
					)";
				}
				
				$cond_order = 'ORDER BY id DESC';
				if(!empty($sort_field)){
					$cond_order = 'ORDER BY '.$sort_field.' '.$sort_type;
				}
				
				$sql=" SELECT DISTINCT count(DISTINCT(vsm.id)) as count,vsm.sku,vsm.product_id,spm.product_title,vsm.created_on,spvm.sku as spvmsku,(SELECT GROUP_CONCAT(' ',sku) FROM varient_alternate_sku_master WHERE varient_sku_master_id = vsm.id ) as alternate_sku FROM varient_sku_master as vsm INNER JOIN store_product_variant_master as spvm ON spvm.sku= vsm.sku INNER JOIN store_product_master as spm ON vsm.product_id=spm.id WHERE 1
				$cond_keyword ";
				$all_count = parent::selectTable_f_mdl($sql);
				$sql1="
					SELECT DISTINCT vsm.id,vsm.sku,vsm.product_id,spm.product_title,vsm.created_on,spvm.sku as spvmsku,(SELECT GROUP_CONCAT(' ',sku) FROM varient_alternate_sku_master WHERE varient_sku_master_id = vsm.id ) as alternate_sku FROM varient_sku_master as vsm INNER JOIN store_product_variant_master as spvm ON spvm.sku= vsm.sku INNER JOIN store_product_master as spm ON vsm.product_id=spm.id  WHERE 1
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
					$html .= '<th>SKU</th>';
					$html .= '<th>Product Title</th>';
					$html .= '<th>Alternate SKU</th>';
					$html .= '<th>Created Date</th>';
					$html .= '<th>Action</th>';
					$html .= '</tr>';
					$html .= '</thead>';
					$html .= '<tbody>';
					if(!empty($all_list))
					{
						$sr = $sr_start;
							foreach($all_list as $single){
							$html .= '<tr>';
							$html .= '<td>'.$sr.'</td>';
							$html .= '<td>'.$single["sku"].'</td>';
							$html .= '<td>'.$single["product_title"].'</td>';
							$html .= '<td>'.$single["alternate_sku"].'</td>';
							$html .= '<td>'.date('m/d/Y h:i A', strtotime($single["created_on"])).'</td>';
							$html .= '<td><button type="button" class="btn btn-primary waves-effect waves-classic btn-addedit-sku" data-href="sa-addedit-sku.php?id='.$single["id"].'">Edit</button><button type="button" class="btn btn-danger waves-effect waves-classic btn-confirm-delete-sku" data-id="'.$single["id"].'">Delete</button></td>';											
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
}
?>