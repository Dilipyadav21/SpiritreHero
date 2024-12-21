<?php
include_once 'model/header_mdl.php';

class header_ctl extends header_mdl
{
	function __construct()
	{
		if(parent::isGET() || parent::isPOST()){
			$this->SITE_ACCESS_KEY = parent::getVal("stkn");
		}

		common::CheckLoginSession();
	}

	function loginUserDetails()
	{
		$user_id = $_SESSION['user_id'];
		$sql="SELECT email,password,first_name,last_name,id,status FROM users WHERE id = '".$user_id."' ";
		$getData = parent::selectTable_f_mdl($sql);
		return $getData;die();
	}

}
?>