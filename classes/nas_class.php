<?php
class nas_class
{
	protected $dbh;
	protected $my_db_field;
	
	function __construct($dbh) {
		$this->dbh=$dbh;
	}
	
	/*function AllDomains(){
		$stmt = $this->dbh->prepare("SELECT DISTINCT domain FROM nas_balances");
		$stmt->execute();
		$res = $stmt->fetchAll();
		foreach($res as $row)
			$domains[] = $row['domain'];
			
		return $domains;
	}*/
	
	function RunningStates(){
		$stmt = $this->dbh->prepare("SELECT * FROM domains");
		$stmt->execute();
		$res = $stmt->fetchAll();
		return $res;
	}
	
	function RunningDomains(){
		$stmt = $this->dbh->prepare("SELECT DISTINCT nas_balances.domain 
		FROM nas_balances,domains,groups 
		WHERE (nas_balances.domain=domains.domain 
		AND nas_balances.group_name=groups.name) AND groups.enabled=1 
		AND domains.enabled=1
		AND nas_balances.enabled=1");
		$stmt->execute();
		$res = $stmt->fetchAll();
		foreach ($res as $k=>$v){
			$t[]=$v;
		}

		return $t;
	}
	
	function FirstPriorities(){
		$stmt = $this->dbh->prepare("SELECT * FROM nas_balances WHERE priority=10");
		$stmt->execute();
		$res = $stmt->fetchAll();
		return $res;
	}
	
	function CountAll(){
		$stmt = $this->dbh->prepare("SELECT COUNT(*) FROM nas_balances");
		$stmt->execute();
		$res = $stmt->fetch();
		return $res[0];
	}
	
	function nas_balance_regulator(){
		$domains = $this->AllDomains();
		foreach($domains as $my_domain){
			$stmt = $this->dbh->prepare("SELECT * FROM nas_balances WHERE domain=? ORDER BY priority");
			$stmt->execute(array($my_domain));
			$res = $stmt->fetchAll();
			$i=0;
			foreach($res as $row){
				$i=$i+1;
				$data2['NasBalance']['priority'] = $i*10;
				$stmt = $this->dbh->prepare("UPDATE nas_balances SET priority=? WHERE id=?");
				$stmt->execute(array($i*10,$row['id']));
			}
		}
	}
	
	function MaxPriority($domain){
		$stmt = $this->dbh->prepare("SELECT COUNT(*) FROM nas_balances WHERE domain=?");
		$stmt->execute(array($domain));
		$res = $stmt->fetch();
		return $res[0]*10;
	}
	
	function NextPriority($domain,$priority){
		if($priority >= $this->MaxPriority($domain)) {
			return 10;
		}else{
			return $priority + 10;		
		}
	}
	
	function FirstAvailablePriority($domain){
		$stmt2 = $this->dbh->prepare("SELECT nas_balances.*  FROM nas_balances,domains,groups 
		WHERE (nas_balances.domain=domains.domain 
		AND nas_balances.group_name=groups.name) 
		AND groups.enabled=1 
		AND domains.enabled=1
		AND nas_balances.enabled=1
		AND nas_balances.domain=?
		GROUP BY nas_balances.domain
		ORDER BY nas_balances.priority");
		$stmt2->execute(array($domain));
		if($Record = $stmt2->fetch()){
			return $Record;
		}else{
			return null;
		}
	}
	
	function NextAvailablePriority($domain,$priority){
		$stmt2 = $this->dbh->prepare("SELECT nas_balances.*  FROM nas_balances,domains,groups 
		WHERE (nas_balances.domain=domains.domain 
		AND nas_balances.group_name=groups.name) 
		AND groups.enabled=1 
		AND domains.enabled=1
		AND nas_balances.enabled=1
		AND nas_balances.domain=?
		AND nas_balances.priority>?
		GROUP BY nas_balances.domain
		ORDER BY nas_balances.priority");
		$stmt2->execute(array($domain,$priority));
		if($Record = $stmt2->fetch()){
			return $Record;
		}else{
			return $this->FirstAvailablePriority($domain);
		}
	}
	
	function NextRecord($id){
		$stmt = $this->dbh->prepare("SELECT * FROM nas_balances WHERE id=?");
		$stmt->execute(array($id));
		$res = $stmt->fetch();
		
		$my_prio = $res['priority'];
		$my_domain = $res['domain'];
		
		$next_prio = $this->NextPriority($my_domain,$my_prio);
		
		$stmt = $this->dbh->prepare("SELECT * FROM nas_balances WHERE domain=? AND priority=?");
		$stmt->execute(array($my_domain,$next_prio));
		return $stmt->fetch();
	}
	
	function OKDomainsID(){
		$stmt = $this->dbh->prepare("SELECT t1.id FROM nas_balances t1 RIGHT JOIN (SELECT m1.*
FROM dyn_change m1 LEFT JOIN dyn_change m2
 ON (m1.domain= m2.domain AND m1.id < m2.id)
WHERE m2.id IS NULL AND m1.state='ok') as t2
ON (t1.domain=t2.domain AND t1.ip=t2.ip)");
		//$stmt = $this->dbh->prepare("");
		$stmt->execute();
		while($p=$stmt->fetch()){
			$t[]=$p['id'];
		}
		return $t;
	}
	
	function SendRequest($domain,$new_ip){
		//$url="http://abulolo:09358797029@members.dyndns.org/nic/update?hostname=abulolo.dyndns.org&myip=184.154.191.10&wildcard=NOCHG&mx=NOCHG&backmx=NOCHG";
		$ag='Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1';
		
		$username = "shr6557";
		$password = "1viS0fT595";
		
		$url= "https://members.dyndns.org/nic/update?hostname=".$domain."&myip=".$new_ip."&wildcard=NOCHG&mx=NOCHG&backmx=NOCHG";
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_USERAGENT,$ag);
		$output = curl_exec($ch);
		curl_close($ch);
		
		if($output == 'good '.$new_ip){
			$success = true;
			$currentstate='ok';
		}else{
			$success = false;
			$currentstate='';
		}
		
		$lasrrecord=$this->LastLogRecord($domain);
		if($lasrrecord!=null AND $lasrrecord['state']==$currentstate){
			$stmt = $this->dbh->prepare("UPDATE dyn_change SET ip=?,response=? WHERE id=?");
			$stmt->execute(array($new_ip,$output,$lasrrecord['id']));
		}else{
			$stmt = $this->dbh->prepare("INSERT INTO dyn_change (domain,ip,response,state) VALUES (?,?,?,?)");
			$stmt->execute(array($domain,$new_ip,$output,$currentstate));
		}
		
		$stmt = $this->dbh->prepare("INSERT INTO dyn_log (domain,ip,response,state) VALUES (?,?,?,?)");
		$stmt->execute(array($domain,$new_ip,$output,$currentstate));
		
		$stmt = $this->dbh->prepare("DELETE FROM dyn_log WHERE id NOT IN ( 
									SELECT id FROM (SELECT id FROM dyn_log ORDER BY date DESC LIMIT 100) x )");
		$stmt->execute();
		
		$stmt = $this->dbh->prepare("DELETE FROM dyn_change WHERE id NOT IN ( 
									SELECT id FROM (SELECT id FROM dyn_change ORDER BY date DESC LIMIT 500) x )");
		$stmt->execute();
		
		echo 'domain:'.$domain.' ip:'.$new_ip.' response:'.$output.'<br>';
	}
	
	function LastLogRecord($domain){
		$stmt = $this->dbh->prepare("SELECT * FROM dyn_change WHERE domain=? ORDER BY date DESC LIMIT 0,1");
		$stmt->execute(array($domain));
		if($t=$stmt->fetch()){
			return $t;
		}else{
			return null;
		}
	}
	function AllDomains(){
		$stmt = $this->dbh->prepare("SELECT * FROM domains");
		$stmt->execute();
		return $stmt->fetchAll();
	}
	
	function AllGroups(){
		$stmt = $this->dbh->prepare("SELECT * FROM groups");
		$stmt->execute();
		return $stmt->fetchAll();
	}
	
}
?>