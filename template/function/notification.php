<?php

function print_notifications(): void
{
	global $U, $db;
	
	// Initialize counters for notification summary
	$totalNotifications = 0;
	$loginFailures = 0;
	$inboxMessages = 0;
	$pendingGuests = 0;
	
	// Check for failed login attempts
	$stmt = $db->prepare('SELECT loginfails FROM ' . PREFIX . 'members WHERE nickname=?;');
	$stmt->execute([$U['nickname']]);
	$temp = $stmt->fetch(PDO::FETCH_NUM);
	if($temp && $temp[0] > 0) {
		$loginFailures = $temp[0];
		$totalNotifications += $loginFailures;
	}
	
	// Check for inbox messages
	if($U['status'] >= 2 && $U['eninbox'] != 0) {
		$stmt = $db->prepare('SELECT COUNT(*) FROM ' . PREFIX . 'inbox WHERE recipient=?;');
		$stmt->execute([$U['nickname']]);
		$tmp = $stmt->fetch(PDO::FETCH_NUM);
		if($tmp[0] > 0) {
			$inboxMessages = $tmp[0];
			$totalNotifications += $inboxMessages;
		}
	}
	
	// Check for guests waiting for approval
	if($U['status'] >= 5 && get_setting('guestaccess') == 3) {
		$result = $db->query('SELECT COUNT(*) FROM ' . PREFIX . 'sessions WHERE entry=0 AND status=1;');
		$temp = $result->fetch(PDO::FETCH_NUM);
		if($temp[0] > 0) {
			$pendingGuests = $temp[0];
			$totalNotifications += $pendingGuests;
		}
	}
	
	// Check for new mentions (if the function exists)
	if (function_exists('check_new_mentions')) {
		try {
			$mentions = check_new_mentions($U['nickname']);
			$mentionCount = count($mentions);
			$totalNotifications += $mentionCount;
		} catch (Exception $e) {
			error_log("Error checking mentions: " . $e->getMessage());
		}
	}
	
	// Display notification container with total count
	echo '<div id="notifications" class="notification-container">';
	
	// Display login failures notification with icon
	if($loginFailures > 0) {
		echo '<div class="notification-item notification-warning" title="' . sprintf(_('%d failed login attempts'), $loginFailures) . '">';
		echo '<i class="notification-icon">⚠️<span>failed logini</span> </i>';
		echo '<span class="notification-badge">' . $loginFailures . '</span>';
		echo '</div>';
	}
	
	// Display inbox messages notification with icon
	if($inboxMessages > 0) {
		echo '<div class="notification-item notification-inbox">';
		echo form('inbox');
		echo '<button type="submit" class="notification-button" title="' . sprintf(_('You have %d unread messages'), $inboxMessages) . '">';
		echo '<i class="notification-icon">📩<span>inbox</span></i>';
		echo '<span class="notification-badge">' . $inboxMessages . '</span>';
		echo '</button>';
		echo '</form>';
		echo '</div>';
	}
	
	// Display pending guests notification with icon
	if($pendingGuests > 0) {
		echo '<div class="notification-item notification-approval">';
		echo form('admin', 'approve');
		echo '<button type="submit" class="notification-button" title="' . sprintf(_('%d guests waiting for approval'), $pendingGuests) . '">';
		echo '<i class="notification-icon">👥<span>approval guest</span></i>';
		echo '<span class="notification-badge">' . $pendingGuests . '</span>';
		echo '</button>';
		echo '</form>';
		echo '</div>';
	}
	
	
	
	echo '</div>';
}
function send_inbox(): void
{
	global $U, $db;
	print_start('inbox');
	print_css('inbox.css');
	echo'<h2 class="title-inbox"> Inbox Messages </h2>';
	$dateformat=get_setting('dateformat');
	if(!$U['embed'] && get_setting('imgembed')){
		$removeEmbed=true;
	}else{
		$removeEmbed=false;
	}
	if($U['timestamps'] && !empty($dateformat)){
		$timestamps=true;
	}else{
		$timestamps=false;
	}
	if($U['sortupdown']){
		$direction='ASC';
	}else{
		$direction='DESC';
	}
	$stmt=$db->prepare('SELECT id, postdate, text FROM ' . PREFIX . "inbox WHERE recipient=? ORDER BY id $direction;");
	$stmt->execute([$U['nickname']]);
	
	echo form('inbox', 'clean');
	$hasMessages = false;
	while($message=$stmt->fetch(PDO::FETCH_ASSOC)){
		$hasMessages = true;
		prepare_message_print($message, $removeEmbed);
		echo "<div class=\"msg\"><label><input type=\"checkbox\" name=\"mid[]\" value=\"$message[id]\">";
		if($timestamps){
			echo ' <small>'.date($dateformat, $message['postdate']).' - </small>';
		}
		echo " $message[text]</label></div>";
	}
	
	if (!$hasMessages) {
		echo '<div class="no-messages">'._('No messages in your inbox').'</div>';
	}
	
	echo '<div class="button-group">';
	echo submit(_('Delete selected messages'), 'class="delbutton"');
	echo '</form>';
	
	echo form('view');
	echo submit(_('Back to the chat.'), 'class="backbutton"');
	echo '</form>';
	echo '</div>';
	
	print_end();
}