<?php
include_once 'model/sa_stores_mdl.php';
$path = preg_replace('/controller(?!.*controller).*/', '', __DIR__);
include_once $path . 'libraries/Aws3.php';
$s3Obj = new Aws3;
$login_user_email="";
if(isset($_SESSION['user_email']) && $_SESSION['user_email'] != "") {
	$login_user_email=trim($_SESSION['user_email']);
}
class sa_shipping_rates_ctl extends sa_stores_mdl
{
	public $TempSession = "";
	function __construct()
	{
		if (parent::isGET() || parent::isPOST()) {
			if (parent::getVal("method")) {
				$this->checkRequestProcess(parent::getVal("method"));
			} else {
				$this->SITE_ACCESS_KEY = parent::getVal("stkn");
			}
		}
		common::CheckLoginSession();
	}

	function checkRequestProcess($requestFor)
	{
		if ($requestFor != "") {
			switch ($requestFor) {
				case "sync_recent_shipping_rates":
					$this->sync_recent_shipping_rates();
				break;
				case "sync_all_shipping_rates":
					$this->sync_all_shipping_rates();
				break;
				case "export_shipping_rates":
					$this->export_shipping_rates();
				break;
			}
		}
	}

	function shippingRatesPagination()
	{
		if (parent::isPOST()) {

			if (parent::getVal("hdn_method") == "shipping_rates_pagination") {
				$record_count = 0;
				$page = 0;
				$current_page = 1;
				$rows = '10';
				$keyword = '';
				if ((isset($_REQUEST['rows'])) && (!empty($_REQUEST['rows']))) {
					$rows = $_REQUEST['rows'];
				}
				if ((isset($_REQUEST['keyword'])) && (!empty($_REQUEST['keyword']))) {
					$keyword = $_REQUEST['keyword'];
				}
				if ((isset($_REQUEST['current_page'])) && (!empty($_REQUEST['current_page']))) {
					$current_page = $_REQUEST['current_page'];
				}
				$start = ($current_page - 1) * $rows;
				$end = $rows;
				$sort_field = '';
				if (isset($_POST['sort_field']) && !empty($_POST['sort_field'])) {
					$sort_field = $_POST['sort_field'];
				}
				$sort_type = '';
				if (isset($_POST['sort_type']) && !empty($_POST['sort_type'])) {
					$sort_type = $_POST['sort_type'];
				}

				$cond_keyword = '';
				if (isset($keyword) && !empty($keyword)) {
					$cond_keyword = "AND (
						ssrm.label_id LIKE '%" . trim($keyword) . "%' OR
                        ssrm.shipment_id LIKE '%" . trim($keyword) . "%' OR 
                        ssrm.shipment_cost LIKE '%" . trim($keyword) . "%' OR 
                        ssrm.tracking_number LIKE '%" . trim($keyword) . "%' OR 
                        ssrm.tracking_status LIKE '%" . trim($keyword) . "%' OR 
                        ssrm.ship_date LIKE '%" . trim($keyword) . "%' OR 
                        ssrm.ship_status LIKE '%" . trim($keyword) . "%' OR 
                        ssrm.package_code LIKE '%" . trim($keyword) . "%' OR 
                        ssrm.created_at LIKE '%" . trim($keyword) . "%' OR 
                        fwm.fe_order_id LIKE '%" . trim($keyword) . "%' OR 
                        ssrm.package_id LIKE '%" . trim($keyword) . "%'
                    )";
				}

				$shipStatus = "";
				if ((isset($_POST['ship_status'])) && $_POST['ship_status'] != '') {
					$shipStatus = 'AND (ssrm.ship_status="'.$_POST['ship_status'].' ")';
				}

                $trackingStatus = "";
				if ((isset($_POST['tracking_status'])) && $_POST['tracking_status'] != '') {
					$trackingStatus = 'AND (ssrm.tracking_status="'.$_POST['tracking_status'].' ")';
				}

				$cond_order = 'ORDER BY ssrm.created_at DESC';
				if (!empty($sort_field)) {
					$cond_order = 'ORDER BY ' . $sort_field . ' ' . $sort_type;
				}



				$createDate = '';
				$from_date = '';
				$to_date = '';
				if ((isset($_POST['start_date'])) && $_POST['start_date'] != '') {
					$from_date = $_POST['start_date'].'T00:00:00.000Z';
                }
				if ((isset($_POST['end_date'])) && $_POST['end_date'] != '') {
					$to_date = $_POST['end_date'].'T23:59:59.000Z';
				}
				
				if ((isset($from_date) && $from_date != '')) {
					$fromDateTime = new DateTime($from_date);
				    $toDateTime = new DateTime($to_date);
					$createDate = " AND ssrm.`created_at` >= '$from_date' AND ssrm.`created_at` <=  '$to_date' ";
					// $endDate=strtotime($endDate);
				}

				
                $sql = "SELECT count(ssrm.id) as count FROM `shipengine_shipping_rates_master` as ssrm LEFT JOIN fe_webhook_master as fwm ON fwm.fe_tracker_number=ssrm.tracking_number WHERE 1 
                    $cond_keyword
                    $shipStatus
                    $trackingStatus
                    $createDate
                    $cond_order
                ";
				$all_count = parent::selectTable_f_mdl($sql);

                $sql1 = "SELECT ssrm.id,ssrm.label_id,ssrm.shipment_id,ssrm.carrier_id,ssrm.service_code,ssrm.shipment_cost,ssrm.shipment_currency,ssrm.tracking_number,ssrm.tracking_status,ssrm.ship_label_download_pdf,ssrm.ship_label_download_image,ssrm.ship_status,ssrm.ship_date,ssrm.created_at,ssrm.package_id,ssrm.package_code,ssrm.package_weight,ssrm.package_label_download_pdf,ssrm.package_label_download_image,fwm.fe_order_id,fwm.fe_shipment_id,fwm.fe_tracking_url,fwm.fe_tracker_number,som.shop_order_number,
                som.id as sa_order_id,som.shop_order_id FROM `shipengine_shipping_rates_master` as ssrm LEFT JOIN fe_webhook_master as fwm ON fwm.fe_tracker_number=ssrm.tracking_number LEFT JOIN store_orders_master as som ON som.fe_order_id=fwm.fe_order_id WHERE 1
                    $cond_keyword
                    $shipStatus
                    $trackingStatus
                    $createDate
                    $cond_order
                    LIMIT $start,$end
                ";
				$all_list = parent::selectTable_f_mdl($sql1);

				if ((isset($all_count[0]['count'])) && (!empty($all_count[0]['count']))) {
					$record_count = $all_count[0]['count'];
					$page = $record_count / $rows;
					$page = ceil($page);
				}
				$sr_start = 0;
				if ($record_count >= 1) {
					$sr_start = (($current_page - 1) * $rows) + 1;
				}
				$sr_end = ($current_page) * $rows;
				if ($record_count <= $sr_end) {
					$sr_end = $record_count;
				}

				if (isset($_POST['pagination_export']) && $_POST['pagination_export'] == 'Y') {
				} else {
					$html = '';
					$html .= '<div class="row">';
					$html .= '<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">';
					$html .= '<div class="table-responsive dropdown-active">'; // Task 54 19/10/2021 Add new class dropdown-active
					$html .= '<table class="table table-bordered table-hover">';
					$html .= '<thead>';
					$html .= '<tr>';
					$html .= '<th>#</th>';
					$html .= '<th class="sort_th" data-sort_field="fe_order_id">FE Order #</th>';
					$html .= '<th>SA Order #</th>';
					$html .= '<th class="sort_th" data-sort_field="label_id">Label ID</th>';
					$html .= '<th class="sort_th" data-sort_field="shipment_id">Shipment ID</th>';
					$html .= '<th class="sort_th" data-sort_field="carrier_id">Carrier Id</th>';
					$html .= '<th class="sort_th" data-sort_field="service_code">Service Code</th>';
					$html .= '<th class="sort_th" data-sort_field="shipment_cost">Shipment Cost</th>';
					//$html .= '<th class="sort_th" data-sort_field="tracking_number">Tracking Number</th>';
					$html .= '<th class="sort_th" data-sort_field="tracking_status">Tracking Status</th>';
					$html .= '<th>Ship Label PDF</th>';
					$html .= '<th>Ship Label Image</th>';/* Task 121 */
					$html .= '<th class="sort_th" data-sort_field="package_id">Package ID</th>';
					$html .= '<th class="sort_th" data-sort_field="package_code">Package Code</th>';
					$html .= '<th class="sort_th" data-sort_field="package_weight">Package Weight</th>';
					//$html .= '<th class="sort_th" data-sort_field="store_in_hands_date">Package Label PDF</th>'; //Task 74
					//$html .= '<th class="sort_th" data-sort_field="store_in_hands_date">Package Label  Image</th>';
					$html .= '<th class="sort_th" data-sort_field="ship_status">Ship Status</th>';
					$html .= '<th class="sort_th" data-sort_field="ship_date">Ship Date</th>';
					$html .= '<th class="sort_th" data-sort_field="created_at">Created Date</th>';
					$html .= '</tr>';
					$html .= '</thead>';

					$html .= '<tbody>';

					if (!empty($all_list)) {
						$sr = $sr_start;
						foreach ($all_list as $single) {
							$tracking_url = '<a target="_blank" href="http://tools.usps.com/go/TrackConfirmAction?tLabels='.$single["tracking_number"].'" >'.$single["tracking_number"].'</a>';
                            if(isset($_SERVER['HTTP_HOST']) && ($_SERVER['HTTP_HOST']=="localhost") || ($_SERVER['HTTP_HOST']=="spirithero-rds.bitcotapps.com")){
                                $fe_order_url = '<a target="_blank" href="https://app.fulfillengine.com/user-view/accounts/act-8330840/orders/'.$single["fe_order_id"].'" >'.$single["fe_order_id"].'</a>';
                            }else{
                                $fe_order_url = '<a target="_blank" href="https://app.fulfillengine.com/user-view/accounts/act-9113822/orders/'.$single["fe_order_id"].'" >'.$single["fe_order_id"].'</a>';
                            }
							
							$html .= '<tr>';
							//$html .= '<td><input type="checkbox" value=' . $single["id"] . ' class="checkBoxClass"></td>';
							$html .= '<td>' . $sr . '</td>';
                            if(empty($single["fe_order_id"])){
                                $html .= '<td></td>';
                            }else{
                                $html .= '<td>'.$fe_order_url.'</td>';
                            }
                            if(empty($single["shop_order_number"])){
                                $html .= '<td></td>';
                            }else{
                                $html .= '<td><a href="sa-order-view.php?stkn=&oid='.$single['sa_order_id'].'">'.$single['shop_order_number'].'</a></td>';
                            }
                            
							$html .= '<td>' . $single["label_id"] . '</td>';
                            $html .= '<td>' . $single["shipment_id"] . '</td>';
                            $html .= '<td>' . $single["carrier_id"] . '</td>';
                            $html .= '<td>' . $single["service_code"]. '</td>';
                            $html .= '<td>' . $single["shipment_cost"].' '.$single["shipment_currency"]. '</td>';
                            // $html .= '<td>' . $tracking_url . '</td>';
                            // $html .= '<td>' . $single["tracking_status"] . '</td>';
                            $html .= '<td>';
								if ($single['tracking_status'] == 'in_transit') {
									$html .= '<a target="_blank" href="http://tools.usps.com/go/TrackConfirmAction?tLabels='.$single["tracking_number"].'" class="btn btn-xs" style="color:#fff;background-color:#ffc107"><i class="site-menu-icon icon fa-truck" aria-hidden="true" style="color: #fff;"></i> '.$single['tracking_status'].'</a><br>';
								}else if($single['tracking_status'] == 'delivered') {
									$html .= '<a target="_blank" href="http://tools.usps.com/go/TrackConfirmAction?tLabels='.$single["tracking_number"].'" class="btn btn-xs" style="color:#fff;background-color:#28a745"><i class="site-menu-icon icon fa-check" aria-hidden="true" style="color: #fff;"></i> '.$single['tracking_status'].'</a><br>';
								}else{
									$html .= '<a class="btn btn-xs" style="color:#fff;background-color:#330166"><i class="site-menu-icon icon fa fa-file-text-o" aria-hidden="true" style="color: #fff;"></i> In Production</a><br>';
								}
							$html .= '</td>';
                            $html .= '<td><a href="'.$single["ship_label_download_pdf"].'" target="_blank">Download</a></td>';
                            $html .= '<td><a href="'.$single["ship_label_download_image"].'" target="_blank">Download</a></td>';
                            $html .= '<td>' . $single["package_id"] . '</td>';
                            $html .= '<td>' . $single["package_code"] . '</td>';
                            $html .= '<td>' . $single["package_weight"] . '</td>';
                            // if(empty($single["package_label_download_pdf"])){
							// 	$html .= '<td></td>';
							// }else{
							// 	$html .= '<td><a href="'.$single["package_label_download_pdf"].'" target="_blank">Download</a></td>';
							// }
							// if(empty($single["package_label_download_image"])){
							// 	$html .= '<td></td>';
							// }else{
							// 	$html .= '<td><a href="'.$single["package_label_download_image"].'" target="_blank">Download</a></td>';
							// }
                            $html .= '<td>' . $single["ship_status"] . '</td>';
                            if (!empty($single["ship_date"])) {
							    $date = new DateTime($single["ship_date"]);
							    $html .= '<td>' . $date->format('m/d/Y h:i A') . '</td>';
							} else {
							    $html .= '<td></td>';
							}
                            if (!empty($single["created_at"])) {
							    $date = new DateTime($single["created_at"]);
							    $html .= '<td>' . $date->format('m/d/Y h:i A') . '</td>';
							} else {
							    $html .= '<td></td>';
							}
                            

							// if (!empty($single['store_open_date'])) {
							// 	$html .= '<td>' . date('m/d/Y', $single["store_open_date"]) . '</td>';
							// } else {
							// 	$html .= '<td></td>';
							// }
						    $html .= '</tr>';
							$sr++;
						}
					} else {
						$html .= '<tr>';
						$html .= '<td colspan="18" align="center">No Record Found</td>';
						$html .= '</tr>';
					}

					$html .= '</tbody>';
					$html .= '</table></br></br></br></br></br>';
					$html .= '</div>';
					$html .= '</div>';
					$html .= '</div>';

					$res['DATA'] = $html;
					$res['page_count'] = $page;
					$res['record_count'] = $record_count;
					$res['sr_start'] = $sr_start;
					$res['sr_end'] = $sr_end;
					echo json_encode($res, 1);
					exit;
				}
			}
		}
	}

    function getlastSyncDate()
	{	
        $sqlG = "SELECT last_sync_date FROM shipengine_shipping_rates_master ORDER BY id desc limit 1";/* Task 82 Add where condition is_deleted = 0*/
        return parent::selectTable_f_mdl($sqlG);  
	}

    function sync_recent_shipping_rates()
	{
		global $s3Obj;
		global $login_user_email;
		if (parent::isPOST()) {
			if (!empty(parent::getVal("method")) && parent::getVal("method") == "sync_recent_shipping_rates") {
				require_once(common::EMAIL_REQUIRE_URL);

				$last_sync_date = parent::getVal("last_sync_date");

                if(empty($last_sync_date)){
                    $last_sync_date = date('Y-m-d');
                }else{
                	// $last_sync_date= date('Y-m-d',strtotime($last_sync_date));
                	$last_sync_date='2021-01-01';
                }
                $api_key = 'p3Y1jh4FOPOlD8clDqi1UZ4pdtNjyiXlGzmRJ/SKWao';
                $created_at_start = date('Y-m-d', strtotime($last_sync_date));
                $resultData = [];
                try{
                    $curl = curl_init();

                    curl_setopt_array($curl, array(
                    CURLOPT_URL => 'https://api.shipengine.com/v1/labels?page_size=220&created_at_start='.$created_at_start,
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
                
				$resultArray["isSuccess"] = "1";
				$resultArray["msg"] = "Shipping rates synced successfully.";
				echo json_encode($resultArray, 1);
				exit;
			}
		}
	}

	function sync_all_shipping_rates()
	{
		if (parent::isPOST()) {
			if (!empty(parent::getVal("method")) && parent::getVal("method") == "sync_all_shipping_rates") {
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
                
				$resultArray["isSuccess"] = "1";
				$resultArray["msg"] = "Shipping rates synced successfully.";
				echo json_encode($resultArray, 1);
				exit;
			}
		}
	}

	public function export_shipping_rates()
	{
		global $s3Obj;
		$reportData=[];
		if (parent::isPOST()) {
			if (!empty(parent::getVal("method")) && parent::getVal("method") == "export_shipping_rates") {
				
				$from_date='';
				if ((isset($_POST['start_date'])) && $_POST['start_date'] != '') {
					$from_date = date('Y-m-d', strtotime($_POST['start_date']));
				}
				$to_date='';
				if ((isset($_POST['end_date'])) && $_POST['end_date'] != '') {
					$to_date = date('Y-m-d', strtotime($_POST['end_date'].' 23:59:59'));
				}

				$cond_date = '';
				if (isset($_POST['start_date'])) {
					$cond_date = "AND date(ssrm.created_at) BETWEEN '".$from_date."' AND '".$to_date."' ";
				}

				$resultArray = array();
				$export_file = 'shipping-rates-'.time().'.csv';
				$export_file_path = 'image_uploads/_export/' . $export_file;
				$export_file_url = common::IMAGE_UPLOAD_URL . '_export/' . $export_file;
				$file_for_export_data = fopen($export_file_path, "w");
				$BOM = "\xEF\xBB\xBF";
				header('Content-Encoding: UTF-8');
				header('Content-type: text/plain; charset=utf-8');
				header('Content-type: text/csv; charset=UTF-8');
				header('Content-Type: text/html; charset=utf-8');
				header('Content-Transfer-Encoding: binary');
				header('Content-type: application/csv');
				header('Content-type: application/excel');
				mb_convert_encoding($export_file_url, 'UTF-16LE', 'UTF-8');
				header("Content-type: application/vnd.ms-excel");
				header('Content-Disposition: attachment; filename=' . $export_file_url);

				
                $sql = "SELECT ssrm.id,ssrm.label_id,ssrm.shipment_id,ssrm.carrier_id,ssrm.service_code,ssrm.shipment_cost,ssrm.shipment_currency,ssrm.tracking_number,ssrm.tracking_status,ssrm.ship_label_download_pdf,ssrm.ship_label_download_image,ssrm.ship_status,ssrm.ship_date,ssrm.created_at,ssrm.package_id,ssrm.package_code,ssrm.package_weight,ssrm.package_label_download_pdf,ssrm.package_label_download_image,fwm.fe_order_id,fwm.fe_shipment_id,fwm.fe_tracking_url,fwm.fe_tracker_number FROM `shipengine_shipping_rates_master` as ssrm LEFT JOIN fe_webhook_master as fwm ON fwm.fe_tracker_number=ssrm.tracking_number WHERE 1  
                    $cond_date 
                    ORDER BY ssrm.created_at DESC
                ";
                $reportData=parent::selectTable_f_mdl($sql);
                fputcsv(
                    $file_for_export_data,
                    ['Order #' , 'Shipment ID' , 'Shipping Cost', 'Order Date']
                );
                foreach ($reportData as $values) {
                    $fe_order_id  	= $values['fe_order_id'];
                    $shipment_id  	= $values['shipment_id'];
                    $shipment_cost  = $values['shipment_cost'].' '.$values["shipment_currency"];
                    $created_at     = $values['created_at'];
                    $date           = new DateTime($created_at);
                    $created_date	=$date->format('m/d/Y h:i A');
                    fputcsv(
                        $file_for_export_data,
                        [
                            trim($fe_order_id),
                            trim($shipment_id),
                            trim($shipment_cost),
                            trim($created_date)
                        ]
                    );
                }
				fputcsv(
					$file_for_export_data,
					['']
				);
				$status = true;
				if ($status == true) {
					fclose($file_for_export_data);
					$resultArray['SUCCESS'] = 'TRUE';
					$resultArray['MESSAGE'] = '';
					$resultArray['EXPORT_URL'] = $export_file_url; // Task 59
				} else {
					$resultArray['SUCCESS'] = 'FALSE';
					$resultArray['MESSAGE'] = 'Records are not found.';
				}
				common::sendJson($resultArray);
			}
		}
	}

}
