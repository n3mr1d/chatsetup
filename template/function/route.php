<?php
		function route(): void
		{
			global $U, $db;
			if($_SERVER['REQUEST_URI'] === '/'){
				send_welcome();
				return;
		
			}
			
			if(!isset($_REQUEST['action'])){
				send_login();
			}elseif($_REQUEST['action']==='view'){
				check_session();
				send_messages();
			}elseif($_REQUEST['action']==='redirect' && !empty($_GET['url'])){
				send_redirect($_GET['url']);
			}elseif($_REQUEST['action']==='wait'){
				parse_sessions();
				send_waiting_room();
			}elseif($_REQUEST['action']==='post'){
				check_session();
				if(isset($_POST['message']) && isset($_POST['sendto'])){
					send_post(validate_input());
					send_messages();
				}
				send_post();
				send_messages();
			}elseif($_REQUEST['action']==='login'){
				check_login();
				show_fails();
				send_frameset();
			}elseif($_REQUEST['action']==='controls'){
				check_session();
				send_controls();
			}elseif($_REQUEST['action']==='delete'){
				check_session();
				if(!isset($_POST['what'])){
				}elseif($_POST['what']==='all'){
					if(isset($_POST['confirm'])){
						del_all_messages('', (int) ($U['status']==1 ? $U['entry'] : 0));
					}else{
						send_del_confirm();
					}
				}elseif ($_POST['what'] === 'hilite' && isset($_POST['message_id'])) {
					check_session();
					delete_message((int)$_POST['message_id']);
				}
				send_post();
			}elseif($_REQUEST['action']==='profile'){
				check_session();
				$arg='';
				if(!isset($_POST['do'])){
				}elseif($_POST['do']==='save'){
					$arg=save_profile();
				}elseif($_POST['do']==='delete'){
					if(isset($_POST['confirm'])){
						delete_account();
					}else{
						send_delete_account();
					}
				}
				send_profile($arg);
			}elseif($_REQUEST['action']==='logout' && $_SERVER['REQUEST_METHOD'] === 'POST'){
				check_session();
				if($U['status']<3 && get_setting('exitwait')){
					$U['exiting']=1;
					$stmt=$db->prepare('UPDATE ' . PREFIX . 'sessions SET exiting=1 WHERE session=? LIMIT 1;');
					$stmt->execute([$U['session']]);
				} else {
					kill_session();
				}
				send_logout();
			}elseif($_REQUEST['action']==='colours'){
				check_session();
				send_colours();
			}elseif($_REQUEST['action']==='notes'){
				check_session();
				if(!isset($_POST['do'])){
				}elseif($_POST['do']==='admin' && $U['status']>6){
					send_notes(0);
				}elseif($_POST['do']==='staff' && $U['status']>=5){
					send_notes(1);
				}elseif($_POST['do']==='public' && $U['status']>=3){
					send_notes(3);
				}elseif($_POST['do']==='announcement' && $U['status']>=5){
					send_notes(4);
				}
				if($U['status']<3 || (!get_setting('personalnotes') && !get_setting('publicnotes'))){
					send_access_denied();
				}
				send_notes(2);
			}elseif($_REQUEST['action']==='viewpublicnotes'){
				check_session();
				view_publicnotes();
			}elseif($_REQUEST['action']==='show_commands'){
				check_session();
				show_commands();
			}elseif($_REQUEST['action']==='inbox'){
				check_session();
				if(isset($_POST['do'])){
					clean_inbox_selected();
				}
				send_inbox();
			}elseif($_REQUEST['action']==='help'){
				check_session();
				send_help();
			}elseif($_REQUEST['action']==='download'){
				send_download();
			}elseif($_REQUEST['action']==='admin'){
				check_session();
				send_admin(route_admin());
			}elseif($_REQUEST['action']==='setup'){
				route_setup();
			}elseif($_REQUEST['action']==='sa_password_reset'){
				send_sa_password_reset();
			}elseif ($_REQUEST['action'] === 'send_toggle_afk') {
				check_session();
				send_toggle_afk();
				send_messages();
			}elseif($_REQUEST['action'] === 'hidden_line') {
				send_hidden_line();
			}elseif($_REQUEST['action'] === 'play_mention_sound') {
				// Route untuk memutar suara notifikasi mention
				stream_mention_sound();
			}elseif(isset($_REQUEST['do']) && $_REQUEST['do'] == 'logout') {
				kill_session();
				send_login();
			}else{
				send_login();
			}
		}
