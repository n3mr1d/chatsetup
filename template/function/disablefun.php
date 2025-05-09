<?php
function send_access_denied(): void
{
	global $U, $language, $dir;
	http_response_code(403);
	print_start('access_denied');
	
	echo '<div style="font-family: Poppins, Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 30px; background-color: #f8f8f8; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.15); text-align: center; border-left: 5px solid #e74c3c;">';
	echo '<div style="font-size: 64px; color: #e74c3c; margin-bottom: 20px;">🔒</div>';
	echo '<h1 style="color: #e74c3c; font-size: 28px; margin-bottom: 20px;">'._('Access denied').'</h1>';
	echo '<div style="background-color: #fff; padding: 15px; border-radius: 8px; margin-bottom: 25px; border: 1px solid #ddd;">';
	echo '<p style="color: #555; font-size: 16px; line-height: 1.6; margin-bottom: 15px;">'.sprintf(_("You are logged in as %s and don't have access to this section."), style_this(htmlspecialchars($U['nickname']), $U['style'])).'</p>';
	echo '<p style="color: #777; font-size: 14px; margin-bottom: 10px;">'._('If you believe this is an error, please contact the administrator.').'</p>';
	echo '</div>';
	
	// Show user info
	echo '<div style="background-color: #f0f0f0; padding: 12px; border-radius: 6px; margin-bottom: 20px; text-align: left; font-size: 14px;">';
	echo '<p style="margin: 5px 0; color: #666;"><strong>'._('User Status:').'</strong> ' . $U['status'] . '</p>';
	echo '<p style="margin: 5px 0; color: #666;"><strong>'._('IP Address:').'</strong> ' . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown') . '</p>';
	echo '<p style="margin: 5px 0; color: #666;"><strong>'._('Time:').'</strong> ' . date('Y-m-d H:i:s') . '</p>';
	echo '</div>';
	
	echo form('logout');
	echo '<button type="submit" id="exitbutton" style="background-color: #e74c3c; color: white; border: none; padding: 12px 25px; border-radius: 5px; font-size: 16px; cursor: pointer; transition: background-color 0.3s;">'._('Logout').'</button>';
	echo '</form>';
	
	echo '<div style="margin-top: 25px; font-size: 13px; color: #999;">';
	echo '<p>'._('For security reasons, this access attempt has been logged.').'</p>';
	echo '</div>';
	echo '</div>';
	
	print_end();
}
function send_chat_disabled(): void
{
	global $language, $dir;
	print_start('disabled');
	print_css('error.css');
	
	echo '<div class="error-container">';
	echo '<div class="error-icon">🚫</div>';
	echo '<h2 class="error-title">'._('Chat Room Disabled').'</h2>';
	echo '<div class="error-message">';
	echo '<p>'.get_setting('disabletext').'</p>';
	echo '<p>'._('The chat room is currently unavailable. Please try again later.').'</p>';
	echo '</div>';
	echo '<div class="error-actions">';
	echo '<span>'._('Report this issue to admin idrift@dnmx.su').'</span>';
    	echo '</div>';
	
	// Add timestamp for reference
	echo '<div class="error-timestamp">';
	echo '<small>'._('Current time: ').date('Y-m-d H:i:s').'</small>';
	echo '</div>';
	echo '</div>';
	
	print_end();
}

function send_approve_waiting(): void
{
	global $db;
	print_start('approve_waiting');
	print_css('disable.css');
	echo '<div class="waiting-room-container">';
	echo '<h2>'._('Waiting Room Management').'</h2>';
	

	$result=$db->query('SELECT * FROM ' . PREFIX . 'sessions WHERE entry=0 AND status=1 ORDER BY id LIMIT 100;');
	if($tmp=$result->fetchAll(PDO::FETCH_ASSOC)){
		echo form('admin', 'approve');
		
		echo '<div class="waiting-users-container">';
		echo '<div class="waiting-header">';
		echo '<div>'._('Select').'</div>';
		echo '<div>'._('Nickname').'</div>';
		echo '<div>'._('User-Agent').'</div>';
		echo '</div>';
		
		echo '<div class="waiting-users-list">';
		$counter = 0;
		foreach($tmp as $temp){
			$bgColor = ($counter % 2 == 0) ? '#222' : '#2a2a2a';
			echo '<div class="waiting-user-row">'.hidden('alls[]', htmlspecialchars($temp['nickname']));
			echo '<div><input type="checkbox" name="csid[]" value="'.htmlspecialchars($temp['nickname']).'"></div>';
			echo '<div>'.style_this(htmlspecialchars($temp['nickname']), $temp['style']).'</div>';
			echo '<div>'.$temp['useragent'].'</div>';
			echo '</div>';
			$counter++;
		}
		echo '</div>';
		echo '</div>';
		
		echo '<div class="action-controls">';
		echo '<h3>'._('Action Controls').'</h3>';
		
		echo '<div class="radio-options">';
		
		echo '<label>';
		echo '<input type="radio" name="what" value="allowchecked" id="allowchecked" checked>';
		echo '<span>'._('Allow checked').'</span></label>';
		
		echo '<label>';
		echo '<input type="radio" name="what" value="allowall" id="allowall">';
		echo '<span>'._('Allow all').'</span></label>';
		
		echo '<label>';
		echo '<input type="radio" name="what" value="denychecked" id="denychecked">';
		echo '<span>'._('Deny checked').'</span></label>';
		
		echo '<label>';
		echo '<input type="radio" name="what" value="denyall" id="denyall">';
		echo '<span>'._('Deny all').'</span></label>';
		echo '</div>';
		
		echo '<div class="deny-message">';
		echo '<label>'._('Send message to denied:').'</label>';
		echo '<input type="text" name="kickmessage" placeholder="'._('Optional message for denied users').'">';
		echo '</div>';
		
		echo '<div class="button-group">';
		echo submit(_('Apply Actions'), 'class="save-button"');
		echo '</div>';
		echo '</div>';
		echo '</form>';
	} else {
		echo '<div class="no-requests">';
		echo '<div>✓</div>';
		echo '<p>'._('No more entry requests to approve.').'</p>';
		echo '</div>';
	}
	
	echo '<div class="button-group">';
	echo form('view').submit(_('Back to the chat'), 'class="backbutton"').'</form>';
	echo '</div>';
	
	
	echo '</div>';
	
	print_end();
}
