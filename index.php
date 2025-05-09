<?php

function delete_message(int $message_id): void {
    global $U, $db;
    
    // Check if the user has access to delete the message
    $stmt = $db->prepare('SELECT id, poster, postdate FROM ' . PREFIX . 'messages WHERE id = ?');
    $stmt->execute([$message_id]);
    $message = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Only can delete own message within 10 minutes or if admin status
    if ($message) {
        $canDelete = false;
        
        if ($U['status'] >= 5) {
            // Admin can delete all messages
            $canDelete = true;
        } else if ($message['poster'] === $U['nickname']) {
            // Sender can delete their message within 10 minutes
            $timeDiff = time() - $message['postdate']; 
            if ($timeDiff <= 600) { // 600 seconds = 10 minutes
                $canDelete = true;
            }
        }
        
        if ($canDelete) {
            // Delete message from database
            $stmt = $db->prepare('DELETE FROM ' . PREFIX . 'messages WHERE id = ?');
            $stmt->execute([$message_id]);
            
            // Delete from inbox if exists
            $stmt = $db->prepare('DELETE FROM ' . PREFIX . 'inbox WHERE postid = ?');
            $stmt->execute([$message_id]);
            
        }
    }
    
    // Refresh messages
    send_messages();
}

function load_function(){
	require_once __DIR__ . '/template/function/route.php';
	require_once __DIR__ . '/template/page/welcomepage.php';
	require_once __DIR__ . '/template/page/capthcafun.php';
	require_once __DIR__ . '/template/function/sendadmincon.php';
	require_once __DIR__ . '/template/function/sendsetup.php';
	require_once __DIR__ . '/template/function/afktoggle.php';
	require_once __DIR__ . '/template/function/showcomamnd.php';
	require_once __DIR__ . '/template/function/waitingroom.php';
	require_once __DIR__ . '/template/function/redirect.php';
	require_once __DIR__ . '/template/function/hiddenline.php';
	require_once __DIR__ . '/template/function/filterfun.php';
	require_once __DIR__ . '/template/function/publicnotes.php';
	require_once __DIR__ . '/template/function/control-bottom.php';
	require_once __DIR__ . '/template/function/profile.php';
	require_once __DIR__ . '/template/function/cron.php';
	require_once __DIR__ . '/template/function/setupdb.php';
	require_once __DIR__ . '/template/form/login.php';
	require_once __DIR__ . '/template/form/logout.php';
	require_once __DIR__ . '/template/function/errorfun.php';
	require_once __DIR__ . '/template/function/disablefun.php';
	require_once __DIR__ . '/template/function/notification.php';
	require_once __DIR__ . '/template/function/send_notes.php';
	require_once __DIR__ . '/template/function/help&bottom.php';
	require_once __DIR__ . '/template/function/kirim.php';
	require_once __DIR__ . '/template/function/mention_notification.php';
}
load_function();

load_config();
$U=[];// This user data
$db = null;// Database connection
$memcached = null;// Memcached connection
$styles = []; //css styles
$session = $_REQUEST['session'] ?? ''; //requested session
// set session variable to cookie if cookies are enabled
if(!isset($_REQUEST['session']) && isset($_COOKIE[COOKIENAME])){
	$session = $_COOKIE[COOKIENAME];
}
$session = preg_replace('/[^0-9a-zA-Z]/', '', $session);
load_lang();
check_db();
cron();
route();

function print_css($names): void {
    echo '<link rel="stylesheet" href="/template/style/'.$names.'">';
}



function print_stylesheet(string $class): void
{
	global $scripts, $styles;
	//default css
	if ( $class === 'init' ) {
		return;
	}
	if(isset($styles[$class])) {
		echo "<style>$styles[$class]</style>";
	}
}

function print_end(): void
{
	echo '</body></html>';
	exit;
}

function credit() : string {
	return '<small><br><br><a target="_blank" href="https://github.com/DanWin/le-chat-php" rel="noreferrer noopener">LE CHAT-PHP - ' . VERSION . '</a></small>';
}

function meta_html() : string {
	global $U, $db;
	$colbg = '000000';
	$description = '';
	if(!empty($U['bgcolour'])){
		$colbg = $U['bgcolour'];
	}else{
		if($db instanceof PDO){
			$colbg = get_setting('colbg');
			$description = '<meta name="description" content="'.htmlspecialchars(get_setting('metadescription')).'">';
		}
	}
	return '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"><meta name="referrer" content="no-referrer"><meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=yes"><meta name="theme-color" content="#'.$colbg.'"><meta name="msapplication-TileColor" content="#'.$colbg.'">' . $description;
}

function form(string $action, string $do='') : string {
	global $language, $session;
	$form="<form action=\"$_SERVER[SCRIPT_NAME]\" enctype=\"multipart/form-data\" method=\"post\">".hidden('lang', $language).hidden('nc', substr(time(), -6)).hidden('action', $action);
	if(!empty($session)){
		$form.=hidden('session', $session);
	}
	if($do!==''){
		$form.=hidden('do', $do);
	}
	return $form;
}

function form_target(string $target, string $action, string $do='') : string {
	global $language, $session;
	$form="<form action=\"$_SERVER[SCRIPT_NAME]\" enctype=\"multipart/form-data\" method=\"post\" target=\"$target\">".hidden('lang', $language).hidden('nc', substr(time(), -6)).hidden('action', $action);
	if(!empty($session)){
		$form.=hidden('session', $session);
	}
	if($do!==''){
		$form.=hidden('do', $do);
	}
	return $form;
}

function hidden(string $name='', string $value='') : string {
	return "<input type=\"hidden\" name=\"$name\" value=\"$value\">";
}

function submit(string $value='', string $extra_attribute='') : string {
	return "<input type=\"submit\" value=\"$value\" $extra_attribute>";
}

function thr(): void
{
	echo '<tr><td><hr></td></tr>';
}

function print_start(string $class='', int $ref=0, string $url=''): void
{
	global $language, $dir;
	send_headers();
	
	if(!empty($url)){
		$url=str_replace('&amp;', '&', $url);
		header("Refresh: $ref; URL=$url");
	}
	
	echo '<!DOCTYPE html><html lang="'.$language.'" dir="'.$dir.'"><head>'.meta_html();
	
	if(!empty($url)){
		echo "<meta http-equiv=\"Refresh\" content=\"$ref; URL=$url\">";
	}
	
	// Only include external resources if not in lite mode
		echo '<link rel="icon" href="./LOFO.png" type="image/x-icon">';
		echo '<link rel="stylesheet" href="./template/icon/fontawesome-free-6.7.2-web/css/all.min.css">';


	if($class==='init'){
		echo '<title>'._('Initial Setup').'</title>';
	}else{
		echo '<title>'.get_setting('chatname').'</title>';
	}

	
	echo "</head><body class=\"$class\">";
}




function restore_backup(array $C): void
{
	global $db, $memcached;
	if(!extension_loaded('json')){
		return;
	}
	$code=json_decode($_POST['restore'], true);
	if(isset($_POST['settings'])){
		foreach($C['settings'] as $setting){
			if(isset($code['settings'][$setting])){
				update_setting($setting, $code['settings'][$setting]);
			}
		}
	}
	if(isset($_POST['filter']) && (isset($code['filters']) || isset($code['linkfilters']))){
		$db->exec('DELETE FROM ' . PREFIX . 'filter;');
		$db->exec('DELETE FROM ' . PREFIX . 'linkfilter;');
		$stmt=$db->prepare('INSERT INTO ' . PREFIX . 'filter (filtermatch, filterreplace, allowinpm, regex, kick, cs) VALUES (?, ?, ?, ?, ?, ?);');
		foreach($code['filters'] as $filter){
			if(!isset($filter['cs'])){
				$filter['cs']=0;
			}
			$stmt->execute([$filter['match'], $filter['replace'], $filter['allowinpm'], $filter['regex'], $filter['kick'], $filter['cs']]);
		}
		$stmt=$db->prepare('INSERT INTO ' . PREFIX . 'linkfilter (filtermatch, filterreplace, regex) VALUES (?, ?, ?);');
		foreach($code['linkfilters'] as $filter){
			$stmt->execute([$filter['match'], $filter['replace'], $filter['regex']]);
		}
		if(MEMCACHED){
			$memcached->delete(DBNAME . '-' . PREFIX . 'filter');
			$memcached->delete(DBNAME . '-' . PREFIX . 'linkfilter');
		}
	}
	if(isset($_POST['members']) && isset($code['members'])){
		$db->exec('DELETE FROM ' . PREFIX . 'inbox;');
		$db->exec('DELETE FROM ' . PREFIX . 'members;');
		$stmt=$db->prepare('INSERT INTO ' . PREFIX . 'members (nickname, passhash, status, refresh, bgcolour, regedby, lastlogin, loginfails, timestamps, embed, incognito, style, nocache, tz, eninbox, sortupdown, hidechatters, nocache_old) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);');
		foreach($code['members'] as $member){
			$new_settings=['nocache', 'tz', 'eninbox', 'sortupdown', 'hidechatters', 'nocache_old'];
			foreach($new_settings as $setting){
				if(!isset($member[$setting])){
					$member[$setting]=0;
				}
			}
			$stmt->execute([$member['nickname'], $member['passhash'], $member['status'], $member['refresh'], $member['bgcolour'], $member['regedby'], $member['lastlogin'], $member['loginfails'], $member['timestamps'], $member['embed'], $member['incognito'], $member['style'], $member['nocache'], $member['tz'], $member['eninbox'], $member['sortupdown'], $member['hidechatters'], $member['nocache_old']]);
		}
	}
	if(isset($_POST['notes']) && isset($code['notes'])){
		$db->exec('DELETE FROM ' . PREFIX . 'notes;');
		$stmt=$db->prepare('INSERT INTO ' . PREFIX . 'notes (type, lastedited, editedby, text) VALUES (?, ?, ?, ?);');
		foreach($code['notes'] as $note){
			if($note['type']==='admin'){
				$note['type']=0;
			}elseif($note['type']==='staff'){
				$note['type']=1;
			}elseif($note['type']==='public'){
				$note['type']=3;
			}elseif($note['type']==='announcement'){
				$note['type']=4;
			}
			if(MSGENCRYPTED){
				try {
					$note['text']=base64_encode(sodium_crypto_aead_aes256gcm_encrypt($note['text'], '', AES_IV, ENCRYPTKEY));
				} catch (SodiumException $e){
					send_error($e->getMessage());
				}
			}
			$stmt->execute([$note['type'], $note['lastedited'], $note['editedby'], $note['text']]);
		}
	}
}

function send_backup(array $C): void
{
	global $db;
	$code=[];
	if($_POST['do']==='backup'){
		if(isset($_POST['settings'])){
			foreach($C['settings'] as $setting){
				$code['settings'][$setting]=get_setting($setting);
			}
		}
		if(isset($_POST['filter'])){
			$result=$db->query('SELECT * FROM ' . PREFIX . 'filter;');
			while($filter=$result->fetch(PDO::FETCH_ASSOC)){
				$code['filters'][]=['match'=>$filter['filtermatch'], 'replace'=>$filter['filterreplace'], 'allowinpm'=>$filter['allowinpm'], 'regex'=>$filter['regex'], 'kick'=>$filter['kick'], 'cs'=>$filter['cs']];
			}
			$result=$db->query('SELECT * FROM ' . PREFIX . 'linkfilter;');
			while($filter=$result->fetch(PDO::FETCH_ASSOC)){
				$code['linkfilters'][]=['match'=>$filter['filtermatch'], 'replace'=>$filter['filterreplace'], 'regex'=>$filter['regex']];
			}
		}
		if(isset($_POST['members'])){
			$result=$db->query('SELECT * FROM ' . PREFIX . 'members;');
			while($member=$result->fetch(PDO::FETCH_ASSOC)){
				$code['members'][]=$member;
			}
		}
		if(isset($_POST['notes'])){
			$result=$db->query('SELECT * FROM ' . PREFIX . "notes;");
			while($note=$result->fetch(PDO::FETCH_ASSOC)){
				if(MSGENCRYPTED){
					try {
						$note['text']=sodium_crypto_aead_aes256gcm_decrypt(base64_decode($note['text']), null, AES_IV, ENCRYPTKEY);
					} catch (SodiumException $e){
						send_error($e->getMessage());
					}
				}
				$code['notes'][]=$note;
			}
		}
	}
	if(isset($_POST['settings'])){
		$chksettings=' checked';
	}else{
		$chksettings='';
	}
	if(isset($_POST['filter'])){
		$chkfilters=' checked';
	}else{
		$chkfilters='';
	}
	if(isset($_POST['members'])){
		$chkmembers=' checked';
	}else{
		$chkmembers='';
	}
	if(isset($_POST['notes'])){
		$chknotes=' checked';
	}else{
		$chknotes='';
	}
	print_start('backup');
	echo '<h2>'._('Backup and restore').'</h2><table>';
	thr();
	if(!extension_loaded('json')){
		echo '<tr><td>'.sprintf(_('The %s extension of PHP is required for this feature. Please install it first.'), 'json').'</td></tr>';
	}else{
		echo '<tr><td>'.form('setup', 'backup');
		echo '<table id="backup"><tr><td id="backupcheck">';
		echo '<label><input type="checkbox" name="settings" id="backupsettings" value="1"'.$chksettings.'>'._('Settings').'</label>';
		echo '<label><input type="checkbox" name="filter" id="backupfilter" value="1"'.$chkfilters.'>'._('Filter').'</label>';
		echo '<label><input type="checkbox" name="members" id="backupmembers" value="1"'.$chkmembers.'>'._('Members').'</label>';
		echo '<label><input type="checkbox" name="notes" id="backupnotes" value="1"'.$chknotes.'>'._('Notes').'</label>';
		echo '</td><td id="backupsubmit">'.submit(_('Backup')).'</td></tr></table></form></td></tr>';
		thr();
		echo '<tr><td>'.form('setup', 'restore');
		echo '<table id="restore">';
		echo '<tr><td colspan="2"><textarea name="restore" rows="4" cols="60">'.htmlspecialchars(json_encode($code)).'</textarea></td></tr>';
		echo '<tr><td id=\"restorecheck\"><label><input type="checkbox" name="settings" id="restoresettings" value="1"'.$chksettings.'>'._('Settings').'</label>';
		echo '<label><input type="checkbox" name="filter" id="restorefilter" value="1"'.$chkfilters.'>'._('Filter').'</label>';
		echo '<label><input type="checkbox" name="members" id="restoremembers" value="1"'.$chkmembers.'>'._('Members').'</label>';
		echo '<label><input type="checkbox" name="notes" id="restorenotes" value="1"'.$chknotes.'>'._('Notes').'</label>';
		echo '</td><td id="restoresubmit">'.submit(_('Restore')).'</td></tr></table>';
		echo '</form></td></tr>';
	}
	thr();
	echo '<tr><td>'.form('setup').submit(_('Go to the Setup-Page'), 'class="backbutton"')."</form></tr></td>";
	echo '</table>';
	print_end();
}

