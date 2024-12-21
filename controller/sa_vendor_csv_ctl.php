<?php
include_once 'model/sa_vendor_csv_mdl.php';
$path = preg_replace('/controller(?!.*controller).*/','',__DIR__);
include_once $path . '/libraries/Aws3.php';
$login_user_email="";
if(isset($_SESSION['user_email']) && $_SESSION['user_email'] != "") {
	$login_user_email=trim($_SESSION['user_email']);
}
class sa_vendor_csv_ctl extends sa_vendor_csv_mdl
{
	public $TempSession = "";

	function __construct(){	
		if(parent::isGET() || parent::isPOST()){
			$this->SITE_ACCESS_KEY = parent::getVal("stkn");
		}
		
		common::CheckLoginSession();
	}
	
	function VendorProductCSVPagination(){
		if(parent::isPOST()){
			if(parent::getVal("hdn_method") == "vendor_prod_csv_pagination")
			{
				$s3Obj = new Aws3;
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
						csv_file LIKE '%".trim($keyword)."%' OR
						uploaded_by LIKE '%".trim($keyword)."%'
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
						SELECT count(*) as count
						FROM vendor_product_csv
						WHERE status = 1
						$cond_keyword
					";
				$all_count = parent::selectTable_f_mdl($sql);
				
				$sql1="
					SELECT id, csv_file, status, created_on, created_on_ts,uploaded_by FROM vendor_product_csv WHERE status = '1'
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
					$html .= '<th>File Name</th>';
					$html .= '<th>Uploaded By</th>';
					$html .= '<th>Date</th>';
					$html .= '<th>Download</th>';
					$html .= '</tr>';
					$html .= '</thead>';
					$html .= '<tbody>';

					if(!empty($all_list))
					{
						$sr = $sr_start;
						foreach($all_list as $single){
							$fileUrl = $s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH.$single["csv_file"]);
							if(!$s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH.$single["csv_file"])){
								$fileUrl = $single["csv_file"];
							}
							$html .= '<tr>';
							$html .= '<td>'.$sr.'</td>';
							$html .= '<td>'.$single["csv_file"].'</td>';
							$html .= '<td>'.$single["uploaded_by"].'</td>';
							$html .= '<td>'.date('m/d/Y h:i A',$single["created_on_ts"]).'</td>';
							$html .= '<td>						   
										<a href="'.$fileUrl.'" target="_blank" class="btn btn-info waves-effect waves-classic" download>Download</a>
									  </td>';
							
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
	
    function addVendorProdCsvFile(){
		if(parent::isPOST()){
			if(!empty(parent::getVal("add_vendor_product_csv")) && parent::getVal("add_vendor_product_csv") == "add_vendorProd_csvFile"){
				$csvFile = "";
				$s3Obj = new Aws3;
				global $login_user_email;
				#region - Check Image Posted Or Not
                if(isset($_FILES['upload_file_fe']) && !empty($_FILES['upload_file_fe']))
                {

					$fileUploadPath = "image_uploads/";
					$tmpFileExtention = ".csv";
					
					$mimeType = $_FILES['upload_file_fe']['type'];
					$fileName = $_FILES['upload_file_fe']['name'];
					$tempfileName = $_FILES['upload_file_fe']['tmp_name'];
					
					#region - Get Uploaded File's Extension
					$fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
					#endregion

					$fileName = explode('.', $fileName);/* Task 103 add new line for fileName */
					#region - Can Upload Same Image Using Rand Function
					// $tempFinalfileName = time().rand(1000,1000000).$tmpFileExtention;
					$tempFinalfileName = $fileName[0].'_'.time().rand(1000,1000000).$tmpFileExtention;/* Task 103 add file name */
					#endregion
					
					$validImgExtArray = explode(",", common::VALID_CSV_EXTENSIONS);
					
					#region - Check's Valid Format
					if(in_array($fileExt, $validImgExtArray)){ 
						$fileUploadPath = $fileUploadPath.strtolower($tempFinalfileName);
					}
					#endregion
					
					#region - Upload File To Dir
					// if(move_uploaded_file($tempfileName, $fileUploadPath))
					if($s3Obj->uploadFile($fileUploadPath,$tempfileName,$mimeType))
					{
						$csvFile = $tempFinalfileName;
					}
					#endregion

                    $this->csvFile = $csvFile;
                    $getInsertedInfo = parent::addVendorProductCSV_f_mdl($login_user_email);
                    
                    sleep(1);
                    $getInsertedId = $getInsertedInfo['master_id'];
                    $getFileInfo = parent::getFileInfo_f_mdl($getInsertedId);
                    
                    $fileName = $getFileInfo[0]['csv_file'];
                    $filePath = 'image_uploads/'.$fileName;
                    $filePath = $s3Obj->getAwsUrl($filePath);
                    $file = fopen($filePath, "r");
                    $variantCount = 0;
                    $isAnyErrorFound = '0';
                    while (($csvData = fgetcsv($file, 10000, ",")) !== FALSE)
                    {	
                        if($csvData[0] != ''){
                            $insertCSVData = [
                                'catalog_product_id'		 => trim($csvData[0]),
                                'catalog_product_name'		 => trim($csvData[1]),
                                'color_name'				 => trim($csvData[2]),
                                'size'						 => trim($csvData[3]),
                                'sku'						 => trim($csvData[4]),
                                'gtin'						 => trim($csvData[5]),
                                'carolina_made_part_id'		 => trim($csvData[6]),
                                'sanMar_part_id'			 => trim($csvData[7]),
                                'ss_activewear_sku'			 => trim($csvData[8]),
                                'alpha_broder_part_id'		 => trim($csvData[9]),
                                'fulfillengine_price'		 => trim($csvData[10]),
                                'ImageType'					 => trim($csvData[11]),
                                'fulfillengine_image'		 => trim($csvData[12]),
                                'printing_methods'			 => trim($csvData[13]),
                                'print_locations'			 => trim($csvData[14]),
                                'front_width'				 => trim($csvData[15]),
                                'front_height'				 => trim($csvData[16]),
                                'back_width'				 => trim($csvData[17]),
                                'back_height'				 => trim($csvData[18]),
                                'left_chest_width'			 => trim($csvData[19]),
                                'left_chest_height'			 => trim($csvData[20]),
                                'right_chest_width'			 => trim($csvData[21]),
                                'right_chest_height'		 => trim($csvData[22]),
                                'left_sleeve_short_width'	 => trim($csvData[23]),
                                'left_sleeve_short_height'	 => trim($csvData[24]),
                                'right_sleeve_short_width'	 => trim($csvData[25]),
                                'right_sleeve_short_height'	 => trim($csvData[26]),
                                'left_sleeve_long_width'	 => trim($csvData[27]),
                                'left_sleeve_long_height'	 => trim($csvData[28]),
                                'right_sleeve_long_width'	 => trim($csvData[29]),
                                'right_sleeve_long_height'	 => trim($csvData[30]),
                                'cap_front_width'			 => trim($csvData[31]),
                                'cap_front_height'			 => trim($csvData[32]),
                                'cap_front_left_width'		 => trim($csvData[33]),
                                'cap_front_left_height'		 => trim($csvData[34]),
                                'cap_front_right_width'		 => trim($csvData[35]),
                                'cap_front_right_height'	 => trim($csvData[36]),
                                'left_hip_width'			 => trim($csvData[37]),
                                'left_hip_height'			 => trim($csvData[38]),
                                'right_hip_width'			 => trim($csvData[39]),
                                'right_hip_height'			 => trim($csvData[40]),
                                'middle_front_pocket_width'	 => trim($csvData[41]),
                                'middle_front_pocket_height' => trim($csvData[42]),
                                'left_leg_width'			 => trim($csvData[43]),
                                'left_leg_height'			 => trim($csvData[44]),
                                'right_leg_width'			 => trim($csvData[45]),
                                'right_leg_height'			 => trim($csvData[46]),
                                'on_left_pocket_width'		 => trim($csvData[47]),
                                'on_left_pocket_height'		 => trim($csvData[48]),
                                'bottom_front_pocket_width'	 => trim($csvData[49]),
                                'bottom_front_pocket_height' => trim($csvData[50]),
                                'bottom_center_height'		 => trim($csvData[51]),
                                'bottom_center_width'		 => trim($csvData[52]),
                                'corner_width'				 => trim($csvData[53]),
                                'corner_height'				 => trim($csvData[54]),
                                'drinkware_front_width'		 => trim($csvData[55]),
                                'drinkware_front_height'	 => trim($csvData[56]),
                                'top_center_width'			 => trim($csvData[57]),
                                'top_center_height'			 => trim($csvData[58]),
								'product_category'			 => trim($csvData[59]),
								'product_sub_category'		 => trim($csvData[60]),
								'product_status'		     => trim($csvData[61])
                            ];
                            parent::insertTable_f_mdl('fulfillengine_products_master', $insertCSVData);
                            $variantCount++;  
                        }
                    }
                    fclose($file);
                }else if(isset($_FILES['upload_file_sanmar']) && !empty($_FILES['upload_file_sanmar'])){

					$fileUploadPath = "image_uploads/";
					$tmpFileExtention = ".csv";
					
					$mimeType = $_FILES['upload_file_sanmar']['type'];
					$fileName = $_FILES['upload_file_sanmar']['name'];
					$tempfileName = $_FILES['upload_file_sanmar']['tmp_name'];
					
					#region - Get Uploaded File's Extension
					$fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
					#endregion

					$fileName = explode('.', $fileName);/* Task 103 add new line for fileName */
					#region - Can Upload Same Image Using Rand Function
					// $tempFinalfileName = time().rand(1000,1000000).$tmpFileExtention;
					$tempFinalfileName = $fileName[0].'_'.time().rand(1000,1000000).$tmpFileExtention;/* Task 103 add file name */
					#endregion
					
					$validImgExtArray = explode(",", common::VALID_CSV_EXTENSIONS);
					
					#region - Check's Valid Format
					if(in_array($fileExt, $validImgExtArray)){ 
						$fileUploadPath = $fileUploadPath.strtolower($tempFinalfileName);
					}
					#endregion
					
					#region - Upload File To Dir
					// if(move_uploaded_file($tempfileName, $fileUploadPath))
					if($s3Obj->uploadFile($fileUploadPath,$tempfileName,$mimeType))
					{
						$csvFile = $tempFinalfileName;
					}
					#endregion

                    $this->csvFile = $csvFile;
                    $getInsertedInfo = parent::addVendorProductCSV_f_mdl($login_user_email);
                    
                    sleep(1);
                    $getInsertedId = $getInsertedInfo['master_id'];
                    $getFileInfo = parent::getFileInfo_f_mdl($getInsertedId);
                    
                    $fileName = $getFileInfo[0]['csv_file'];
                    $filePath = 'image_uploads/'.$fileName;
                    $filePath = $s3Obj->getAwsUrl($filePath);
                    $file = fopen($filePath, "r");
                    $variantCount = 0;
                    $isAnyErrorFound = '0';
                    while (($csvData = fgetcsv($file, 10000, ",")) !== FALSE)
                    {	
                        if($csvData[0] != ''){
                            $insertCSVData = [
                                'id'							=> trim($csvData[0]),
                                'unique_key'					=> trim($csvData[1]),
                                'product_title'					=> trim($csvData[2]),
                                'product_description'			=> trim($csvData[3]),
                                'style'							=> trim($csvData[4]),
                                'available_sizes'				=> trim($csvData[5]),
                                'brand_logo_image'				=> trim($csvData[6]),
                                'thumbnail_image'				=> trim($csvData[7]),
                                'color_swatch_image'			=> trim($csvData[8]),
                                'product_image'					=> trim($csvData[9]),
                                'spec_sheet'					=> trim($csvData[10]),
                                'front_flat'					=> trim($csvData[11]),
                                'back_flat'						=> trim($csvData[12]),
                                'front_model'					=> trim($csvData[13]),
                                'back_model'					=> trim($csvData[14]),
                                'side_model'					=> trim($csvData[15]),
                                'three_q_model'					=> trim($csvData[16]),
                                'price_text'					=> trim($csvData[17]),
                                'color_name'					=> trim($csvData[18]),
                                'color_code'					=> trim($csvData[19]),
                                'color_square_image'			=> trim($csvData[20]),
                                'color_product_image'			=> trim($csvData[21]),
                                'color_product_image_thumbnail'	=> trim($csvData[22]),
                                'size'							=> trim($csvData[23]),
                                'piece_weight'					=> trim($csvData[24]),
                                'piece_price'					=> trim($csvData[25]),
                                'dozen_price'					=> trim($csvData[26]),
                                'case_price'					=> trim($csvData[27]),
                                'piece_sale_price'				=> trim($csvData[28]),
                                'dozen_sale_price'				=> trim($csvData[29]),
                                'case_sale_price'				=> trim($csvData[30]),
                                'sale_start_date'				=> trim($csvData[31]),
                                'sale_end_date'					=> trim($csvData[32]),
                                'case_size'						=> trim($csvData[33]),
                                'inventory_key'					=> trim($csvData[34]),
                                'size_index'					=> trim($csvData[35]),
                                'catelog_color'					=> trim($csvData[36]),
                                'price_code'					=> trim($csvData[37]),
                                'product_status'				=> trim($csvData[38]),
                                'title_image'					=> trim($csvData[39]),
                                'brand_name'					=> trim($csvData[40]),
                                'keywords'						=> trim($csvData[41]),
                                'category'						=> trim($csvData[42])
                            ];
                            $rrr=parent::insertTable_f_mdl('sanmar_products_master', $insertCSVData);

                            $variantCount++;  
                        }
                    }
                    fclose($file);
                }
				
				$resultResp = array();
				
				if($isAnyErrorFound == '0'){
					$resultResp["isSuccess"] = "1";
					$resultResp["msg"] = "Changes saved successfully.";
				}
				else{
					$resultResp["isSuccess"] = "0";
					$resultResp["msg"] = "Oops! there is some issue during insert. Please try again.";
				}
				
				common::sendJson($resultResp);
				
			}
		}
	}
}
?>