<?php

function send_admin(string $arg): void
{
	global $U, $db;
	$ga=(int) get_setting('guestaccess');
	print_start('admin');
    print_css('sendadmin.css');
	$chlist='<select name="name[]" size="5" multiple class="dark-select"><option value="">'._('(choose)').'</option>';
	$chlist.='<option value="s &#42;">'._('All guests').'</option>';
	$users=[];
	$stmt=$db->query('SELECT nickname, style, status FROM ' . PREFIX . 'sessions WHERE entry!=0 AND status>0 ORDER BY LOWER(nickname);');
	while($user=$stmt->fetch(PDO::FETCH_NUM)){
		$users[]=[htmlspecialchars($user[0]), $user[1], $user[2]];
	}
	foreach($users as $user){
		if($user[2]<$U['status']){
			$chlist.="<option value=\"$user[0]\" style=\"$user[1]\">$user[0]</option>";
		}
	}
	$chlist.='</select>';
	echo '<div class="admin-container">';
	echo '<h2 class="admin-title">'._('Administrative functions')."</h2><p class=\"admin-subtitle\">$arg</p>";
	echo '<div class="admin-panel">';
	if($U['status']>=7){
		echo '<div class="admin-section">';
		echo form_target('view', 'setup').'<button type="submit" class="admin-button">'._('Go to the Setup-Page').'</button></form>';
		echo '</div>';
	}
	
	echo '<div class="admin-section" id="clean">';
	echo '<h3 class="section-title">'._('Clean messages').'</h3>';
	echo form('admin', 'clean');
	echo '<div class="admin-form-group">';
	echo '<div class="radio-group">';
	echo '<label class="radio-label"><input type="radio" name="what" id="room" value="room">'._('Whole room').'</label>';
	echo '</div>';
	echo '<div class="radio-group">';
	echo '<label class="radio-label"><input type="radio" name="what" id="nick" value="nick">'._('Following nickname:').'</label>';
	echo '<select name="nickname" class="dark-select"><option value="">'._('(choose)').'</option>';
	$stmt=$db->prepare('SELECT DISTINCT poster FROM ' . PREFIX . "messages WHERE delstatus<? AND poster!='';");
	$stmt->execute([$U['status']]);
	while($nick=$stmt->fetch(PDO::FETCH_NUM)){
		echo '<option value="'.htmlspecialchars($nick[0]).'">'.htmlspecialchars($nick[0]).'</option>';
	}
	echo '</select>';
	echo '</div>';
	echo '<button type="submit" class="admin-button delbutton">'._('Clean').'</button>';
	echo '</div></form>';
	echo '</div>';
	

	echo '<div class="admin-section" id="logout">';
	echo '<h3 class="section-title">'._('Logout inactive Chatter').'</h3>';
	echo form('admin', 'logout');
	echo '<div class="admin-form-group">';
	echo $chlist;
	echo '<button type="submit" class="admin-button">'._('Logout').'</button>';
	echo '</div></form>';
	echo '</div>';
	
	$views=['sessions' => _('View active sessions'), 'filter' => _('Filter'), 'linkfilter' => _('Linkfilter')];
	foreach($views as $view => $title){
		echo "<div class=\"admin-section\" id=\"$view\">";
		echo '<h3 class="section-title">'.$title.'</h3>';
		echo form('admin', $view);
		echo '<button type="submit" class="admin-button">'._('View').'</button></form>';
		echo '</div>';
	}
	
	echo '<div class="admin-section" id="topic">';
	echo '<h3 class="section-title">'._('Topic').'</h3>';
	echo form('admin', 'topic');
	echo '<div class="admin-form-group">';
	echo '<input type="text" name="topic" class="dark-input" value="'.htmlspecialchars(get_setting('topic')).'">';
	echo '<button type="submit" class="admin-button">'._('Change').'</button>';
	echo '</div></form>';
	echo '</div>';
	
	echo '<div class="admin-section" id="guestaccess">';
	echo '<h3 class="section-title">'._('Change Guestaccess').'</h3>';
	echo form('admin', 'guestaccess');
	echo '<div class="admin-form-group">';
	echo '<select name="guestaccess" class="dark-select">';
	echo '<option value="1"'.($ga===1 ? ' selected' : '').'>'._('Allow').'</option>';
	echo '<option value="2"'.($ga===2 ? ' selected' : '').'>'._('Allow with waitingroom').'</option>';
	echo '<option value="3"'.($ga===3 ? ' selected' : '').'>'._('Require moderator approval').'</option>';
	echo '<option value="0"'.($ga===0 ? ' selected' : '').'>'._('Only members').'</option>';
	if($ga===4){
		echo '<option value="4" selected>'._('Disable chat').'</option>';
	}
	echo '</select>';
	echo '<button type="submit" class="admin-button">'._('Change').'</button>';
	echo '</div></form>';
	echo '</div>';
	
	
		echo '<div class="admin-section" id="suguests">';
		echo '<h3 class="section-title">'._('Register applicant').'</h3>';
		echo form('admin','superguest');
		echo '<div class="admin-form-group">';
		echo '<select name="name" class="dark-select"><option value="">'._('(choose)').'</option>';
		foreach($users as $user){
			if($user[2]==1){
				echo "<option value=\"$user[0]\" style=\"$user[1]\">$user[0]</option>";
			}
		}
		echo '</select>';
		echo '<button type="submit" class="admin-button">'._('Register').'</button>';
		echo '</div></form>';
		echo '</div>';
	
	if($U['status']>=7){
		echo '<div class="admin-section" id="status">';
		echo '<h3 class="section-title">'._('Members').'</h3>';
		echo form('admin', 'status');
		echo '<div class="admin-form-group">';
		echo '<select name="name" class="dark-select"><option value="">'._('(choose)').'</option>';
		$members=[];
		$result=$db->query('SELECT nickname, style, status FROM ' . PREFIX . 'members ORDER BY LOWER(nickname);');
		while($temp=$result->fetch(PDO::FETCH_NUM)){
			$members[]=[htmlspecialchars($temp[0]), $temp[1], $temp[2]];
		}
		foreach($members as $member){
			echo "<option value=\"$member[0]\" style=\"$member[1]\">$member[0]";
			if($member[2]==0){
				echo ' (!)';
			}elseif($member[2]==2){
				echo ' (SG)';
			}elseif($member[2]==3){
			}elseif($member[2]==5){
				echo ' (M)';
			}elseif($member[2]==6){
				echo ' (SM)';
			}elseif($member[2]==7){
				echo ' (A)';
			}else{
				echo ' (SA)';
			}
			echo '</option>';
		}
		echo '</select>';
		echo '<select name="set" class="dark-select"><option value="">'._('(choose)').'</option>';
		echo '<option value="-">'._('Delete from database').'</option>';
		echo '<option value="0">'._('Deny access (!)').'</option>';
		if(get_setting('suguests')){
			echo '<option value="2">'._('Set to applicant (SG)').'</option>';
		}
		echo '<option value="3">'._('Set to regular member').'</option>';
		echo '<option value="5">'._('Set to moderator (M)').'</option>';
		echo '<option value="6">'._('Set to supermod (SM)').'</option>';
		if($U['status']>=8){
			echo '<option value="7">'._('Set to admin (A)').'</option>';
		}
		echo '</select>';
		echo '<button type="submit" class="admin-button">'._('Change').'</button>';
		echo '</div></form>';
		echo '</div>';
		
		echo '<div class="admin-section" id="passreset">';
		echo '<h3 class="section-title">'._('Reset password').'</h3>';
		echo form('admin', 'passreset');
		echo '<div class="admin-form-group">';
		echo '<select name="name" class="dark-select"><option value="">'._('(choose)').'</option>';
		foreach($members as $member){
			echo "<option value=\"$member[0]\" style=\"$member[1]\">$member[0]</option>";
		}
		echo '</select>';
		echo '<input type="password" name="pass" class="dark-input" autocomplete="off">';
		echo '<button type="submit" class="admin-button">'._('Change').'</button>';
		echo '</div></form>';
		echo '</div>';
		
		echo '<div class="admin-section" id="register">';
		echo '<h3 class="section-title">'._('Register Members').'</h3>';
		echo form('admin', 'register');
		echo '<div class="admin-form-group">';
		echo '<select name="name" class="dark-select"><option value="">'._('(choose)').'</option>';
		foreach($users as $user){
			if($user[2]==1){
				echo "<option value=\"$user[0]\" style=\"$user[1]\">$user[0]</option>";
			}
		}
		echo '</select>';
		echo '<button type="submit" class="admin-button">'._('Register').'</button>';
		echo '</div></form>';
		echo '</div>';
		
		echo '<div class="admin-section" id="regnew">';
		echo '<h3 class="section-title">'._('Register new Member').'</h3>';
		echo form('admin', 'regnew');
		echo '<div class="admin-form-group">';
		echo '<label class="form-label">'._('Nickname:').'</label>';
		echo '<input type="text" name="name" class="dark-input">';
		echo '<label class="form-label">'._('Password:').'</label>';
		echo '<input type="password" name="pass" class="dark-input" autocomplete="off">';
		echo '<button type="submit" class="admin-button">'._('Register').'</button>';
		echo '</div></form>';
		echo '</div>';
	}
	
	echo '</div>'; // End admin-panel
	echo '<div class="admin-footer">';
	echo form('admin').'<button type="submit" class="admin-button reload-button">'._('Reload').'</button></form>';
	echo '</div>';
	echo '</div>'; // End admin-container
	print_end();
}

