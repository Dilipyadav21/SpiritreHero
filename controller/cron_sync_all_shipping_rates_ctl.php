<?php
include_once 'model/sa_stores_mdl.php';
$path  = preg_replace('/controller(?!.*controller).*/','',__DIR__);
include_once $path . '/libraries/Aws3.php';
$s3Obj = new Aws3;
class cron_sync_all_shipping_rates_ctl extends sa_stores_mdl
{
	function __construct(){
		$this->sync_all_shipping_rates();
	}
	
	public function sync_all_shipping_rates(){
		global $path;
		$logFileOpen = fopen("logs.txt", "a+") or die("Unable to open file!");
		$errorText  = "-----------------------------------------------------------------<br>";
		$errorText .= "cron sync shipping rates start time ".date("m/d/Y h:i A");
		$errorText .= "-----------------------------------------------------------------<br>";
		fwrite($logFileOpen, $errorText);
		unset($errorText);
        $api_key = 'p3Y1jh4FOPOlD8clDqi1UZ4pdtNjyiXlGzmRJ/SKWao';
        $resultData = [];
        try{
            $curl = curl_init();
            curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.shipengine.com/v1/labels?page_size=1000',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'API-Key: '.$api_key.' ',
                'Cache-Control: no-cache'
            ),
            ));

            $response = curl_exec($curl);

            curl_close($curl);

        } catch(Exception $e){
            echo 'Message: ' .$e->getMessage();
        }
        if (!empty($response)) {
            $resultData = json_decode($response, true);
        }
        //echo "";print_r($resultData);die;

        if (isset($resultData['labels']) && !empty($resultData['labels'])) {
        
            foreach ($resultData['labels'] as $singleproduct) {

                $label_id = $singleproduct['label_id'];
                $shipment_id = $singleproduct['shipment_id'];
                $carrier_id = $singleproduct['carrier_id'];
                $service_code = $singleproduct['service_code'];
                $shipment_cost = $singleproduct['shipment_cost']['amount']; // Access 'amount' key for cost
                $shipment_currency = $singleproduct['shipment_cost']['currency']; // Access 'currency' key for currency
                $tracking_number = $singleproduct['tracking_number'];
                $tracking_status = $singleproduct['tracking_status'];
                $ship_label_download_pdf = $singleproduct['label_download']['pdf'];
                $ship_label_download_image = $singleproduct['label_download']['png']; // Access 'png' instead of 'image'
                $ship_status = $singleproduct['status'];
                $ship_date = $singleproduct['ship_date'];
                $created_at = $singleproduct['created_at'];
                $package_id = $singleproduct['packages'][0]['package_id']; // Access 'package_id' instead of 'package_code'
                $package_code = $singleproduct['packages'][0]['package_code'];
                $package_weight = $singleproduct['packages'][0]['weight']['value'];
                $package_unit = $singleproduct['packages'][0]['weight']['unit'];
                $package_label_download_pdf = isset($singleproduct['packages'][0]['label_download']['pdf']) ? $singleproduct['packages'][0]['label_download']['pdf'] : '';
                $package_label_download_image = isset($singleproduct['packages'][0]['label_download']['png'])? $singleproduct['packages'][0]['label_download']['png'] :''; // Access 'png' instead of 'image'
                $batch_id = $singleproduct['batch_id'];
                $carrier_code = $singleproduct['carrier_code'];
                $insurance_cost = $singleproduct['insurance_cost']['amount'];
                $insurance_currency = $singleproduct['insurance_cost']['currency'];
                $requested_comparison_amount = $singleproduct['requested_comparison_amount']['amount'];
                $is_return_label = $singleproduct['is_return_label'];
                $rma_number = $singleproduct['rma_number'];
                $is_international = $singleproduct['is_international'];
                $label_format = $singleproduct['label_format'];
                $display_scheme = $singleproduct['display_scheme'];
                $label_layout = $singleproduct['label_layout'];
                $trackable = $singleproduct['trackable'];
                $package_dimensions_width = $singleproduct['packages'][0]['dimensions']['width'];
                $package_dimensions_length = $singleproduct['packages'][0]['dimensions']['length'];
                $package_dimensions_height = $singleproduct['packages'][0]['dimensions']['height'];
                $package_dimensions_unit = $singleproduct['packages'][0]['dimensions']['unit'];
                $package_insured_value = $singleproduct['packages'][0]['insured_value']['amount'];
                $qr_code_download = $singleproduct['packages'][0]['qr_code_download'];
                $charge_event = $singleproduct['charge_event'];


                $insertData = [
                    'label_id' => $label_id,
                    'shipment_id' => $shipment_id,
                    'batch_id'=> $batch_id,
                    'carrier_id' => $carrier_id,
                    'carrier_code' => $carrier_code,
                    'service_code' => $service_code,
                    'shipment_cost' => $shipment_cost,
                    'shipment_currency' => $shipment_currency,
                    'ship_insurance_cost'=> $insurance_cost,
                    'requested_comparison_amount'=> $requested_comparison_amount,
                    'is_return_label'=> $is_return_label,
                    'rma_number'=> $rma_number,
                    'is_international'=> $is_international,
                    'label_format'=> $label_format,
                    'display_scheme'=> $display_scheme,
                    'tracking_number' => $tracking_number,
                    'tracking_status' => $tracking_status,
                    'trackable'=> $trackable,
                    'label_layout'=> $label_layout,
                    'ship_label_download_pdf' => $ship_label_download_pdf,
                    'ship_label_download_image' => $ship_label_download_image,
                    'ship_status' => $ship_status,
                    'ship_date' => $ship_date,
                    'created_at' => $created_at,
                    'package_id' => $package_id,
                    'package_code' => $package_code,
                    'package_weight' => $package_weight,
                    'package_dimensions_width'=> $package_dimensions_width,
                    'package_dimensions_length'=> $package_dimensions_length,
                    'package_dimensions_height'=> $package_dimensions_height,
                    'package_dimensions_unit'=> $package_dimensions_unit,
                    'package_insured_value'=> $package_insured_value,
                    'package_label_download_pdf' => $package_label_download_pdf,
                    'package_label_download_image' => $package_label_download_image,
                    'qr_code_download'=>$qr_code_download,
                    'charge_event'=>$charge_event,
                    'last_sync_date' => date('Y-m-d H:i:s')
                ];

                $sql1 = "SELECT id,shipment_id,carrier_id FROM `shipengine_shipping_rates_master` WHERE shipment_id='".$singleproduct['shipment_id']."' 
                ";
                $get_shipment_id = parent::selectTable_f_mdl($sql1);
                if(empty($get_shipment_id)){
                    parent::insertTable_f_mdl('shipengine_shipping_rates_master', $insertData);
                }else{
                    parent::updateTable_f_mdl('shipengine_shipping_rates_master',$insertData,'shipment_id="'.$shipment_id.'"');
                }
            }
        }
        $logFileOpen = fopen("logs.txt", "a+") or die("Unable to open file!");
		$errorText  = "----------------------------------------------------------------</br>";
		$errorText .= "cron sync shipping rates end time ".date("m/d/Y h:i A");
		$errorText .= "----------------------------------------------------------------</br>";
		$errorText .= "</br></br></br>";
		
		$resultResp = array();
		$resultResp["isSuccess"] = "1";
		$resultResp["msg"] = "Shipping rates synced successfully.";
	}
}
?>