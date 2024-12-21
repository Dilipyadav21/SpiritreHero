<?php
include_once 'model/sa_stores_mdl.php';

class sa_sanmar_product_check_ctl extends sa_stores_mdl
{

    function __construct(){
		$this->getProdctAvailbility();
	}

    public function getProdctAvailbility(){
        $res = [];
        $message = '';
		
        if(!empty($_POST)){
            if(empty($_POST['style']) || empty($_POST['color']) || empty($_POST['size']) || empty($_POST['vendor'])){
                if(empty($_POST['style'])){
                    $message = "Style field is required.";
                }

                if(empty($_POST['color'])){
                    if(empty($message)){
                        $message = "Color field is required.";
                    }else{
                        $message = $message.", Color field is required.";
                    }  
                }

                if(empty($_POST['size'])){
                    if(empty($message)){
                        $message = "Size field is required.";
                    }else{
                        $message = $message.", Size field is required.";
                    }   
                }

				if(empty($_POST['vendor'])){
                    if(empty($message)){
                        $message = "Vendo is required.";
                    }else{
                        $message = $message.", Vendor field is required.";
                    }   
                }

                $res['STATUS'] = FALSE;
                $res['message']  = $message;
                $res['statusCode'] = 400;
            }else if($_POST['vendor']=='FulfillEngine'){
				$sku=trim($_POST['style']);
				$fesql = "SELECT catalog_product_id,sku,size,color_name FROM fulfillengine_products_master WHERE  sku='".$sku."' LIMIT 1 ";
				$feData = parent::selectTable_f_mdl($fesql);
				$catlog_prod_id=$fesku='';
				if(!empty($feData)){
					$catlog_prod_id=$feData[0]['catalog_product_id'];
					$fesku=$feData[0]['sku'];
				}

				$curl = curl_init();
				$res=$response=[];
				try{
					$fullfill_inventory_endpoint =common::FULFILLENGINE_INVENTORY_ENDPOINTS;
					$api_key                     =common::FULFILLENGINE_API_KEY;
					
					curl_setopt_array($curl, array(
						CURLOPT_URL => $fullfill_inventory_endpoint,
						CURLOPT_RETURNTRANSFER => true,
						CURLOPT_ENCODING => '',
						CURLOPT_MAXREDIRS => 10,
						CURLOPT_TIMEOUT => 0,
						CURLOPT_FOLLOWLOCATION => true,
						CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
						CURLOPT_CUSTOMREQUEST => 'POST',
						CURLOPT_POSTFIELDS =>'{
							"productIds": [
								"'.$catlog_prod_id.'"
							]
						}',

						CURLOPT_HTTPHEADER => array(
							'accept: text/plain',
							'X-API-KEY: '.$api_key.'',
							'Content-Type: application/json',
							'Cookie: ARRAffinity=d2ab478ad1b5182da49b5da6c5de75b9a352b44b0db0a8ae344d4b305e092e19; ARRAffinitySameSite=d2ab478ad1b5182da49b5da6c5de75b9a352b44b0db0a8ae344d4b305e092e19'
						),
					));

					$response = curl_exec($curl);
					curl_close($curl);

				}catch(Exception $e) {
					echo 'Message: ' .$e->getMessage();
				}

				// echo "<pre>";print_r($response);
				$array = json_decode($response, true);
				if(!empty($array['products'][0]['skus'])){
					foreach($array['products'][0]['skus'] as $single){
						if($fesku==$single['sku']){
							if($single['isAvailable']=='1'){
								$res['STATUS'] = TRUE;
								$res['message']  = "Product is avilable";
								$res['statusCode'] = 200;
							}else{
								$res['STATUS'] = FALSE;
								$res['message']  = "Product is not avilable";
								$res['statusCode'] = 404;
							}
						}
					}
				}else{
					$res['STATUS'] = FALSE;
					$res['message']  = "Product is not avilable";
					$res['statusCode'] = 404;
				}

			}else{
				$color_code='';
				$color_sql = "SELECT product_color_name,product_color FROM store_product_colors_master WHERE product_color_name ='".trim($_POST['color'])."' ";
				$colorData = parent::selectTable_f_mdl($color_sql);
				if(!empty($colorData)){
					$color_code=$colorData[0]['product_color'];
				}

				$sanmar_sql = "SELECT store_product_master_id,sanmar_color_code,sku,sanmar_size,is_ver_deleted FROM store_product_variant_master WHERE  sku='".trim($_POST['style'])."' AND size='".trim($_POST['size'])."' AND color='".$color_code."' ";
				$sanmar_Data = parent::selectTable_f_mdl($sanmar_sql);

				$localhostWsdlUrl = common::PRODUCT_INFO;
				$client= new SoapClient($localhostWsdlUrl, array(
					'trace'=>true,
					'exceptions'=>true
				));
				$webServiceUser =array(
					'sanMarCustomerNumber' => common::sanMarCustomerNumber,
					'sanMarUserName' => common::sanMarUserName,
					'sanMarUserPassword' => common::sanMarUserPassword
				);

				$style 		= $sanmar_Data[0]['sku'];
				$size 		= $sanmar_Data[0]['sanmar_size'];
				$color_code	= $sanmar_Data[0]['sanmar_color_code'];

				$getcolorcodeSql = "SELECT store_product_variant_master_id,spvm.sanmar_color_code,sopvm.sku,sopvm.size,spvm.color,spcm.product_color_name FROM store_owner_product_variant_master as sopvm INNER JOIN  store_product_variant_master as spvm ON spvm.id=sopvm.store_product_variant_master_id INNER JOIN store_product_colors_master as spcm ON spcm.product_color=spvm.color WHERE  sopvm.size='".trim($_POST['size'])."' AND sopvm.sku='".$style."' AND  spcm.product_color_name='".trim($_POST['color'])."'";
				$sanmarcolorcode_List = parent::selectTable_f_mdl($getcolorcodeSql);
				if(!empty($sanmarcolorcode_List)){
					$color_code= $sanmarcolorcode_List[0]['sanmar_color_code'];
				}
				$arr = [
					'style' => trim($style),
					'color' => trim($color_code),
					'size' => trim($size)
				];
				$getProductInfoByStyleColorSize= array('arg0' =>$arr,'arg1' =>$webServiceUser );

				$result=$client->__soapCall('getProductInfoByStyleColorSize',array('getProductInfoByStyleColorSize' => $getProductInfoByStyleColorSize) );
				$array = json_decode(json_encode($result), true);

				$sanmarResponce = [];

				if(!empty($array['return']['listResponse']['productBasicInfo'])){
					$sanmarResponce = $array['return']['listResponse']['productBasicInfo'];
				}else{
					if(!empty($array['return']['listResponse'][0]['productBasicInfo'])){
						$sanmarResponce = $array['return']['listResponse'][0]['productBasicInfo']; 
					}	
				}

				$color = '';
				$inventoryKey = '';
				$sizeIndex = '';
				$size = '';
				$style = '';
				$PONumber='';
				$attention='';
				if(!empty($sanmarResponce)){
					$inventoryKey = $sanmarResponce['inventoryKey'];
					$color = $sanmarResponce['catalogColor'];
					$sizeIndex = $sanmarResponce['sizeIndex'];
					$size = $sanmarResponce['size'];
					$style = $sanmarResponce['style'];
				}

				if(isset($array['return']['errorOccured']) && $array['return']['errorOccured']==1){
					$res['STATUS'] = FALSE;
					$res['PRO_STATUS'] = FALSE;
					$res['message']  = "Product is not avilable";
					$res['statusCode'] = 404;
				}else{

					$localhostWsdlUrl = common::SANMAR_PO_SERVICE_POST;
					$client= new SoapClient($localhostWsdlUrl, array(
						'trace'=>true,
						'exceptions'=>true
					));
					
					$productInfoByStyleColorSize=[
						'attention' => $attention,
						'internalMessage'=>'',
						'notes'=>'',
						'poNum' => '1234',
						'residence' => 'N',
						'shipAddress1' => '2641 Crow Canyon Rd',
						'shipCity' =>'San Ramon',
						'shipEmail'=>'matt@spirithero.com',
						'shipMethod'=>'REN',
						'shipState'=>'CA',
						'shipTo'=>'Spirit Hero LLC',
						'shipZip'=>'94583',
						'webServicePoDetailList'=>[
							'errorOccured'=>'',
							'message'=>'',
							'poId'=>'',
							'style'=>$style,
							'color'=>$color,
							'size'=>$size,
							'quantity'=>'1',
							'sizeIndex'=>'',
							'inventoryKey'=>'',
							'whseNo'=>''
							//'sizeIndex'=>$sizeIndex,
							//'inventoryKey'=>$inventoryKey,
							
						]
					];

					$webServiceUser =array(
						'sanMarCustomerNumber' => common::sanMarCustomerNumber,
						'sanMarUserName' => common::sanMarUserName,
						'sanMarUserPassword' => common::sanMarUserPassword
					);

					$getPreSubmitInfo= array('arg0' =>$productInfoByStyleColorSize,'arg1' =>$webServiceUser );

					$result=$client->__soapCall('getPreSubmitInfo',array('getPreSubmitInfo' => $getPreSubmitInfo) );

					$array = json_decode(json_encode($result), true);

					if(isset($array['return']['errorOccurred']) && $array['return']['errorOccurred']==1){
						$res['STATUS'] = FALSE;
						$res['message']  = "Product is not avilable";
						$res['statusCode'] = 404;
					}else{
						$res['STATUS'] = TRUE;
						$res['message']  = "Product is avilable";
						$res['statusCode'] = 200;
					}
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