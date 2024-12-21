<?php
include_once 'model/sa_addedit_color_family_mdl.php';
$path = preg_replace('/controller(?!.*controller).*/','',__DIR__);
include_once $path . '/libraries/Aws3.php';
$s3Obj = new Aws3;

class sa_addedit_color_family_ctl extends sa_addedit_color_family_mdl
{
	public $TempSession = "";
	public $passedId = 0;

	function __construct(){	
		if(parent::isGET() || parent::isPOST()){
			$this->SITE_ACCESS_KEY = parent::getVal("stkn");
			
			$this->passedId = parent::getVal("cfId");
		}
		
		common::CheckLoginSession();
	}
	
	function getColorFamilyInfo(){
		return parent::getColorFamilyInfo_f_mdl($this->passedId);
	}
	
	function addEditColorFamily(){
		global $s3Obj;
		if(parent::isPOST()){
			if(!empty(parent::getVal("method")) && parent::getVal("method") == "addedit-color-family"){
				
				/* Task 90 start */
				// $this->id = parent::getVal("id");
				// $this->colorFamily = parent::getVal("colorFamily");
				// $this->color_family_color = parent::getVal("colorFamilyColor");

				$this->id                 = $_POST["hdn_id"];
				$this->colorFamily        = $_POST["txt-color-family"];
				$this->color_family_color = $_POST["txt-family-color"];
				$filePath                 = common::IMAGE_UPLOAD_S3_PATH;
				$color_image              = $_FILES['txt-color-image']['name'];/* Task 90 add color_image*/
				$tmp_name                 = $_FILES['txt-color-image']['tmp_name'];
				$mimeType                 = $_FILES['txt-color-image']['type'];
				$extension                = pathinfo($color_image, PATHINFO_EXTENSION);
	            $fileNameToStore          = time().rand(1000,1000000).'.'.$extension;
				$image='';
	            if(!empty($color_image)){
	            	$image = $fileNameToStore;
	            	$s3Obj->uploadFile($filePath.$fileNameToStore,$tmp_name);
	            }
	            elseif($_POST["hdn_id"]>0){
	          		$image = $_POST["old_image"];
	            }
				$this->color_image = $image;
				/* Task 90 end */
				
				$clrExisted = parent::checkColorFamilyCodeExist_f_mdl($this->id,$this->color_family_color);
				if(empty($clrExisted)){
					$slug = strtolower(preg_replace('/[^a-zA-Z0-9\-]/', '',preg_replace('/\s+/', '-', $this->colorFamily) ));
					$this->color_family_color_slug  = $slug;
					parent::addEditColorFamily_f_mdl();
				}else{
					$resultArray["isSuccess"] = "0";
					$resultArray["msg"] = "Color Hex code is already exists. Please try other color-code.";
					common::sendJson($resultArray);
				}
			}
		}
	}
}
?>