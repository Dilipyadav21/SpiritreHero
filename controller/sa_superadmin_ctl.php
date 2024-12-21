<?php 
include_once 'model/sa_superadmin_mdl.php';
$path = preg_replace('/controller(?!.*controller).*/','',__DIR__);
include_once $path . '/libraries/Aws3.php';
class sa_superadmin_ctl extends sa_superadmin_mdl
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
				case "delete_super_admin":
					$this->deleteSuperadmin();
                break;
			}
        }
    }
    
	function superadminPagination(){
		if(parent::isPOST()){
			if(parent::getVal("hdn_method") == "superadmin_pagination")
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
						id = '".trim($keyword)."' OR
						email LIKE '%".trim($keyword)."%' OR
						first_name LIKE '%".trim($keyword)."%' OR
						last_name LIKE '%".trim($keyword)."%' OR
						status LIKE '%".trim($keyword)."%'
					)";
				}
				$cond_order = 'ORDER BY id DESC';
				if(!empty($sort_field)){
					$cond_order = 'ORDER BY '.$sort_field.' '.$sort_type;
				}

				$sql="
                    SELECT count(id) as count
                    FROM `users` WHERE role_id ='1'
                    $cond_keyword
                ";
				$all_count = parent::selectTable_f_mdl($sql);

				$sql1="
                    SELECT DISTINCT * FROM `users` WHERE role_id='1' 
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
					$html .= '<th>First Name</th>';
					$html .= '<th>Last Name</th>';
					$html .= '<th>Email</th>';
					$html .= '<th>Status</th>';
					$html .= '<th>Created Date & Time</th>';
                    $html .= '<th>Actions</th>';
					$html .= '</tr>';
					$html .= '</thead>';

					$html .= '<tbody>';

					if(!empty($all_list)){
						$sr = $sr_start;
						foreach($all_list as $single){
							$html .= '<tr>';
							// $html .= '<td><input type="checkbox" value='.$single["id"].' class="checkBoxClass"></td>';
							$html .= '<td>'.$sr.'</td>';
							$html .= '<td>'.$single['first_name'].'</td>';
							$html .= '<td>'.$single['last_name'].'</td>';
							$html .= '<td>'.$single['email'].'</td>';
                            
							if ($single['status']== '1') {
								$html .= '<td> Active </td>';
							} else {
								$html .= '<td> Inactive </td>';
							}
                            
                            if (!empty($single['created_at'])) {
								$html .= '<td>' . date('m/d/Y h:i A', strtotime($single["created_at"])) . '</td>';
							} else {
								$html .= '<td></td>';
							}
                            if((isset($_SESSION['user_id']))&&(!empty($_SESSION['user_id'])) ){
								$SA1 =common::SUPER_ADMIN_EMAIL_ONE;
								$SA2 =common::SUPER_ADMIN_EMAIL_TWO;
								if($single['email']==$SA1 || $single['email']==$SA2){
									$html .= '<td> Owner </td>';
                                }else{
                                    $html .= '<td><div class="btn-group" role="group">
									  <button type="button" class="btn btn-primary dropdown-toggle" id="exampleGroupDrop1" data-toggle="dropdown" aria-expanded="false">
										Actions
									  </button>
									  <div class="dropdown-menu" aria-labelledby="exampleGroupDrop1" role="menu" x-placement="bottom-start" style="position: absolute; transform: translate3d(0px, 36px, 0px); top: 0px; left: 0px; will-change: transform;">
										
										<a role="menuitem" href="sa-addedit-superadmin.php?stkn='.$_POST['stkn'].'&id='.$single["id"] .'" class="dropdown-item superadmin_edit_btn">Edit</a>
										<button data-href="" href="javascript:void(0)" role="menuitem" class="dropdown-item delete_superadmin" data-id=' . $single["id"] . '>Delete</button>
									';
                                }
                            }
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
			die();
		}
	}

	function deleteSuperadmin()
	{
		if (parent::isPOST()) {
			if (!empty(parent::getVal("method")) && parent::getVal("method") == "delete_super_admin") {
				$super_admin_id = parent::getVal("super_admin_id");

				parent::deleteTable_f_mdl('users', 'id =' . $super_admin_id);
				
				$resultArray = array();
				$resultArray["isSuccess"] = "TRUE";
				$resultArray["msg"] = "Super admin delete successfully.";

				common::sendJson($resultArray);
			}
		}
	}
}
?>
