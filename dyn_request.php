<?php
///it must be run as deamon(CronJob)
include_once 'config/config.php';

function write_log($log){
	$myFile = "log.txt";
	$fh = fopen($myFile, 'a');
	$stringData = $log."\n";
	fwrite($fh, $stringData);
	fclose($fh);
}
/////////////////////////////
$mynas= new nas_class($dbh);
//$stmt = $dbh->prepare("SELECT * FROM nas_balances,domains,groups WHERE (nas_balances.domain=domains.doamin AND nas_balances.group=groups.name)");
$stmt = $dbh->prepare("SELECT domains.*  FROM nas_balances,domains,groups 
WHERE (nas_balances.domain=domains.domain 
AND nas_balances.group_name=groups.name) 
AND groups.enabled=1 
AND domains.enabled=1
AND nas_balances.enabled=1 
GROUP BY nas_balances.domain");
$stmt->execute();
$actdomains = $stmt->fetchAll();

foreach($actdomains as $row){
	$stmt2 = $dbh->prepare("SELECT nas_balances.*  FROM nas_balances,domains,groups 
WHERE (nas_balances.domain=domains.domain 
AND nas_balances.group_name=groups.name) 
AND groups.enabled=1 
AND domains.enabled=1
AND nas_balances.enabled=1
AND nas_balances.id=?");
	$stmt2->execute(array($row['p_id']));
	$currentRecord = $stmt2->fetch();
	if(empty($currentRecord)){
		echo 'not found request first';echo '<br>';
		if ($f_record=$mynas->FirstAvailablePriority($row['domain'])){
			$stmt = $dbh->prepare("UPDATE domains SET count=1,priority=?,p_id=? WHERE id=?");
			$stmt->execute(array($f_record['priority'],$f_record['id'],$row['id']));
			$mynas->SendRequest($f_record['domain'],$f_record['ip']);
		}
	}else{
		if ( $row['count'] >= $currentRecord['duration']){
			$nxt_record =  $mynas->NextAvailablePriority($row['domain'],$row['priority']);
			//TODO : if The duration was zero goto next;
			$stmt = $dbh->prepare("UPDATE domains SET count=1,priority=?,p_id=? WHERE id=?");
			$stmt->execute(array($nxt_record['priority'],$nxt_record['id'],$row['id']));
			
			$mynas->SendRequest($nxt_record['domain'],$nxt_record['ip']);
		}else{
			$stmt = $dbh->prepare("UPDATE domains SET count=? WHERE id=?");
			$stmt->execute(array($row['count']+1,$row['id']));
		}
	}
}
?>