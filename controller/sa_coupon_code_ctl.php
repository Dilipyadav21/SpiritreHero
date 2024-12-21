<?php 
include_once 'model/sa_superadmin_mdl.php';
$path = preg_replace('/controller(?!.*controller).*/','',__DIR__);
include_once $path . '/libraries/Aws3.php';
class sa_coupon_code_ctl extends sa_superadmin_mdl
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
				case "delete_coupon_code":
					$this->deleteCouponCode();
                break;
                case'discount_code_history':
					$this->discount_code_history();
				break;
				case "enabeld_or_disabled_discount":
					$this->enabeld_or_disabled_discount();
				break;
				case'discount_csv_codes':
					$this->discount_csv_codes();
				break;
				case "update_csv_couponcode":
					$this->update_csv_couponcode();
				break;
				case "delete_csv_coupon_code":
					$this->deleteCsvCouponCode();
                break;
			}
        }
    }
    
	public function couponCodePagination(){
		if(parent::isPOST()){
			if(parent::getVal("hdn_method") == "coupon_code_pagination")
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

				$cond_keyword = '';
				if(isset($keyword) && !empty($keyword)){
					$cond_keyword = "AND (
						ccm.discount_type LIKE '%".trim($keyword)."%' OR
						ccm.discount_code LIKE '%".trim($keyword)."%' OR
						ccm.discount_price LIKE '%".trim($keyword)."%' OR
						ccm.discount_series_name LIKE '%".trim($keyword)."%' OR
						ccsm.coupon_code LIKE '%".trim($keyword)."%' OR
						ccm.discount_code_start_date LIKE '%".trim($keyword)."%'
					)";
				}
				$cond_order = 'ORDER BY ccm.id DESC';
				if(!empty($sort_field)){
					$cond_order = 'ORDER BY '.$sort_field.' '.$sort_type;
				}

				$sql=" SELECT COUNT(*) AS count FROM 
					(SELECT ccm.id FROM `coupon_code_master` as ccm  LEFT JOIN `coupon_code_series_master` as ccsm ON ccm.id = ccsm.coupon_code_master_id WHERE 1 $cond_keyword GROUP BY ccm.id 
					) as grouped_data
                ";
				$all_count = parent::selectTable_f_mdl($sql);

				$sql1="SELECT ccm.id,ccm.discount_type,ccm.discount_code,ccm.discount_series_name,ccm.discount_value,ccm.discount_price,ccm.minimum_purchase,ccm.minimum_purchase_value,ccm.discount_code_limit,ccm.apply_for,ccm.discount_code_start_date,ccm.discount_code_end_date,ccm.discount_status,ccm.created_on,ccsm.coupon_code FROM `coupon_code_master` as ccm LEFT JOIN `coupon_code_series_master` as ccsm  
    				ON ccm.id = ccsm.coupon_code_master_id WHERE 1 
                    $cond_keyword
					GROUP BY ccm.id
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
					// $html .= '<th><input type="checkbox" id="ckbCheckAll"></td></th>';
					$html .= '<th>#</th>';
					$html .= '<th>Discount Type</th>';
					$html .= '<th>Discount Series</th>';
					$html .= '<th>Discount Code</th>';
					$html .= '<th>Discount Value</th>';
					$html .= '<th>Discount Apply For</th>';
					 $html .= '<th>Discount Code Used/Limit</th>';
					$html .= '<th>Start Date</th>';
					$html .= '<th>End Date</th>';
					$html .= '<th>Discount Status</th>';
					$html .= '<th>Created Date</th>';
                    $html .= '<th>Actions</th>';
					$html .= '</tr>';
					$html .= '</thead>';

					$html .= '<tbody>';

					if(!empty($all_list)){
						$sr = $sr_start;
						$orderdata=[];
						foreach($all_list as $single){

							if(!empty($single['discount_code'])){
								$sql = 'SELECT count(shop_order_number) as count FROM store_orders_master WHERE discount_code="'.$single['discount_code'].'" ORDER BY id DESC';
								$orderdata = parent::selectTable_f_mdl($sql);
							}else{
								$sql = 'SELECT group_concat(coupon_code) as couponcodes FROM coupon_code_series_master where coupon_code_master_id ="'.$single["id"].'" ';
								$couponData = parent::selectTable_f_mdl($sql);

								$couponcodes=$couponData[0]['couponcodes'];
								if (!empty($couponcodes)) {
									$couponcodesArr=explode(",",$couponcodes);
									$JsoncouponArray = json_encode(array_values($couponcodesArr));
									$DiscountsValues  = str_replace(array('[', ']'), '', $JsoncouponArray);
								
									$sql = 'SELECT count(shop_order_number) as count FROM store_orders_master WHERE discount_code IN('.$DiscountsValues.') ORDER BY id DESC';
									$orderdata = parent::selectTable_f_mdl($sql);
								}
							}

							$orderCount='0';
							if(!empty($orderdata)){
								$orderCount=$orderdata[0]['count'];
							}
							$discount_codehtml ='';
							if(empty($single['discount_code'])){
								$discount_codehtml = '<button class="btn btn-primary waves-effect waves-classic btn-view-couponcode waves-effect waves-classic" data-id='.$single["id"].'>View Codes</button>';
							}else{
								$discount_codehtml = $single['discount_code'];
							}

							if(!empty($single['discount_code_end_date'])) {
								$endDate = strtotime($single['discount_code_end_date']);
								$currentDate = time();
								if ($endDate < $currentDate) {
							        $html .= '<tr class="discoubt-expired">';
							    } else {
							        $html .= '<tr>';
							    }
							} else {
								$html .= '<tr>';
							}

							// $html .= '<td><input type="checkbox" value='.$single["id"].' class="checkBoxClass"></td>';
							$html .= '<td>'.$sr.'</td>';
							//$html .= '<td>' . (($single['discount_type'] == '0') ? 'Amount of order' : ($single['discount_type'] == '1') ? 'Amount of Products' : 'Free shipping') . '</td>';
							$html .= '<td>' . (($single['discount_type'] == '0') ? 'Amount off order' : (($single['discount_type'] == '1') ? 'Amount off products' : 'Free shipping')) . '</td>';
							$html .= '<td>'.$single['discount_series_name'].'</td>';
							$html .= '<td>'.$discount_codehtml.'</td>';
							$html .= '<td>' . (($single['discount_value'] == '0') ? $single['discount_price'].'%' : '$'.$single['discount_price']).'</td>';
							$html .= '<td>' . (($single['apply_for'] == '0') ? 'Collection(s)' : (($single['apply_for'] == '1') ? 'Product(s)' : 'All')) . '</td>';
							if(!empty($single['discount_code'])) {
								$html .= '<td>'.$orderCount.'/'.(empty($single['discount_code_limit']) ? '∞' : $single['discount_code_limit']).'</td>';
							} else {
								$html .= '<td></td>';
							}
							
							if(!empty($single['discount_code_start_date'])) {
								$html .= '<td>' . date('m/d/Y h:i A', strtotime($single["discount_code_start_date"])) . '</td>';
							} else {
								$html .= '<td></td>';
							}

                            if(!empty($single['discount_code_end_date'])) {
								$html .= '<td>' . date('m/d/Y h:i A', strtotime($single["discount_code_end_date"])) . '</td>';
							} else {
								$html .= '<td></td>';
							}

							$checked = '';
							if ($single['discount_status'] == 0) {
								$checked = 'checked';
							}

							$html .= '<td>
									<div class="form-group toggal-email-temp">
					                    <label class="inex-switch">
					                        <input type="checkbox" id="discount_status" name="discount_status" value="'.$single["id"].'" '.$checked.'>
					                        <span class="inex-slider round"></span>
					                    </label>
					                </div>
								</td>';	

							//$html .= '<td>' . (($single['discount_status'] == '0') ? 'Active' : 'Inactive') . '</td>';
                            
                            if (!empty($single['created_on'])) {
								$html .= '<td>' . date('m/d/Y h:i A', strtotime($single["created_on"])) . '</td>';
							} else {
								$html .= '<td></td>';
							}
							if(!empty($single['discount_code'])){
								$sql = 'SELECT shop_order_number,discount_code,discount_code_amount,discount_code_type FROM store_orders_master WHERE discount_code="'.$single['discount_code'].'" ORDER BY shop_order_number DESC';
								$DiscountOrderdata = parent::selectTable_f_mdl($sql);
							}else{
								$sql = 'SELECT group_concat(coupon_code) as couponcodes FROM coupon_code_series_master where coupon_code_master_id ="'.$single['id'].'" ';
								$couponData = parent::selectTable_f_mdl($sql);
								$couponcodes=$couponData[0]['couponcodes'];
								if(!empty($couponcodes)){
									$couponcodesArr=explode(",",$couponcodes);
									$JsoncouponArray = json_encode(array_values($couponcodesArr));
									$DiscountsValues  = str_replace(array('[', ']'), '', $JsoncouponArray);
									
									$sql = 'SELECT shop_order_number,discount_code,discount_code_amount,discount_code_type FROM store_orders_master WHERE discount_code IN('.$DiscountsValues.') ORDER BY shop_order_number DESC';
									$DiscountOrderdata = parent::selectTable_f_mdl($sql);
								}else{
									$DiscountOrderdata=[];
								}
							}

							$discountdelete='';
							if(empty($DiscountOrderdata)){
								$discountdelete = '<button data-href="" href="javascript:void(0)" role="menuitem" class="btn btn-danger btn-round btn-sm delete_couponcode_btn waves-effect waves-classic" data-id='.$single["id"].' data-coupon_code='.$single['discount_code'].' data-toggle="tooltip" data-placement="top" data-trigger="hover" data-original-title="Remove discount code" title=""><i class="fa fa-trash"></i></button>';
							}

							$discounthistory = '<button class="btn btn-primary btn-round btn-sm  discount_code_history waves-effect waves-classic" data-id='. $single["id"].' id="discount_code_history_'.$single["id"].'" data-discount_code="'.$single['discount_code'].'" title="Click to Discount Code History" ><i class="fa fa-history"></i></button>';
							$discountedit = '<button role="menuitem" data-href="sa-addedit-coupon-code.php?stkn=&id='.$single["id"].'" href="sa-addedit-coupon-code.php?stkn=&id='.$single["id"].'" class="btn btn-primary btn-round btn-sm couponcode_edit_btn waves-effect waves-classic"data-toggle="tooltip" data-placement="top" data-trigger="hover" data-original-title="Remove discount code" title=""><i class="fa fa-edit"></i></button>';
							
							$html .= '<td class="discount-action-col">'.$discounthistory.''.$discountedit.''.$discountdelete.'</td>';

                            // $html .= '<td><div class="btn-group" role="group">
                            //     <button type="button" class="btn btn-primary dropdown-toggle" id="exampleGroupDrop1" data-toggle="dropdown" aria-expanded="false">
                            //     Actions
                            //     </button>
                            //     <div class="dropdown-menu" aria-labelledby="exampleGroupDrop1" role="menu" x-placement="bottom-start" style="position: absolute; transform: translate3d(0px, 36px, 0px); top: 0px; left: 0px; will-change: transform;">
                                
                            //     
                            //     
                            // ';

							$html .= '</tr>';
							$sr++;
						}
					}else{
						$html .= '<tr>';
						$html .= '<td colspan="11" align="center">No Record Found</td>';
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
			die();
		}
	}

	public function deleteCouponCode(){
		if (parent::isPOST()) {
			$resultArray = array();
			if (!empty(parent::getVal("method")) && parent::getVal("method") == "delete_coupon_code") {
				$coupon_code_id = parent::getVal("coupon_code_id");
				$discount_code = parent::getVal("discount_code");
				
				parent::deleteTable_f_mdl('coupon_code_master', 'id =' . $coupon_code_id);
				$resultArray["isSuccess"] = "TRUE";
				$resultArray["msg"] = "Coupon code delete successfully.";

				common::sendJson($resultArray);
			}
		}
	}

	public function discount_code_history(){
		$res =$DiscountHistorydata= [];

		if(!empty($_POST['discount_code_id'])){
			$discount_code_id = $_POST['discount_code_id'];
			$discount_code = trim($_POST['discount_code']);

			if(!empty($discount_code)){
				$sql = 'SELECT shop_order_number,discount_code,discount_code_amount,discount_code_type FROM store_orders_master WHERE discount_code="'.$discount_code.'" ORDER BY shop_order_number DESC';
				$DiscountHistorydata = parent::selectTable_f_mdl($sql);
			}else{
				$sql = 'SELECT group_concat(coupon_code) as couponcodes FROM coupon_code_series_master where coupon_code_master_id ="'.$discount_code_id.'" ';
				$couponData = parent::selectTable_f_mdl($sql);
				$couponcodes=$couponData[0]['couponcodes'];
				if(!empty($couponcodes)){
					$couponcodesArr=explode(",",$couponcodes);
					$JsoncouponArray = json_encode(array_values($couponcodesArr));
					$DiscountsValues  = str_replace(array('[', ']'), '', $JsoncouponArray);
					
					$sql = 'SELECT shop_order_number,discount_code,discount_code_amount,discount_code_type FROM store_orders_master WHERE discount_code IN('.$DiscountsValues.') ORDER BY shop_order_number DESC';
					$DiscountHistorydata = parent::selectTable_f_mdl($sql);
				}
			}

			$html = '';
			$html .='<div id="tab1" class="tab-content-single">';
					$html .= '<table class="table table-bordered table-hover">';
						$html .= '<thead>';
							$html .= '<tr>';
								$html .= '<th>Discount Code</th>';
								$html .= '<th>Order Number</th>';
								$html .= '<th>Discount Amount</th>';
							$html .= '</tr>';
						$html .= '</thead>';
						$html .= '<tbody>';
						if(!empty($DiscountHistorydata)){
							foreach ($DiscountHistorydata as $value) { 
								$html .= '<tr>';
									$html .= '<td>#'.$value['discount_code'] .'</td>';
									$html .= '<td>#'.$value['shop_order_number'] .'</td>';
									$html .= '<td>$'.$value['discount_code_amount'].'</td>';
								$html .= '</tr>';
							}
						}else{
							$html .= '<tr><td colspan="3">No Record Found</td></tr>';
						}
						$html .= '</tbody>';
					$html .= '</table>';
			$html .='</div>';
			$res['DATA'] = $html;
			$res['SUCCESS'] = 'TRUE';	
		}
		echo json_encode($res);die();
	}

	public function enabeld_or_disabled_discount(){
		$res = [];
		if(!empty(parent::getVal("method")) && parent::getVal("method") == "enabeld_or_disabled_discount"){
			
			//update email template status
			$update_data = [
				'discount_status' => parent::getVal("discount_status")
			];

			$res = parent::updateTable_f_mdl('coupon_code_master',$update_data,'id="'.parent::getVal('discount_id').'"');
		}
		echo common::sendJson($res,1);die();
	}

	public function discount_csv_codes(){
		$res = [];
		if(!empty($_POST['discount_code_id'])){
			$discount_code_id = $_POST['discount_code_id'];

			$sql = 'SELECT id,coupon_code FROM coupon_code_series_master where coupon_code_master_id ="'.$discount_code_id.'" ';
			$DiscountCSVdata = parent::selectTable_f_mdl($sql);
			
			$html = '';
			$html .='<div id="tab1" class="tab-content-single">';
					$html .= '<table class="table table-bordered table-hover">';
						$html .= '<thead>';
							$html .= '<tr>';
								$html .= '<th>Discount Code</th>';
								$html .= '<th>Action</th>';
							$html .= '</tr>';
						$html .= '</thead>';
						$html .= '<tbody>';
						if(!empty($DiscountCSVdata)){
							foreach ($DiscountCSVdata as $value) { 
								$sql = 'SELECT shop_order_number,discount_code,discount_code_amount,discount_code_type FROM store_orders_master WHERE discount_code="'.$value['coupon_code'].'" ORDER BY shop_order_number DESC';
								$DiscountOrderdata = parent::selectTable_f_mdl($sql);
								$discountdelete=$discountSave=$discountedit='';
								if(empty($DiscountOrderdata)){
									$discountdelete = '<button data-href="" href="javascript:void(0)" role="menuitem" class="btn btn-danger btn-round btn-sm delete_csv_couponcode waves-effect waves-classic" data-id='.$value["id"].' data-coupon_code='.$value['coupon_code'].' data-toggle="tooltip" data-placement="top" data-trigger="hover" data-original-title="Remove discount code" title=""><i class="fa fa-trash"></i></button>';								
								}
								$discountSave = '<button class="btn btn-primary btn-round btn-sm save_coupon_codecsv waves-effect waves-classic" data-toggle="tooltip" data-placement="top" data-trigger="hover" data-id="'.$value["id"].'" id="csv_discount_save_'.$value["id"].'" data-original-title="Save" title="" style="display:none;">Save</button>';

								$discountedit = '<button class="btn btn-primary btn-round btn-sm edit_coupon_codecsv waves-effect waves-classic" data-toggle="tooltip" data-placement="top" data-trigger="hover" id="csv_discount_edit_'.$value["id"].'" data-id="'.$value["id"].'" data-original-title="Edit" title="" style="display:;"><i class="fa fa-edit"></i></button>';

								$html .= '<tr>';
									$html .= '<td><input type="text" value="'.$value['coupon_code'] .'" class="coupon_codecsv" id="coupon_codecsv_'.$value["id"].'" name="coupon_codecsv" autocomplete="off" disabled=""></td>';
									$html .= '<td class="discount-action-col">'.$discountSave.''.$discountedit.''.$discountdelete.'</td>';
								$html .= '</tr>';
							}
						}else{
							$html .= '<tr><td colspan="2">No Record Found</td></tr>';
						}
						$html .= '</tbody>';
					$html .= '</table>';
			$html .='</div>';
			$res['DATA'] = $html;
			$res['SUCCESS'] = 'TRUE';	
		}
		echo json_encode($res);die();
	}

	public function update_csv_couponcode(){
		$res = [];
		if(!empty(parent::getVal("method")) && parent::getVal("method") == "update_csv_couponcode"){
			$coupon_csv_id 	= $_POST['coupon_csv_id'];
			$couponcode 	= $_POST['couponcode'];

			$sql = 'SELECT * FROM coupon_code_master WHERE discount_code="'.$couponcode.'" ';
			$discountdata = parent::selectTable_f_mdl($sql);
			if(!empty($discountdata)){
				$res["isSuccess"] = "False";
				$res["msg"] = "Coupon code already available.";
			}else{
				$sql = 'SELECT * FROM coupon_code_series_master WHERE coupon_code="'.$couponcode.'" AND id !="'.$coupon_csv_id.'" ';
				$couponcodedata = parent::selectTable_f_mdl($sql);
				if(!empty($couponcodedata)){
					$res["isSuccess"] = "False";
					$res["msg"] = "Coupon code already available.";
				}else{
					$update_data = [
						'coupon_code' => $couponcode
					];
					$res = parent::updateTable_f_mdl('coupon_code_series_master',$update_data,'id="'.$coupon_csv_id.'"');
				}
			}
		}
		echo common::sendJson($res,1);die();
	}

	public function deleteCsvCouponCode(){
		if (parent::isPOST()) {
			$resultArray = array();
			if (!empty(parent::getVal("method")) && parent::getVal("method") == "delete_csv_coupon_code") {
				$coupon_code_id = parent::getVal("coupon_code_id");
				$discount_code = parent::getVal("discount_code");
				
				parent::deleteTable_f_mdl('coupon_code_series_master', 'id =' . $coupon_code_id);
				$resultArray["isSuccess"] = "TRUE";
				$resultArray["msg"] = "Coupon code delete successfully.";

				common::sendJson($resultArray);
			}
		}
	}

}
?>
