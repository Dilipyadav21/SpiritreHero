<?php
include_once 'model/sa_orders_test_mdl.php';

class sa_orders_ctl extends sa_orders_mdl
{
	public $TempSession = "";

	function __construct(){	
		if(parent::isGET() || parent::isPOST()){
			$this->SITE_ACCESS_KEY = parent::getVal("stkn");
		}
		
		common::CheckLoginSession();
	}
	
	function getStoreDropdownInfo(){
		return parent::getStoreDropdownInfo_f_mdl();
	}
	
	function updateCancelOrderFlag(){
		if(parent::isPOST()){
			if(!empty(parent::getVal("method")) && parent::getVal("method") == "cancel_order") {
				$id = parent::getVal("id");
				$store_master_id = parent::getVal("store_master_id");
				
				return parent::updateCancelOrderFlag_f_mdl($id,$store_master_id);				
			}
		}
	}
	
	function get_order_list_post(){
		if(parent::isPOST()){
			if(parent::getVal("hdn_method") == "orders_pagination")
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

				$cond_keyword = '';
				if(isset($keyword) && !empty($keyword)){
					$cond_keyword = "AND (
							store_orders_master.shop_order_id = '$keyword' OR
							store_orders_master.shop_order_number = '$keyword' OR
							store_orders_master.cust_email LIKE '%$keyword%' OR
							store_orders_master.cust_phone LIKE '%$keyword%' OR
							store_orders_master.cust_name LIKE '%$keyword%'
						)";
				}
				$cond_order = 'ORDER BY store_orders_master.id DESC';
				if(!empty($sort_field)){
					$cond_order = 'ORDER BY '.$sort_field.' '.$sort_type;
				}

				$selectedStore = "";
				if( (isset($_REQUEST['select_store']))&&(!empty($_REQUEST['select_store'])) ){
					$selectedStore = 'AND store_master.id = "'.$_REQUEST['select_store'].'"';
				}
				
				
				$sql="
						SELECT count(store_orders_master.id) as count
						FROM `store_orders_master`
						LEFT JOIN `store_master` ON store_master.id = store_orders_master.store_master_id
						WHERE 1
						$cond_keyword
						$selectedStore
					";
				$all_count = parent::selectTable_f_mdl($sql);

				$sql1="
						SELECT DISTINCT 
							store_master.store_name,
							store_orders_master.id, 
							store_orders_master.store_master_id,
							store_orders_master.shop_order_id, store_orders_master.shop_order_number, store_orders_master.total_price, 
							store_orders_master.cust_email, 
							store_orders_master.cust_name,
							store_orders_master.is_order_cancel,
							store_orders_master.created_on_ts,
							store_orders_master.json_data
						FROM `store_orders_master`
						LEFT JOIN `store_master` ON store_master.id = store_orders_master.store_master_id
						WHERE 1
						$cond_keyword
						$selectedStore

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
					//$html .= '<th>#</th>';
					$html .= '<th>Store Name</th>';
					//$html .= '<th>Shop Order Id</th>';// class="sort_th" data-sort_field="shop_order_id"
					$html .= '<th>Order Number</th>';
					$html .= '<th>Order mismatch</th>';
					$html .= '<th>Price</th>';
					$html .= '<th>Customer Name</th>';
					$html .= '<th>Email</th>';
					$html .= '<th colspan="2" style="text-align: center;">Action</th>';
					$html .= '</tr>';
					$html .= '</thead>';

					$html .= '<tbody>';

					if(!empty($all_list)){
						$sr = $sr_start;
						foreach($all_list as $single){

							$sql2="
									SELECT count(id) as item_count
									FROM `store_order_items_master`
									WHERE store_orders_master_id = '".$single['id']."'

								";
							$all_list2 = parent::selectTable_f_mdl($sql2);

							$order_json_count = json_decode($single['json_data'],1);


							$html .= '<tr>';
							
							$html .= '<td>'.$single['store_name'].'</td>';
							
							$html .= '<td><a href="sa-order-view.php?stkn='.parent::getVal("stkn").'&oid='.$single['id'].'">'.$single['shop_order_number'].'</a></td>';
							
							if(count($order_json_count['line_items'])!=$all_list2[0]['item_count']){
								$html .= '<td>'.count($order_json_count['line_items']).', '.$all_list2[0]['item_count'].'</td>';
							}else{
								$html .= '<td></td>';
							}
							$html .= '<td>$'.$single['total_price'].'</td>';
							$html .= '<td>'.$single['cust_name'].'</td>';
							$html .= '<td>'.$single['cust_email'].'</td>';
							
							if($single["is_order_cancel"] == '0')
							{
								$html .= '<td style="text-align:center;"><button data-href="sa-orders-test.php?stkn='.parent::getVal("stkn").'&oid='.$single["id"].'" data-id = "'.$single["id"].'" data-store_master_id = "'.$single["store_master_id"].'" type="button" class="btn btn-danger btn-cancel-order">Cancel</button></td>';
							}
							else if($single["is_order_cancel"] == '1')
							{
								$html .= '<td style="text-align:center;">Cancelled</td>';
							}
							
							$html .= '<td style="text-align:center;"><button data-href="sa-order-view.php?stkn='.parent::getVal("stkn").'&oid='.$single["id"].'" type="button" class="btn btn-info btn-edit-order">Edit</button></td>';

							$html .= '</tr>';
							$sr++;
						}
					}else{
						$html .= '<tr>';
						$html .= '<td colspan="7" align="center">No Record Found</td>';
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