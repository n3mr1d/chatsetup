<?php 
function send_profile(): void
{
	global $U, $db, $language;
	print_start('profile');
	print_css('profile.css');
	echo '<div class="profile-container">';
	echo form('profile', 'save');
	echo '<h2 class="profile-title">'._('Your Profile').'</h2>';

if (!empty($arg)) {
    echo '<div class="profile-message"><i>' . $arg . '</i></div>';
}

// Ignored users section
$ignored = [];
$stmt = $db->prepare('SELECT ign FROM ' . PREFIX . 'ignored WHERE ignby=? ORDER BY LOWER(ign);');
$stmt->execute([$U['nickname']]);
while ($tmp = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $ignored[] = htmlspecialchars($tmp['ign']);
}

echo '<div class="profile-section">';
if (count($ignored) > 0) {
    echo '<div class="profile-card">';
    echo '<h3>'._("Don't ignore anymore").'</h3>';
    echo '<select name="unignore" class="profile-select"><option value="">'._('(choose)').'</option>';
    foreach ($ignored as $ign) {
        echo "<option value=\"$ign\">$ign</option>";
    }
    echo '</select>';
    echo '</div>';
}

// Ignore users section
echo '<div class="profile-card">';
echo '<h3>'._('Ignore User').'</h3>';
echo '<select name="ignore" class="profile-select"><option value="">'._('(choose)').'</option>';
$stmt = $db->prepare('SELECT DISTINCT poster, style FROM ' . PREFIX . 'messages INNER JOIN (SELECT nickname, style FROM ' . PREFIX . 'sessions UNION SELECT nickname, style FROM ' . PREFIX . 'members) AS t ON (' . PREFIX . 'messages.poster=t.nickname) WHERE poster!=? AND poster NOT IN (SELECT ign FROM ' . PREFIX . 'ignored WHERE ignby=?) ORDER BY LOWER(poster);');
$stmt->execute([$U['nickname'], $U['nickname']]);
while ($nick = $stmt->fetch(PDO::FETCH_NUM)) {
    echo '<option value="'.htmlspecialchars($nick[0])."\" style=\"$nick[1]\">".htmlspecialchars($nick[0]).'</option>';
}
echo '</select>';
echo '</div>';
echo '</div>';

// Display settings section
echo '<div class="profile-section">';
echo '<h3 class="section-title">'._('Display Settings').'</h3>';

// Refresh rate
$max_refresh_rate = get_setting('max_refresh_rate');
$min_refresh_rate = get_setting('min_refresh_rate');
echo '<div class="profile-card">';
echo '<label for="refresh">'.sprintf(_('Refresh rate (%1$d-%2$d seconds)'), $min_refresh_rate, $max_refresh_rate).'</label>';
echo '<input type="number" id="refresh" name="refresh" min="'.$min_refresh_rate.'" max="'.$max_refresh_rate.'" value="'.$U['refresh'].'" class="profile-input">';
echo '</div>';

// Font color
preg_match('/#([0-9a-f]{6})/i', $U['style'], $matches);
echo '<div class="profile-card">';
echo '<label for="colour">'._('Font colour')." (<a href=\"$_SERVER[SCRIPT_NAME]?action=colours&amp;session=$U[session]&amp;lang=$language\" target=\"view\">"._('View examples').'</a>)</label>';
echo "<input type=\"color\" id=\"colour\" value=\"#$matches[1]\" name=\"colour\" class=\"color-picker\">";
echo '</div>';

// Background color
echo '<div class="profile-card">';
echo '<label for="bgcolour">'._('Background colour')." (<a href=\"$_SERVER[SCRIPT_NAME]?action=colours&amp;session=$U[session]&amp;lang=$language\" target=\"view\">"._('View examples').'</a>)</label>';
echo "<input type=\"color\" id=\"bgcolour\" value=\"#$U[bgcolour]\" name=\"bgcolour\" class=\"color-picker\">";
echo '</div>';

// Font settings for members
if ($U['status'] >= 3) {
    echo '<div class="profile-card">';
    echo '<h3>'._('Font Settings').'</h3>';
    echo '<div class="font-options">';
    echo '<select name="font" class="profile-select"><option value="">* '._('Room Default').' *</option>';
    $F = load_fonts();
    foreach ($F as $name => $font) {
        echo "<option style=\"$font\" ";
        if (strpos($U['style'], $font) !== false) {
            echo 'selected ';
        }
        echo "value=\"$name\">$name</option>";
    }
    echo '</select>';
    
    echo '<div class="font-styles">';
    echo '<label class="checkbox-container"><input type="checkbox" name="bold" id="bold" value="on"';
    if (strpos($U['style'], 'font-weight:bold;') !== false) {
        echo ' checked';
    }
    echo '><span class="checkmark"></span><b>'._('Bold').'</b></label>';
    
    echo '<label class="checkbox-container"><input type="checkbox" name="italic" id="italic" value="on"';
    if (strpos($U['style'], 'font-style:italic;') !== false) {
        echo ' checked';
    }
    echo '><span class="checkmark"></span><i>'._('Italic').'</i></label>';
    
    echo '<label class="checkbox-container"><input type="checkbox" name="small" id="small" value="on"';
    if (strpos($U['style'], 'font-size:smaller;') !== false) {
        echo ' checked';
    }
    echo '><span class="checkmark"></span><small>'._('Small').'</small></label>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
}

// Font preview
echo '<div class="profile-card">';
echo '<h3>'._('Font Preview').'</h3>';
echo '<div class="font-preview">' . style_this(htmlspecialchars($U['nickname']) . " : " . _('Example for your chosen font'), $U['style']) . '</div>';
echo '</div>';
echo '</div>';

// Chat preferences section
echo '<div class="profile-section">';
echo '<h3 class="section-title">'._('Chat Preferences').'</h3>';

$bool_settings = [
    'timestamps' => _('Show Timestamps'),
    'nocache' => _('Autoscroll (for old browsers or top-to-bottom sort)'),
    'sortupdown' => _('Sort messages from top to bottom'),
    'hidechatters' => _('Hide list of chatters'),
];

if (get_setting('imgembed')) {
    $bool_settings['embed'] = _('Embed images');
}

if ($U['status'] >= 5 && get_setting('incognito')) {
    $bool_settings['incognito'] = _('Incognito mode');
}

echo '<div class="profile-card">';
echo '<div class="toggle-options">';
foreach ($bool_settings as $setting => $title) {
    echo '<label class="switch-container">';
    echo '<span class="switch-label">' . $title . '</span>';
    echo '<label class="switch">';
    echo "<input type=\"checkbox\" name=\"$setting\" value=\"on\"";
    if ($U[$setting]) {
        echo ' checked';
    }
    echo '><span class="slider round"></span>';
    echo '</label>';
    echo '</label>';
}
echo '</div>';
echo '</div>';

// Inbox settings for members
if ($U['status'] >= 2 && get_setting('eninbox')) {
    echo '<div class="profile-card">';
    echo '<label for="eninbox">'._('Enable offline inbox').'</label>';
    echo '<select name="eninbox" id="eninbox" class="profile-select">';
    echo '<option value="0"' . ($U['eninbox'] == 0 ? ' selected' : '') . '>'._('Disabled').'</option>';
    echo '<option value="1"' . ($U['eninbox'] == 1 ? ' selected' : '') . '>'._('For everyone').'</option>';
    echo '<option value="3"' . ($U['eninbox'] == 3 ? ' selected' : '') . '>'._('For members only').'</option>';
    echo '<option value="5"' . ($U['eninbox'] == 5 ? ' selected' : '') . '>'._('For staff only').'</option>';
    echo '</select>';
    echo '</div>';
}

// Timezone
echo '<div class="profile-card">';
echo '<label for="tz">'._('Time zone').'</label>';
echo '<select name="tz" id="tz" class="profile-select">';
$tzs = timezone_identifiers_list();
foreach ($tzs as $tz) {
    echo "<option value=\"$tz\"";
    if ($U['tz'] == $tz) {
        echo ' selected';
    }
    echo ">$tz</option>";
}
echo '</select>';
echo '</div>';
echo '</div>';

// Account settings for members
if ($U['status'] >= 2) {
    echo '<div class="profile-section">';
    echo '<h3 class="section-title">'._('Account Settings').'</h3>';
    
    // Change password
    echo '<div class="profile-card">';
    echo '<h3>'._('Change Password').'</h3>';
    echo '<div class="form-group">';
    echo '<label for="oldpass">'._('Old password:').'</label>';
    echo '<input type="password" id="oldpass" name="oldpass" class="profile-input" autocomplete="current-password">';
    echo '</div>';
    echo '<div class="form-group">';
    echo '<label for="newpass">'._('New password:').'</label>';
    echo '<input type="password" id="newpass" name="newpass" class="profile-input" autocomplete="new-password">';
    echo '</div>';
    echo '<div class="form-group">';
    echo '<label for="confirmpass">'._('Confirm new password:').'</label>';
    echo '<input type="password" id="confirmpass" name="confirmpass" class="profile-input" autocomplete="new-password">';
    echo '</div>';
    echo '</div>';
    
    // Change nickname
    echo '<div class="profile-card">';
    echo '<h3>'._('Change Nickname').'</h3>';
    echo '<div class="form-group">';
    echo '<label for="newnickname">'._('New nickname:').'</label>';
    echo '<input type="text" id="newnickname" name="newnickname" class="profile-input" autocomplete="username">';
    echo '</div>';
    echo '</div>';
    echo '</div>';
}

// Submit buttons
echo '<div class="profile-actions">';
echo submit(_('Save changes'), 'class="save-button"');
echo '</form>';

if ($U['status'] > 1 && $U['status'] < 8) {
    echo form('profile', 'delete') . submit(_('Delete account'), 'class="delete-button"') . '</form>';
}

echo form_target('_parent', 'login') . submit(_('Back to the chat'), 'class="back-button"') . '</form>';
echo '</div>';
echo '</div>';

	print_end();
}
function send_delete_account(): void
{
	global $U, $db;
	print_start('delete_account');
	print_css('profile.css');
	
	echo '<div class="delete-account-container">';
	echo '<h2 class="delete-title">'._('Delete Your Account').'</h2>';
	
	if (isset($_POST['confirm']) && $_POST['confirm'] === 'yes' && isset($_POST['password'])) {
		// Verify password before deletion
		$stmt = $db->prepare('SELECT passhash FROM ' . PREFIX . 'members WHERE nickname = ?');
		$stmt->execute([$U['nickname']]);
		$result = $stmt->fetch(PDO::FETCH_ASSOC);
		
		if ($result && password_verify($_POST['password'], $result['passhash'])) {
			// Password verified, proceed with deletion
			$stmt = $db->prepare('DELETE FROM ' . PREFIX . 'members WHERE nickname = ?');
			$stmt->execute([$U['nickname']]);
			
			// Clean up related data
			$stmt = $db->prepare('DELETE FROM ' . PREFIX . 'sessions WHERE nickname = ?');
			$stmt->execute([$U['nickname']]);
			
			$stmt = $db->prepare('DELETE FROM ' . PREFIX . 'ignored WHERE ignby = ?');
			$stmt->execute([$U['nickname']]);
			
			// Redirect to logout
			echo '<div class="success-message">';
			echo '<p>'._('Your account has been successfully deleted.').'</p>';
			echo '<p>'._('You will be redirected to the login page in 5 seconds.').'</p>';
			echo '</div>';
			echo '<meta http-equiv="refresh" content="5;url=?action=logout">';
			echo form_target('_parent', 'logout').submit(_('Return to Login'), 'class="return-button"').'</form>';
		} else {
			// Password verification failed
			echo '<div class="error-message">';
			echo '<p>'._('Password verification failed. Your account was not deleted.').'</p>';
			echo '</div>';
			show_delete_confirmation();
		}
	} else {
		show_delete_confirmation();
	}
	
	echo '</div>';
	print_end();
}