function send_sessions(): void
{
	global $U, $db;
	$stmt=$db->prepare('SELECT nickname, style, lastpost, status, useragent, ip FROM ' . PREFIX . 'sessions WHERE entry!=0 AND (incognito=0 OR status<? OR nickname=?) ORDER BY status DESC, lastpost DESC;');
	$stmt->execute([$U['status'], $U['nickname']]);
	if(!$lines=$stmt->fetchAll(PDO::FETCH_ASSOC)){
		$lines=[];
	}
	print_start('sessions');
	print_css('sendadmin.css');
	echo '<div class="sessions-container">';
	echo '<h1 class="sessions-title">'._('Active Sessions').'</h1>';
	echo '<div class="sessions-table">';
	echo '<div class="table-header">';
	echo '<div class="header-cell">'._('Nickname').'</div>';
	echo '<div class="header-cell">'._('Timeout in').'</div>';
	echo '<div class="header-cell">'._('User-Agent').'</div>';
	$trackip=(bool) get_setting('trackip');
	$memexpire=(int) get_setting('memberexpire');
	$guestexpire=(int) get_setting('guestexpire');
	if($trackip) echo '<div class="header-cell">'._('IP-Address').'</div>';
	echo '<div class="header-cell">'._('Actions').'</div>';
	echo '</div>';
	
	foreach($lines as $temp){
		if($temp['status']==0){
			$s=' (K)';
		}elseif($temp['status']<=1){
			$s=' (G)';
		}elseif($temp['status']==2){
			$s=' (SG)';
		}elseif($temp['status']==3){
			$s='';
		}elseif($temp['status']==5){
			$s=' (M)';
		}elseif($temp['status']==6){
			$s=' (SM)';
		}elseif($temp['status']==7){
			$s=' (A)';
		}else{
			$s=' (SA)';
		}
		echo '<div class="table-row">';
		echo '<div class="table-cell nickname">'.style_this(htmlspecialchars($temp['nickname']).$s, $temp['style']).'</div>';
		echo '<div class="table-cell timeout">';
		if($temp['status']>2){
			get_timeout((int) $temp['lastpost'], $memexpire);
		}else{
			get_timeout((int) $temp['lastpost'], $guestexpire);
		}
		echo '</div>';
		
		if($U['status']>$temp['status'] || $U['nickname']===$temp['nickname']){
			echo "<div class=\"table-cell ua\">".htmlspecialchars($temp['useragent'])."</div>";
			if($trackip){
				echo "<div class=\"table-cell ip\">".htmlspecialchars($temp['ip'])."</div>";
			}
			echo '<div class="table-cell action">';
			if($temp['nickname']!==$U['nickname']){
				echo '<div class="action-buttons">';
				if($temp['status']!=0){
					echo form('admin', 'sessions');
					echo hidden('kick', '1').hidden('nick', htmlspecialchars($temp['nickname'])).'<button type="submit" class="session-button kick-button">'._('Kick').'</button></form>';
				}
				echo form('admin', 'sessions');
				echo hidden('logout', '1').hidden('nick', htmlspecialchars($temp['nickname'])).'<button type="submit" class="session-button logout-button">'.($temp['status']==0 ? _('Unban') : _('Logout')).'</button></form>';
				echo '</div>';
			}else{
				echo '-';
			}
			echo '</div>';
		}else{
			echo '<div class="table-cell ua">-</div>';
			if($trackip){
				echo '<div class="table-cell ip">-</div>';
			}
			echo '<div class="table-cell action">-</div>';
		}
		echo '</div>'; // End table-row
	}
	
	echo '</div>'; // End sessions-table
	echo '<div class="sessions-footer">';
	echo form('admin', 'sessions').'<button type="submit" class="admin-button reload-button">'._('Reload').'</button></form>';
	echo form('admin').'<button type="submit" class="admin-button back-button">'._('Back to Admin').'</button></form>';
	echo '</div>';
	echo '</div>'; // End sessions-container
	print_end();
}
?>