function send_destroy_chat(): void
{
	print_start('destroy_chat');
	echo '<table><tr><td colspan="2">'._('Are you sure?').'</td></tr><tr><td>';
	echo form_target('_parent', 'setup', 'destroy').hidden('confirm', 'yes').submit(_('Yes'), 'class="delbutton"').'</form></td><td>';
	echo form('setup').submit(_('No'), 'class="backbutton"').'</form></td><tr></table>';
	print_end();
}


function send_init(): void
{
	print_start('init');
	print_css('init.css');
	echo '<h2>'._('Initial Setup').'</h2>';
	echo form('init').'<table><tr><td><h3>'._('Superadmin Login').'</h3><table>';
	echo '<tr><td>'._('Superadmin Nickname:').'</td><td><input type="text" name="sunick" size="15" autocomplete="username"></td></tr>';
	echo '<tr><td>'._('Superadmin Password:').'</td><td><input type="password" name="supass" size="15" autocomplete="new-password"></td></tr>';
	echo '<tr><td>'._('Confirm Password:').'</td><td><input type="password" name="supassc" size="15" autocomplete="new-password"></td></tr>';
	echo '</table></td></tr><tr><td><br>'.submit(_('Initialise Chat')).'</td></tr></table></form>';
	echo '<p id="changelang">'._('Change language:');
	echo'</p>'.credit();
	print_end();
}

function send_update(string $msg): void
{
	print_start('update');
	echo '<h2>'._('Database successfully updated!',).'</h2><br>'.form('setup').submit(_('Go to the Setup-Page'))."</form>$msg<br>".credit();
	print_end();
}


function send_sa_password_reset(): void
{
	global $db;
	print_start('sa_password_reset');
	echo '<h1>'._('Reset password').'</h1>';
	if(defined('RESET_SUPERADMIN_PASSWORD') && !empty(RESET_SUPERADMIN_PASSWORD)){
		$stmt = $db->query('SELECT nickname FROM ' . PREFIX . 'members WHERE status = 8 LIMIT 1;');
		if($user = $stmt->fetch(PDO::FETCH_ASSOC)){
			$mem_update = $db->prepare('UPDATE ' . PREFIX . 'members SET passhash = ? WHERE nickname = ? LIMIT 1;');
			$mem_update->execute([password_hash(RESET_SUPERADMIN_PASSWORD, PASSWORD_DEFAULT), $user['nickname']]);
			$sess_delete = $db->prepare('DELETE FROM ' . PREFIX . 'sessions WHERE nickname = ?;');
			$sess_delete->execute([$user['nickname']]);
			printf('<p>'._('Successfully reset password for username %s. Please remove the password reset define from the script again.').'</p>', $user['nickname']);
		}
	} else {
		echo '<p>'._("Please modify the script and put the following at the bottom of it (change the password). Then refresh this page: define('RESET_SUPERADMIN_PASSWORD', 'changeme');").'</p>';
	}
	echo '<a href="?action=setup">'._('Go to the Setup-Page').'</a>';
	echo " <a href=\"?action=sa_password_reset&amp;lang=en\" hreflang=\"en\">English</a>";
	echo '</p>'.credit();
	print_end();
}

function check_filter_match(int &$reg) : string {
	$_POST['match']=htmlspecialchars($_POST['match']);
	if(isset($_POST['regex']) && $_POST['regex']==1){
		if(!valid_regex($_POST['match'])){
			return _('Incorrect regular expression!').'<br>'.sprintf(_('Your match was as follows: %s'), htmlspecialchars($_POST['match']));
		}
		$reg=1;
	}else{
		$_POST['match']=preg_replace('/([^\w\d])/u', "\\\\$1", $_POST['match']);
		$reg=0;
	}
	if(mb_strlen($_POST['match'])>255){
		return _('Your match was too long. You can use max. 255 characters. Try splitting it up.')."<br>".sprintf(_('Your match was as follows: %s'), htmlspecialchars($_POST['match']));
	}
	return '';
}

function manage_filter() : string {
	global $db, $memcached;
	if(isset($_POST['id'])){
		$reg=0;
		if(($tmp=check_filter_match($reg)) !== ''){
			return $tmp;
		}
		if(isset($_POST['allowinpm']) && $_POST['allowinpm']==1){
			$pm=1;
		}else{
			$pm=0;
		}
		if(isset($_POST['kick']) && $_POST['kick']==1){
			$kick=1;
		}else{
			$kick=0;
		}
		if(isset($_POST['cs']) && $_POST['cs']==1){
			$cs=1;
		}else{
			$cs=0;
		}
		if(preg_match('/^[0-9]+$/', $_POST['id'])){
			if(empty($_POST['match'])){
				$stmt=$db->prepare('DELETE FROM ' . PREFIX . 'filter WHERE id=?;');
				$stmt->execute([$_POST['id']]);
			}else{
				$stmt=$db->prepare('UPDATE ' . PREFIX . 'filter SET filtermatch=?, filterreplace=?, allowinpm=?, regex=?, kick=?, cs=? WHERE id=?;');
				$stmt->execute([$_POST['match'], $_POST['replace'], $pm, $reg, $kick, $cs, $_POST['id']]);
			}
		}elseif($_POST['id']==='+'){
			$stmt=$db->prepare('INSERT INTO ' . PREFIX . 'filter (filtermatch, filterreplace, allowinpm, regex, kick, cs) VALUES (?, ?, ?, ?, ?, ?);');
			$stmt->execute([$_POST['match'], $_POST['replace'], $pm, $reg, $kick, $cs]);
		}
		if(MEMCACHED){
			$memcached->delete(DBNAME . '-' . PREFIX . 'filter');
		}
	}
	return '';
}

function manage_linkfilter() : string {
	global $db, $memcached;
	if(isset($_POST['id'])){
		$reg=0;
		if(($tmp=check_filter_match($reg)) !== ''){
			return $tmp;
		}
		if(preg_match('/^[0-9]+$/', $_POST['id'])){
			if(empty($_POST['match'])){
				$stmt=$db->prepare('DELETE FROM ' . PREFIX . 'linkfilter WHERE id=?;');
				$stmt->execute([$_POST['id']]);
			}else{
				$stmt=$db->prepare('UPDATE ' . PREFIX . 'linkfilter SET filtermatch=?, filterreplace=?, regex=? WHERE id=?;');
				$stmt->execute([$_POST['match'], $_POST['replace'], $reg, $_POST['id']]);
			}
		}elseif($_POST['id']==='+'){
			$stmt=$db->prepare('INSERT INTO ' . PREFIX . 'linkfilter (filtermatch, filterreplace, regex) VALUES (?, ?, ?);');
			$stmt->execute([$_POST['match'], $_POST['replace'], $reg]);
		}
		if(MEMCACHED){
			$memcached->delete(DBNAME . '-' . PREFIX . 'linkfilter');
		}
	}
	return '';
}

function get_filters() : array {
	global $db, $memcached;
	$filters=[];
	if(MEMCACHED){
		$filters=$memcached->get(DBNAME . '-' . PREFIX . 'filter');
	}
	if(!MEMCACHED || $memcached->getResultCode()!==Memcached::RES_SUCCESS){
		$filters=[];
		$result=$db->query('SELECT id, filtermatch, filterreplace, allowinpm, regex, kick, cs FROM ' . PREFIX . 'filter;');
		while($filter=$result->fetch(PDO::FETCH_ASSOC)){
			$filters[]=['id'=>$filter['id'], 'match'=>$filter['filtermatch'], 'replace'=>$filter['filterreplace'], 'allowinpm'=>$filter['allowinpm'], 'regex'=>$filter['regex'], 'kick'=>$filter['kick'], 'cs'=>$filter['cs']];
		}
		if(MEMCACHED){
			$memcached->set(DBNAME . '-' . PREFIX . 'filter', $filters);
		}
	}
	return $filters;
}

function get_linkfilters() : array {
	global $db, $memcached;
	$filters=[];
	if(MEMCACHED){
		$filters=$memcached->get(DBNAME . '-' . PREFIX . 'linkfilter');
	}
	if(!MEMCACHED || $memcached->getResultCode()!==Memcached::RES_SUCCESS){
		$filters=[];
		$result=$db->query('SELECT id, filtermatch, filterreplace, regex FROM ' . PREFIX . 'linkfilter;');
		while($filter=$result->fetch(PDO::FETCH_ASSOC)){
			$filters[]=['id'=>$filter['id'], 'match'=>$filter['filtermatch'], 'replace'=>$filter['filterreplace'], 'regex'=>$filter['regex']];
		}
		if(MEMCACHED){
			$memcached->set(DBNAME . '-' . PREFIX . 'linkfilter', $filters);
		}
	}
	return $filters;
}


function send_linkfilter(string $arg=''): void
{
	global $U;
	print_start('linkfilter');
	echo '<h2>'._('Linkfilter')."</h2><i>$arg</i><table>";
	thr();
	echo '<tr><th><table><tr>';
	echo '<td>'._('Filter ID:').'</td>';
	echo '<td>'._('Match').'</td>';
	echo '<td>'._('Replace').'</td>';
	echo '<td>'._('Regex').'</td>';
	echo '<td>'._('Apply').'</td>';
	echo '</tr></table></th></tr>';
	$filters=get_linkfilters();
	foreach($filters as $filter){
		if($filter['regex']==1){
			$checked=' checked';
		}else{
			$checked='';
			$filter['match']=preg_replace('/(\\\\(.))/u', "$2", $filter['match']);
		}
		echo '<tr><td>';
		echo form('admin', 'linkfilter').hidden('id', $filter['id']);
		echo '<table><tr><td>'._('Filter')." $filter[id]:</td>";
		echo '<td><input type="text" name="match" value="'.$filter['match'].'" size="20" style="'.$U['style'].'"></td>';
		echo '<td><input type="text" name="replace" value="'.htmlspecialchars($filter['replace']).'" size="20" style="'.$U['style'].'"></td>';
		echo '<td><label><input type="checkbox" name="regex" value="1"'.$checked.'>'._('Regex').'</label></td>';
		echo '<td class="filtersubmit">'.submit(_('Change')).'</td></tr></table></form></td></tr>';
	}
	echo '<tr><td>';
	echo form('admin', 'linkfilter').hidden('id', '+');
	echo '<table><tr><td>'._('New filter:').'</td>';
	echo '<td><input type="text" name="match" value="" size="20" style="'.$U['style'].'"></td>';
	echo '<td><input type="text" name="replace" value="" size="20" style="'.$U['style'].'"></td>';
	echo '<td><label><input type="checkbox" name="regex" value="1">'._('Regex').'</label></td>';
	echo '<td class="filtersubmit">'.submit(_('Add')).'</td></tr></table></form></td></tr>';
	echo '</table><br>';
	echo form('admin', 'linkfilter').submit(_('Reload')).'</form>';
	print_end();
}
function generate_navbar($active_page = '') {
	require_once __DIR__ . '/template/navbar.php';

}



function send_frameset(): void
{
	global $U, $db, $language, $dir;
	print_start('frameset');
	print_css('frameset.css');

	if(isset($_POST['sort'])){
		$tmp=$U['nocache'];
		$U['nocache']=$U['nocache_old'];
		$U['nocache_old']=$tmp;
		$stmt=$db->prepare('UPDATE ' . PREFIX . 'sessions SET nocache=?, nocache_old=? WHERE nickname=?;');
		$stmt->execute([$U['nocache'], $U['nocache_old'], $U['nickname']]);
		if($U['status']>1){
			$stmt=$db->prepare('UPDATE ' . PREFIX . 'members SET nocache=?, nocache_old=? WHERE nickname=?;');
			$stmt->execute([$U['nocache'], $U['nocache_old'], $U['nickname']]);
		}
	}
	
	$action_mid='view';
	$action_top='post';
	$action_bot='controls';
	generate_navbar();
	if(isset($U['session']) && !empty($U['session'])){
		$stmt=$db->prepare('SELECT null FROM ' . PREFIX . 'sessions WHERE session=?;');
		$stmt->execute([$U['session']]);
		if($stmt->fetch(PDO::FETCH_ASSOC)){
			echo "<iframe id=\"top\" name=\"$action_top\" src=\"$_SERVER[SCRIPT_NAME]?action=$action_top&session=$U[session]&lang=$language\">".noframe_html()."</iframe>";
			echo "<iframe id=\"middle\" name=\"$action_mid\" src=\"$_SERVER[SCRIPT_NAME]?action=$action_mid&session=$U[session]&lang=$language\">".noframe_html()."</iframe>";
			echo "<iframe id=\"bottom\" name=\"$action_bot\" src=\"$_SERVER[SCRIPT_NAME]?action=$action_bot&session=$U[session]&lang=$language\">".noframe_html()."</iframe>";
			print_end();
			exit;
		}
	}
	
	echo "<iframe id=\"top\" name=\"$action_top\" src=\"$_SERVER[SCRIPT_NAME]?action=$action_top&session=$U[session]&lang=$language\">".noframe_html()."</iframe>";
	echo "<iframe id=\"middle\" name=\"$action_mid\" src=\"$_SERVER[SCRIPT_NAME]?action=$action_mid&session=$U[session]&lang=$language\">".noframe_html()."</iframe>";
	echo "<iframe id=\"bottom\" name=\"$action_bot\" src=\"$_SERVER[SCRIPT_NAME]?action=$action_bot&session=$U[session]&lang=$language\">".noframe_html()."</iframe>";

	print_end();
	exit;
}

function noframe_html() : string {
	return _('This chat uses <b>frames</b>. Please enable frames in your browser or use a suitable one!').form_target('_parent', '').submit(_('Back to the login page.'), 'class="backbutton"').'</form>';
}

function send_messages(): void
{
	global $U, $language, $session;
	
	
	if($U['nocache']){
		$nocache='&nc='.substr(time(), -6);
	}else{
		$nocache='';
	}
	
	// Tambahkan meta refresh berdasarkan pengaturan pengguna
	echo '<meta http-equiv="refresh" content="' . (int) $U['refresh'] . ';url=' . $_SERVER['SCRIPT_NAME'] . '?action=view&session=' . $session . '&lang=' . $language . $nocache . '">';
    
	send_headers();
	
	print_messages();
	print_chatters();
	print_end();
}





function send_del_confirm(): void
{
	print_start('del_confirm');
	echo '<table><tr><td colspan="2">'._('Are you sure?').'</td></tr><tr><td>'.form('delete');
	if(isset($_POST['multi'])){
		echo hidden('multi', 'on');
	}
	if(isset($_POST['sendto'])){
		echo hidden('sendto', $_POST['sendto']);
	}
	echo hidden('confirm', 'yes').hidden('what', $_POST['what']).submit(_('Yes'), 'class="delbutton"').'</form></td><td>'.form('post');
	if(isset($_POST['multi'])){
		echo hidden('multi', 'on');
	}
	if(isset($_POST['sendto'])){
		echo hidden('sendto', $_POST['sendto']);
	}
	echo submit(_('No'), 'class="backbutton"').'</form></td><tr></table>';
	print_end();
}