function show_delete_confirmation(): void
{
	global $U;
	
	echo '<div class="warning-box">';
	echo '<h3><i class="warning-icon">⚠️</i>'._('Warning: This action cannot be undone').'</h3>';
	echo '<p>'._('Deleting your account will permanently remove:').'</p>';
	echo '<ul>';
	echo '<li>'._('Your profile and all account settings').'</li>';
	echo '<li>'._('Your access to any private messages').'</li>';
	echo '<li>'._('Your nickname will become available for others').'</li>';
	echo '</ul>';
	echo '</div>';
	
	echo '<div class="confirmation-form">';
	echo '<h3>'._('Please confirm your decision').'</h3>';
	echo form('profile', 'delete');
	echo '<div class="form-group">';
	echo '<label for="password">'._('Enter your password to confirm:').'</label>';
	echo '<input type="password" id="password" name="password" class="profile-input" required ">';
	echo '</div>';
	echo '<div class="checkbox-group">';
	echo '<input type="checkbox" id="understand" name="understand" required>';
	echo '<label for="understand">'._('I understand this action cannot be reversed').'</label>';
	echo '</div>';
	echo '<div class="button-group">';
    echo hidden('confirm', 'yes');
	echo '<button type="submit" class="delbutton">'._('Yes, delete my account').'</button></form>';
	echo form_target('view', 'profile').'<button type="submit" class="backbutton">'._('Cancel').'</button></form>';
	echo '</div>';
	echo '</div>';
}

?>