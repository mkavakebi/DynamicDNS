<?php 
include_once 'config/config.php';
if($_SESSION['login']!=1){
	header("Location:login.php");
	exit();
}
?>
<?php include 'header.php';?>
<form method="post">
	query:<br>
	<textarea name="query" rows="5" cols="60"><?php if (isset($_REQUEST['query'])) echo stripslashes($query);?></textarea><br>
	<input type="submit" name="submit" value="query that!">
</form>
<?php 
if(isset($_REQUEST['query'])){
	$query=stripslashes($_REQUEST['query']);
	$stmt = $dbh->prepare($query);
	$head_hr=true;
	$stmt->execute();?>
	
 	<table border="1">
	<?php while($db_field = $stmt->fetch()){?>
		<?php if($head_hr==true){ $head_hr=false;?>
			<tr>
			<?php foreach ($db_field as $key => $value) {
				if(!is_int($key)){?>
				<th><?php echo $key;?></th>
				<?php }
			}?>
			</tr>
		<?php }?>
		<tr>
		<?php foreach ($db_field as $key => $value) {
			if(!is_int($key)){?>
			<td><?php echo $value;?></td>
			<?php }
		}?>
		</tr>
	<?php }?>
	</table>
<?php	echo "Query done!";	
}
?>