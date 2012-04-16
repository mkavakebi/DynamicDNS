<?php
include_once 'config/config.php';
if($_SESSION['login']!=1){
	header("Location:logs.php");
	exit();
}
?>
<?php
$mynas= new nas_class($dbh);

if(isset($_REQUEST['submit_manual'])){
	$mynas->SendRequest($_REQUEST['domain'],$_REQUEST['ip']);
}

$stmt = $dbh->prepare("SELECT * FROM dyn_log ORDER BY date DESC LIMIT 0,20");
$stmt->execute();
$logs = $stmt->fetchAll();
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
		<div class="content_title">
			<h2>Log and Request</h2>
		</div>
			
			<fieldset>
			<legend>Manual REquest</legend>
				<form method="POST" >
					<label>IP: </label><input name="ip">
					<label>Domain: </label><input name="domain">
					<input type="submit" value="send" name="submit_manual">
				</form>
			</fieldset>
			
			<fieldset>
			<legend>LOG List</legend>
				<table border="0" class="listTable">
					<tr>
						<th>IP</th>
						<th>Domain</th>
						<th>Response</th>
						<th>State</th>
						<th>Date</th>
					</tr>
					<?php $i=true; foreach ($logs as $row) { ?>
					<tr class="<?php $i=!$i; echo ($i?'even':'odd');?>">
						<td><?php echo $row['ip']; ?></td>
						<td><?php echo $row['domain']; ?></td>
						<td><?php echo $row['response']; ?></td>
						<td><?php echo $row['state']; ?></td>
						<td><?php echo $row['date']; ?></td>
					</tr>
					<?php } ?>
				</table>
			</fieldset>
		</div>
	</div>
	</body>
</html>