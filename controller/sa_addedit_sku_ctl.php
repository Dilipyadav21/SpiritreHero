<?php
include_once 'model/sa_addedit_sku_mdl.php';
$path = preg_replace('/controller(?!.*controller).*/','',__DIR__);
include_once $path . '/libraries/Aws3.php';

class sa_addedit_sku_ctl extends sa_addedit_sku_mdl
{
	public $TempSession = "";
	function __construct()
	{
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
				case "add_edit_sku":
					$this->addEditSku();
                break;
				case "checkProduct":
					$this->checkProduct();
                break;
			}
        }
    }
	
	function getSkuInfo($id){
		return parent::getSkuInfo_f_mdl($id);
	}

    function getAlternateSkuInfo($id){
		return parent::getAlternateSkuInfo_f_mdl($id);
	}

    function getProductColor(){
        $sql1="SELECT id, product_color,product_color_name, color_family, status FROM store_product_colors_master WHERE 1 ";
		$all_color = parent::selectTable_f_mdl($sql1);
        return $all_color;
    }

	function getProductSize(){
        $sql_size="SELECT id, size FROM size_master WHERE 1 ";
		$all_size = parent::selectTable_f_mdl($sql_size);
        return $all_size;
    }
	
	function addEditSku(){
		if(parent::isPOST()){
			if(!empty(parent::getVal("method")) && parent::getVal("method") == "add_edit_sku"){
                
				$this->id  			= parent::getVal("hdn_id");
				$this->sku 			= trim(parent::getVal("sku"));
				$this->product_title 			= trim(parent::getVal("product_title"));
				$this->alternatesku = parent::getVal("alternatesku");
				echo parent::addEditSku_f_mdl();
			}
		}
	}

	function checkProduct(){
		if(parent::isPOST()){
			if(!empty(parent::getVal("method")) && parent::getVal("method") == "checkProduct"){
				$sku = parent::getVal("sku");
				$resultArray = array();
				$sqlsku="
					SELECT sku FROM store_product_variant_master WHERE sku='".$sku."'
				";
				$all_sku = parent::selectTable_f_mdl($sqlsku);
				if(empty($all_sku)){
					$resultArray["isSuccess"] = "0";
					$resultArray["msg"] = "SKU not match any product.";
				}else{
					$sql="SELECT DISTINCT(spm.product_title),spm.id FROM store_product_variant_master as spvm INNER JOIN store_product_master as spm ON spm.id= spvm.store_product_master_id  WHERE spvm.sku = '".$sku."' AND spm.is_deleted='0' ";
					$productData = parent::selectTable_f_mdl($sql);
					$dropdownHtml = '';
					$dropdownHtml .= '<option value="">Please Select</option>';
					foreach($productData as $data){
						$dropdownHtml .= '<option value="'.$data['id'].'">'.$data['product_title'].'</option>';
					}
					$resultArray["isSuccess"] = "1";
					$resultArray["dropdownHtml"] = $dropdownHtml;
				}
				echo json_encode($resultArray);die;
			}
		} 
	}
}
?>