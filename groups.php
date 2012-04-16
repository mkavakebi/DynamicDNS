<?php

include_once 'config/config.php';
if($_SESSION['login']!=1){
	header("Location:login.php");
	exit();
}
?>
<?php
$mynas=new nas_class($dbh);
$editing=false;

if(isset($_REQUEST['submit_new'])){
	
	$stmt = $dbh->prepare("SELECT * FROM groups WHERE name=? ");
	$stmt->execute(array($_REQUEST['name']));
	if($stmt->fetch()){
		$err[]='Group name is existing.';
	}else{
		$stmt = $dbh->prepare("INSERT INTO groups (name,enabled) VALUES(?,?)");
		$stmt->execute(array($_REQUEST['name'],isset($_REQUEST['enabled'])));
	}
}

if(isset($_REQUEST['submit_edit'])){
	$stmt = $dbh->prepare("SELECT * FROM groups WHERE name=? AND id!=?");
	$stmt->execute(array($_REQUEST['name'],$_REQUEST['id']));
	if($stmt->fetch()){
		$err[]='Group name is existing.';
	}else{
		$stmt = $dbh->prepare("UPDATE groups SET name=?,enabled=? WHERE id=?");
		$stmt->execute(array($_REQUEST['name'],$_REQUEST['enabled'],$_REQUEST['id']));
	}
}

if(isset($_REQUEST['action'])){

	$action = $_REQUEST['action'];

	if($action == 'edit'){
		$stmt = $dbh->prepare("SELECT * FROM groups WHERE id=?");
		$stmt->execute(array($_REQUEST['id']));
		$edit = $stmt->fetch();
		$editing = true;
	}
	if($action == 'delete'){
		$stmt = $dbh->prepare("DELETE FROM groups WHERE id=?");
		$stmt->execute(array($_REQUEST['id']));
	}
}
//////////////////Listings
$groups = $mynas->AllGroups();
?>
<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
		<title>Master VPN in touch</title>
		<link type="text/css" href="css/style.css" rel="stylesheet"/>
	</head>
	<body>
	<div class="page">
		<?php include('header.php');?>
		<?php if(count($err)){?>
		<div class="errdiv">
			<?php foreach($err as $r){?>
				<p><?php echo $r;?></p>
			<?php }?>
		</div>
		<?php }?>
		<div class="content_title">
			<h2>Groups</h2>
		</div>
		<div class="content_content">
			<fieldset>
			<legend>Group List</legend>
				<table border="0" class="listTable">
					<tr>
						<th>Name</th>
						<th>Enabled</th>
					</tr>
					<?php $i=true; foreach ($groups as $row) { ?>
					<tr class="<?php $i=!$i; echo ($i?'even':'odd');?>">
						<td><?php echo $row['name']; ?></td>
						<td><?php echo ($row['enabled']=='1'?'yes':'no'); ?></td>
						<td>
							<a href="?id=<?php echo $row['id']; ?>&action=edit"><img src="icons/187-pencil.png"></a>
							<a href="?id=<?php echo $row['id']; ?>&action=delete"><img src="icons/22-skull-n-bones.png"></a>
						</td>
					</tr>
					<?php } ?>
				</table>
			</fieldset>
			
			<fieldset>
			<legend>ADD & EDIT</legend>
				<form method="POST" >
					<?php if($editing) {?>
						<input type=hidden name="id" value="<?php echo $edit['id']; ?>">
					<?php }?>
					<label>Name: </label><input name="name" value="<?php if($editing) echo $edit['name']; ?>">
					<label>Enabled: </label><input type="checkbox" name="enabled" value="1" <?php if($editing AND $edit['enabled']==1) echo 'checked=checked'; ?>>
					<input type="submit" value="send" name="<?php echo ($editing?'submit_edit':'submit_new');?>">
				</form>
			</fieldset>
		</div>
	</div>
	</body>
</html>