function send_greeting(): void
{
	global $U, $language;
	print_start('greeting', (int) $U['refresh'], "$_SERVER[SCRIPT_NAME]?action=view&session=$U[session]&lang=$language");
	printf('<h1>'._('Welcome %s!').'</h1>', style_this(htmlspecialchars($U['nickname']), $U['style']));
	printf('<hr><small>'._('If this frame does not reload in %d seconds, you\'ll have to enable automatic redirection (meta refresh) in your browser. Also make sure no web filter, local proxy tool or browser plugin is preventing automatic refreshing! This could be for example "Polipo", "NoScript", etc.<br>As a workaround (or in case of server/proxy reload errors) you can always use the buttons at the bottom to refresh manually.').'</small>', $U['refresh']);
	$rulestxt=get_setting('rulestxt');
	if(!empty($rulestxt)){
		echo '<hr><div id="rules"><h2>'._('Rules')."</h2>$rulestxt</div>";
	}
	print_end();
}


function send_download(): void
{
	global $db;
	if(isset($_GET['id'])){
		$stmt=$db->prepare('SELECT filename, type, data FROM ' . PREFIX . 'files WHERE hash=?;');
		$stmt->execute([$_GET['id']]);
		if($data=$stmt->fetch(PDO::FETCH_ASSOC)){
			// Pastikan tidak ada output sebelumnya
			ob_clean();
			send_headers();
			
			// Set header yang tepat untuk gambar
			$fileExt = pathinfo($data['filename'], PATHINFO_EXTENSION);
			if(in_array(strtolower($fileExt), ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
				// Pastikan content-type sesuai dengan ekstensi file
				$imageTypes = [
					'jpg' => 'image/jpeg',
					'jpeg' => 'image/jpeg',
					'png' => 'image/png',
					'gif' => 'image/gif',
					'webp' => 'image/webp'
				];
				$ext = strtolower($fileExt);
				$mime = isset($imageTypes[$ext]) ? $imageTypes[$ext] : $data['type'];
				header("Content-Type: $mime");
			} else {
				header("Content-Type: $data[type]");
			}
			
			header("Content-Disposition: filename=\"$data[filename]\"");
			header("Content-Security-Policy: default-src 'none'; img-src 'self' data:;");
			echo base64_decode($data['data']);
			exit; // Pastikan tidak ada output tambahan
		}else{
			http_response_code(404);
			send_error(_('File not found!'));
		}
	}else{
		http_response_code(404);
		send_error(_('File not found!'));
	}
}

function send_colours(): void
{
	print_start('colours');
	echo '<h2>'._('Colourtable').'</h2><kbd><b>';
	for($red=0x00;$red<=0xFF;$red+=0x33){
		for($green=0x00;$green<=0xFF;$green+=0x33){
			for($blue=0x00;$blue<=0xFF;$blue+=0x33){
				$hcol=sprintf('%02X%02X%02X', $red, $green, $blue);
				echo "<span style=\"color:#$hcol\">$hcol</span> ";
			}
			echo '<br>';
		}
		echo '<br>';
	}
	echo '</b></kbd>'.form('profile').submit(_('Back to your Profile'), ' class="backbutton"').'</form>';
	print_end();
}




function print_chatters(): void
{
	global $U, $db, $language;
	if(!$U['hidechatters']){
		echo '<div id="chatters">';
		$stmt=$db->prepare('SELECT s.nickname, s.style, s.status, s.exiting, a.is_afk FROM ' . PREFIX . 'sessions s LEFT JOIN ' . PREFIX . 'afk_status a ON s.nickname = a.nickname WHERE s.entry!=0 AND s.status>0 AND s.incognito=0 AND s.nickname NOT IN (SELECT ign FROM '. PREFIX . 'ignored WHERE ignby=? UNION SELECT ignby FROM '. PREFIX . 'ignored WHERE ign=?) ORDER BY s.status DESC, s.lastpost DESC;');
		$stmt->execute([$U['nickname'], $U['nickname']]);
		$nc=substr(time(), -6);
		$G=$M=$S=$A=$F=[];
		$channellink="<a class=\"channellink\" href=\"$_SERVER[SCRIPT_NAME]?action=post&amp;session=$U[session]&amp;lang=$language&amp;nc=$nc&amp;sendto=";
		$nicklink="<a class=\"nicklink\" href=\"$_SERVER[SCRIPT_NAME]?action=post&amp;session=$U[session]&amp;lang=$language&amp;nc=$nc&amp;sendto=";
		while($user=$stmt->fetch(PDO::FETCH_NUM)){
			$link=$nicklink.urlencode($user[0]).'" target="post">'.style_this(htmlspecialchars($user[0]), $user[1]);
			if ($user[4]) {
				$link .= '<span class="afk-badge">💤</span>';
			}
			$link .= '</a>';
			if ($user[3]>0) {
				$link .= '<span class="sysmsg" title="'._('logging out').'">'.get_setting('exitingtxt').'</span>';
			}
			if($user[2]<2){ // guest or superguest
				$G[]=$link;
			} elseif($user[2]==7 || $user[2]==8){ // admin or superadmin
				$A[]=$link;
			} elseif($user[2]==9){ // staff
				$S[]=$link;
			} elseif($user[2]>=5 && $user[2]<=6){ // staff
				$S[]=$link;
			} elseif($user[2]==3){ // member
				$M[]=$link;
			} elseif($user[2]==2){
				$F[]=$link;
			}
		}
		
		echo '<div class="chatter-group">';
		if($U['status']>5){ // can chat in admin channel
			echo '<div class="chatter-section">';
			echo '<span class="chatter-section-title">' . $channellink . 's _" target="post">' . _('Admin') . ':</a></span>';
			echo '<div class="chatter-list">'.implode(' ', $A).'</div>';
			echo '</div>';
		} else {
			echo '<div class="chatter-section">';
			echo '<span class="chatter-section-title">'._('Admin:').'</span>';
			echo '<div class="chatter-list">'.implode(' ', $A).'</div>';
			echo '</div>';
		}

		if($U['status']>4){ // can chat in staff channel
			echo '<div class="chatter-section">';
			echo '<span class="chatter-section-title">' . $channellink . 's &#37;" target="post">' . _('Staff') . ':</a></span>';
			echo '<div class="chatter-list">'.implode(' ', $S).'</div>';
			echo '</div>';
		} else {
			echo '<div class="chatter-section">';
			echo '<span class="chatter-section-title">'._('Staff:').'</span>';
			echo '<div class="chatter-list">'.implode(' ', $S).'</div>';
			echo '</div>';
		}

		if($U['status']>=3){ // can chat in member channel
			echo '<div class="chatter-section">';
			echo '<span class="chatter-section-title">' . $channellink . 's ?" target="post">' . _('Members') . ':</a></span>';
			echo '<div class="chatter-list">'.implode(' ', $M).'</div>';
			echo '</div>';
		} else {
			echo '<div class="chatter-section">';
			echo '<span class="chatter-section-title">'._('Members:').'</span>';
			echo '<div class="chatter-list">'.implode(' ', $M).'</div>';
			echo '</div>';
		}
		
		if($U['status']){ // Show friends section if user has status
			echo '<div class="chatter-section">';
			echo '<span class="chatter-section-title">' . _('Friends:') . '</span>';
			echo '<div class="chatter-list">'.implode(' ', $F).'</div>';
			echo '</div>';
		}

		echo '<div class="chatter-section">';
		echo '<span class="chatter-section-title">' . $channellink . 's *" target="post">' . _('Guests') . ':</a></span>';
		echo '<div class="chatter-list">'.implode(' ', $G).'</div>';
		echo '</div>';
		
		echo '</div>'; // end chatter-group
		echo '</div>'; // end chatters
	}
}

//  session management

function create_session(bool $setup, string $nickname, string $password): void
{
	global $U;
	$U['nickname']=preg_replace('/\s/', '', $nickname);
	if(check_member($password)){
		if($setup && $U['status']>=7){
			$U['incognito']=1;
		}
		$U['entry']=$U['lastpost']=time();
	}else{
		add_user_defaults($password);
		check_captcha($_POST['challenge'] ?? '', $_POST['captcha'] ?? '');
		$ga=(int) get_setting('guestaccess');
		if(!valid_nick($U['nickname'])){
			send_error(sprintf(_('Invalid nickname (%1$d characters maximum and has to match the regular expression "%2$s")'), get_setting('maxname'), get_setting('nickregex')));
		}
		if(!valid_pass($password)){
			send_error(sprintf(_('Invalid password (At least %1$d characters and has to match the regular expression "%2$s")'), get_setting('minpass'), get_setting('passregex')));
		}
		if($ga===0){
			send_error(_('Sorry, currently members only!'));
		}elseif(in_array($ga, [2, 3], true)){
			$U['entry'] = 0;
		}
		if(get_setting('englobalpass')!=0 && isset($_POST['globalpass']) && $_POST['globalpass']!=get_setting('globalpass')){
			send_error(_('Wrong global Password!'));
		}
	}
	$U['exiting']=0;
	try {
		$U[ 'postid' ] = bin2hex( random_bytes( 3 ) );
	} catch(Exception $e) {
		send_error($e->getMessage());
	}
	write_new_session($password);
}

function check_captcha(string $challenge, string $captcha_code): void
{
	global $db, $memcached, $err;
	$captcha=(int) get_setting('captcha');
	if($captcha!==0){
		if(empty($challenge)){
			$err = "Wrong Captcha";
			send_login( $err);
		}
		$code = '';
		if(MEMCACHED){
			if(!$code=$memcached->get(DBNAME . '-' . PREFIX . "captcha-$_POST[challenge]")){
				$err = "Captcha expired";
				send_login( $err);
			}
			$memcached->delete(DBNAME . '-' . PREFIX . "captcha-$_POST[challenge]");
		}else{
			$stmt=$db->prepare('SELECT code FROM ' . PREFIX . 'captcha WHERE id=?;');
			$stmt->execute([$challenge]);
			$stmt->bindColumn(1, $code);
			if(!$stmt->fetch(PDO::FETCH_BOUND)){
				$err = "Captcha expired";
				send_login( $err);
			}
			$time=time();
			$stmt=$db->prepare('DELETE FROM ' . PREFIX . 'captcha WHERE id=? OR time<(?-(SELECT value FROM ' . PREFIX . "settings WHERE setting='captchatime'));");
			$stmt->execute([$challenge, $time]);
		}
		if($captcha_code!==$code){
			if($captcha!==3 || strrev($captcha_code)!==$code){
				$err = "Wrong Captcha";
				send_login( $err);
			}
		}
	}
}

function is_definitely_ssl() : bool {
	if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
		return true;
	}
	if (isset($_SERVER['SERVER_PORT']) && ('443' == $_SERVER['SERVER_PORT'])) {
		return true;
	}
	if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && ('https' === $_SERVER['HTTP_X_FORWARDED_PROTO'])) {
		return true;
	}
	return false;
}

function set_secure_cookie(string $name, string $value): void
{
	if (version_compare(PHP_VERSION, '7.3.0') >= 0) {
		setcookie($name, $value, ['expires' => 0, 'path' => '/', 'domain' => '', 'secure' => is_definitely_ssl(), 'httponly' => true, 'samesite' => 'Strict']);
	}else{
		setcookie($name, $value, 0, '/', '', is_definitely_ssl(), true);
	}
}

function write_new_session(string $password): void
{
	global $U, $db, $session;
	$stmt=$db->prepare('SELECT * FROM ' . PREFIX . 'sessions WHERE nickname=?;');
	$stmt->execute([$U['nickname']]);
	if($temp=$stmt->fetch(PDO::FETCH_ASSOC)){
		// check whether alrady logged in
		if(password_verify($password, $temp['passhash'])){
			$U=$temp;
			check_kicked();
			set_secure_cookie(COOKIENAME, $U['session']);
		}else{
			send_error(_('A user with this nickname is already logged in.')."<br>"._('Wrong Password!'));
		}
	}else{
		// create new session
		$stmt=$db->prepare('SELECT null FROM ' . PREFIX . 'sessions WHERE session=?;');
		do{
			try {
				$U[ 'session' ] = bin2hex( random_bytes( 16 ) );
			} catch(Exception $e) {
				send_error($e->getMessage());
			}
			$stmt->execute([$U['session']]);
		}while($stmt->fetch(PDO::FETCH_NUM)); // check for hash collision
		if(isset($_SERVER['HTTP_USER_AGENT'])){
			$useragent=htmlspecialchars($_SERVER['HTTP_USER_AGENT']);
		}else{
			$useragent='';
		}
		if(get_setting('trackip')){
			$ip=$_SERVER['REMOTE_ADDR'];
		}else{
			$ip='';
		}
		$stmt=$db->prepare('INSERT INTO ' . PREFIX . 'sessions (session, nickname, status, refresh, style, lastpost, passhash, useragent, bgcolour, entry, exiting, timestamps, embed, incognito, ip, nocache, tz, eninbox, hidechatters, nocache_old, postid, sortupdown) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);');
		$stmt->execute([
			$U['session'], 
			$U['nickname'], 
			$U['status'], 
			$U['refresh'], 
			$U['style'], 
			$U['lastpost'], 
			$U['passhash'], 
			$useragent, 
			$U['bgcolour'], 
			$U['entry'], 
			$U['exiting'], 
			$U['timestamps'], 
			$U['embed'], 
			$U['incognito'], 
			$ip, 
			$U['nocache'], 
			$U['tz'], 
			$U['eninbox'], 
			$U['hidechatters'], 
			$U['nocache_old'], 
			$U['postid'],
			$U['sortupdown'] ?? 0
		]);
		$session = $U['session'];
		set_secure_cookie(COOKIENAME, $U['session']);
		if($U['status']>=3 && !$U['incognito']){
			add_system_message(sprintf(get_setting('msgenter'), style_this(htmlspecialchars($U['nickname']), $U['style'])), '');
		}
	}
}



function approve_session(): void
{
	global $db;
	if(isset($_POST['what'])){
		if($_POST['what']==='allowchecked' && isset($_POST['csid'])){
			$stmt=$db->prepare('UPDATE ' . PREFIX . 'sessions SET entry=lastpost WHERE nickname=?;');
			foreach($_POST['csid'] as $nick){
				$stmt->execute([$nick]);
			}
		}elseif($_POST['what']==='allowall' && isset($_POST['alls'])){
			$stmt=$db->prepare('UPDATE ' . PREFIX . 'sessions SET entry=lastpost WHERE nickname=?;');
			foreach($_POST['alls'] as $nick){
				$stmt->execute([$nick]);
			}
		}elseif($_POST['what']==='denychecked' && isset($_POST['csid'])){
			$time=60*(get_setting('kickpenalty')-get_setting('guestexpire'))+time();
			$stmt=$db->prepare('UPDATE ' . PREFIX . 'sessions SET lastpost=?, status=0, kickmessage=? WHERE nickname=? AND status=1;');
			foreach($_POST['csid'] as $nick){
				$stmt->execute([$time, $_POST['kickmessage'], $nick]);
			}
		}elseif($_POST['what']==='denyall' && isset($_POST['alls'])){
			$time=60*(get_setting('kickpenalty')-get_setting('guestexpire'))+time();
			$stmt=$db->prepare('UPDATE ' . PREFIX . 'sessions SET lastpost=?, status=0, kickmessage=? WHERE nickname=? AND status=1;');
			foreach($_POST['alls'] as $nick){
				$stmt->execute([$time, $_POST['kickmessage'], $nick]);
			}
		}
	}
}

