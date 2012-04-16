<?php
include_once 'config/config.php';
if (isset($_POST['pass']) && $_POST['pass']=='your password here'){
	$_SESSION['login']=1;
	if(isset($_REQUEST['ret'])){
		header("Location:".$_REQUEST['ret']);
	}else{
		header("Location:index.php");
	}
}else{
	$_SESSION['login']=0;
}
?>
<form method=post>
Password:<input name=pass type=password>
</form>