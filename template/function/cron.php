<?php
function cron(): void
{
	global $db;
	$time=time();
	if(get_setting('nextcron')>$time){
		return;
	}
	update_setting('nextcron', $time+10);
	// delete old sessions
	$stmt=$db->prepare('DELETE FROM ' . PREFIX . 'sessions WHERE (status<=2 AND lastpost<(?-60*(SELECT value FROM ' . PREFIX . "settings WHERE setting='guestexpire'))) OR (status>2 AND lastpost<(?-60*(SELECT value FROM " . PREFIX . "settings WHERE setting='memberexpire'))) OR (status<3 AND exiting>0 AND lastpost<(?-(SELECT value FROM " . PREFIX . "settings WHERE setting='exitwait')));");
	$stmt->execute([$time, $time, $time]);
	// delete old messages
	$limit=get_setting('messagelimit');
	$stmt=$db->query('SELECT id FROM ' . PREFIX . "messages WHERE poststatus=1 OR poststatus=4 ORDER BY id DESC LIMIT 1 OFFSET $limit;");
	if($id=$stmt->fetch(PDO::FETCH_NUM)){
		$stmt=$db->prepare('DELETE FROM ' . PREFIX . 'messages WHERE id<=?;');
		$stmt->execute($id);
	}
	$stmt=$db->prepare('DELETE FROM ' . PREFIX . 'messages WHERE id IN (SELECT * FROM (SELECT id FROM ' . PREFIX . 'messages WHERE postdate<(?-60*(SELECT value FROM ' . PREFIX . "settings WHERE setting='messageexpire'))) AS t);");
	$stmt->execute([$time]);
	// delete expired ignored people
	$result=$db->query('SELECT id FROM ' . PREFIX . 'ignored WHERE ign NOT IN (SELECT nickname FROM ' . PREFIX . 'sessions UNION SELECT nickname FROM ' . PREFIX . 'members UNION SELECT poster FROM ' . PREFIX . 'messages) OR ignby NOT IN (SELECT nickname FROM ' . PREFIX . 'sessions UNION SELECT nickname FROM ' . PREFIX . 'members UNION SELECT poster FROM ' . PREFIX . 'messages);');
	$stmt=$db->prepare('DELETE FROM ' . PREFIX . 'ignored WHERE id=?;');
	while($tmp=$result->fetch(PDO::FETCH_NUM)){
		$stmt->execute($tmp);
	}
	// delete files that do not belong to any message
	$result=$db->query('SELECT id FROM ' . PREFIX . 'files WHERE postid NOT IN (SELECT id FROM ' . PREFIX . 'messages UNION SELECT postid FROM ' . PREFIX . 'inbox);');
	$stmt=$db->prepare('DELETE FROM ' . PREFIX . 'files WHERE id=?;');
	while($tmp=$result->fetch(PDO::FETCH_NUM)){
		$stmt->execute($tmp);
	}
	// delete old notes
	$limit=get_setting('numnotes');
	$to_keep = [];
	$stmt = $db->query('SELECT id FROM ' . PREFIX . "notes WHERE type=0 ORDER BY id DESC LIMIT $limit;");
	while($tmp = $stmt->fetch(PDO::FETCH_ASSOC)){
		$to_keep []= $tmp['id'];
	}
	$stmt = $db->query('SELECT id FROM ' . PREFIX . "notes WHERE type=1 ORDER BY id DESC LIMIT $limit;");
	while($tmp = $stmt->fetch(PDO::FETCH_ASSOC)){
		$to_keep []= $tmp['id'];
	}
	$query = 'DELETE FROM ' . PREFIX . 'notes WHERE type!=2 AND type!=3 AND type!=4';
	if(!empty($to_keep)){
		$query .= ' AND id NOT IN (';
		for($i = count($to_keep); $i > 1; --$i){
			$query .= '?, ';
		}
		$query .= '?)';
	}
	$stmt = $db->prepare($query);
	$stmt->execute($to_keep);
	$result=$db->query('SELECT editedby, COUNT(*) AS cnt FROM ' . PREFIX . "notes WHERE type=2 GROUP BY editedby HAVING cnt>$limit;");
	$stmt=$db->prepare('DELETE FROM ' . PREFIX . 'notes WHERE (type=2 OR type=3) AND editedby=? AND id NOT IN (SELECT * FROM (SELECT id FROM ' . PREFIX . "notes WHERE (type=2 OR type=3) AND editedby=? ORDER BY id DESC LIMIT $limit) AS t);");
	while($tmp=$result->fetch(PDO::FETCH_NUM)){
		$stmt->execute([$tmp[0], $tmp[0]]);
	}
	// delete old captchas
	$stmt=$db->prepare('DELETE FROM ' . PREFIX . 'captcha WHERE time<(?-(SELECT value FROM ' . PREFIX . "settings WHERE setting='captchatime'));");
	$stmt->execute([$time]);
	// delete member associated data of deleted accounts
	$db->query('DELETE FROM ' . PREFIX . 'inbox WHERE recipient NOT IN (SELECT nickname FROM ' . PREFIX . 'members);');
	$db->query('DELETE FROM ' . PREFIX . 'notes WHERE (type=2 OR type=3) AND editedby NOT IN (SELECT nickname FROM ' . PREFIX . 'members);');
}