function check_login(): void
{
	global $U, $db;
	$ga=(int) get_setting('guestaccess');
	parse_sessions();
	if(isset($U['session'])){
		if($U['exiting']==1){
			$stmt=$db->prepare('UPDATE ' . PREFIX . 'sessions SET exiting=0 WHERE session=? LIMIT 1;');
			$stmt->execute([$U['session']]);
		}
		check_kicked();
	}elseif(get_setting('englobalpass')==1 && (!isset($_POST['globalpass']) || $_POST['globalpass']!=get_setting('globalpass'))){
		send_error(_('Wrong global Password!'));
	}elseif(!isset($_POST['nick']) || !isset($_POST['pass'])){
		send_login();
	}else{
		if($ga===4){
			send_chat_disabled();
		}
		if(!empty($_POST['regpass']) && $_POST['regpass']!==$_POST['pass']){
			send_error(_('Password confirmation does not match!'));
		}
		
		
		create_session(false, $_POST['nick'], $_POST['pass']);
		if(!empty($_POST['regpass'])){
			$guestreg=(int) get_setting('guestreg');
			if($guestreg===1){
				register_guest(2, $_POST['nick']);
				$U['status']=2;
			}elseif($guestreg===2){
				register_guest(3, $_POST['nick']);
				$U['status']=3;
			}
		}
	}
	if($U['status']==1){
		if(in_array($ga, [2, 3], true)){
			send_waiting_room();
		}
	}
}
function kill_session(): void
{
	global $U, $db, $session;
	parse_sessions();
	check_expired();
	check_kicked();
	setcookie(COOKIENAME, false);
	$session = '';
	$stmt=$db->prepare('DELETE FROM ' . PREFIX . 'sessions WHERE session=?;');
	$stmt->execute([$U['session']]);
	if($U['status']>=3 && !$U['incognito']){
		add_system_message(sprintf(get_setting('msgexit'), style_this(htmlspecialchars($U['nickname']), $U['style'])), '');
	}
}

function kick_chatter(array $names, string $mes, bool $purge) : bool {
	global $U, $db;
	$lonick='';
	$time=60*(get_setting('kickpenalty')-get_setting('guestexpire'))+time();
	$check=$db->prepare('SELECT style, entry FROM ' . PREFIX . 'sessions WHERE nickname=? AND status!=0 AND (status<? OR nickname=?);');
	$stmt=$db->prepare('UPDATE ' . PREFIX . 'sessions SET lastpost=?, status=0, kickmessage=? WHERE nickname=?;');
	$all=false;
	if($names[0]==='s *'){
		$tmp=$db->query('SELECT nickname FROM ' . PREFIX . 'sessions WHERE status=1;');
		$names=[];
		while($name=$tmp->fetch(PDO::FETCH_NUM)){
			$names[]=$name[0];
		}
		$all=true;
	}
	$i=0;
	foreach($names as $name){
		// Skip if trying to kick yourself
		if($name === $U['nickname']){
			continue;
		}
		
		$check->execute([$name, $U['status'], $U['nickname']]);
		if($temp=$check->fetch(PDO::FETCH_ASSOC)){
			$stmt->execute([$time, $mes, $name]);
			if($purge){
				del_all_messages($name, (int) $temp['entry']);
			}
			$lonick.=style_this(htmlspecialchars($name), $temp['style']).', ';
			++$i;
		}
	}
	if($i>0){
		if($all){
			add_system_message(get_setting('msgallkick'), $U['nickname']);
		}else{
			$lonick=substr($lonick, 0, -2);
			if($i>1){
				add_system_message(sprintf(get_setting('msgmultikick'), $lonick), $U['nickname']);
			}else{
				add_system_message(sprintf(get_setting('msgkick'), $lonick), $U['nickname']);
			}
		}
		return true;
	}
	return false;
}

function logout_chatter(array $names): void
{
	global $U, $db;
	$stmt=$db->prepare('DELETE FROM ' . PREFIX . 'sessions WHERE nickname=? AND status<?;');
	if($names[0]==='s *'){
		$tmp=$db->query('SELECT nickname FROM ' . PREFIX . 'sessions WHERE status=1;');
		$names=[];
		while($name=$tmp->fetch(PDO::FETCH_NUM)){
			$names[]=$name[0];
		}
	}
	foreach($names as $name){
		$stmt->execute([$name, $U['status']]);
	}
}

function check_session(): void
{
	global $U;
	parse_sessions();
	check_expired();
	check_kicked();
	if($U['entry']==0){
		send_waiting_room();
	}
}

function check_expired(): void
{
	global $U, $session;
	if(!isset($U['session'])){
		setcookie(COOKIENAME, false);
		$session = '';
		send_error(_('Invalid/expired session'));
	}
}

function get_count_mods() : int {
	global $db;
	$c=$db->query('SELECT COUNT(*) FROM ' . PREFIX . 'sessions WHERE status>=5')->fetch(PDO::FETCH_NUM);
	return (int) $c[0];
}

function check_kicked(): void
{
	global $U, $session;
	if($U['status']==0){
		setcookie(COOKIENAME, false);
		$session = '';
		send_error(_('You have been kicked!')."<br>$U[kickmessage]");
	}
}

function get_nowchatting(): void
{
	global $db;
	parse_sessions();
	$stmt=$db->query('SELECT COUNT(*) FROM ' . PREFIX . 'sessions WHERE entry!=0 AND status>0 AND incognito=0;');
	$count=$stmt->fetch(PDO::FETCH_NUM);
	echo '<div id="chatters">'.sprintf(_('Currently %d chatter(s) in room:'), $count[0]).'<br>';
	if(!get_setting('hidechatters')){
		$stmt=$db->query('SELECT nickname, style FROM ' . PREFIX . 'sessions WHERE entry!=0 AND status>0 AND incognito=0 ORDER BY status DESC, lastpost DESC;');
		while($user=$stmt->fetch(PDO::FETCH_NUM)){
			echo style_this(htmlspecialchars($user[0]), $user[1]).' &nbsp; ';
		}
	}
	echo '</div>';
}

function parse_sessions(): void
{
	global $U, $db, $session;
	// look for our session
	if(!empty($session)){
		$stmt=$db->prepare('SELECT * FROM ' . PREFIX . 'sessions WHERE session=?;');
		$stmt->execute([$session]);
		if($tmp=$stmt->fetch(PDO::FETCH_ASSOC)){
			$U=$tmp;
		}
	}
	set_default_tz();
}

//  member handling

function check_member(string $password) : bool {
	global $U, $db;
	$stmt=$db->prepare('SELECT * FROM ' . PREFIX . 'members WHERE nickname=?;');
	$stmt->execute([$U['nickname']]);
	if($temp=$stmt->fetch(PDO::FETCH_ASSOC)){
		if(get_setting('dismemcaptcha')==0){
			check_captcha($_POST['challenge'] ?? '', $_POST['captcha'] ?? '');
		}
		if($temp['passhash']===md5(sha1(md5($U['nickname'].$password)))){
			// old hashing method, update on the fly
			$temp['passhash']=password_hash($password, PASSWORD_DEFAULT);
			$stmt=$db->prepare('UPDATE ' . PREFIX . 'members SET passhash=? WHERE nickname=?;');
			$stmt->execute([$temp['passhash'], $U['nickname']]);
		}
		if(password_verify($password, $temp['passhash'])){
			$U=$temp;
			$stmt=$db->prepare('UPDATE ' . PREFIX . 'members SET lastlogin=? WHERE nickname=?;');
			$stmt->execute([time(), $U['nickname']]);
			return true;
		}else{
			$stmt=$db->prepare('UPDATE ' . PREFIX . 'members SET loginfails=? WHERE nickname=?;');
			$stmt->execute([$temp['loginfails']+1, $temp['nickname']]);
			send_error(_('This nickname is a registered member.')."<br>"._('Wrong Password!'));
		}
	}
	return false;
}

function delete_account(): void
{
	global $U, $db;
	if($U['status']<8){
		$stmt=$db->prepare('UPDATE ' . PREFIX . 'sessions SET status=1, incognito=0 WHERE nickname=?;');
		$stmt->execute([$U['nickname']]);
		$stmt=$db->prepare('DELETE FROM ' . PREFIX . 'members WHERE nickname=?;');
		$stmt->execute([$U['nickname']]);
		$stmt=$db->prepare('DELETE FROM ' . PREFIX . 'inbox WHERE recipient=?;');
		$stmt->execute([$U['nickname']]);
		$stmt=$db->prepare('DELETE FROM ' . PREFIX . 'notes WHERE (type=2 OR type=3) AND editedby=?;');
		$stmt->execute([$U['nickname']]);
		$U['status']=1;
	}
}

function register_guest(int $status, string $nick) : string {
	global $U, $db;
	$stmt=$db->prepare('SELECT style FROM ' . PREFIX . 'members WHERE nickname=?');
	$stmt->execute([$nick]);
	if($tmp=$stmt->fetch(PDO::FETCH_NUM)){
		return sprintf(_('%s is already registered.'), style_this(htmlspecialchars($nick), $tmp[0]));
	}
	$stmt=$db->prepare('SELECT * FROM ' . PREFIX . 'sessions WHERE nickname=? AND status=1;');
	$stmt->execute([$nick]);
	if($reg=$stmt->fetch(PDO::FETCH_ASSOC)){
		$reg['status']=$status;
		$stmt=$db->prepare('UPDATE ' . PREFIX . 'sessions SET status=? WHERE session=?;');
		$stmt->execute([$reg['status'], $reg['session']]);
	}else{
		return sprintf(_("Can't register %s"), htmlspecialchars($nick));
	}
	$stmt=$db->prepare('INSERT INTO ' . PREFIX . 'members (nickname, passhash, status, refresh, bgcolour, regedby, timestamps, embed, style, incognito, nocache, tz, eninbox, sortupdown, hidechatters, nocache_old) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);');
	$stmt->execute([$reg['nickname'], $reg['passhash'], $reg['status'], $reg['refresh'], $reg['bgcolour'], $U['nickname'], $reg['timestamps'], $reg['embed'], $reg['style'], $reg['incognito'], $reg['nocache'], $reg['tz'], $reg['eninbox'], $reg['sortupdown'], $reg['hidechatters'], $reg['nocache_old']]);
	if($reg['status']==3){
		add_system_message(sprintf(get_setting('msgmemreg'), style_this(htmlspecialchars($reg['nickname']), $reg['style'])), $U['nickname']);
	}else{
		add_system_message(sprintf(get_setting('msgsureg'), style_this(htmlspecialchars($reg['nickname']), $reg['style'])), $U['nickname']);
	}
	return sprintf(_('%s successfully registered.'), style_this(htmlspecialchars($reg['nickname']), $reg['style']));
}

function register_new(string $nick, string $pass) : string {
	global $U, $db;
	$nick=preg_replace('/\s/', '', $nick);
	if(empty($nick)){
		return '';
	}
	$stmt=$db->prepare('SELECT null FROM ' . PREFIX . 'sessions WHERE nickname=?');
	$stmt->execute([$nick]);
	if($stmt->fetch(PDO::FETCH_NUM)){
		return sprintf(_("Can't register %s"), htmlspecialchars($nick));
	}
	if(!valid_nick($nick)){
		return sprintf(_('Invalid nickname (%1$d characters maximum and has to match the regular expression "%2$s")'), get_setting('maxname'), get_setting('nickregex'));
	}
	if(!valid_pass($pass)){
		return sprintf(_('Invalid password (At least %1$d characters and has to match the regular expression "%2$s")'), get_setting('minpass'), get_setting('passregex'));
	}
	$stmt=$db->prepare('SELECT null FROM ' . PREFIX . 'members WHERE nickname=?');
	$stmt->execute([$nick]);
	if($stmt->fetch(PDO::FETCH_NUM)){
		return sprintf(_('%s is already registered.'), htmlspecialchars($nick));
	}
	$reg=[
		'nickname'	=>$nick,
		'passhash'	=>password_hash($pass, PASSWORD_DEFAULT),
		'status'	=>3,
		'refresh'	=>get_setting('defaultrefresh'),
		'bgcolour'	=>get_setting('colbg'),
		'regedby'	=>$U['nickname'],
		'timestamps'	=>get_setting('timestamps'),
		'style'		=>'color:#'.get_setting('coltxt').';',
		'embed'		=>1,
		'incognito'	=>0,
		'nocache'	=>0,
		'nocache_old'	=>1,
		'tz'		=>get_setting('defaulttz'),
		'eninbox'	=>0,
		'sortupdown'	=>get_setting('sortupdown'),
		'hidechatters'	=>get_setting('hidechatters'),
	];
	$stmt=$db->prepare('INSERT INTO ' . PREFIX . 'members (nickname, passhash, status, refresh, bgcolour, regedby, timestamps, style, embed, incognito, nocache, tz, eninbox, sortupdown, hidechatters, nocache_old) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);');
	$stmt->execute([$reg['nickname'], $reg['passhash'], $reg['status'], $reg['refresh'], $reg['bgcolour'], $reg['regedby'], $reg['timestamps'], $reg['style'], $reg['embed'], $reg['incognito'], $reg['nocache'], $reg['tz'], $reg['eninbox'], $reg['sortupdown'], $reg['hidechatters'], $reg['nocache_old']]);
	return sprintf(_('%s successfully registered.'), htmlspecialchars($reg['nickname']));
}

function change_status(string $nick, string $status) : string {
	global $U, $db;
	if(empty($nick)){
		return '';
	}elseif($U['status']<=$status || !preg_match('/^[023567\-]$/', $status)){
		return sprintf(_("Can't change status of %s"), htmlspecialchars($nick));
	}
	$stmt=$db->prepare('SELECT incognito, style FROM ' . PREFIX . 'members WHERE nickname=? AND status<?;');
	$stmt->execute([$nick, $U['status']]);
	if(!$old=$stmt->fetch(PDO::FETCH_NUM)){
		return sprintf(_("Can't change status of %s"), htmlspecialchars($nick));
	}
	if($status==='-'){
		$stmt=$db->prepare('DELETE FROM ' . PREFIX . 'members WHERE nickname=?;');
		$stmt->execute([$nick]);
		$stmt=$db->prepare('UPDATE ' . PREFIX . 'sessions SET status=1, incognito=0 WHERE nickname=?;');
		$stmt->execute([$nick]);
		$stmt=$db->prepare('DELETE FROM ' . PREFIX . 'inbox WHERE recipient=?;');
		$stmt->execute([$nick]);
		$stmt=$db->prepare('DELETE FROM ' . PREFIX . 'notes WHERE (type=2 OR type=3) AND editedby=?;');
		$stmt->execute([$nick]);
		return sprintf(_('%s successfully deleted from database.'), style_this(htmlspecialchars($nick), $old[1]));
	}else{
		if($status<5){
			$old[0]=0;
		}
		$stmt=$db->prepare('UPDATE ' . PREFIX . 'members SET status=?, incognito=? WHERE nickname=?;');
		$stmt->execute([$status, $old[0], $nick]);
		$stmt=$db->prepare('UPDATE ' . PREFIX . 'sessions SET status=?, incognito=? WHERE nickname=?;');
		$stmt->execute([$status, $old[0], $nick]);
		return sprintf(_('Status of %s successfully changed.'), style_this(htmlspecialchars($nick), $old[1]));
	}
}

