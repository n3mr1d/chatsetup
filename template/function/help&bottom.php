<?php
function send_help(): void
{
	global $U, $db;
	print_start('help');
	print_css('helping.css');


	
	// Main help section
	echo '<div class="help-container">';

	// Basic usage section
	echo '<div class="help-section">';
	echo '<h3>'._('Welcome to DanChat').'</h3>';
	echo '<div class="help-content">';
	echo _("Welcome to DanChat — a borderless communication hub forged for the underground minds. Built for hackers, coders, cyberpunks, and digital explorers, DanChat aims to become the largest and most active chat room in the Dark Web. Our mission is simple: Freedom of knowledge, respect among outcasts, and unfiltered curiosity.");
	echo '<div class="help-note"><strong>'._('System Protocol:').'</strong> '._('No JavaScript (noscript), Respect others, No spamming or self-promotion, Keep it clean and safe for everyone, Protect your privacy, Report violations to staff').'</div>';

	echo '<h3>'._('Basic Usage').'</h3>';
	echo _("All functions should be pretty much self-explaining, just use the buttons. In your profile you can adjust the refresh rate and font colour, as well as ignore users.");
	echo '<div class="help-note"><strong>'._('Note:').'</strong> '._('This is a chat, so if you don\'t keep talking, you will be automatically logged out after a period of inactivity.').'</div>';
	
	// Add forum and chat links
	$forums = get_setting('forums');
	if(!empty($forums)) {
		echo '<div class="help-tip"><i class="fas fa-external-link-alt"></i> <strong>'._('Forum Link:').'</strong> <a href="'.$forums.'" target="_blank">'.$forums.'</a></div>';
	}

	$hiddenlink = get_setting('hiddenlink'); 
	if(!empty($hiddenlink)) {
		echo '<div class="help-tip"><i class="fas fa-link"></i> <strong>'._('Hidden Link:').'</strong> <a href="'.$hiddenlink.'" target="_blank">'.$hiddenlink.'</a></div>';
	}
	echo '</div></div>';
	// Chat features section
	echo '<div class="help-section">';
	echo '<h3>'._('Chat Features').'</h3>';
	echo '<div class="help-content">';
	
	// AFK status
	echo '<div class="feature-item"><i class="fas fa-clock"></i> <strong>'._('AFK Status:').'</strong> '._('Use the AFK button to mark yourself as away from keyboard.').'</div>';
	
	// Fonts available
	echo '<div class="feature-item"><i class="fas fa-font"></i> <strong>'._('Available Fonts:').'</strong></div>';
	echo '<div class="fonts-list">';
	$fonts = load_fonts();
	$count = 0;
	foreach($fonts as $font => $style) {
		echo '<span style="'.$style.'">'.$font.'</span>';
		$count++;
		if($count % 3 == 0) echo '<br>';
	}
	echo '</div>';
	
	echo '</div></div>';
	
	// User-specific help based on status
	if($U['status'] >= 3){
		echo '<div class="help-section">';
		echo '<h3>'._('Member Features').'</h3>';
		echo '<div class="help-content">';
		echo '<div class="feature-item"><i class="fas fa-user"></i> '._("As a member, you have additional options in your profile. You can adjust your font face, change your password anytime, and delete your account if needed.").'</div>';
		
		// Get inbox message count
		if($U['eninbox'] != 0) {
			$stmt = $db->prepare('SELECT COUNT(*) FROM ' . PREFIX . 'inbox WHERE recipient=?;');
			$stmt->execute([$U['nickname']]);
			$inbox_count = $stmt->fetch(PDO::FETCH_NUM)[0];
			echo '<div class="feature-item"><i class="fas fa-envelope"></i> <strong>'._('Inbox:').'</strong> '._('You have').' '.$inbox_count.' '._('messages in your inbox.').'</div>';
		}
		
		echo '</div></div>';
		
		if($U['status'] >= 5){
			echo '<div class="help-section">';
			echo '<h3>'._('Moderator Tools').'</h3>';
			echo '<div class="help-content">';
			echo '<div class="feature-item"><i class="fas fa-shield-alt"></i> '._("Notice the Admin-button at the bottom. It'll bring up a page where you can:").'</div>';
			echo '<ul class="feature-list">';
			echo '<li>'._("Clean the chat room").'</li>';
			echo '<li>'._("Kick inactive chatters").'</li>';
			echo '<li>'._("View all active sessions").'</li>';
			echo '<li>'._("Disable guest access if needed").'</li>';
			echo '</ul>';
			
			// Get number of active users
			$stmt = $db->query('SELECT COUNT(*) FROM ' . PREFIX . 'sessions WHERE entry!=0 AND status>0');
			if($stmt) {
				$active_users = $stmt->fetchColumn();
				echo '<div class="feature-item"><i class="fas fa-users"></i> <strong>'._('Active Users:').'</strong> '.$active_users.'</div>';
			}
			
			echo '</div></div>';
			
			if($U['status'] >= 7){
				echo '<div class="help-section">';
				echo '<h3>'._('Administrator Controls').'</h3>';
				echo '<div class="help-content">';
				echo '<div class="feature-item"><i class="fas fa-user-shield"></i> '._("As an administrator, you have full control over the chat system. You can:").'</div>';
				echo '<ul class="feature-list">';
				echo '<li>'._("Register guests as permanent members").'</li>';
				echo '<li>'._("Edit member profiles and permissions").'</li>';
				echo '<li>'._("Register new nicknames").'</li>';
				echo '<li>'._("Modify system settings").'</li>';
				echo '<li>'._("View system logs and statistics").'</li>';
				echo '</ul>';
				
				// Get total member count
				$stmt = $db->query('SELECT COUNT(*) FROM ' . PREFIX . 'members');
				if($stmt) {
					$total_members = $stmt->fetchColumn();
					echo '<div class="feature-item"><i class="fas fa-database"></i> <strong>'._('Total Members:').'</strong> '.number_format($total_members).'</div>';
				}
				
				echo '</div></div>';
			}
		}
	}
	
	echo '</div>'; // Close help-container
	
	// Back button and credits
	echo '<div class="help-footer">';
	echo '<hr>';
	echo '<div id="backcredit">'.form('view').submit(_('Back to the chat'), 'class="backbutton"').'</form></div>';
	echo '</div>';
	
	print_end();
}