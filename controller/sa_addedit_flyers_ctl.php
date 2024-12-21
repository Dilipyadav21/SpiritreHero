<?php
$path = preg_replace('/controller(?!.*controller).*/','',__DIR__);
include_once $path . 'libraries/Aws3.php';

include_once 'model/sa_addedit_flyers_mdl.php';

class sa_addedit_flyers_ctl extends sa_addedit_flyers_mdl
{
	public $TempSession = "";

	function __construct(){	
		if(parent::isGET() || parent::isPOST()){
			$this->SITE_ACCESS_KEY = parent::getVal("stkn");
			
			$this->passedId = parent::getVal("did");
		}
	
		common::CheckLoginSession();
		
	}
	
	function getFlyersInfo(){
		return parent::getFlyersInfo_f_mdl($this->passedId);
	}
	
	function getStoreDropdownInfo(){
		return parent::getStoreDropdownInfo_f_mdl();
	}
	
	function getProductImagesInfo(){
		if(parent::isPOST()){
			if(!empty(parent::getVal("method")) && parent::getVal("method") == "getProductImage")
			{	
				$this->storeId = parent::getVal("storeId");
				
				return parent::getProductImagesInfo_f_mdl($this->storeId);
			}
		}
	}
	
	function getStoreFlashEndDate(){
		if(parent::isPOST()){
			if(!empty(parent::getVal("method")) && parent::getVal("method") == "getFlashEndDate")
			{	
				$this->storeId = parent::getVal("storeId");
				
				return parent::getStoreFlashEndDate_f_mdl($this->storeId);
			}
		}
	}
	
	// function addEditFlyers(){
	// 	if(parent::isPOST()){
	// 		if(!empty(parent::getVal("hdn_method")) && parent::getVal("hdn_method") == "addedit-flyers"){
    //             $s3Obj = new Aws3; //Task 59
	// 			/*
	// 			$flyersPdf = "";
	// 			#region - Check Image Posted Or Not
	// 			if($_FILES['upload_pdf']){
					
	// 				$pdfUploadPath = "image_uploads/";
					
	// 				$pdfName = $_FILES['upload_pdf']['name'];
	// 				$temppdfName = $_FILES['upload_pdf']['tmp_name'];
					
	// 				#region - Get Uploaded File's Extension
	// 				$imageExt = strtolower(pathinfo($pdfName, PATHINFO_EXTENSION));
	// 				#endregion
					
	// 				#region - Can Upload Same Image Using Rand Function
	// 				$tempFinalpdfName = rand(1000,1000000).$pdfName;
	// 				#endregion
					
	// 				$validImgExtArray = explode(",", common::VALID_PDF_EXTENSIONS);
					
	// 				#region - Check's Valid Format
	// 				if(in_array($imageExt, $validImgExtArray)){ 
	// 					$pdfUploadPath = $pdfUploadPath.strtolower($tempFinalpdfName);
	// 				}
	// 				#endregion
					
	// 				#region - Upload File To Dir
	// 				if(move_uploaded_file($temppdfName, $pdfUploadPath)){
	// 					$flyersPdf = $tempFinalpdfName;
	// 				}
	// 				#endregion
	// 			}
	// 			#endregion
	// 			*/
	// 			$image_for_flyer = '';
	// 			$this->id = parent::getVal("hdn_id");
	// 			$this->end_date = parent::getVal("flyer_end_date");
	// 			$this->flyer_title = parent::getVal("flyers_title");
	// 			$this->store_master_id = parent::getVal("select_store");
	// 			$this->selected_image_path = parent::getVal("hdn_img_path");
	// 			//$this->flyer_file = $flyersPdf;
				
	// 			$respData = parent::getDesignLogoInfo_f_mdl($this->store_master_id);
				
	// 			$logo_image_file = '';
	// 			if(!empty($respData)){
	// 				if($respData[0]['is_default']){
	// 					$logo_image_file = $respData[0]['logo_image'];
	// 				}
	// 			}
	// 			//Task 59
	// 			/*if(isset($logo_image_file) && !empty($logo_image_file) && file_exists(common::IMAGE_UPLOAD_URL.$logo_image_file) && !empty($this->selected_image_path) && file_exists(common::IMAGE_UPLOAD_URL.$this->selected_image_path)){
	// 				$image = parent::merge_two_images(common::IMAGE_UPLOAD_URL,$this->selected_image_path,$logo_image_file);
	// 			}else{
	// 				$image = $this->selected_image_path;
	// 			}*/
	// 			if(isset($logo_image_file) && !empty($logo_image_file) && !empty($this->selected_image_path)){
	// 				$image = parent::merge_two_images(common::IMAGE_UPLOAD_S3_PATH,$this->selected_image_path,$logo_image_file);
	// 			}else{
	// 				$image = $this->selected_image_path;
	// 			}
	// 			//Task 59