function passreset(string $nick, string $pass) : string {
	global $U, $db;
	if(empty($nick)){
		return '';
	}
	$stmt=$db->prepare('SELECT null FROM ' . PREFIX . 'members WHERE nickname=? AND status<?;');
	$stmt->execute([$nick, $U['status']]);
	if($stmt->fetch(PDO::FETCH_ASSOC)){
		$passhash=password_hash($pass, PASSWORD_DEFAULT);
		$stmt=$db->prepare('UPDATE ' . PREFIX . 'members SET passhash=? WHERE nickname=?;');
		$stmt->execute([$passhash, $nick]);
		$stmt=$db->prepare('UPDATE ' . PREFIX . 'sessions SET passhash=? WHERE nickname=?;');
		$stmt->execute([$passhash, $nick]);
		return sprintf(_('Successfully reset password for %s'), htmlspecialchars($nick));
	}else{
		return sprintf(_("Can't reset password for %s"), htmlspecialchars($nick));
	}
}

function amend_profile(): void
{
	global $U;
	if(isset($_POST['refresh'])){
		$U['refresh']=$_POST['refresh'];
	}
	if($U['refresh']<5){
		$U['refresh']=5;
	}elseif($U['refresh']>150){
		$U['refresh']=150;
	}
	if(preg_match('/^#([a-f0-9]{6})$/i', $_POST['colour'], $match)){
		$colour=$match[1];
	}else{
		preg_match('/#([0-9a-f]{6})/i', $U['style'], $matches);
		$colour=$matches[1];
	}
	if(preg_match('/^#([a-f0-9]{6})$/i', $_POST['bgcolour'], $match)){
		$U['bgcolour']=$match[1];
	}
	$U['style']="color:#$colour;";
	if($U['status']>=3){
		$F=load_fonts();
		if(isset($F[$_POST['font']])){
			$U['style'].=$F[$_POST['font']];
		}
		if(isset($_POST['small'])){
			$U['style'].='font-size:smaller;';
		}
		if(isset($_POST['italic'])){
			$U['style'].='font-style:italic;';
		}
		if(isset($_POST['bold'])){
			$U['style'].='font-weight:bold;';
		}
	}
	if($U['status']>=5 && isset($_POST['incognito']) && get_setting('incognito')){
		$U['incognito']=1;
	}else{
		$U['incognito']=0;
	}
	if(isset($_POST['tz'])){
		$tzs=timezone_identifiers_list();
		if(in_array($_POST['tz'], $tzs)){
			$U['tz']=$_POST['tz'];
		}
	}
	if(isset($_POST['eninbox']) && $_POST['eninbox']>=0 && $_POST['eninbox']<=5){
		$U['eninbox']=$_POST['eninbox'];
	}
	$bool_settings=['timestamps', 'embed', 'nocache', 'sortupdown', 'hidechatters'];
	foreach($bool_settings as $setting){
		if(isset($_POST[$setting])){
			$U[$setting]=1;
		}else{
			$U[$setting]=0;
		}
	}
}

function save_profile() : string {
	global $U, $db;
	amend_profile();
	$stmt=$db->prepare('UPDATE ' . PREFIX . 'sessions SET refresh=?, style=?, bgcolour=?, timestamps=?, embed=?, incognito=?, nocache=?, tz=?, eninbox=?, sortupdown=?, hidechatters=? WHERE session=?;');
	$stmt->execute([$U['refresh'], $U['style'], $U['bgcolour'], $U['timestamps'], $U['embed'], $U['incognito'], $U['nocache'], $U['tz'], $U['eninbox'], $U['sortupdown'], $U['hidechatters'], $U['session']]);
	if($U['status']>=2){
		$stmt=$db->prepare('UPDATE ' . PREFIX . 'members SET refresh=?, bgcolour=?, timestamps=?, embed=?, incognito=?, style=?, nocache=?, tz=?, eninbox=?, sortupdown=?, hidechatters=? WHERE nickname=?;');
		$stmt->execute([$U['refresh'], $U['bgcolour'], $U['timestamps'], $U['embed'], $U['incognito'], $U['style'], $U['nocache'], $U['tz'], $U['eninbox'], $U['sortupdown'], $U['hidechatters'], $U['nickname']]);
	}
	if(!empty($_POST['unignore'])){
		$stmt=$db->prepare('DELETE FROM ' . PREFIX . 'ignored WHERE ign=? AND ignby=?;');
		$stmt->execute([$_POST['unignore'], $U['nickname']]);
	}
	if(!empty($_POST['ignore'])){
		$stmt=$db->prepare('SELECT null FROM ' . PREFIX . 'messages WHERE poster=? AND poster NOT IN (SELECT ign FROM ' . PREFIX . 'ignored WHERE ignby=?);');
		$stmt->execute([$_POST['ignore'], $U['nickname']]);
		if($U['nickname']!==$_POST['ignore'] && $stmt->fetch(PDO::FETCH_NUM)){
			$stmt=$db->prepare('INSERT INTO ' . PREFIX . 'ignored (ign, ignby) VALUES (?, ?);');
			$stmt->execute([$_POST['ignore'], $U['nickname']]);
		}
	}
	if($U['status']>1 && !empty($_POST['newpass'])){
		if(!valid_pass($_POST['newpass'])){
			return sprintf(_('Invalid password (At least %1$d characters and has to match the regular expression "%2$s")'), get_setting('minpass'), get_setting('passregex'));
		}
		if(!isset($_POST['oldpass'])){
			$_POST['oldpass']='';
		}
		if(!isset($_POST['confirmpass'])){
			$_POST['confirmpass']='';
		}
		if($_POST['newpass']!==$_POST['confirmpass']){
			return _('Password confirmation does not match!');
		}else{
			$U['newhash']=password_hash($_POST['newpass'], PASSWORD_DEFAULT);
		}
		if(!password_verify($_POST['oldpass'], $U['passhash'])){
			return _('Wrong Password!');
		}
		$U['passhash']=$U['newhash'];
		$stmt=$db->prepare('UPDATE ' . PREFIX . 'sessions SET passhash=? WHERE session=?;');
		$stmt->execute([$U['passhash'], $U['session']]);
		$stmt=$db->prepare('UPDATE ' . PREFIX . 'members SET passhash=? WHERE nickname=?;');
		$stmt->execute([$U['passhash'], $U['nickname']]);
	}
	if($U['status']>1 && !empty($_POST['newnickname'])){
		$msg=set_new_nickname();
		if($msg!==''){
			return $msg;
		}
	}
	return _('Your profile has successfully been saved.');
}

function set_new_nickname() : string {
	global $U, $db;
	$_POST['newnickname']=preg_replace('/\s/', '', $_POST['newnickname']);
	if(!valid_nick($_POST['newnickname'])){
		return sprintf(_('Invalid nickname (%1$d characters maximum and has to match the regular expression "%2$s")'), get_setting('maxname'), get_setting('nickregex'));
	}
	$stmt=$db->prepare('SELECT id FROM ' . PREFIX . 'sessions WHERE nickname=? UNION SELECT id FROM ' . PREFIX . 'members WHERE nickname=?;');
	$stmt->execute([$_POST['newnickname'], $_POST['newnickname']]);
	if($stmt->fetch(PDO::FETCH_NUM)){
		return _('Nickname is already taken');
	}else{
		// Make sure members can not read private messages of previous guests with the same name
		$stmt=$db->prepare('UPDATE ' . PREFIX . 'messages SET poster = "" WHERE poster = ? AND poststatus = 9;');
		$stmt->execute([$_POST['newnickname']]);
		$stmt=$db->prepare('UPDATE ' . PREFIX . 'messages SET recipient = "" WHERE recipient = ? AND poststatus = 9;');
		$stmt->execute([$_POST['newnickname']]);
		// change names in all tables
		$stmt=$db->prepare('UPDATE ' . PREFIX . 'members SET nickname=? WHERE nickname=?;');
		$stmt->execute([$_POST['newnickname'], $U['nickname']]);
		$stmt=$db->prepare('UPDATE ' . PREFIX . 'sessions SET nickname=? WHERE nickname=?;');
		$stmt->execute([$_POST['newnickname'], $U['nickname']]);
		$stmt=$db->prepare('UPDATE ' . PREFIX . 'messages SET poster=? WHERE poster=?;');
		$stmt->execute([$_POST['newnickname'], $U['nickname']]);
		$stmt=$db->prepare('UPDATE ' . PREFIX . 'messages SET recipient=? WHERE recipient=?;');
		$stmt->execute([$_POST['newnickname'], $U['nickname']]);
		$stmt=$db->prepare('UPDATE ' . PREFIX . 'ignored SET ignby=? WHERE ignby=?;');
		$stmt->execute([$_POST['newnickname'], $U['nickname']]);
		$stmt=$db->prepare('UPDATE ' . PREFIX . 'ignored SET ign=? WHERE ign=?;');
		$stmt->execute([$_POST['newnickname'], $U['nickname']]);
		$stmt=$db->prepare('UPDATE ' . PREFIX . 'inbox SET poster=? WHERE poster=?;');
		$stmt->execute([$_POST['newnickname'], $U['nickname']]);
		$stmt=$db->prepare('UPDATE ' . PREFIX . 'notes SET editedby=? WHERE editedby=?;');
		$stmt->execute([$_POST['newnickname'], $U['nickname']]);
		$U['nickname']=$_POST['newnickname'];
	}
	return '';
}

//sets default settings for guests
function add_user_defaults(string $password): void
{
	global $U;
	$U['refresh']=get_setting('defaultrefresh');
	$U['bgcolour']=get_setting('colbg');
	if(!isset($_POST['colour']) || !preg_match('/^[a-f0-9]{6}$/i', $_POST['colour']) || abs(greyval($_POST['colour'])-greyval(get_setting('colbg')))<75){
		do{
			$colour=sprintf('%06X', mt_rand(0, 16581375));
		}while(abs(greyval($colour)-greyval(get_setting('colbg')))<75);
	}else{
		$colour=$_POST['colour'];
	}
	$U['style']="color:#$colour;";
	$U['timestamps']=get_setting('timestamps');
	$U['embed']=1;
	$U['incognito']=0;
	$U['status']=1;
	$U['nocache']=get_setting('sortupdown');
	if($U['nocache']){
		$U['nocache_old']=0;
	}else{
		$U['nocache_old']=1;
	}
	$U['loginfails']=0;
	$U['tz']=get_setting('defaulttz');
	$U['eninbox']=0;
	$U['sortupdown']=get_setting('sortupdown');
	$U['hidechatters']=get_setting('hidechatters');
	$U['passhash']=password_hash($password, PASSWORD_DEFAULT);
	$U['entry']=$U['lastpost']=time();
	$U['exiting']=0;
}

// message handling
function handle_command(string $message): bool {
    global $U, $db;
    
    // Jika bukan command, return false
    if ($message[0] !== '/') {
        return false;
    }

    // Parse command dan arguments
    $parts = explode(' ', trim($message));
    $command = strtolower($parts[0]);
    $args = array_slice($parts, 1);

    try {
        switch ($command) {
            case '/kick':
                if (empty($args)) {
                    $_SESSION['command_error'] = _('Usage: /kick username [reason]');
                    return true;
                }
                
                $target = $args[0];
                
                // Cek permission untuk kick
                if ($U['status'] < 3 || ($U['status'] < 5 && !($U['status'] >= 3 && (get_setting('memkickalways') || (get_count_mods() == 0 && get_setting('memkick')))))) {
                    $_SESSION['command_error'] = _('You do not have permission to kick users');
                    return true;
                } elseif ($target === $U['nickname']) {
                    $_SESSION['command_error'] = _('You cannot kick yourself');
                    return true;
                }
                
                // Check target user status
                $stmt = $db->prepare('SELECT status FROM ' . PREFIX . 'sessions WHERE nickname = ? LIMIT 1');
                $stmt->execute([$target]);
                $targetUser = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($targetUser && $targetUser['status'] >= $U['status']) {
                    $_SESSION['command_error'] = _('You cannot kick users with equal or higher status');
                    return true;
                }
                
                if($U['status']>=5 || ($U['status']>=3 && (get_setting('memkickalways') || (get_count_mods()==0 && get_setting('memkick'))))){
                    $reason = count($args) > 1 ? implode(' ', array_slice($args, 1)) : _('');
                    kick_chatter([$target], $reason, true);
                }
                return true;

            case '/clean':
                if (empty($args)) {
                    $_SESSION['command_error'] = _('Usage: /clean username');
                    return true;
                }
                
                if ($U['status'] < 5) {
                    $_SESSION['command_error'] = _('You do not have permission to clean messages');
                    return true;
                }
                
                $target = $args[0];
                
                // Tidak bisa clean diri sendiri
                if ($target === $U['nickname']) {
                    $_SESSION['command_error'] = _('You cannot clean your own messages with this command');
                    return true;
                }
                
                // Cek status target
                $stmt = $db->prepare('SELECT status FROM ' . PREFIX . 'sessions WHERE nickname = ? LIMIT 1');
                $stmt->execute([$target]);
                $targetUser = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($targetUser && $targetUser['status'] >= $U['status']) {
                    $_SESSION['command_error'] = _('You cannot clean messages from users with equal or higher status');
                    return true;
                }
                
                del_all_messages($target, 0);
                return true;

            case '/dall':
                del_all_messages($U['nickname'], $U['entry']);
                return true;
                
            case '/multi':
                $_POST['multi'] = 'on';
                return true;
                
            case '/single':
                unset($_POST['multi']); 
                return true;

            default:
                $_SESSION['command_error'] = _('Unknown command: ') . $command;
                return true;
        }
    } catch (Exception $e) {
        add_system_message($e->getMessage(), 'System');
        return true;
    }
}

