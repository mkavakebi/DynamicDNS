<?php

include_once 'config/config.php';
if($_SESSION['login']!=1){
	header("Location:login.php");
	exit();
}
?>
<?php
$mynas= new nas_class($dbh);
$editing=false;

if(isset($_REQUEST['submit_new'])){
	$c_priority = ($mynas->CountAll()+1)*10;
	$stmt = $dbh->prepare("INSERT INTO nas_balances (ip,domain,duration,enabled,group_name,priority) VALUES(?,?,?,?,?,?)");
	$stmt->execute(array($_REQUEST['ip'],$_REQUEST['domain'],$_REQUEST['duration'],0,$_REQUEST['group_name'],$c_priority));
	//$err[]=print_r($stmt->errorInfo(),true);
	$mynas->nas_balance_regulator();
	header('Location:index.php');
	//$this->Session->setFlash('New nas successfuly added.', 'default', array('class' => 'success-msg'));
}

if(isset($_REQUEST['submit_edit'])){
	$stmt = $dbh->prepare("UPDATE nas_balances SET ip=?,domain=?,duration=?,enabled=?,group_name=? WHERE id=?");
	$stmt->execute(array($_REQUEST['ip'],$_REQUEST['domain'],$_REQUEST['duration'],$_REQUEST['enabled'],$_REQUEST['group_name'],$_REQUEST['id']));
	//$err[]=print_r($stmt->errorInfo(),true);
	$mynas->nas_balance_regulator();
	header('Location:index.php');
	//$this->Session->setFlash('New nas successfuly added.', 'default', array('class' => 'success-msg'));
}

if(isset($_REQUEST['action'])){

	$action = $_REQUEST['action'];

	if($action == 'edit'){
		$stmt = $dbh->prepare("SELECT * FROM nas_balances WHERE id=?");
		$stmt->execute(array($_REQUEST['id']));
		$edit = $stmt->fetch();
		$editing = true;
	}
	if($action == 'delete'){
		$stmt = $dbh->prepare("DELETE FROM nas_balances WHERE id=?");
		$stmt->execute(array($_REQUEST['id']));
		//$this->Session->setFlash('Successfuly Deleted.', 'default', array('class' => 'success-msg'));
		$mynas->nas_balance_regulator();
		header('Location:index.php');
	}
	if($action == 'up'){
		$stmt = $dbh->prepare("SELECT * FROM nas_balances WHERE id=?");
		$stmt->execute(array($_REQUEST['id']));
		$c_p = $stmt->fetch();
		$stmt = $dbh->prepare("UPDATE nas_balances SET priority=? WHERE id=?");
		$stmt->execute(array($c_p['priority'] - 15,$_REQUEST['id']));
		
		$mynas->nas_balance_regulator();
		header('Location:index.php');
	}
	if($action == 'down'){
		$stmt = $dbh->prepare("SELECT * FROM nas_balances WHERE id=?");
		$stmt->execute(array($_REQUEST['id']));
		$c_p = $stmt->fetch();
		$stmt = $dbh->prepare("UPDATE nas_balances SET priority=? WHERE id=?");
		$stmt->execute(array($c_p['priority'] + 15,$_REQUEST['id']));
		
		$mynas->nas_balance_regulator();
		header('Location:index.php');
	}
}
//////////////////Listings
$stmt = $dbh->prepare("SELECT * FROM nas_balances ORDER BY domain,priority");
$stmt->execute();
$nases = $stmt->fetchAll();
//------------------
$stmt = $dbh->prepare("SELECT * FROM domains");
$stmt->execute();
$res1 = $stmt->fetchAll();
foreach($res1 as $row){
	$t = $mynas->NextAvailablePriority($row['domain'],$row['priority']);
	$nxt_ids[] = $t['id'];
}
//-------------------------------------------
$groups = $mynas->AllGroups();
$domains= $mynas->AllDomains();
//------------------
$curr_ids=$mynas->OKDomainsID();
//---------------------------------
$curr_RunningStates=$mynas->RunningStates();
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
			<h2>DynDns List</h2>
		</div>
		<div class="content_content">
			
			<fieldset>
			<legend>Schaduled List</legend>
				Next:<img src="icons/78-stopwatch.png">   Current:<img src="icons/59-flag.png"> *Durations are per minutes
				<table border="0" class="listTable">
					<tr>
						<th>IP</th>
						<th>Domain</th>
						<th>Groups</th>
						<th>Duration</th>
						<th>Enabled</th>
					</tr>
					<?php $i=true; foreach ($nases as $row) { ?>
					<tr class="<?php $i=!$i; echo ($i?'even':'odd');?>">
						<td><?php 
							if(in_array($row['id'], $nxt_ids)){?>
								<img src="icons/78-stopwatch.png">
							<?php }if(in_array($row['id'], $curr_ids)){?>
								<img src="icons/59-flag.png">
							<?php }
							echo $row['ip']; 
						?></td>
						<td><?php echo $row['domain']; ?></td>
						<td><?php echo $row['group_name']; ?></td>
						<td><?php echo $row['duration']; ?></td>
						<td><?php echo $row['enabled']; ?></td>
						<td>
							<a href="?id=<?php echo $row['id']; ?>&action=edit"><img src="icons/187-pencil.png"></a>
							<a href="?id=<?php echo $row['id']; ?>&action=delete"><img src="icons/22-skull-n-bones.png"></a>
							<a href="?id=<?php echo $row['id']; ?>&action=up"><img src="icons/57.2-upload.png"></a>
							<a href="?id=<?php echo $row['id']; ?>&action=down"><img src="icons/57-download.png"></a>
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
					<label>IP: </label><input name="ip" value="<?php if($editing) echo $edit['ip']; ?>">
					<label>Duration: </label><input name="duration" value="<?php if($editing) echo $edit['duration']; ?>">
					<label>Enabled: </label><input type="checkbox" name="enabled" value="1" <?php if($editing AND $edit['enabled']==1) echo 'checked=checked'; ?>><br>


					<label>Domain: </label>
					<select name="domain">
						<?php foreach($domains as $dom){?>
							<option <?php echo (($editing AND ($edit['domain']==$dom['domain']))?'selected="selected"':"");?>
							value="<?php echo $dom['domain'];?>"><?php echo $dom['domain'];?></option>
						<?php }?>
					</select>
					<label>Group: </label>
					<select name="group_name">
						<?php foreach($groups as $gr){?>
							<option <?php echo (($editing AND ($edit['group_name']==$gr['name']))?'selected="selected"':"");?>
								title="" value="<?php echo $gr['name'];?>"><?php echo $gr['name'];?></option>
						<?php }?>
					</select>
					<input type="submit" value="send" name="<?php echo ($editing?'submit_edit':'submit_new');?>">
				</form>
			</fieldset>

		</div>
	</div>
	</body>
</html>