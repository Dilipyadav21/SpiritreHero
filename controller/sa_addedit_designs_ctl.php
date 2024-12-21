<?php
include_once 'model/sa_addedit_designs_mdl.php';

$path = preg_replace('/controller(?!.*controller).*/','',__DIR__);
include_once $path . 'libraries/Aws3.php';

class sa_addedit_designs_ctl extends sa_addedit_designs_mdl
{
	public $TempSession = "";
	public $passedId = 0;

	function __construct(){	
		if(parent::isGET() || parent::isPOST()){
			$this->SITE_ACCESS_KEY = parent::getVal("stkn");
			
			$this->passedId = parent::getVal("did");
		}
		
		common::CheckLoginSession();
	}
	
	function getDesignInfo(){
		return parent::getDesignInfo_f_mdl($this->passedId);
	}
	
	function addEditDesigns(){
		if(parent::isPOST()){
			if(!empty(parent::getVal("hdn_method")) && parent::getVal("hdn_method") == "addedit-designs"){
                $s3Obj = new Aws3;
				$productImage = "";
				
				#region - Check Image Posted Or Not
				if($_FILES['txt-product-file']){
					$imgUploadPath = "image_uploads/";

                    $mimeType = $_FILES['txt-product-file']['type'];
					$imageName = $_FILES['txt-product-file']['name'];
					$tempImageName = $_FILES['txt-product-file']['tmp_name'];
					
					#region - Get Uploaded File's Extension
					$imageExt = strtolower(pathinfo($imageName, PATHINFO_EXTENSION));
					#endregion
					
					#region - Can Upload Same Image Using Rand Function
					$tempFinalImageName = rand(1000,1000000).$imageName;
					#endregion
					
					$validImgExtArray = explode(",", common::VALID_IMAGE_EXTENSIONS);
					
					#region - Check's Valid Format
					if(in_array($imageExt, $validImgExtArray)){ 
						/* Task 17 start*/
						//$imgUploadPath = $imgUploadPath.strtolower($tempFinalImageName);
						$imgUploadPath = $imgUploadPath.$tempFinalImageName;
						/* Task 17 end*/
					}
					#endregion
					
					#region - Upload File To Dir
//                    if(move_uploaded_file($tempImageName, $imgUploadPath))
                    if($s3Obj->uploadFile($imgUploadPath,$tempImageName,$mimeType))
					{
						$productImage = $tempFinalImageName;
					}
					#endregion
				}
				#endregion
				
				$this->id = parent::getVal("hdn_id");
				$this->product_title = parent::getVal("txt-product-name");
				$this->product_image = $productImage;
				$this->status = parent::getVal("drp-status");
				
				if($this->id > 0 && $this->product_image != ""){
					parent::updateProductWithImage_f_mdl();
				}
				else if($this->id > 0 && $this->product_image == ""){
					parent::updateProductWithoutImage_f_mdl();
				}
				else{
					parent::insertProductTypeDetail_f_mdl();
				}
			}
		}
	}
}
?>