function validate_input() : string {
    global $U, $db;
    $inbox=false;
    $maxmessage=get_setting('maxmessage');
    $message=mb_substr($_POST['message'], 0, $maxmessage);
    $rejected=mb_substr($_POST['message'], $maxmessage);
    
    if(!isset($_POST['postid'])){ 
        kick_chatter([$U['nickname']], '', false);
    }
    
    if($U['postid'] !== $_POST['postid'] || (time() - $U['lastpost']) <= 1){ // reject bogus messages
        $rejected=$_POST['message'];
        $message='';
    }
    
    if(!empty($rejected)){
        $rejected=trim($rejected);
        $rejected=htmlspecialchars($rejected);
    }
    
    $message=htmlspecialchars($message);
    $message=preg_replace("/(\r?\n|\r\n?)/u", '<br>', $message);
    
    if(isset($_POST['multi'])){
        $message=preg_replace('/\s*<br>/u', '<br>', $message);
        $message=preg_replace('/<br>(<br>)+/u', '<br><br>', $message);
        $message=preg_replace('/<br><br>\s*$/u', '<br>', $message);
        $message=preg_replace('/^<br>\s*$/u', '', $message);
    }else{
        $message=str_replace('<br>', ' ', $message);
    }
    
    $message=trim($message);
    
    // Check if message is a command
    if (!empty($message) && $message[0] === '/') {
        if (handle_command($message)) {
            if (isset($_SESSION['command_error'])) {
                $rejected = $_SESSION['command_error'];
                unset($_SESSION['command_error']);
            }
            return $rejected;
        }
    }
    
    $message=preg_replace('/\s+/u', ' ', $message);
    $recipient='';
    
    if($_POST['sendto']==='s *'){
        $poststatus=1;
        $displaysend=sprintf(get_setting('msgsendall'), style_this(htmlspecialchars($U['nickname']), $U['style']));
    }elseif($_POST['sendto']==='s ?' && $U['status']>=3){
        $poststatus=3;
        $displaysend=sprintf(get_setting('msgsendmem'), style_this(htmlspecialchars($U['nickname']), $U['style']));
    }elseif($_POST['sendto']==='s %' && $U['status']>=5){
        $poststatus=5;
        $displaysend=sprintf(get_setting('msgsendmod'), style_this(htmlspecialchars($U['nickname']), $U['style']));
    }elseif($_POST['sendto']==='s _' && $U['status']>=6){
        $poststatus=6;
        $displaysend=sprintf(get_setting('msgsendadm'), style_this(htmlspecialchars($U['nickname']), $U['style']));
    }elseif($_POST['sendto'] === $U['nickname']){ // message to yourself?
        return '';
    }else{ // known nick in room?
        if(get_setting('disablepm')){
            //PMs disabled
            return '';
        }
        $stmt=$db->prepare('SELECT null FROM ' . PREFIX . 'ignored WHERE (ignby=? AND ign=?) OR (ign=? AND ignby=?);');
        $stmt->execute([$_POST['sendto'], $U['nickname'], $_POST['sendto'], $U['nickname']]);
        if($stmt->fetch(PDO::FETCH_NUM)){
            //ignored
            return '';
        }
        $stmt=$db->prepare('SELECT s.style, 0 AS inbox FROM ' . PREFIX . 'sessions AS s LEFT JOIN ' . PREFIX . 'members AS m ON (m.nickname=s.nickname) WHERE s.nickname=? AND (s.incognito=0 OR (m.eninbox!=0 AND m.eninbox<=?));');
        $stmt->execute([$_POST['sendto'], $U['status']]);
        if(!$tmp=$stmt->fetch(PDO::FETCH_ASSOC)){
            $stmt=$db->prepare('SELECT style, 1 AS inbox FROM ' . PREFIX . 'members WHERE nickname=? AND eninbox!=0 AND eninbox<=?;');
            $stmt->execute([$_POST['sendto'], $U['status']]);
            if(!$tmp=$stmt->fetch(PDO::FETCH_ASSOC)){
                //nickname left or disabled offline inbox for us
                return '';
            }
        }
        $recipient=$_POST['sendto'];
        $poststatus=9;
        $displaysend=sprintf(get_setting('msgsendprv'), style_this(htmlspecialchars($U['nickname']), $U['style']), style_this(htmlspecialchars($recipient), $tmp['style']));
        $inbox=$tmp['inbox'];
    }
    
    if($poststatus!==9 && preg_match('~^/me~iu', $message)){
        $displaysend=style_this(htmlspecialchars("$U[nickname] "), $U['style']);
        $message=preg_replace("~^/me\s?~iu", '', $message);
    }
    
    $message=apply_filter($message, $poststatus, $U['nickname']);
    $message=create_hotlinks($message);
    $message=apply_linkfilter($message);
    
    if(isset($_FILES['file']) && get_setting('enfileupload')>0 && get_setting('enfileupload')<=$U['status']){
        if($_FILES['file']['error']===UPLOAD_ERR_OK && $_FILES['file']['size']<=(1024*get_setting('maxuploadsize'))){
            $hash=sha1_file($_FILES['file']['tmp_name']);
            $name=htmlspecialchars($_FILES['file']['name']);
            $message=sprintf(get_setting('msgattache'), "<a class=\"attachement\" href=\"$_SERVER[SCRIPT_NAME]?action=download&amp;id=$hash\" target=\"_blank\">$name</a>", $message);
        }
    }
    
    if(add_message($message, $recipient, $U['nickname'], (int) $U['status'], $poststatus, $displaysend, $U['style'])){
        $U['lastpost']=time();
        try {
            $U[ 'postid' ] = bin2hex( random_bytes( 3 ) );
        } catch(Exception $e) {
            $U['postid'] = substr(time(), -6);
        }
        $stmt=$db->prepare('UPDATE ' . PREFIX . 'sessions SET lastpost=?, postid=? WHERE session=?;');
        $stmt->execute([$U['lastpost'], $U['postid'], $U['session']]);
        $stmt=$db->prepare('SELECT id FROM ' . PREFIX . 'messages WHERE poster=? ORDER BY id DESC LIMIT 1;');
        $stmt->execute([$U['nickname']]);
        $id=$stmt->fetch(PDO::FETCH_NUM);
        if($inbox && $id){
            $newmessage=[
                'postdate'  =>time(),
                'poster'    =>$U['nickname'],
                'recipient' =>$recipient,
                'text'      =>"<span class=\"usermsg\">$displaysend".style_this($message, $U['style']).'</span>'
            ];
            if(MSGENCRYPTED){
                try {
                    $newmessage[ 'text' ] = base64_encode( sodium_crypto_aead_aes256gcm_encrypt( $newmessage[ 'text' ], '', AES_IV, ENCRYPTKEY ) );
                } catch (SodiumException $e){
                    send_error($e->getMessage());
                }
            }
            $stmt=$db->prepare('INSERT INTO ' . PREFIX . 'inbox (postdate, postid, poster, recipient, text) VALUES(?, ?, ?, ?, ?)');
            $stmt->execute([$newmessage['postdate'], $id[0], $newmessage['poster'], $newmessage['recipient'], $newmessage['text']]);
        }
        if(isset($hash) && $id){
            if(function_exists('mime_content_type')){
                $type = mime_content_type($_FILES['file']['tmp_name']);
            }elseif(!empty($_FILES['file']['type']) && preg_match('~^[a-z0-9/\-.+]*$~i', $_FILES['file']['type'])){
                $type = $_FILES['file']['type'];
            }else{
                $type = 'application/octet-stream';
            }
            $stmt=$db->prepare('INSERT INTO ' . PREFIX . 'files (postid, hash, filename, type, data) VALUES (?, ?, ?, ?, ?);');
            $stmt->execute([$id[0], $hash, str_replace('"', '\"', $_FILES['file']['name']), $type, base64_encode(file_get_contents($_FILES['file']['tmp_name']))]);
            unlink($_FILES['file']['tmp_name']);
        }
    }
    return $rejected;
}

function apply_filter(string $message, int $poststatus, string $nickname) : string {
	global $U, $session;
	$message=str_replace('<br>', "\n", $message);
	$message=apply_mention($message);
	$filters=get_filters();
	foreach($filters as $filter){
		if($poststatus!==9 || !$filter['allowinpm']){
			if($filter['cs']){
				$message=preg_replace("/$filter[match]/u", $filter['replace'], $message, -1, $count);
			}else{
				$message=preg_replace("/$filter[match]/iu", $filter['replace'], $message, -1, $count);
			}
		}
		if(isset($count) && $count>0 && $filter['kick'] && ($U['status']<5 || get_setting('filtermodkick'))){
			kick_chatter([$nickname], $filter['replace'], false);
			setcookie(COOKIENAME, false);
			$session = '';
			send_error(_('You have been kicked!')."<br>$filter[replace]");
		}
	}
	$message=str_replace("\n", '<br>', $message);
	return $message;
}

function apply_linkfilter(string $message) : string {
	$filters=get_linkfilters();
	foreach($filters as $filter){
		$message=preg_replace_callback("/<a href=\"([^\"]+)\" target=\"_blank\" rel=\"noreferrer noopener\">([^<]*)<\/a>/iu",
			function ($matched) use(&$filter){
				return "<a href=\"$matched[1]\" target=\"_blank\" rel=\"noreferrer noopener\">".preg_replace("/$filter[match]/iu", $filter['replace'], $matched[2]).'</a>';
			}
		, $message);
	}
	$redirect=get_setting('redirect');
	if(get_setting('imgembed')){
		$message=preg_replace_callback('/\[img]\s?<a href="([^"]+)" target="_blank" rel="noreferrer noopener">([^<]*)<\/a>/iu',
			function ($matched){
				return str_ireplace('[/img]', '', "<br><a href=\"$matched[1]\" target=\"_blank\" rel=\"noreferrer noopener\"><img src=\"$matched[1]\" rel=\"noreferrer\" loading=\"lazy\"></a><br>");
			}
		, $message);
	}
	if(empty($redirect)){
		$redirect="$_SERVER[SCRIPT_NAME]?action=redirect&amp;url=";
	}
	if(get_setting('forceredirect')){
		$message=preg_replace_callback('/<a href="([^"]+)" target="_blank" rel="noreferrer noopener">([^<]*)<\/a>/u',
			function ($matched) use($redirect){
				return "<a href=\"$redirect".rawurlencode($matched[1])."\" target=\"_blank\" rel=\"noreferrer noopener\">$matched[2]</a>";
			}
		, $message);
	}elseif(preg_match_all('/<a href="([^"]+)" target="_blank" rel="noreferrer noopener">([^<]*)<\/a>/u', $message, $matches)){
		foreach($matches[1] as $match){
			if(!preg_match('~^http(s)?://~u', $match)){
				$message=preg_replace_callback('/<a href="('.preg_quote($match, '/').')\" target=\"_blank\" rel=\"noreferrer noopener\">([^<]*)<\/a>/u',
					function ($matched) use($redirect){
						return "<a href=\"$redirect".rawurlencode($matched[1])."\" target=\"_blank\" rel=\"noreferrer noopener\">$matched[2]</a>";
					}
				, $message);
			}
		}
	}
	return $message;
}

function create_hotlinks(string $message) : string {
	//Make hotlinks for URLs, redirect through dereferrer script to prevent session leakage
	// 1. all explicit schemes with whatever xxx://yyyyyyy
	$message=preg_replace('~(^|[^\w"])(\w+://[^\s<>]+)~iu', "$1<<$2>>", $message);
	// 2. valid URLs without scheme:
	$message=preg_replace('~((?:[^\s<>]*:[^\s<>]*@)?[a-z0-9\-]+(?:\.[a-z0-9\-]+)+(?::\d*)?/[^\s<>]*)(?![^<>]*>)~iu', "<<$1>>", $message); // server/path given
	$message=preg_replace('~((?:[^\s<>]*:[^\s<>]*@)?[a-z0-9\-]+(?:\.[a-z0-9\-]+)+:\d+)(?![^<>]*>)~iu', "<<$1>>", $message); // server:port given
	$message=preg_replace('~([^\s<>]*:[^\s<>]*@[a-z0-9\-]+(?:\.[a-z0-9\-]+)+(?::\d+)?)(?![^<>]*>)~iu', "<<$1>>", $message); // au:th@server given
	// 3. likely servers without any hints but not filenames like *.rar zip exe etc.
	$message=preg_replace('~((?:[a-z0-9\-]+\.)*(?:[a-z2-7]{55}d|[a-z2-7]{16})\.onion)(?![^<>]*>)~iu', "<<$1>>", $message);// *.onion
	$message=preg_replace('~([a-z0-9\-]+(?:\.[a-z0-9\-]+)+(?:\.(?!rar|zip|exe|gz|7z|bat|doc)[a-z]{2,}))(?=[^a-z0-9\-.]|$)(?![^<>]*>)~iu', "<<$1>>", $message);// xxx.yyy.zzz
	// Convert every <<....>> into proper links:
	$message=preg_replace_callback('/<<([^<>]+)>>/u',
		function ($matches){
			if(strpos($matches[1], '://')===false){
				return "<a href=\"http://$matches[1]\" target=\"_blank\" rel=\"noreferrer noopener\">$matches[1]</a>";
			}else{
				return "<a href=\"$matches[1]\" target=\"_blank\" rel=\"noreferrer noopener\">$matches[1]</a>";
			}
		}
	, $message);
	return $message;
}

function apply_mention(string $message) : string {
	return preg_replace_callback('/@([^\s]+)/iu', function ($matched){
		global $db;
		$nick=htmlspecialchars_decode($matched[1]);
		$rest='';
		for($i=0;$i<=3;++$i){
			//match case-sensitive present nicknames
			$stmt=$db->prepare('SELECT style FROM ' . PREFIX . 'sessions WHERE nickname=?;');
			$stmt->execute([$nick]);
			if($tmp=$stmt->fetch(PDO::FETCH_NUM)){
				return style_this(htmlspecialchars("@$nick"), $tmp[0]).$rest;
			}
			//match case-insensitive present nicknames
			$stmt=$db->prepare('SELECT style FROM ' . PREFIX . 'sessions WHERE LOWER(nickname)=LOWER(?);');
			$stmt->execute([$nick]);
			if($tmp=$stmt->fetch(PDO::FETCH_NUM)){
				return style_this(htmlspecialchars("@$nick"), $tmp[0]).$rest;
			}
			//match case-sensitive members
			$stmt=$db->prepare('SELECT style FROM ' . PREFIX . 'members WHERE nickname=?;');
			$stmt->execute([$nick]);
			if($tmp=$stmt->fetch(PDO::FETCH_NUM)){
				return style_this(htmlspecialchars("@$nick"), $tmp[0]).$rest;
			}
			//match case-insensitive members
			$stmt=$db->prepare('SELECT style FROM ' . PREFIX . 'members WHERE LOWER(nickname)=LOWER(?);');
			$stmt->execute([$nick]);
			if($tmp=$stmt->fetch(PDO::FETCH_NUM)){
				return style_this(htmlspecialchars("@$nick"), $tmp[0]).$rest;
			}
			if(strlen($nick)===1){
				break;
			}
			$rest=mb_substr($nick, -1).$rest;
			$nick=mb_substr($nick, 0, -1);
		}
		return $matched[0];
	}, $message);
}

function add_message(string $message, string $recipient, string $poster, int $delstatus, int $poststatus, string $displaysend, string$style) : bool {
	global $db;
	if($message===''){
		return false;
	}
	$newmessage=[
		'postdate'	=>time(),
		'poststatus'	=>$poststatus,
		'poster'	=>$poster,
		'recipient'	=>$recipient,
		'text'		=>"<span class=\"usermsg\">$displaysend".style_this($message, $style).'</span>',
		'delstatus'	=>$delstatus
	];
	//prevent posting the same message twice, if no other message was posted in-between.
	$stmt=$db->prepare('SELECT id FROM ' . PREFIX . 'messages WHERE poststatus=? AND poster=? AND recipient=? AND text=? AND id IN (SELECT * FROM (SELECT id FROM ' . PREFIX . 'messages ORDER BY id DESC LIMIT 1) AS t);');
	$stmt->execute([$newmessage['poststatus'], $newmessage['poster'], $newmessage['recipient'], $newmessage['text']]);
	if($stmt->fetch(PDO::FETCH_NUM)){
		return false;
	}
	write_message($newmessage);
	return true;
}

