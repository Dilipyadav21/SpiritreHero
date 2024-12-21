<?php
include_once 'model/sa_product_colors_mdl.php';

class sa_product_colors_ctl extends sa_product_colors_mdl
{
	public $TempSession = "";

	function __construct(){	
		if(parent::isGET() || parent::isPOST()){
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
        		case "updatecolorfamily":
					$this->updateColorFamily();
                break;
                
          	}
        }
    }
	
	function getAllColors(){
		return parent::getAllColors_f_mdl();
	}
	
	function deleteColor(){
		if(parent::isPOST()){
			if(!empty(parent::getVal("method")) && parent::getVal("method") == "delete-ink-col"){
				
				$id = parent::getVal("pcId");
				
				parent::deleteColor_f_mdl($id);
			}
		}
	}
	
	function prodColorsPagination(){
		if(parent::isPOST()){
			if(parent::getVal("hdn_method") == "prodcolors_pagination")
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

				/*if(isset($_POST['date_range_filter']) && !empty($_POST['date_range_filter'])){
					$dr_arr = explode(' To ',$_POST['date_range_filter']);
					if(isset($dr_arr[0]) && !empty($dr_arr[0]) && isset($dr_arr[1]) && !empty($dr_arr[1]) ){
						$start_ts = strtotime($dr_arr[0].' 0:0');
						$end_ts = strtotime($dr_arr[1].' 23:59');
						$User->set_start_date($start_ts);
						$User->set_end_date($end_ts);
					}
				}*/

				$cond_keyword = '';
				if(isset($keyword) && !empty($keyword)){
					$cond_keyword = "AND (
							product_color_name LIKE '%".trim($keyword)."%' OR product_color LIKE '%".trim($keyword)."%' OR color_family LIKE '%".trim($keyword)."%'
						)";
				}
				
				$cond_order = 'ORDER BY product_color = "" DESC';
				if(!empty($sort_field)){
					$cond_order = 'ORDER BY '.$sort_field.' '.$sort_type;
				}
				/*$cond_start_end = '';
				if(isset($this->start_date) && !empty($this->start_date) && isset($this->end_date) && !empty($this->end_date) ){
					$cond_start_end = "AND add_date BETWEEN ".$this->start_date." AND ".$this->end_date."";
				}*/
				$sql="
						SELECT count(id) as count FROM store_product_colors_master WHERE 1
						$cond_keyword
					";
					
				$all_count = parent::selectTable_f_mdl($sql);
				
				$sql1="
						SELECT id,product_color,product_color_name,color_family,status FROM store_product_colors_master WHERE 1
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
					$html .= '<th>Product Color</th>';
					$html .= '<th>Color Family</th>';
					$html .= '<th>Status</th>';
					
					$html .= '<th>Actions</th>';
					
					$html .= '</tr>';
					$html .= '</thead>';

					$html .= '<tbody>';

					/*
						get all color familygetFamilyColorDropdownInfo()
					*/
					$colorFamily = parent::getFamilyColorDropdownInfo_f_mdl();

					if(!empty($all_list))
					{
						$sr = $sr_start;
							foreach($all_list as $single){
							
							$html .= '<tr>';
							
							$html .= '<td>'.$sr.'</td>';
							
							$html .= '<td><div class="col-preview" style="background-color: '.	$single["product_color"].'"></div>'.$single["product_color_name"].' ('.$single["product_color"].')</td>';
							
							$ColorFamilyHtml  = '';
							$ColorFamilyHtml .= '<select  id="color_family'.$single["id"].'" name="color_family'.$single["id"].'" class="color_family_prod form-control" store_prod_color_master_id="'.$single["id"].'">';
							$ColorFamilyHtml .= '<option value="">Select one</option>';
							if($colorFamily){
								foreach ($colorFamily as $familys) {
									$selected = "";
									if($single['color_family'] == $familys['color_family_name']){
										$selected = "selected";
									}
									$ColorFamilyHtml .= '<option value="'.$familys['color_family_name'].'" '.$selected.'>'.$familys['color_family_name'].'</option>';
								}
							}							
							$ColorFamilyHtml .= '</select>';

							$html .= '<td>'.$ColorFamilyHtml.'</td>';
							if($single["status"]){
								$html .= '<td>Active</td>';
							}
							else{
								$html .= '<td>Deactive</td>';
							}
							
							$html .= '<td><button type="button" class="btn btn-primary waves-effect waves-classic btn-addedit-prod-col" data-href="sa-addedit-product-colors.php?stkn='.parent::getVal("stkn").'&pcId='.$single["id"].'">Edit</button><button type="button" class="btn btn-danger waves-effect waves-classic btn-confirm-delete-prod-col" data-id="'.$single["id"].'">Delete</button></td>';
											
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

	function updateColorFamily(){
		if(parent::isPOST()){
			if(!empty(parent::getVal("method")) && parent::getVal("method") == "updatecolorfamily"){
				
				$color_family_name = parent::getVal("color_family_name");
				$store_prod_color_master_id = parent::getVal("store_prod_color_master_id");
				$updateinfo=parent::colorFamilyUpdate_f_mdl($color_family_name,$store_prod_color_master_id);
				common::sendJson($updateinfo);die;
			}
		}
	}
}
?>