	// 			if($image!=''){
	// 				$image_for_flyer = $image;
	// 			}
				
	// 			$english_color_pdf = '';
	// 			$english_bw_pdf = '';
	// 			$spanish_color_pdf = '';
	// 			$spanish_bw_pdf = '';
				
	// 			if($image_for_flyer!=''){
	// 				$api_path = '';
	// 				//Task 59
	// 				/* $documentData = json_encode([
	// 					'image'=>common::IMAGE_UPLOAD_URL.$image_for_flyer,
	// 					'date'=>$_POST['flyer_end_date']
	// 				]); */
					
	// 				// Task 87 start Task 90 add store_name in sql
	// 				$qrSql      = "SELECT qr_code_image,store_name FROM store_master WHERE id = '".$this->store_master_id."' ";
	// 				$qrCodeData = parent::selectTable_f_mdl($qrSql);
	// 				$qrCodeImage = '';
	// 				if(!empty($qrCodeData)){
	// 					if(!empty($qrCodeData[0]['qr_code_image'])){
	// 						$qrCodeImage = $s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH.$qrCodeData[0]['qr_code_image']);	
	// 					}else{
	// 						$qrCodeImage = '';
	// 					}
						
	// 					$store_name  = $qrCodeData[0]['store_name'];
	// 				}
	// 				// Task 87 end

	// 				$documentData = json_encode([
	// 					// 'image'=>$s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH.$image_for_flyer),/* Task 90 old */
	// 					'image'      => $image_for_flyer,/* Task 90 new */
	// 					'date'       => $_POST['flyer_end_date'],
	// 					'qr_code'    => $qrCodeImage, // Task 87 add qr_code
	// 					'store_name' => $store_name // Task 90 add store name
	// 				]);
	// 				//Task 59
	// 				/ $template_id = common::PDF_GENERATE_API_FLYER_ENGCLR_TEMPL_ID;
	// 				$pdf_res = parent::get_pdf_by_api(common::PDF_GENERATE_API_KEY,common::PDF_GENERATE_API_SECRET,common::PDF_GENERATE_API_WORKSPACE,$template_id,$documentData,$api_path);
	// 				if(isset($pdf_res['pdf_file']) && !empty($pdf_res['pdf_file'])){
	// 					$english_color_pdf = $pdf_res['pdf_file'];
	// 				}
	// 				$template_id = common::PDF_GENERATE_API_FLYER_ENGBW_TEMPL_ID;
	// 				$pdf_res = parent::get_pdf_by_api(common::PDF_GENERATE_API_KEY,common::PDF_GENERATE_API_SECRET,common::PDF_GENERATE_API_WORKSPACE,$template_id,$documentData,$api_path);
	// 				if(isset($pdf_res['pdf_file']) && !empty($pdf_res['pdf_file'])){
	// 					$english_bw_pdf = $pdf_res['pdf_file'];
	// 				}
	// 				$template_id = common::PDF_GENERATE_API_FLYER_SPACLR_TEMPL_ID;
	// 				$pdf_res = parent::get_pdf_by_api(common::PDF_GENERATE_API_KEY,common::PDF_GENERATE_API_SECRET,common::PDF_GENERATE_API_WORKSPACE,$template_id,$documentData,$api_path);
	// 				if(isset($pdf_res['pdf_file']) && !empty($pdf_res['pdf_file'])){
	// 					$spanish_color_pdf = $pdf_res['pdf_file'];
	// 				}
	// 				$template_id = common::PDF_GENERATE_API_FLYER_SPABW_TEMPL_ID;
	// 				$pdf_res = parent::get_pdf_by_api(common::PDF_GENERATE_API_KEY,common::PDF_GENERATE_API_SECRET,common::PDF_GENERATE_API_WORKSPACE,$template_id,$documentData,$api_path);
	// 				if(isset($pdf_res['pdf_file']) && !empty($pdf_res['pdf_file'])){
	// 					$spanish_bw_pdf = $pdf_res['pdf_file'];
	// 				}
	// 			}
	// 			$this->english_color_pdf = $english_color_pdf;
	// 			$this->english_bw_pdf = $english_bw_pdf;
	// 			$this->spanish_color_pdf = $spanish_color_pdf;
	// 			$this->spanish_bw_pdf = $spanish_bw_pdf;

	// 			if($this->id > 0){
	// 				parent::updateFlyersInfo_f_mdl();
	// 			} else{
	// 				parent::insertFlyersInfo_f_mdl();
	// 			}
	// 		}
	// 	}
	// }
	
}
?>