function route_admin() : string {
	global $U, $db;
	if($U['status']<5){
		send_access_denied();
	}
	if(!isset($_POST['do'])){
		return '';
	}elseif($_POST['do']==='clean'){
		if($_POST['what']==='choose'){
			send_choose_messages();
		}elseif($_POST['what']==='selected'){
			clean_selected((int) $U['status'], $U['nickname']);
		}elseif($_POST['what']==='room'){
			clean_room();
		}elseif($_POST['what']==='nick'){
			$stmt=$db->prepare('SELECT null FROM ' . PREFIX . 'members WHERE nickname=? AND status>=?;');
			$stmt->execute([$_POST['nickname'], $U['status']]);
			if(!$stmt->fetch(PDO::FETCH_ASSOC)){
				del_all_messages($_POST['nickname'], 0);
			}
		}
	}elseif($_POST['do']==='kick'){
		if(isset($_POST['name'])){
			if(isset($_POST['what']) && $_POST['what']==='purge'){
				kick_chatter($_POST['name'], $_POST['kickmessage'], true);
			}else{
				kick_chatter($_POST['name'], $_POST['kickmessage'], false);
			}
		}
	}elseif($_POST['do']==='logout'){
		if(isset($_POST['name'])){
			logout_chatter($_POST['name']);
		}
	}elseif($_POST['do']==='sessions'){
		if(isset($_POST['kick']) && isset($_POST['nick'])){
			kick_chatter([$_POST['nick']], '', false);
		}elseif(isset($_POST['logout']) && isset($_POST['nick'])){
			logout_chatter([$_POST['nick']]);
		}
		send_sessions();
	}elseif($_POST['do']==='register'){
		return register_guest(3, $_POST['name']);
	}elseif($_POST['do']==='superguest'){
		return register_guest(2, $_POST['name']);
	}elseif($_POST['do']==='status'){
		return change_status($_POST['name'], $_POST['set']);
	}elseif($_POST['do']==='regnew'){
		return register_new($_POST['name'], $_POST['pass']);
	}elseif($_POST['do']==='approve'){
		approve_session();
		send_approve_waiting();
	}elseif($_POST['do']==='guestaccess'){
		if(isset($_POST['guestaccess']) && preg_match('/^[0123]$/', $_POST['guestaccess'])){
			update_setting('guestaccess', $_POST['guestaccess']);
			change_guest_access(intval($_POST['guestaccess']));
		}
	}elseif($_POST['do']==='filter'){
		send_filter(manage_filter());
	}elseif($_POST['do']==='linkfilter'){
		send_linkfilter(manage_linkfilter());
	}elseif($_POST['do']==='topic'){
		if(isset($_POST['topic'])){
			update_setting('topic', htmlspecialchars($_POST['topic']));
		}
	}elseif($_POST['do']==='passreset'){
		return passreset($_POST['name'], $_POST['pass']);
	}
	return '';
}