function add_system_message(string $mes, string $doer): void
{
	if($mes===''){
		return;
	}
	if($doer==='' || !get_setting('namedoers')){
		$sysmessage=[
			'postdate'	=>time(),
			'poststatus'	=>4,
			'poster'	=>'',
			'recipient'	=>'',
			'text'		=>"$mes",
			'delstatus'	=>4
		];

	} else {
		$sysmessage=[
			'postdate'	=>time(),
			'poststatus'	=>4,
			'poster'	=>'',
			'recipient'	=>'',
			'text'		=>"$mes ($doer)",
			'delstatus'	=>4
		];
	}
	write_message($sysmessage);
}
function write_message(array $message): void
{
	global $db;
	if(MSGENCRYPTED){
		try {
			$message['text']=base64_encode(sodium_crypto_aead_aes256gcm_encrypt($message['text'], '', AES_IV, ENCRYPTKEY));
		} catch (SodiumException $e){
			send_error($e->getMessage());
		}
	}
	$stmt=$db->prepare('INSERT INTO ' . PREFIX . 'messages (postdate, poststatus, poster, recipient, text, delstatus) VALUES (?, ?, ?, ?, ?, ?);');
	$stmt->execute([$message['postdate'], $message['poststatus'], $message['poster'], $message['recipient'], $message['text'], $message['delstatus']]);
	
	// Get the last insert ID for mention processing
	$messageId = $db->lastInsertId();
	
	// Process mentions if the message is not encrypted or after decrypting
	if(!MSGENCRYPTED) {
		// Process mentions directly
		process_message_mentions($message['text'], $messageId, $message['poster']);
	} else {
		// Decrypt message for mention processing
		try {
			$decrypted_text = sodium_crypto_aead_aes256gcm_decrypt(base64_decode($message['text']), null, AES_IV, ENCRYPTKEY);
			process_message_mentions($decrypted_text, $messageId, $message['poster']);
		} catch (SodiumException $e){
			error_log("Failed to process mentions: " . $e->getMessage());
		}
	}
	
	if($message['poststatus']<9 && get_setting('sendmail')){
		$subject='New Chat message';
		$headers='From: '.get_setting('mailsender')."\r\nX-Mailer: PHP/".phpversion()."\r\nContent-Type: text/html; charset=UTF-8\r\n";
		$body='<html><body style="background-color:#'.get_setting('colbg').';color:#'.get_setting('coltxt').";\">$message[text]</body></html>";
		mail(get_setting('mailreceiver'), $subject, $body, $headers);
	}
}


function clean_room(): void
{
	global $U, $db;
	$db->query('DELETE FROM ' . PREFIX . 'messages;');
	add_system_message(sprintf(get_setting('msgclean'), get_setting('chatname')), $U['nickname']);
}

function clean_selected(int $status, string $nick): void
{
	global $db;
	if(isset($_POST['mid'])){
		$stmt=$db->prepare('DELETE FROM ' . PREFIX . 'messages WHERE id=? AND (poster=? OR recipient=? OR (poststatus<? AND delstatus<?));');
		foreach($_POST['mid'] as $mid){
			$stmt->execute([$mid, $nick, $nick, $status, $status]);
		}
	}
}

function clean_inbox_selected(): void
{
	global $U, $db;
	if(isset($_POST['mid'])){
		$stmt=$db->prepare('DELETE FROM ' . PREFIX . 'inbox WHERE id=? AND recipient=?;');
		foreach($_POST['mid'] as $mid){
			$stmt->execute([$mid, $U['nickname']]);
		}
	}
}

function del_all_messages(string $nick, int $entry): void
{
	global $db, $U;
	$globally = (bool) get_setting('postbox_delete_globally');
	if($globally && $U['status'] > 4){
		$stmt = $db->prepare('DELETE FROM ' . PREFIX . 'messages;');
		$stmt->execute();
	} else {
		if ($nick === '') {
			$nick = $U['nickname'];
		}
		
		// Delete messages from main chat regardless of postdate
		$stmt = $db->prepare('DELETE FROM ' . PREFIX . 'messages WHERE poster=?;');
		$stmt->execute([$nick]);
		
		// Delete messages from inbox regardless of postdate
		$stmt = $db->prepare('DELETE FROM ' . PREFIX . 'inbox WHERE poster=?;');
		$stmt->execute([$nick]);
	}
}
function print_messages(int $delstatus=0): void
{
	global $U, $db;
	$dateformat = get_setting('dateformat');
	$removeEmbed = !$U['embed'] && get_setting('imgembed');
	$timestamps = $U['timestamps'] && !empty($dateformat);
	$direction = $U['sortupdown'] ? 'ASC' : 'DESC';
	$entry = $U['status'] > 1 ? 0 : $U['entry'];
	echo'<link rel="stylesheet" href="./template/style/frameset/midframe.css">';
	
	// Check if there are notifications before displaying them
	$hasNotifications = false;
	
	// Check for failed login attempts
	$stmt = $db->prepare('SELECT loginfails FROM ' . PREFIX . 'members WHERE nickname=?;');
	$stmt->execute([$U['nickname']]);
	$temp = $stmt->fetch(PDO::FETCH_NUM);
	if($temp && $temp[0] > 0) {
		$hasNotifications = true;
	}
	
	// Check for inbox messages
	if($U['status'] >= 2 && $U['eninbox'] != 0) {
		$stmt = $db->prepare('SELECT COUNT(*) FROM ' . PREFIX . 'inbox WHERE recipient=?;');
		$stmt->execute([$U['nickname']]);
		$tmp = $stmt->fetch(PDO::FETCH_NUM);
		if($tmp[0] > 0) {
			$hasNotifications = true;
		}
	}
	
	// Check for guests waiting for approval
	if($U['status'] >= 5 && get_setting('guestaccess') == 3) {
		$result = $db->query('SELECT COUNT(*) FROM ' . PREFIX . 'sessions WHERE entry=0 AND status=1;');
		$temp = $result->fetch(PDO::FETCH_NUM);
		if($temp[0] > 0) {
			$hasNotifications = true;
		}
	}
	
	// Check for mention notifications
	try {
		// Include this if statement to prevent errors if the function doesn't exist yet
		if (function_exists('check_new_mentions')) {
			$hasMentions = !empty(check_new_mentions($U['nickname']));
			if ($hasMentions) {
				$hasNotifications = true;
			}
		}
	} catch (Exception $e) {
		error_log("Error checking mentions: " . $e->getMessage());
	}
	
	// Only display notifications if there are any
	echo '<div id="messages">';
	if($hasNotifications) {
		print_notifications();
		
		// Display mention notifications if any
		try {
			if (function_exists('render_mention_notification')) {
				echo render_mention_notification($U['nickname']);
			}
		} catch (Exception $e) {
			error_log("Error rendering mention notifications: " . $e->getMessage());
		}
	}
	
	if($delstatus > 0) {
		$stmt = $db->prepare('SELECT postdate, id, text, poster, delstatus FROM ' . PREFIX . 'messages WHERE '.
			"(poststatus<? AND delstatus<?) OR ((poster=? OR recipient=?) AND postdate>=?) ORDER BY id $direction;");
		if($stmt === false) {
			send_error('Database error');
			return;
		}
		$stmt->execute([$U['status'], $delstatus, $U['nickname'], $U['nickname'], $entry]);
		
		while($message = $stmt->fetch(PDO::FETCH_ASSOC)) {
			prepare_message_print($message, $removeEmbed);
			echo '<div class="msg-container">';
			echo '<div class="msg">';
			echo "<label><input type=\"checkbox\" name=\"mid[]\" value=\"$message[id]\">";
			if($timestamps) {
				echo ' <small>'.date($dateformat, $message['postdate']).'</small><span class="space"> - </span>';
			}
			echo " $message[text]</label>";
			echo '</div>';
			
			$canDelete = false;
			if($message['poster'] === $U['nickname'] && (time() - $message['postdate']) <= 600) {
				$canDelete = true;
			} elseif($U['status'] >= 5 && isset($message['delstatus']) && $message['delstatus'] < $U['status']) {
				$canDelete = true;
			}
			
			if($canDelete) {
				echo '<div class="msg-delete">';
				echo form('delete','postbox') . 
					 hidden('what', 'hilite') . 
					 hidden('message_id', $message['id']) . 
					 submit('❌', 'style="color: red; border: none; background: none; cursor: pointer; padding: 0; font-size: 1em; font-weight: bold; text-shadow: 1px 1px 2px #000;"') . 
					 '</form>';
				echo '</div>';
			}
			echo '</div>';
		}
	} else {
		$stmt = $db->prepare('SELECT id, postdate, poststatus, text, poster, delstatus FROM ' . PREFIX . 'messages WHERE (poststatus<=? OR poststatus=4 OR '.
			'(poststatus=9 AND ( (poster=? AND recipient NOT IN (SELECT ign FROM ' . PREFIX . 'ignored WHERE ignby=?) ) OR recipient=?) AND postdate>=?)'.
			') AND poster NOT IN (SELECT ign FROM ' . PREFIX . "ignored WHERE ignby=?) ORDER BY id $direction;");
		if($stmt === false) {
			send_error('Database error');
			return;
		}
		$stmt->execute([$U['status'], $U['nickname'], $U['nickname'], $U['nickname'], $entry, $U['nickname']]);
		
		while($message = $stmt->fetch(PDO::FETCH_ASSOC)) {
			prepare_message_print($message, $removeEmbed);
			echo '<div class="msg-container">';
			echo '<div class="msg">';
			if($timestamps) {
				echo ' <small>'.date($dateformat, $message['postdate']).'</small><span class="space"> - </span>';
			}
			if($message['poststatus'] == 4) {
				echo '<span class="sysmsg" title="'._('system message').'">'.get_setting('sysmessagetxt')."$message[text]</span>";
			} else {
				echo $message['text'];
			}
			echo '</div>';
			
			$canDelete = false;
			if($message['poster'] === $U['nickname'] && (time() - $message['postdate']) <= 600) {
				$canDelete = true; 
			} elseif($U['status'] >= 5 && isset($message['delstatus']) && $message['delstatus'] < $U['status']) {
				$canDelete = true;
			}
			
			if($canDelete) {
				echo '<div class="msg-delete">';
				echo form('delete','postbox') . 
					 hidden('what', 'hilite') . 
					 hidden('message_id', $message['id']) . 
					 submit('❌', 'style="color: red; border: none; background: none; cursor: pointer; padding: 0; font-size: 1em; font-weight: bold; text-shadow: 1px 1px 2px #000;"') . 
					 '</form>';
				echo '</div>';
			}
			echo '</div>';
		}
	}
	echo '</div>';
	
}
function prepare_message_print(array &$message, bool $removeEmbed): void
{
	if(MSGENCRYPTED){
		try {
			$message['text']=sodium_crypto_aead_aes256gcm_decrypt(base64_decode($message['text']), null, AES_IV, ENCRYPTKEY);
		} catch (SodiumException $e){
			send_error($e->getMessage());
		}
	}
	if($removeEmbed){
		$message['text']=preg_replace_callback('/<img src="([^"]+)" rel="noreferrer" loading="lazy"><\/a>/u',
			function ($matched){
				return "$matched[1]</a>";
			}
		, $message['text']);
	}
}
// this and that
function send_headers(): void
{
	global $U, $scripts, $styles;
	header('Content-Type: text/html; charset=UTF-8');
	header('Pragma: no-cache');
	header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0, private');
	header('Expires: 0');
	header('Referrer-Policy: no-referrer');
	header("Permissions-Policy: accelerometer=(), ambient-light-sensor=(), autoplay=(), battery=(), camera=(), cross-origin-isolated=(), display-capture=(), document-domain=(), encrypted-media=(), execution-while-not-rendered=(), execution-while-out-of-viewport=(), fullscreen=(), geolocation=(), gyroscope=(), magnetometer=(), microphone=(), midi=(), navigation-override=(), payment=(), picture-in-picture=(), publickey-credentials-get=(), screen-wake-lock=(), sync-xhr=(), usb=(), web-share=(), xr-spatial-tracking=(), clipboard-read=(), clipboard-write=(), gamepad=(), speaker-selection=(), conversion-measurement=(), focus-without-user-activation=(), hid=(), idle-detection=(), sync-script=(), vertical-scroll=(), serial=(), trust-token-redemption=(), interest-cohort=(), otp-credentials=()");
	if(!get_setting('imgembed') || !($U['embed'] ?? false)){
		header("Cross-Origin-Embedder-Policy: require-corp");
	}
	header("Cross-Origin-Opener-Policy: same-origin");
	header("Cross-Origin-Resource-Policy: same-origin");
	$style_hashes = '';
	foreach($styles as $style) {
		$style_hashes .= " 'sha256-".base64_encode(hash('sha256', $style, true))."'";
	}
	// Completely disable JavaScript
	header("Content-Security-Policy: base-uri 'self'; default-src 'none'; font-src 'self' https://cdnjs.cloudflare.com; form-action 'self'; frame-src 'self'; img-src * data:; media-src * data:; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; script-src 'none';");
	header('X-Content-Type-Options: nosniff');
	header('X-XSS-Protection: 1; mode=block');
	if($_SERVER['REQUEST_METHOD'] === 'HEAD'){
		exit; // headers sent, no further processing needed
	}
}

function save_setup(array $C): void
{
	global $db;
	//sanity checks and escaping
	foreach($C['msg_settings'] as $setting => $title){
		$_POST[$setting]=htmlspecialchars($_POST[$setting]);
	}
	foreach($C['number_settings'] as $setting => $title){
		settype($_POST[$setting], 'int');
	}
	foreach($C['colour_settings'] as $setting => $title){
		if(preg_match('/^#([a-f0-9]{6})$/i', $_POST[$setting], $match)){
			$_POST[$setting]=$match[1];
		}else{
			unset($_POST[$setting]);
		}
	}
	settype($_POST['guestaccess'], 'int');
	if(!preg_match('/^[01234]$/', $_POST['guestaccess'])){
		unset($_POST['guestaccess']);
	}else{
		change_guest_access(intval($_POST['guestaccess']));
	}
	settype($_POST['englobalpass'], 'int');
	settype($_POST['captcha'], 'int');
	settype($_POST['dismemcaptcha'], 'int');
	settype($_POST['guestreg'], 'int');
	if(isset($_POST['defaulttz'])){
		$tzs=timezone_identifiers_list();
		if(!in_array($_POST['defaulttz'], $tzs)){
			unset($_POST['defualttz']);
		}
	}
	$_POST['rulestxt']=preg_replace("/(\r?\n|\r\n?)/u", '<br>', $_POST['rulestxt']);
	$_POST['chatname']=htmlspecialchars($_POST['chatname']);
	$_POST['redirect']=htmlspecialchars($_POST['redirect']);
	if($_POST['memberexpire']<5){
		$_POST['memberexpire']=5;
	}
	if($_POST['captchatime']<30){
		$_POST['memberexpire']=30;
	}
	$max_refresh_rate = (int) get_setting('max_refresh_rate');
	$min_refresh_rate = (int) get_setting('min_refresh_rate');
	if($_POST['defaultrefresh']<$min_refresh_rate){
		$_POST['defaultrefresh']=$min_refresh_rate;
	}elseif($_POST['defaultrefresh']>$max_refresh_rate){
		$_POST['defaultrefresh']=$max_refresh_rate;
	}
	if($_POST['maxname']<1){
		$_POST['maxname']=1;
	}elseif($_POST['maxname']>50){
		$_POST['maxname']=50;
	}
	if($_POST['maxmessage']<1){
		$_POST['maxmessage']=1;
	}elseif($_POST['maxmessage']>16000){
		$_POST['maxmessage']=16000;
	}
		if($_POST['numnotes']<1){
		$_POST['numnotes']=1;
	}
	if(!valid_regex($_POST['nickregex'])){
		unset($_POST['nickregex']);
	}
	if(!valid_regex($_POST['passregex'])){
		unset($_POST['passregex']);
	}
	//save values
	foreach($C['settings'] as $setting){
		if(isset($_POST[$setting])){
			update_setting($setting, $_POST[$setting]);
		}
	}
}

