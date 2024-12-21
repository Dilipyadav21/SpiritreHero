<?php
include_once 'model/sa_email_template_mdl.php';

class sa_email_template_ctl extends sa_email_template_mdl
{
	public $TempSession = "";

	function __construct(){	
		if(parent::isGET() || parent::isPOST()){
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
				case "enabeld_or_disabled":
					$this->enabeldOrDisabled();
				break;
			}
		}
	}

	function emailPagination(){
		if(parent::isPOST()){
			if(parent::getVal("hdn_method") == "email_pagination")
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
							title LIKE '%".trim($keyword)."%' OR
							subject LIKE '%".trim($keyword)."%'
						)";
				}
				$cond_order = 'ORDER BY id DESC';
				if(!empty($sort_field)){
					$cond_order = 'ORDER BY '.$sort_field.' '.$sort_type;
				}

				$emailSentTo = "";
				if ((isset($_POST['email_sent_to'])) && $_POST['email_sent_to'] != '') {
					if ($_POST['email_sent_to'] == "All") {
						$emailSentTo = '';
					} else {
						$emailSentTo = 'AND email_sent_to = "' . $_REQUEST['email_sent_to'] . '"';
					}
				}
				
				$sql="
						SELECT count(id) as count FROM email_templates_master
						WHERE 1
						$cond_keyword
						$emailSentTo
					";
				$all_count = parent::selectTable_f_mdl($sql);
				
				
				$sql1="
						SELECT id,title,subject,body,variables,status,email_sent_to,email_sent_condition FROM email_templates_master WHERE 1
						$cond_keyword
						$emailSentTo
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
					
					$html .= '<th>#</th>';
					$html .= '<th>Title</th>';
					$html .= '<th>Subject</th>';
					$html .= '<th>Email Trigger Condition</th>';
					$html .= '<th>Status</th>';
					$html .= '<th>Action</th>';
					
					$html .= '</tr>';
					$html .= '</thead>';

					$html .= '<tbody>';

					
					if(!empty($all_list))
					{
						$sr = $sr_start;
						foreach($all_list as $single){
							$checked = '';
							if ($single['status'] == 1) {
								$checked = 'checked';
							}
							$html .= '<tr>';
							$html .= '<td>'.$sr.'</td>';
							$html .= '<td>'.$single["title"].'</td>';
							$html .= '<td>'.$single["subject"].'</td>';
							$html .= '<td>'.$single["email_sent_condition"].'</td>';
							if($single['id'] == 4){
								$html .= '<td>
									<div class="form-group toggal-email-temp">
					                    <label class="pt-3">Off</label>
					                    <label class="inex-switch">
					                        <input type="checkbox" id="email_status" name="email_status" value="'.$single["id"].'" '.$checked.'>
					                        <span class="inex-slider round"></span>
					                    </label>
					                    <label class="pt-3">On</label>
					                </div>
								</td>';	
							}else{
								$html .= '<td></td>';	
							}

							$html .= '<td><button data-href="sa-edit-email-template.php?stkn='.parent::getVal("stkn").'&eid='.$single["id"].'" type="button" class="btn btn-info waves-effect waves-classic btn-addedit-org">Edit</button>';
							
							$html .= '</tr>';	
							$sr++;
						}
					}
					else{
						$html .= '<tr>';
						$html .= '<td colspan="5" align="center">No Record Found</td>';
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

	function enabeldOrDisabled(){
		$res = [];
		if(!empty(parent::getVal("method")) && parent::getVal("method") == "enabeld_or_disabled"){
			
			//update email template status
			$emt_update_data = [
				'status' => parent::getVal("status")
			];

			$res = parent::updateTable_f_mdl('email_templates_master',$emt_update_data,'id="'.parent::getVal('email_temp_id').'"');
		}
		echo common::sendJson($res,1);die();
	}
}
?>