<?php
include_once 'model/sa_stores_mdl.php';

class sa_customcat_product_check_ctl extends sa_stores_mdl
{
    function __construct(){
        $this->getProdctAvailbilityCustomCat();
    }

    public function getProdctAvailbilityCustomCat(){
        $res = [];
        $api_key=common::CUSTOMCAT_API_KEY;
        $liveenventory_endpoint=common::CUSTOMCAT_ENVENTORY_ENDPOINT;
        $message = '';
        if(!empty($_POST)){
            if(empty($_POST['curProdId']) && empty($_POST['current_product_style']) && empty($_POST['current_product_color']) && empty($_POST['current_product_size'])){
                
                if(empty($_POST['curProdId'])){
                    $message = "Product id field is required.";
                }

                if(empty($_POST['current_product_style'])){
                    $message = "SKU field is required.";
                }

                if(empty($_POST['current_product_color'])){
                    $message = "Color field is required.";
                }

                if(empty($_POST['current_product_size'])){
                    $message = "Size field is required."; 
                }
                
                $res['STATUS'] = FALSE;
                $res['message']  = $message;
                $res['statusCode'] = 400;
            }
            else
            {
                $custcatSkuSql="select spvm.customcat_sku,sopm.store_product_master_id,sopm.shop_product_id,spvm.size,spvm.color,spvm.sku,spcm.product_color_name FROM store_owner_product_master as sopm INNER JOIN store_product_variant_master as spvm ON spvm.store_product_master_id=sopm.store_product_master_id INNER JOIN store_product_colors_master as spcm ON spcm.product_color=spvm.color where sopm.shop_product_id='".trim($_POST['curProdId'])."' AND spcm.product_color_name='".trim($_POST['current_product_color'])."' AND spvm.size='".trim($_POST['current_product_size'])."' ";
                $customcatSkuData = parent::selectTable_f_mdl($custcatSkuSql);
                $catalog_sku='';
                if(!empty($customcatSkuData)){
                    $catalog_sku=$customcatSkuData[0]['customcat_sku'];
                }
                $live_inventory_customcat=$liveenventory_endpoint.$catalog_sku.'/?api_key='.$api_key;

                $curl = curl_init();
                curl_setopt_array($curl, array(
                CURLOPT_URL => $live_inventory_customcat,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                ));
                $response = curl_exec($curl);
                curl_close($curl);
                $array = json_decode($response);

                if(isset($array) && !empty($array) && $array !=""){
                    if($array->in_stock=='1'){
                        $res['STATUS'] = TRUE;
                        $res['message']  = "Product is avilable";
                        $res['statusCode'] = 200;
                        $res['array'] = $array;
                    }else{
                        $res['STATUS'] = FALSE;
                        $res['message']  = "Product is not avilable";
                        $res['statusCode'] =404;
                        $res['array'] = $array;
                    }
                }else{
                    $res['STATUS'] = FALSE;
                    $res['message']  = "Somthing went wrong";
                    $res['statusCode'] = 500;
                }   
            }    
        }else{
            $res['STATUS'] = FALSE;
            $res['message']  = "Somthing went wrong";
            $res['statusCode'] = 500;
        } 
        echo json_encode($res);die();
    }
}

?>