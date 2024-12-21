<?php
include_once 'model/sa_edit_email_template_mdl.php';

class sa_edit_email_template_ctl extends sa_edit_email_template_mdl
{
	public $TempSession = "";

	function __construct(){	
		if(parent::isGET() || parent::isPOST()){
			$this->SITE_ACCESS_KEY = parent::getVal("stkn");
			
			$this->passedId = parent::getVal("eid");
		}
		
		common::CheckLoginSession();
	}
	
	function getEmailInfo(){
		$emailData = parent::getEmailInfo_f_mdl($this->passedId);
		
		if(!empty($emailData)){
			return $emailData;
		}
		else{
			header("Location: sa-email-template.php?stkn=".$this->SITE_ACCESS_KEY);
			exit;
		}
	}
	
	function saveTemplate(){
		if(parent::isPOST()){
			if(!empty(parent::getVal("method")) && parent::getVal("method") == "saveEmailBody"){
				
				$email_template_id = parent::getVal("email_template_id");
				$email_template_body = parent::getVal("email_template_body");
				$email_template_subject = parent::getVal("email_template_subject");
				$email_template_title = parent::getVal("email_template_title");
				$recipients             = (!empty(parent::getVal("recipients")) && parent::getVal("recipients"))?parent::getVal("recipients"):'';//Task 11
				
				parent::saveTemplate_f_mdl($email_template_id,$email_template_body,$email_template_subject,$email_template_title,$recipients);//Task 11 add parameter recipients		
			}
		}
	}
}