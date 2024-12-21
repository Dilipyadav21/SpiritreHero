<?php
include_once 'model/sa_store_managers_mdl.php';
$path = preg_replace('/controller(?!.*controller).*/','',__DIR__);
include_once $path . 'libraries/Aws3.php';
class sa_store_managers_ctl extends sa_store_managers_mdl
{
	public $TempSession = "";

	function __construct(){	
		if(parent::isGET() || parent::isPOST()){
			$this->SITE_ACCESS_KEY = parent::getVal("stkn");
		}

		if(isset($_REQUEST['action'])){
			$action = $_REQUEST['action'];
			if($action=='delete_store_manager'){
				$this->delete_store_manager();exit;
			}else if($action=='send_invitation_store_manager'){
				$this->send_invitation_store_manager();exit;
			}
		}
		
		common::CheckLoginSession();
	}
	
	function store_manager_pagination(){

		if(parent::isPOST()){
			if(parent::getVal("hdn_method") == "store_managers_pagination")
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
						smm.id = '".trim($keyword)."' OR
                        smm.first_name = '".trim($keyword)."' OR
                        smm.last_name LIKE '%".trim($keyword)."%' OR
                        smm.email LIKE '%".trim($keyword)."%' OR
                        smm.mobile LIKE '%".trim($keyword)."%'
						)";
				}
				$cond_order = 'ORDER BY smm.id DESC';
				if(!empty($sort_field)){
					$cond_order = 'ORDER BY '.$sort_field.' '.$sort_type;
				}

				$sql="
						SELECT count(id) as count
						FROM `store_manager_master`
						WHERE 1 
						$cond_keyword
					";
				$all_count = parent::selectTable_f_mdl($sql);



				$sql1=" SELECT DISTINCT smm.id,smm.store_owner_id,smm.associate_store_ids,smm.first_name,smm.last_name,smm.email,smm.mobile,smm.status,smm.create_on,sodm.first_name as storeowner_first_name,sodm.last_name as storeowner_last_name FROM `store_manager_master` as smm LEFT JOIN store_owner_details_master as sodm ON smm.store_owner_id=sodm.id WHERE 1  
				$cond_keyword
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
					$html .= '<div class="table-responsive sa-store-managers-list">';
					$html .= '<table class="table table-bordered table-hover">';

					$html .= '<thead>';
					$html .= '<tr>';
					$html .= '<th>#</th>';
					$html .= '<th>Store Owner Name</th>';
					$html .= '<th>Manager First Name</th>';
					$html .= '<th>Manager Last Name</th>';
					$html .= '<th>Manager Email</th>';
					$html .= '<th>Associated Stores</th>';
					$html .= '<th>Manager Mobile</th>';
					$html .= '<th>Manager Status</th>';
					$html .= '<th>Created Date & Time</th>';
					$html .= '<th>Action</th>';
					$html .= '</tr>';
					$html .= '</thead>';

					$html .= '<tbody>';

					if(!empty($all_list)){
						$sr = $sr_start;
						foreach($all_list as $single){

							$store_sql='SELECT GROUP_CONCAT("<span>",`store_name`,"</span>") as store_name  from store_master where id IN('.$single['associate_store_ids'].')';
							$store_list = parent::selectTable_f_mdl($store_sql);
							$store_names=$store_list[0]['store_name'];
							$store_names=str_replace(",","",$store_names);

							$store_owner_name=$single['storeowner_first_name'].' '.$single['storeowner_last_name'];
							if($single['status']==0){
								$status = 'Active';
							}else if($single['status']==1){
								$status = 'Inactive';
							}else{
								$status = 'Has not accepted invitation'; 
							}

							$createtime = date('m/d/Y h:i A', strtotime($single['create_on']));
							$send_invitation_btn= '<button data-href="" href="javascript:void(0)" role="menuitem" class="dropdown-item send_invitation_btn " id="send_invitation_btn" data-id=' . $single["id"] . '>Send Invitation</button>';
							$delete_btn= '<button data-href="" href="javascript:void(0)" role="menuitem" class="dropdown-item stores_manager_delete_btn " data-id=' . $single["id"] . ' >Delete</button>';
							$action ='<div class="btn-group" role="group">
										<button type="button" class="btn btn-primary dropdown-toggle" id="exampleGroupDrop1" data-toggle="dropdown" aria-expanded="false">
											Actions
										</button>
							
										<div class="dropdown-menu" aria-labelledby="exampleGroupDrop1" role="menu" x-placement="bottom-start" style="position: absolute; transform: translate3d(0px, 36px, 0px); top: 0px; left: 0px; will-change: transform;">                                           
											<a role="menuitem" href="sa-store-managers-addedit.php?id=' . $single["id"] . '" class="dropdown-item stores_managers_view_btn">Edit</a>                                 
											'.$send_invitation_btn.'
											'.$delete_btn.'	
										</div>
									</div>	
							';

							$html .= '<tr>';
							$html .= '<td>'.$sr.'</td>';
							$html .= '<td>'.$store_owner_name.'</td>';
							$html .= '<td>'.$single['first_name'].'</td>';
							$html .= '<td>'.$single['last_name'].'</td>';
							$html .= '<td>'.$single['email'].'</td>';
							$html .= '<td>'.$store_names.'</td>';
							$html .= '<td>'.$single['mobile'].'</td>';
							$html .= '<td>'.$status.'</td>';
							$html .= '<td>'.$createtime.'</td>';
							$html .= '<td>'.$action.'</td>';
							$html .= '</tr>';
							$sr++;
						}
					}else{
						$html .= '<tr>';
						$html .= '<td colspan="8" align="center">No Record Found</td>';
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

	public function delete_store_manager()
	{
		$store_manager_id= parent::getVal('store_manager_id');

		$res=parent::deleteTable_f_mdl('store_manager_master', 'id =' . $store_manager_id);
        echo json_encode($res);die;
	}

    public function send_invitation_store_manager()
	{
        if (parent::isPOST()) {
            $res='';
			if (!empty(parent::getVal("action")) && parent::getVal("action") == "send_invitation_store_manager") {
		        $store_manager_id= parent::getVal('store_manager_id');

		        $res=parent::send_invitation_f_mdl($store_manager_id);             
            }
            echo json_encode($res);die;
        }
	}
}
?>
