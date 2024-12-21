<?php
include_once 'model/sa_addresses_mdl.php';

class sa_product_tags_ctl extends sa_addresses_mdl
{
	public $TempSession = "";

	function __construct(){	
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
				case "enabeld_or_disabled_tag":
					$this->enabeld_or_disabled_tag();
				break;
				case "add_product_tags":
					$this->add_product_tags();
                break;
				case "check_add_tag":
					$this->check_add_tag();
                break;
			}
        }
    }
	
	function ProductTagsPagination(){
		if(parent::isPOST()){
			if(parent::getVal("hdn_method") == "product_tags_pagination")
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
						tag LIKE '%".trim($keyword)."%'
					)";
				}
				
				$cond_order = 'ORDER BY id DESC';
				if(!empty($sort_field)){
					$cond_order = 'ORDER BY '.$sort_field.' '.$sort_type;
				}
				
				$sql="
						SELECT count(id) as count FROM product_tag_master WHERE 1
						$cond_keyword
					";
				$all_count = parent::selectTable_f_mdl($sql);
				
				$sql1="
						SELECT id,tag,tag_status,created_on FROM product_tag_master WHERE 1
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
					$html .= '<th>#</th>';
					$html .= '<th>Tag Name</th>';
					$html .= '<th style="width: 15%;">Tag Status</th>';
					
					$html .= '</tr>';
					$html .= '</thead>';

					$html .= '<tbody>';

					if(!empty($all_list))
					{
						$sr = $sr_start;
							foreach($all_list as $single){
							$checked = '';
							if ($single['tag_status'] == 1) {
								$checked = 'checked';
							}
							$html .= '<tr>';
							$html .= '<td>'.$sr.'</td>';
							$html .= '<td>'.$single["tag"].'</td>';
							$html .= '<td>
										<div class="form-group toggal-email-temp">
											<label class="pt-3">Off</label>
											<label class="inex-switch">
												<input type="checkbox" id="tag_status" name="tag_status" value="'.$single["id"].'" '.$checked.'>
												<span class="inex-slider round"></span>
											</label>
											<label class="pt-3">On</label>
										</div>
									</td>';											
							$html .= '</tr>';	
							$sr++;
						}
					}
					else{
						$html .= '<tr>';
						$html .= '<td colspan="3" align="center">No Record Found</td>';
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

	function enabeld_or_disabled_tag(){
		$res = [];
		if(!empty(parent::getVal("method")) && parent::getVal("method") == "enabeld_or_disabled_tag"){
			$update_data = [
				'tag_status' => parent::getVal("tag_status")
			];
			$res = parent::updateTable_f_mdl('product_tag_master',$update_data,'id="'.parent::getVal('tag_id').'"');
		}
		echo common::sendJson($res,1);die();
	}

	function add_product_tags(){
		$res = [];
		if(parent::isPOST()){
			if(!empty(parent::getVal("method")) && parent::getVal("method") == "add_product_tags"){
			
				$tag_name = parent::getVal("tag_name");
				$tag_status = parent::getVal("tag_status");
				$sql1 = 'SELECT tag FROM `product_tag_master` WHERE tag="'.$tag_name.'" ';
				$tag_data = parent::selectTable_f_mdl($sql1);
				if(!empty($tag_data)){
					$res["isSuccess"] = "0";
					$res["msg"] = "Tag name is already exists. Please try other tag name.";
					
				}else{
					$insertNewColorData = [
						'tag' 	=> $tag_name,
						'tag_status'  	=> $tag_status,
						'created_on'            => date('Y-m-d H:i:s')
					];
					$res=parent::insertTable_f_mdl('product_tag_master',$insertNewColorData);
				}
				echo common::sendJson($res,1);die();
			}
		} 
	}

	public function check_add_tag(){
		if (parent::isPOST()) {
			if (!empty(parent::getVal("method")) && parent::getVal("method") == "check_add_tag") {
				$tag= parent::getVal('tag');
				$sql1 = 'SELECT tag from product_tag_master where tag ="'.$tag.'" ';
				
				$tag_data = parent::selectTable_f_mdl($sql1);
				if(!empty($tag_data)){
					$status=1;
				}else{
					$status=0;
				}
				echo $status;
			}
			die;
		}
	}
}
?>