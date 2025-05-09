<?php
function send_setup(array $C): void
{
    global $U;
    print_start('setup');
    print_css('setupadmin.css');
    echo '<div class="setup-container">';
    echo '<h2 class="setup-title">'._('Chat Setup').'</h2>'.form('setup', 'save');
    echo '<div class="setup-section">';
    
    // Guest Access Section
    echo '<div class="setup-card">';
    echo '<h3>'._('Guest Access Settings').'</h3>';
    $ga = (int) get_setting('guestaccess');
    echo '<div class="form-group">';
    echo '<label>'._('Change Guest Access').'</label>';
    echo '<select name="guestaccess" class="form-control">';
    echo '<option value="1"'.($ga === 1 ? ' selected' : '').'>'._('Allow').'</option>';
    echo '<option value="2"'.($ga === 2 ? ' selected' : '').'>'._('Allow with waiting room').'</option>';
    echo '<option value="3"'.($ga === 3 ? ' selected' : '').'>'._('Require moderator approval').'</option>';
    echo '<option value="0"'.($ga === 0 ? ' selected' : '').'>'._('Only members').'</option>';
    echo '<option value="4"'.($ga === 4 ? ' selected' : '').'>'._('Disable chat').'</option>';
    echo '</select>';
    echo '</div>';
    echo '</div>';

    // Global Password Section  
    echo '<div class="setup-card">';
    echo '<h3>'._('Global Password Settings').'</h3>';
    $englobal = (int) get_setting('englobalpass');
    echo '<div class="form-group">';
    echo '<label>'._('Global Password Status').'</label>';
    echo '<select name="englobalpass" class="form-control">';
    echo '<option value="0"'.($englobal === 0 ? ' selected' : '').'>'._('Disabled').'</option>';
    echo '<option value="1"'.($englobal === 1 ? ' selected' : '').'>'._('Enabled').'</option>';
    echo '<option value="2"'.($englobal === 2 ? ' selected' : '').'>'._('Only for guests').'</option>';
    echo '</select>';
    echo '</div>';
    echo '<div class="form-group">';
    echo '<label>'._('Global Password').'</label>';
    echo '<input type="text" name="globalpass" class="form-control" value="'.htmlspecialchars(get_setting('globalpass')).'">';
    echo '</div>';
    echo '</div>';

    // Guest Registration Section
    echo '<div class="setup-card">';
    echo '<h3>'._('Guest Registration').'</h3>';
    $ga = (int) get_setting('guestreg');
    echo '<div class="form-group">';
    echo '<label>'._('Let guests register themselves').'</label>';
    echo '<select name="guestreg" class="form-control">';
    echo '<option value="0"'.($ga === 0 ? ' selected' : '').'>'._('Disabled').'</option>';
    echo '<option value="1"'.($ga === 1 ? ' selected' : '').'>'._('As applicant').'</option>';
    echo '<option value="2"'.($ga === 2 ? ' selected' : '').'>'._('As member').'</option>';
    echo '</select>';
    echo '</div>';
    echo '</div>';

    // System Messages Section
    echo '<div class="setup-card">';
    echo '<h3>'._('System Messages').'</h3>';
    foreach ($C['msg_settings'] as $setting => $title) {
        echo '<div class="form-group">';
        echo '<label>'.$title.'</label>';
        echo '<input type="text" class="form-control" name="'.$setting.'" value="'.htmlspecialchars(get_setting($setting)).'">';
        echo '</div>';
    }
    echo '</div>';

    // Text Settings Section
    echo '<div class="setup-card">';
    echo '<h3>'._('Text Settings').'</h3>';
    foreach ($C['text_settings'] as $setting => $title) {
        echo '<div class="form-group">';
        echo '<label>'.$title.'</label>';
        echo '<input type="text" class="form-control" name="'.$setting.'" value="'.htmlspecialchars(get_setting($setting)).'">';
        echo '</div>';
    }
    echo '</div>';

    // Color Settings Section
    echo '<div class="setup-card">';
    echo '<h3>'._('Color Settings').'</h3>';
    foreach ($C['colour_settings'] as $setting => $title) {
        echo '<div class="form-group">';
        echo '<label>'.$title.'</label>';
        echo '<input type="color" class="form-control color-picker" name="'.$setting.'" value="#'.htmlspecialchars(get_setting($setting)).'">';
        echo '</div>';
    }
    echo '</div>';

    // Captcha Section
    echo '<div class="setup-card">';
    echo '<h3>'._('Captcha Settings').'</h3>';
    if (!extension_loaded('gd')) {
        echo '<div class="alert alert-warning">'.sprintf(_('The %s extension of PHP is required for this feature. Please install it first.'), 'gd').'</div>';
    } else {
        $dismemcaptcha = (bool) get_setting('dismemcaptcha');
        $captcha = (int) get_setting('captcha');
        
        echo '<div class="form-group">';
        echo '<label>'._('Captcha For Members').'</label>';
        echo '<select name="dismemcaptcha" class="form-control">';
        echo '<option value="0"'.(!$dismemcaptcha ? ' selected' : '').'>'._('Enabled').'</option>';
        echo '<option value="1"'.($dismemcaptcha ? ' selected' : '').'>'._('Only for guests').'</option>';
        echo '</select>';
        echo '</div>';

        echo '<div class="form-group">';
        echo '<label>'._('Captcha Difficulty').'</label>';
        echo '<select name="captcha" class="form-control">';
        echo '<option value="0"'.($captcha === 0 ? ' selected' : '').'>'._('Disabled').'</option>';
        echo '<option value="1"'.($captcha === 1 ? ' selected' : '').'>'._('Simple').'</option>';
        echo '<option value="2"'.($captcha === 2 ? ' selected' : '').'>'._('Moderate').'</option>';
        echo '<option value="3"'.($captcha === 3 ? ' selected' : '').'>'._('Hard').'</option>';
        echo '<option value="4"'.($captcha === 4 ? ' selected' : '').'>'._('Extreme').'</option>';
        echo '</select>';
        echo '</div>';
    }
    echo '</div>';

    // Timezone Section
    echo '<div class="setup-card">';
    echo '<h3>'._('Time Zone Settings').'</h3>';
    echo '<div class="form-group">';
    echo '<label>'._('Default time zone').'</label>';
    echo '<select name="defaulttz" class="form-control">';
    $tzs = timezone_identifiers_list();
    $defaulttz = get_setting('defaulttz');
    foreach ($tzs as $tz) {
        echo '<option value="'.$tz.'"'.($defaulttz == $tz ? ' selected' : '').'>'.$tz.'</option>';
    }
    echo '</select>';
    echo '</div>';
    echo '</div>';


    // Additional Settings
    echo '<div class="setup-card">';
    echo '<h3>'._('Additional Settings').'</h3>';
    
    // Textarea Settings
    foreach ($C['textarea_settings'] as $setting => $title) {
        echo '<div class="form-group">';
        echo '<label>'.$title.'</label>';
        echo '<textarea class="form-control" name="'.$setting.'" rows="4">'.htmlspecialchars(get_setting($setting)).'</textarea>';
        echo '</div>';
    }

    // Number Settings
    foreach ($C['number_settings'] as $setting => $title) {
        echo '<div class="form-group">';
        echo '<label>'.$title.'</label>';
        echo '<input type="number" class="form-control" name="'.$setting.'" value="'.htmlspecialchars(get_setting($setting)).'">';
        echo '</div>';
    }

    // Boolean Settings
    foreach ($C['bool_settings'] as $setting => $title) {
        echo '<div class="form-group">';
        echo '<label>'.$title.'</label>';
        echo '<select name="'.$setting.'" class="form-control">';
        $value = (bool) get_setting($setting);
        echo '<option value="0"'.(!$value ? ' selected' : '').'>'._('Disabled').'</option>';
        echo '<option value="1"'.($value ? ' selected' : '').'>'._('Enabled').'</option>';
        echo '</select>';
        echo '</div>';
    }
    echo '</div>';

    // Submit Buttons
    echo '<div class="setup-actions">';
    echo submit(_('Apply'), 'class="btn btn-primary"');
    echo '</div>';
    echo '</form>';

    // Admin Actions
    if ($U['status'] == 8) {
        echo '<div class="admin-actions">';
        echo form('setup', 'backup');
        echo submit(_('Backup and restore'), 'class="btn btn-info"').'</form>';
        echo form('setup', 'destroy');
        echo submit(_('Destroy chat'), 'class="btn btn-danger"').'</form>';
        echo '</div>';
    }

    // Logout Button
    echo '<div class="logout-section">';
    echo form_target('_parent', 'logout');
    echo submit(_('Logout'), 'class="btn btn-secondary"').'</form>';
    echo '</div>';

    echo '</div>'; // Close setup-container
    print_end();
}
