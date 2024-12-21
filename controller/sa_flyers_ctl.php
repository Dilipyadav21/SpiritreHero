<?php
include_once 'model/sa_flyers_mdl.php';
$path = preg_replace('/controller(?!.*controller).*/','',__DIR__);
include_once $path . 'libraries/Aws3.php';
$s3Obj = new Aws3;

class sa_flyers_ctl extends sa_flyers_mdl
{
	public $TempSession = "";

	function __construct(){	
		if(parent::isGET() || parent::isPOST()){
			$this->SITE_ACCESS_KEY = parent::getVal("stkn");
		}
		
		common::CheckLoginSession();
	}
	
	function getFlyersInfo(){
		return parent::getFlyersInfo_f_mdl();
	}
	
	function deleteDesign(){
		if(parent::isPOST()){
			if(!empty(parent::getVal("method")) && parent::getVal("method") == "delete-flyers"){
				$id = parent::getVal("dId");
				
				parent::deleteDesign_f_mdl($id);
			}
		}
	}
	
	public function get_flyer_data(){
		global $s3Obj;
		if(parent::isPOST()){
			if(!empty(parent::getVal("method")) && parent::getVal("method") == "get_flyer_data")
			{
				if(isset($_POST['store_owner_flyer_id']) && !empty($_POST['store_owner_flyer_id']) &&
				isset($_POST['flyer_language']) && !empty($_POST['flyer_language']) &&
				isset($_POST['flyer_color']) && !empty($_POST['flyer_color'])
				)
				{
					$sql = 'SELECT * FROM `store_owner_flyer`
					WHERE 1
					AND `id` = "'.$_POST['store_owner_flyer_id'].'"
					';
					$list_data = parent::selectTable_f_mdl($sql);
					
					if(!empty($list_data))
					{
						$do_data = [];
						$do_data['end_date'] = $list_data[0]['end_date'];
						$do_data['flyer_title'] = $list_data[0]['flyer_title'];
						$do_data['selected_image_path'] = $s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH.$list_data[0]['selected_image_path']);
						$do_data['site_path'] = '';
						$do_data['main_div_class'] = 'flyer-view';

						$lng = $_POST['flyer_language'];
						$clr = $_POST['flyer_color'];
						
						if($lng=='English' && $clr=='Grayscale'){
							//$u = 'https://'.$_SERVER['HTTP_HOST'].'/spirit-hero-app/store-owners/pages/flyer_template_english_greyscale.php';
							
							$file_name = $list_data[0]['english_bw_pdf'];
						}else if($lng=='English' && $clr=='Color'){
							//$u = 'https://'.$_SERVER['HTTP_HOST'].'/spirit-hero-app/store-owners/pages/flyer_template_english_color.php';
							
							$file_name = $list_data[0]['english_color_pdf'];
							
						}else if($lng=='Spanish' && $clr=='Grayscale'){
							//$u = 'https://'.$_SERVER['HTTP_HOST'].'/spirit-hero-app/store-owners/pages/flyer_template_spanish_greyscale.php';
							
							$file_name = $list_data[0]['spanish_bw_pdf'];
							
						}else if($lng=='Spanish' && $clr=='Color'){
							//$u = 'https://'.$_SERVER['HTTP_HOST'].'/spirit-hero-app/store-owners/pages/flyer_template_spanish_color.php';
							
							$file_name = $list_data[0]['spanish_color_pdf'];
							
						}else{
							//$u = 'https://'.$_SERVER['HTTP_HOST'].'/spirit-hero-app/store-owners/pages/flyer_template_english_greyscale.php';
							
							$file_name = $list_data[0]['english_bw_pdf'];
							
						}
						/*
						$curl = curl_init($u);
						curl_setopt($curl, CURLOPT_POST, true);
						curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($do_data));
						curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
						$divHtml = curl_exec($curl);
						
						$download_link = 'sa-flyers.php?stkn='.$_POST["stkn"].'&action=download_flyer&id='.$_POST['store_owner_flyer_id'].'&lng='.$lng.'&clr='.$clr;

						$divHtml .= '<br>';
						$divHtml .= '<div class="row">';
						$divHtml .= '<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 text-center">';
						$divHtml .= '<a href="'.$download_link.'" target="_blank" class="btn btn-primary download_flyer_btn"><i class="fa fa-download"></i> Download Flyer</button>';
						$divHtml .= '</div>';
						$divHtml .= '</div>';
						$res['SUCCESS'] = 'TRUE';
						$res['MESSAGE'] = '';
						$res['divHtml'] = $divHtml;
						*/
						
						if(!empty($file_name)){
                            $filename = $s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH."flyers/".$file_name);
							$res['SUCCESS'] = 'TRUE';
							
							$res['MESSAGE'] = '';
							$res['file_name'] = $filename;
						    @file_put_contents(common::IMAGE_UPLOAD_S3_PATH."flyers/".$file_name, @file_get_contents($filename));
							$res['template_html'] = '<iframe src="'.common::IMAGE_UPLOAD_S3_PATH."flyers/".$file_name.'" frameborder="0" style="width:100%; height:100%;"></iframe>';
							$res['file_name'] = common::IMAGE_UPLOAD_S3_PATH."flyers/".$file_name;
						}
						else{
							$res['SUCCESS'] = 'FALSE';
							$res['MESSAGE'] = 'Flyer is not available.';
							$res['file_name'] = '';
							$res['template_html'] = '';
						}
					}
					else
					{
						$res['SUCCESS'] = 'FALSE';
						$res['MESSAGE'] = 'Flyer is not found.';
					}
				}
				else
				{
					$res['SUCCESS'] = 'FALSE';
					$res['MESSAGE'] = 'Invalid request';
				}
				echo json_encode($res,1);
				exit;
			}
		}
	}
	
	public function download_flyer(){
		global $s3Obj;
		if(parent::isGET()){
			if(!empty(parent::getVal("action")) && parent::getVal("action") == "download_flyer")
			{
				if(isset($_GET['id']) && !empty($_GET['id']) &&
					isset($_GET['lng']) && !empty($_GET['lng']) &&
					isset($_GET['clr']) && !empty($_GET['clr'])
				){
					$sql = 'SELECT * FROM `store_owner_flyer`
					WHERE 1
					AND `id` = "'.$_GET['id'].'"
					';
					$list_data = parent::selectTable_f_mdl($sql);
					if(!empty($list_data)){
						$do_data = [];
						$do_data['end_date'] = $list_data[0]['end_date'];
						$do_data['flyer_title'] = $list_data[0]['flyer_title'];
						$do_data['selected_image_path'] = $s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH.$list_data[0]['selected_image_path']);
						$do_data['site_path'] = '';

						$lng = $_GET['lng'];
						$clr = $_GET['clr'];
						if($lng=='English' && $clr=='Grayscale'){
							$u = 'https://'.$_SERVER['HTTP_HOST'].'/store-owners/pages/flyer_template_english_greyscale.php';
						}else if($lng=='English' && $clr=='Color'){
							$u = 'https://'.$_SERVER['HTTP_HOST'].'/store-owners/pages/flyer_template_english_color.php';
						}else if($lng=='Spanish' && $clr=='Grayscale'){
							$u = 'https://'.$_SERVER['HTTP_HOST'].'/store-owners/pages/flyer_template_spanish_greyscale.php';
						}else if($lng=='Spanish' && $clr=='Color'){
							$u = 'https://'.$_SERVER['HTTP_HOST'].'/store-owners/pages/flyer_template_spanish_color.php';
						}else{
							$u = 'https://'.$_SERVER['HTTP_HOST'].'/store-owners/pages/flyer_template_english_greyscale.php';
						}

						$curl = curl_init($u);
						curl_setopt($curl, CURLOPT_POST, true);
						curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($do_data));
						curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
						$divHtml = curl_exec($curl);

						require_once("html-templates/custom/dompdf/vendor/autoload.php");

						$dompdf = new \Dompdf\Dompdf();
						$dompdf->loadHtml($divHtml);
						$dompdf->setPaper('A4', 'portrait');
						$dompdf->render();
						$dompdf->stream('spirithero-flyer-'.time().'.pdf');


					}else{
						$_SESSION['SUCCESS'] = 'FALSE';
						$_SESSION['MESSAGE'] = 'Flyer is not found.';
						header('location:index.php');
					}
				}else{
					$_SESSION['SUCCESS'] = 'FALSE';
					$_SESSION['MESSAGE'] = 'Invalid request.';
					header('location:index.php');
				}
			}
		}
	}
	
	function flyersPagination(){
		if(parent::isPOST()){
			if(parent::getVal("hdn_method") == "flyers_pagination")
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
							sof.flyer_title LIKE '%".trim($keyword)."%' OR
							sm.store_name LIKE '%".trim($keyword)."%' OR 
							sof.end_date LIKE '%".trim($keyword)."%'
						)";
				}
				$cond_order = 'ORDER BY sof.id DESC';
				if(!empty($sort_field)){
					$cond_order = 'ORDER BY '.$sort_field.' '.$sort_type;
				}
				/*$cond_start_end = '';
				if(isset($this->start_date) && !empty($this->start_date) && isset($this->end_date) && !empty($this->end_date) ){
					$cond_start_end = "AND add_date BETWEEN ".$this->start_date." AND ".$this->end_date."";
				}*/
				$sql="
						SELECT count(sof.id) as count
						FROM store_owner_flyer sof
						INNER JOIN store_master sm ON sof.store_master_id = sm.id
						WHERE sof.status = 1
						$cond_keyword
					";
				$all_count = parent::selectTable_f_mdl($sql);

				$sql1="
						SELECT sof.id,sm.store_name,sof.end_date, sof.flyer_title,sof.selected_image_path FROM store_owner_flyer sof INNER JOIN store_master sm ON sof.store_master_id = sm.id WHERE sof.status = '1'
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
					
					$html .= '<th>Stores Name</th>';
					$html .= '<th>Flash End Date</th>';
					$html .= '<th>Title</th>';
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
							
							$html .= '<td>'.$single["store_name"].'</td>';
							$html .= '<td>'.$single["end_date"].'</td>';
							$html .= '<td>'.$single["flyer_title"].'</td>';
							
							$html .= '<td>
								<button data-href="sa-addedit-flyers.php?stkn='.parent::getVal("stkn").'&did='.$single["id"].'" type="button" class="btn btn-primary waves-effect waves-classic btn-addedit-org">Edit</button>
								
							   <button type="button" class="btn btn-danger waves-effect waves-classic btn-confirm-delete-org flyers_info_delete" data-id='.$single["id"].' >Delete</button>
							   
							   <button type="button" class="btn btn-info get_flyer_data_btn" data-id="'.$single['id'].'">Preview</button>
							   
							</td>';
							
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
}
?>