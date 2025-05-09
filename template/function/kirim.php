<?php 
function send_post(string $rejected=''): void
{
	global $U, $db;
	echo'<link rel="stylesheet" href="./template/style/frameset/topframe.css">';
	print_start('post');
	if(!isset($_REQUEST['sendto'])){
		$_REQUEST['sendto']='';
	}
	echo '<table><tr><td>'.form('post', 'enctype="multipart/form-data"'); // Add enctype for file upload
	echo hidden('postid', $U['postid']);
	if(isset($_POST['multi'])){
		echo hidden('multi', 'on');
	}
	echo '<table><tr><td><table><tr id="firstline"><td>'.style_this(htmlspecialchars($U['nickname']), $U['style']).'</td><td>:</td>';
	if(isset($_POST['multi'])){
		echo "<td><textarea name=\"message\" rows=\"3\" cols=\"40\" autofocus>$rejected</textarea></td>";
	}else{
		echo "<td><input type=\"text\" name=\"message\" value=\"$rejected\" size=\"40\" autofocus></td>";
	}
	echo '<td>'.submit(_('Send to')).'</td><td><select name="sendto" size="1">';
	echo '<option ';
	if($_REQUEST['sendto']==='s *'){
		echo 'selected ';
	}
	echo 'value="s *">-'._('All chatters').'-</option>';
	if($U['status']>=3){
		echo '<option ';
		if($_REQUEST['sendto']==='s ?'){
			echo 'selected ';
		}
		echo 'value="s ?">-'._('Members only').'-</option>';
	}
	if($U['status']>=5){
		echo '<option ';
		if($_REQUEST['sendto']==='s %'){
			echo 'selected ';
		}
		echo 'value="s %">-'._('Staff only').'-</option>';
	}
	if($U['status']>=6){
		echo '<option ';
		if($_REQUEST['sendto']==='s _'){
			echo 'selected ';
		}
		echo 'value="s _">-'._('Admin only').'-</option>';
	}
	$disablepm=(bool) get_setting('disablepm');
	if(!$disablepm){
		$users=[];
		$stmt=$db->prepare('SELECT * FROM (SELECT nickname, style, exiting, 0 AS offline FROM ' . PREFIX . 'sessions WHERE entry!=0 AND status>0 AND incognito=0 UNION SELECT nickname, style, 0, 1 AS offline FROM ' . PREFIX . 'members WHERE eninbox!=0 AND eninbox<=? AND nickname NOT IN (SELECT nickname FROM ' . PREFIX . 'sessions WHERE incognito=0)) AS t WHERE nickname NOT IN (SELECT ign FROM '. PREFIX . 'ignored WHERE ignby=? UNION SELECT ignby FROM '. PREFIX . 'ignored WHERE ign=?) ORDER BY LOWER(nickname);');
		$stmt->execute([$U['status'], $U['nickname'], $U['nickname']]);
		while($tmp=$stmt->fetch(PDO::FETCH_ASSOC)){
			if($tmp['offline']){
				$users[]=["$tmp[nickname] "._('(offline)'), $tmp['style'], $tmp['nickname']];
			}elseif($tmp['exiting']){
				$users[]=["$tmp[nickname] "._('(logging out)'), $tmp['style'], $tmp['nickname']];
			}else{
				$users[]=[$tmp['nickname'], $tmp['style'], $tmp['nickname']];
			}
		}
		foreach($users as $user){
			if($U['nickname']!==$user[2]){
				echo '<option ';
				if($_REQUEST['sendto']==$user[2]){
					echo 'selected ';
				}
				echo 'value="'.htmlspecialchars($user[2])."\" style=\"$user[1]\">".htmlspecialchars($user[0]).'</option>';
			}
		}
	}
	echo '</select></td>';
	echo '</tr></table></td></tr></table>';
	
	echo '<table><tr>';
	
	// File upload section
	if(get_setting('enfileupload')>0 && get_setting('enfileupload')<=$U['status']){
		echo '<td>';
		echo '<input type="file" name="file" id="file">';
		printf('<small>'._('Max %d KB').'</small>', get_setting('maxuploadsize'));
		echo '</td>';
	}
	
	echo '</form>';
	
	// Command list button in separate cell
	echo '<td style="padding-left: 10px;">'.form_target('view', 'show_commands');
	echo '<input type="submit" value="'._('Show Commands').'">';
	echo '</form></td>';
	
	echo '</tr></table>';
	
	// Display command error notification if exists
	if (isset($_SESSION['command_error'])) {
		echo '<div class="error-notification" style="color: red; margin-top: 5px;">';
		echo htmlspecialchars($_SESSION['command_error']);
		echo '</div>';
		unset($_SESSION['command_error']);
	}
	
	echo '</table>';

	print_end();
}
?>