function route_setup(): void
{
	global $U;
	if(!valid_admin()){
		send_alogin();
	}
	$C['bool_settings']=[
		'suguests' => _('Enable applicants'),
		'imgembed' => _('Embed images'),
		'timestamps' => _('Show Timestamps'),
		'trackip' => _('Show session-IP'),
		'memkick' => _('Members can kick, if no moderator is present'),
		'memkickalways' => _('Members can always kick'),
		'forceredirect' => _('Force redirection'),
		'incognito' => _('Incognito mode'),
		'sendmail' => _('Send mail on new public message'),
		'modfallback' => _('Fallback to waiting room, if no moderator is present to approve guests'),
		'disablepm' => _('Disable private messages'),
		'eninbox' => _('Enable offline inbox'),
		'enablegreeting' => _('Show a greeting message before showing the messages'),
		'sortupdown' => _('Sort messages from top to bottom'),
		'hidechatters' => _('Hide list of chatters'),
		'personalnotes' => _('Personal notes'),
		'publicnotes' => _('Public notes'),
		'filtermodkick' => _('Apply kick filter on moderators'),
		'namedoers' => _('Show who kicks people or purges all messages.'),
		'hide_reload_post_box' => _('Hide reload post box button'),
		'hide_reload_messages' => _('Hide reload messages button'),
		'hide_profile' => _('Hide profile button'),
		'hide_admin' => _('Hide admin button'),
		'hide_notes' => _('Hide notes button'),
		'hide_clone' => _('Hide clone button'),
		'hide_rearrange' => _('Hide rearrange button'),
		'postbox_delete_globally' => _('Apply postbox delete button globally'),
	];
	$C['colour_settings']=[
		'colbg' => _('Background colour'),
		'coltxt' => _('Font colour'),
	];
	$C['msg_settings']=[
		'msgenter' => _('Entrance'),
		'msgexit' => _('Leaving'),
		'msgmemreg' => _('Member registered'),
		'msgsureg' => _('Applicant registered'),
		'msgkick' => _('Kicked'),
		'msgmultikick' => _('Multiple kicked'),
		'msgallkick' => _('All kicked'),
		'msgclean' => _('Room cleaned'),
		'msgsendall' => _('Message to all'),
		'msgsendmem' => _('Message to members only'),
		'msgsendmod' => _('Message to staff only'),
		'msgsendadm' => _('Message to admins only'),
		'msgsendprv' => _('Private message'),
		'msgattache' => _('Attachement'),
	];
	$C['number_settings']=[
		'memberexpire' => _('Member timeout (minutes)'),
		'guestexpire' => _('Guest timeout (minutes)'),
		'kickpenalty' => _('Kick penalty (minutes)'),
		'entrywait' => _('Waiting room time (seconds)'),
		'exitwait' => _('Logout delay (seconds)'),
		'captchatime' => _('Captcha timeout (seconds)'),
		'messageexpire' => _('Message timeout (minutes)'),
		'messagelimit' => _('Message limit (public)'),
		'maxmessage' => _('Maximal message length'),
		'maxname' => _('Maximal nickname length'),
		'minpass' => _('Minimal password length'),
		'defaultrefresh' => _('Default message reload time (seconds)'),
		'numnotes' => _('Number of notes revisions to keep'),
		'maxuploadsize' => _('Maximum upload size in KB'),
		'enfileupload' => _('Enable file uploads'),
		'max_refresh_rate' => _('Lowest refresh rate'),
		'min_refresh_rate' => _('Highest refresh rate'),
	];
	$C['textarea_settings']=[
		'rulestxt' => _('Rules (html)'),
		'css' => _('CSS Style'),
		'disabletext' => _('Chat disabled message (html)'),
	];
	$C['text_settings']=[
		'dateformat' => _('<a target="_blank" href="https://php.net/manual/en/function.date.php#refsect1-function.date-parameters" rel="noopener noreferrer">Date formating</a>'),
		'captchachars' => _('Characters used in Captcha'),
		'redirect' => _('Custom redirection script'),
		'chatname' => _('Chat name'),
		'mailsender' => _('Send mail using this address'),
		'mailreceiver' => _('Send mail to this address'),
		'nickregex' => _('Nickname regex'),
		'passregex' => _('Password regex'),
		'externalcss' => _('Link to external CSS file (on your own server)'),
		'metadescription' => _('Meta description (best 50 - 160 characters for SEO)'),
		'exitingtxt' => _('Show this text when a user\'s logout is delayed'),
		'sysmessagetxt' => _('Prepend this text to system messages'),
		'hiddenlink' => _('Hidden Line'),
		'forums' => _('Forum Link'),

	];
	$extra_settings=[
		'guestaccess' => _('Change Guestaccess'),
		'englobalpass' => _('Enable global Password'),
		'globalpass' => _('Global Password:'),
		'captcha' => _('Captcha'),
		'dismemcaptcha' => _('Only for guests'),
		'topic' => _('Topic'),
		'guestreg' => _('Let guests register themselves'),
		'defaulttz' => _('Default time zone'),
	];
	$C['settings']=array_keys(array_merge($extra_settings, $C['bool_settings'], $C['colour_settings'], $C['msg_settings'], $C['number_settings'], $C['textarea_settings'], $C['text_settings'])); // All settings in the database
	if(!isset($_POST['do'])){
	}elseif($_POST['do']==='save'){
		save_setup($C);
	}elseif($_POST['do']==='backup' && $U['status']==8){
		send_backup($C);
	}elseif($_POST['do']==='restore' && $U['status']==8){
		restore_backup($C);
		send_backup($C);
	}elseif($_POST['do']==='destroy' && $U['status']==8){
		if(isset($_POST['confirm'])){
			destroy_chat($C);
		}else{
			send_destroy_chat();
		}
	}
	send_setup($C);
}
