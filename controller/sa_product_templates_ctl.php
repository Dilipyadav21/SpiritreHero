<?php
include_once 'model/sa_fulfillment_colors_mdl.php';

class sa_product_templates_ctl extends sa_fulfillment_colors_mdl
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
				case "delete_product_template":
					$this->deleteProductTemplate();
				break;
				case "check_template_name_exist_or_not_save" :
					$this->checkTemplateNameExistOrNotSave();
				break;
				case "check_and_add_new_template" :
					$this->checkAndAddNewTemplate();
				break;
			}
		}
	}
	
	function productTemplatePagination(){
		if(parent::isPOST()){
			if(parent::getVal("hdn_method") == "product_template_pagination")
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
							template_name LIKE '%".trim($keyword)."%'
						)";
				}
				
				$cond_order = 'ORDER BY id DESC';
				if(!empty($sort_field)){
					$cond_order = 'ORDER BY '.$sort_field.' '.$sort_type;
				}
				/*$cond_start_end = '';
				if(isset($this->start_date) && !empty($this->start_date) && isset($this->end_date) && !empty($this->end_date) ){
					$cond_start_end = "AND add_date BETWEEN ".$this->start_date." AND ".$this->end_date."";
				}*/
				$sql="
						SELECT count(id) as count FROM product_template_master WHERE 1
						$cond_keyword
					";
				$all_count = parent::selectTable_f_mdl($sql);
				
				$sql1="
						SELECT id,template_name FROM product_template_master WHERE 1
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
					$html .= '<th>Template Name</th>';
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
							$html .= '<td><input type="text" value="'.$single["template_name"].'" class=" template-input-btn template_name_edit_input_'.$single["id"].'" id="template_name_'.$single["id"].'" name="template_name" autocomplete="off" disabled>
                            <button class="btn btn-primary btn-round btn-sm edit_template_save_btn edit_template_save_'.$single["id"].'" data-toggle="tooltip" data-placement="top" data-trigger="hover" data-template_id="'.$single["id"].'" data-original-title="Save" title="" style="float: right; display:none;">Save</i></button>
                            <button class="btn btn-primary btn-round btn-sm edit_template_btn edit_template_name_'.$single["id"].'" data-toggle="tooltip" data-placement="top" data-trigger="hover" data-template_id="'.$single["id"].'" data-original-title="Edit" title="" style="float: right;display:block;"><i class="fa fa-edit"></i></button></td>';	
							//$html .= '<td>'.$single["template_name"].'</td>';									
							// if($single["status"]){
							// 	$html .= '<td>Active</td>';
							// }else{
							// 	$html .= '<td>Deactive</td>';
							// }
							
							$html .= '<td><button type="button" class="btn btn-primary waves-effect waves-classic btn-addedit-fulfillment-col" data-href="sa-addedit-product-templates.php?stkn=&icid='.$single["id"].'">Edit</button><button type="button" class="btn btn-danger waves-effect waves-classic btn-delete-template" data-id="'.$single["id"].'" style="margin-left: 5px;">Delete</button></td>';
											
							$html .= '</tr>';	
							$sr++;
						}
					}
					else{
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

	function deleteProductTemplate()
	{
		if (parent::isPOST()) {
			if (!empty(parent::getVal("method")) && parent::getVal("method") == "delete_product_template") {
				$delete_template_id = parent::getVal("delete_template_id");
				parent::deleteTable_f_mdl('product_template_master', 'id =' . $delete_template_id);
				$sql = 'SELECT * FROM store_product_master WHERE FIND_IN_SET("'.$delete_template_id.'", product_template_id) > 0';
				$productData = parent::selectTable_f_mdl($sql);
				if(!empty($productData)){
					foreach ($productData as $key => $value) {
						$product_template_id = $value['product_template_id'];
						$product_template_id = explode(',', $product_template_id);
						$product_template_id = array_diff($product_template_id, array($delete_template_id));
						$product_template_id = implode(',', $product_template_id);
						$sqlUpdate = 'UPDATE store_product_master SET product_template_id = "'.$product_template_id.'" WHERE id ="'.$value['id'].'"';
						parent::selectTable_f_mdl($sqlUpdate);
					}

				}
				$resultArray = array();
				$resultArray["isSuccess"] = "TRUE";
				$resultArray["msg"] = "Product template deleted successfully.";
				common::sendJson($resultArray);
			}
		}
	}

	function checkTemplateNameExistOrNotSave(){
		if(parent::isPOST()){
			$resultArray = array();
			$template_name = trim(parent::getVal("template_name"));
			$template_id = trim(parent::getVal("template_id"));
			
			$sql=" SELECT * FROM product_template_master WHERE template_name='".$template_name."' AND id !='".$template_id."' ";
			$templateData = parent::selectTable_f_mdl($sql);
			if(!empty($templateData)){
				$resultArray["isSuccess"] = "0";
				$resultArray["msg"] = "Template name is already exists. Please try another name.";
			}else{

				$update_data = [
					'template_name' => $template_name
				];

				$res=parent::updateTable_f_mdl('product_template_master',$update_data,'id="'.$template_id.'" ');
				$resultArray["isSuccess"] = "1";
				$resultArray["msg"] = "Template updated successfully.";
			}
			common::sendJson($resultArray);
		}
	}

	function checkAndAddNewTemplate(){
		if(parent::isPOST()){
			$resultArray = array();
			$template_name = trim(parent::getVal("template_name"));
			
			$sql=" SELECT * FROM product_template_master WHERE template_name='".$template_name."' ";
			$templateData = parent::selectTable_f_mdl($sql);
			if(!empty($templateData)){
				$resultArray["isSuccess"] = "0";
				$resultArray["msg"] = "Template name is already exists. Please try another name.";
			}else{

				$insert_data = [
					'template_name' => $template_name
				];
				parent::insertTable_f_mdl('product_template_master',$insert_data);
				$resultArray["isSuccess"] = "1";
				$resultArray["msg"] = "Template added successfully.";
			}
			common::sendJson($resultArray);
		}
	}

	
}
?>