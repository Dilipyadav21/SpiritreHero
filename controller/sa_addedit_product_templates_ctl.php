<?php
include_once 'model/sa_fulfillment_colors_mdl.php';
$path = preg_replace('/controller(?!.*controller).*/','',__DIR__);
include_once $path . 'libraries/Aws3.php';
$s3Obj = new Aws3; //Task 59
class sa_addedit_product_templates_ctl extends sa_fulfillment_colors_mdl
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
				case "delete_template_product":
					$this->deleteTemplateProduct();
				break;
				case "delete_template_product_bulk":
					$this->deleteTemplateProductBulk();
				break;
			}
		}
	}

	public function getTemplateName($template_id){
		$sql='SELECT * FROM product_template_master WHERE id="'.$template_id.'"';
		return parent::selectTable_f_mdl($sql);
	}
	
	function TemplateProductPagination(){
        global $s3Obj;
		if(parent::isPOST()){
			if(parent::getVal("hdn_method") == "template_products_pagination")
			{
                $template_id=parent::getVal("template_id");
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
							spm.product_title LIKE '%".trim($keyword)."%' OR
							svm.vendor_name LIKE '%".trim($keyword)."%' OR
							spm.vendor_product_id LIKE '%".trim($keyword)."%' OR
							spvm.sku LIKE '%".trim($keyword)."%' 

						)";
				}
				
				$cond_order = 'ORDER BY ptmd.id DESC';
				if(!empty($sort_field)){
					$cond_order = 'ORDER BY '.$sort_field.' '.$sort_type;
				}
				/*$cond_start_end = '';
				if(isset($this->start_date) && !empty($this->start_date) && isset($this->end_date) && !empty($this->end_date) ){
					$cond_start_end = "AND add_date BETWEEN ".$this->start_date." AND ".$this->end_date."";
				}*/
				$sql="SELECT COUNT(*) AS count FROM (
					SELECT ptmd.id,ptmd.product_templates_master_id,ptmd.store_product_master_id,spm.vendor_product_id,spm.product_title,spvm.image,spvm.sku,svm.vendor_name  FROM product_templates_master_details as ptmd INNER JOIN store_product_master as spm ON spm.id=ptmd.store_product_master_id AND spm.is_deleted='0' INNER JOIN store_product_variant_master as spvm ON spvm.store_product_master_id=spm.id AND spvm.is_ver_deleted='0' LEFT JOIN store_vendors_master as svm ON svm.id=spm.vendor_id WHERE product_templates_master_id='".$template_id."' 
					$cond_keyword
                    group by spm.id ) AS subquery
				";
				$all_count = parent::selectTable_f_mdl($sql);
				
				$sql1="
						SELECT ptmd.id,ptmd.product_templates_master_id,ptmd.store_product_master_id,spm.vendor_product_id,spm.product_title,spm.vendor_id,spvm.image,spvm.sku,svm.vendor_name FROM product_templates_master_details as ptmd INNER JOIN store_product_master as spm ON spm.id=ptmd.store_product_master_id AND spm.is_deleted='0' INNER JOIN store_product_variant_master as spvm ON spvm.store_product_master_id=spm.id AND spvm.is_ver_deleted='0' LEFT JOIN store_vendors_master as svm ON svm.id=spm.vendor_id
						WHERE product_templates_master_id='".$template_id."' 
						$cond_keyword
                        group by spm.id
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
					$html .= '<th>Vendor Product Id</th>';
					$html .= '<th>Product Image</th>';
					$html .= '<th>Product Name</th>';
					$html .= '<th>Vendor</th>';
					$html .= '<th>Actions</th>';
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
							if($single["vendor_product_id"]){
								$html .= '<td>'.$single["vendor_product_id"].'</td>';
							}else{
								$html .= '<td></td>';
							}
                            $html .= '<td><img class="adj-img-sz" src="'.$s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH.$single['image']).'"/></td>';
							$html .= '<td>'.$single["product_title"].'</td>';	
							$html .= '<td>'.$single["vendor_name"].'</td>';	
							
							$html .= '<td><button type="button" class="btn btn-danger waves-effect waves-classic btn-delete-template-prod" data-id="'.$single["id"].'">Remove</button></td>';
											
							$html .= '</tr>';	
							$sr++;
						}
					}
					else{
						$html .= '<tr>';
						$html .= '<td colspan="5" align="center">No Record Found</td>';
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

	function deleteTemplateProduct(){
		if (parent::isPOST()) {
			if (!empty(parent::getVal("method")) && parent::getVal("method") == "delete_template_product") {
				$delete_template_id = parent::getVal("delete_template_details_id");
				parent::deleteTable_f_mdl('product_templates_master_details', 'id =' . $delete_template_id);
				$resultArray = array();
				$resultArray["isSuccess"] = "TRUE";
				$resultArray["msg"] = "Template product removed successfully.";
				common::sendJson($resultArray);
			}
		}
	}

	function deleteTemplateProductBulk(){
		if (parent::isPOST()) {
			if (!empty(parent::getVal("method")) && parent::getVal("method") == "delete_template_product_bulk") {
				$delete_template_details_ids = parent::getVal("delete_template_details_ids");
				foreach ($delete_template_details_ids as $values) {
					parent::deleteTable_f_mdl('product_templates_master_details', 'id =' . $values);
				}
				$resultArray = array();
				$resultArray["isSuccess"] = "TRUE";
				$resultArray["msg"] = "Template product removed successfully.";
				common::sendJson($resultArray);
			}
		}
	}
	
}
?>