function change_guest_access(int $guest_access) : void {
	global $db;
	if($guest_access === 4){
		$db->exec('DELETE FROM ' . PREFIX . 'sessions WHERE status<7;');
	}elseif($guest_access === 0){
		$db->exec('DELETE FROM ' . PREFIX . 'sessions WHERE status<3;');
	}
}

function set_default_tz(): void
{
	global $U;
	if(isset($U['tz'])){
		date_default_timezone_set($U['tz']);
	}else{
		date_default_timezone_set(get_setting('defaulttz'));
	}
}

function valid_admin() : bool {
	global $U;
	parse_sessions();
	if(!isset($U['session']) && isset($_POST['nick']) && isset($_POST['pass'])){
		create_session(true, $_POST['nick'], $_POST['pass']);
	}
	if(isset($U['status'])){
		if($U['status']>=7){
			return true;
		}
		send_access_denied();
	}
	return false;
}

function valid_nick(string $nick) : bool{
	$len=mb_strlen($nick);
	if($len<1 || $len>get_setting('maxname')){
		return false;
	}
	return preg_match('/'.get_setting('nickregex').'/u', $nick);
}

function valid_pass(string $pass) : bool {
	if(mb_strlen($pass)<get_setting('minpass')){
		return false;
	}
	return preg_match('/'.get_setting('passregex').'/u', $pass);
}

function valid_regex(string &$regex) : bool {
	$regex=preg_replace('~(^|[^\\\\])/~', "$1\/u", $regex); // Escape "/" if not yet escaped
	return (@preg_match("/$_POST[match]/u", '') !== false);
}

function get_timeout(int $lastpost, int $expire): void
{
	$s=($lastpost+60*$expire)-time();
	$m=floor($s/60);
	$s%=60;
	if($s<10){
		$s="0$s";
	}
	if($m>60){
		$h=floor($m/60);
		$m%=60;
		if($m<10){
			$m="0$m";
		}
		echo "$h:$m:$s";
	}else{
		echo "$m:$s";
	}
}

function print_colours(): void
{
	// Prints a short list with selected named HTML colours and filters out illegible text colours for the given background.
	// It's a simple comparison of weighted grey values. This is not very accurate but gets the job done well enough.
	// name=>[colour, greyval(colour), translated name]
	$colours=[
		'Beige'=>['F5F5DC', 242.25, _('Beige')],
		'Black'=>['000000', 0, _('Black')],
		'Blue'=>['0000FF', 28.05, _('Blue')],
		'BlueViolet'=>['8A2BE2', 91.63, _('Blue violet')],
		'Brown'=>['A52A2A', 78.9, _('Brown')],
		'Cyan'=>['00FFFF', 178.5, _('Cyan')],
		'DarkBlue'=>['00008B', 15.29, _('Dark blue')],
		'DarkGreen'=>['006400', 59, _('Dark green')],
		'DarkRed'=>['8B0000', 41.7, _('Dark red')],
		'DarkViolet'=>['9400D3', 67.61, _('Dark violet')],
		'DeepSkyBlue'=>['00BFFF', 140.74, _('Sky blue')],
		'Gold'=>['FFD700', 203.35, _('Gold')],
		'Grey'=>['808080', 128, _('Grey')],
		'Green'=>['008000', 75.52, _('Green')],
		'HotPink'=>['FF69B4', 158.25, _('Hot pink')],
		'Indigo'=>['4B0082', 36.8, _('Indigo')],
		'LightBlue'=>['ADD8E6', 204.64, _('Light blue')],
		'LightGreen'=>['90EE90', 199.46, _('Light green')],
		'LimeGreen'=>['32CD32', 141.45, _('Lime green')],
		'Magenta'=>['FF00FF', 104.55, _('Magenta')],
		'Olive'=>['808000', 113.92, _('Olive')],
		'Orange'=>['FFA500', 173.85, _('Orange')],
		'OrangeRed'=>['FF4500', 117.21, _('Orange red')],
		'Purple'=>['800080', 52.48, _('Purple')],
		'Red'=>['FF0000', 76.5, _('Red')],
		'RoyalBlue'=>['4169E1', 106.2, _('Royal blue')],
		'SeaGreen'=>['2E8B57', 105.38, _('Sea green')],
		'Sienna'=>['A0522D', 101.33, _('Sienna')],
		'Silver'=>['C0C0C0', 192, _('Silver')],
		'Tan'=>['D2B48C', 184.6, _('Tan')],
		'Teal'=>['008080', 89.6, _('Teal')],
		'Violet'=>['EE82EE', 174.28, _('Violet')],
		'White'=>['FFFFFF', 255, _('White')],
		'Yellow'=>['FFFF00', 226.95, _('Yellow')],
		'YellowGreen'=>['9ACD32', 172.65, _('Yellow green')],
	];
	$greybg=greyval(get_setting('colbg'));
	foreach($colours as $name=>$colour){
		if(abs($greybg-$colour[1])>75){
			echo "<option value=\"$colour[0]\" style=\"color:#$colour[0];\">$colour[2]</option>";
		}
	}
}

function greyval(string $colour) : string {
	return hexdec(substr($colour, 0, 2))*.3+hexdec(substr($colour, 2, 2))*.59+hexdec(substr($colour, 4, 2))*.11;
}

function style_this(string $text, string $styleinfo) : string {
	return "<span style=\"$styleinfo\">$text</span>";
}

function check_init() : bool {
	global $db;
	try {
		$db->query( 'SELECT null FROM ' . PREFIX . 'settings LIMIT 1;' );
	} catch (Exception $e){
		return false;
	}
	return true;
}

// run every minute doing various database cleanup task// run every minute doing various database cleanup task

function destroy_chat(array $C): void
{
	global $db, $memcached, $session;
	setcookie(COOKIENAME, false);
	$session = '';
	print_start('destroy');
	$db->exec('DROP TABLE ' . PREFIX . 'captcha;');
	$db->exec('DROP TABLE ' . PREFIX . 'files;');
	$db->exec('DROP TABLE ' . PREFIX . 'filter;');
	$db->exec('DROP TABLE ' . PREFIX . 'ignored;');
	$db->exec('DROP TABLE ' . PREFIX . 'inbox;');
	$db->exec('DROP TABLE ' . PREFIX . 'linkfilter;');
	$db->exec('DROP TABLE ' . PREFIX . 'members;');
	$db->exec('DROP TABLE ' . PREFIX . 'messages;');
	$db->exec('DROP TABLE ' . PREFIX . 'notes;');
	$db->exec('DROP TABLE ' . PREFIX . 'sessions;');
	$db->exec('DROP TABLE ' . PREFIX . 'settings;');
	if(MEMCACHED){
		$memcached->delete(DBNAME . '-' . PREFIX . 'filter');
		$memcached->delete(DBNAME . '-' . PREFIX . 'linkfilter');
		foreach($C['settings'] as $setting){
			$memcached->delete(DBNAME . '-' . PREFIX . "settings-$setting");
		}
		$memcached->delete(DBNAME . '-' . PREFIX . 'settings-dbversion');
		$memcached->delete(DBNAME . '-' . PREFIX . 'settings-msgencrypted');
		$memcached->delete(DBNAME . '-' . PREFIX . 'settings-nextcron');
	}
	echo '<h2>'._('Successfully destroyed chat').'</h2><br><br><br>';
	echo form('setup').submit(_('Initial Setup')).'</form>'.credit();
	print_end();
}



function get_setting(string $setting) : string {
	global $db, $memcached;
	$value = '';
	if($db instanceof PDO && ( !MEMCACHED || ! ($value = $memcached->get(DBNAME . '-' . PREFIX . "settings-$setting") ) ) ){
		try {
			$stmt = $db->prepare( 'SELECT value FROM ' . PREFIX . 'settings WHERE setting=?;' );
			$stmt->execute( [ $setting ] );
			$stmt->bindColumn( 1, $value );
			$stmt->fetch( PDO::FETCH_BOUND );
			if ( MEMCACHED ) {
				$memcached->set( DBNAME . '-' . PREFIX . "settings-$setting", $value );
			}
		} catch (Exception $e){
			return '';
		}
	}
	return $value;
}

function update_setting(string $setting, $value): void
{
	global $db, $memcached;
	$stmt=$db->prepare('UPDATE ' . PREFIX . 'settings SET value=? WHERE setting=?;');
	$stmt->execute([$value, $setting]);
	if(MEMCACHED){
		$memcached->set(DBNAME . '-' . PREFIX . "settings-$setting", $value);
	}
}

// configuration, defaults and internals

function load_fonts() : array {
	return [
		'Poppins'		=>"font-family:Poppins,sans-serif;",
		'Arial'			=>"font-family:Arial,Helvetica,sans-serif;",
		'Book Antiqua'		=>"font-family:'Book Antiqua','MS Gothic',serif;",
		'Comic'			=>"font-family:'Comic Sans MS',Papyrus,sans-serif;",
		'Courier'		=>"font-family:'Courier New',Courier,monospace;",
		'Cursive'		=>"font-family:Cursive,Papyrus,sans-serif;",
		'Fantasy'		=>"font-family:Fantasy,Futura,Papyrus,sans;",
		'Garamond'		=>"font-family:Garamond,Palatino,serif;",
		'Georgia'		=>"font-family:Georgia,'Times New Roman',Times,serif;",
		'Serif'			=>"font-family:'MS Serif','New York',serif;",
		'System'		=>"font-family:System,Chicago,sans-serif;",
		'Times New Roman'	=>"font-family:'Times New Roman',Times,serif;",
		'Verdana'		=>"font-family:Verdana,Geneva,Arial,Helvetica,sans-serif;",
	];
}
function load_lang(): void
{
	global $language, $locale, $dir;
	
	// Default to English
	$language = 'en';
	$locale = 'en_US.UTF-8';
	$dir = 'ltr';
	
	// Check if user has explicitly selected a language
	if(isset($_REQUEST['lang'])){
		$language = $_REQUEST['lang'];
		set_secure_cookie('language', $language);
	}elseif(isset($_COOKIE['language'])){
		$language = $_COOKIE['language'];
	}
	
	// Set locale based on language
	switch($language) {
		case 'ar':
			$locale = 'ar_SA.UTF-8';
			$dir = 'rtl';
			break;
		case 'de':
			$locale = 'de_DE.UTF-8';
			break;
		case 'es':
			$locale = 'es_ES.UTF-8';
			break;
		case 'fr':
			$locale = 'fr_FR.UTF-8';
			break;
		case 'it':
			$locale = 'it_IT.UTF-8';
			break;
		case 'ja':
			$locale = 'ja_JP.UTF-8';
			break;
		case 'nl':
			$locale = 'nl_NL.UTF-8';
			break;
		case 'ru':
			$locale = 'ru_RU.UTF-8';
			break;
		case 'zh':
			$locale = 'zh_CN.UTF-8';
			break;
		default:
			$language = 'en';
			$locale = 'en_US.UTF-8';
			$dir = 'ltr';
	}
	
	if(function_exists('putenv')) {
		putenv('LC_ALL='.$locale);
	}
	setlocale(LC_ALL, $locale);
	bindtextdomain('DanChat', __DIR__.'/locale');
	bind_textdomain_codeset('DanChat', 'UTF-8');
	textdomain('DanChat');
}

function load_config(): void
{
	mb_internal_encoding('UTF-8');
	define('VERSION', '1.24.1'); // Script version
	define('DBVERSION', 48); // Database layout version
	define('MSGENCRYPTED', true); // Store messages encrypted in the database to prevent other database users from reading them - true/false - visit the setup page after editing!
	define('ENCRYPTKEY_PASS', '5PcpFOZ+SfuAIU/32XqK/26ZXKsI198qC7DR1HTdjVY='); // Recommended length: 32. Encryption key for messages
	define('AES_IV_PASS', 'ba94e56f3888507402d5e08484e92cd1'); // Recommended length: 12. AES Encryption IV

	if (!file_exists('confix.php')) {
		die('Error: confix.php file not found');
	}
	require_once ('confix.php');
	
	define('DBHOST', $DBHOST); // Database host
	define('DBUSER', $DBUSER); // Database user 
	define('DBPASS', $DBPASS); // Database password
	define('DBNAME', $DBNAME); // Database name

	define('PERSISTENT', true); // Use persistent database conection true/false
	define('PREFIX', ''); // Prefix - Set this to a unique value for every chat, if you have more than 1 chats on the same database or domain - use only alpha-numeric values (A-Z, a-z, 0-9, or _) other symbols might break the queries
	define('MEMCACHED', false); // Enable/disable memcached caching true/false - needs memcached extension and a memcached server.
		if(defined('MEMCACHED') && MEMCACHED){
			define('MEMCACHEDHOST', 'localhost'); // Memcached host
			define('MEMCACHEDPORT', '11211'); // Memcached port
		}
	define('DBDRIVER', 0); // Selects the database driver to use - 0=MySQL, 1=PostgreSQL, 2=sqlite
	if(DBDRIVER===2){
		define('SQLITEDBFILE', 'public_chat.sqlite'); // Filepath of the sqlite database, if sqlite is used - make sure it is writable for the webserver user
	}
	define('COOKIENAME', PREFIX . 'chat_session'); // Cookie name storing the session information
	define('LANG', 'en'); // Default language
	if (MSGENCRYPTED){
		if (version_compare(PHP_VERSION, '7.2.0') < 0) {
			die("You need at least PHP >= 7.2.x");
		}
		//Do not touch: Compute real keys needed by encryption functions
		if (strlen(ENCRYPTKEY_PASS) !== SODIUM_CRYPTO_AEAD_AES256GCM_KEYBYTES){
			define('ENCRYPTKEY', substr(hash("sha512/256",ENCRYPTKEY_PASS),0, SODIUM_CRYPTO_AEAD_AES256GCM_KEYBYTES));
		}else{
			define('ENCRYPTKEY', ENCRYPTKEY_PASS);
		}
		if (strlen(AES_IV_PASS) !== SODIUM_CRYPTO_AEAD_AES256GCM_NPUBBYTES){
			define('AES_IV', substr(hash("sha512/256",AES_IV_PASS), 0, SODIUM_CRYPTO_AEAD_AES256GCM_NPUBBYTES));
		}else{
			define('AES_IV', AES_IV_PASS);
		}
	}
	// define('RESET_SUPERADMIN_PASSWORD', ''); //Use this to reset your superadmin password in case you forgot it
}