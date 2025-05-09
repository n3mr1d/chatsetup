<?php
function send_waiting_room(): void
{
    global $U, $db, $language;
    $ga = (int) get_setting('guestaccess');
    print_css('waitingroom.css');
    
    // Determine if user needs to wait
    if ($ga === 3 && (get_count_mods() > 0 || !get_setting('modfallback'))) {
        $wait = false; // No wait if guest access is set to moderated and mods are present
    } else {
        $wait = true;
    }
    
    // Check for expired sessions and kicked users
    check_expired();
    check_kicked();
    
    // Calculate remaining wait time
    $timeleft = get_setting('entrywait') - (time() - $U['lastpost']);
    
    // Allow entry if wait time has passed or guest access is immediate
    if ($wait && ($timeleft <= 0 || $ga === 1)) {
        $U['entry'] = $U['lastpost'];
        $stmt = $db->prepare('UPDATE ' . PREFIX . 'sessions SET entry=lastpost WHERE session=?');
        $stmt->execute([$U['session']]);
        send_frameset();
    } elseif (!$wait && $U['entry'] != 0) {
        // User already approved by moderator
        send_frameset();
    } else {
        // User must wait - show waiting room
        $refresh = (int) get_setting('defaultrefresh');
        print_start('waitingroom', $refresh, "$_SERVER[SCRIPT_NAME]?action=wait&session=$U[session]&lang=$language&nc=" . substr(time(), -6));
        
        echo '<h2>' . _('Waiting room') . '</h2><p>';
        
        if ($wait) {
            printf(_('Welcome %1$s, your login has been delayed, you can access the chat in %2$d seconds.'), 
                style_this(htmlspecialchars($U['nickname']), $U['style']), 
                $timeleft);
        } else {
            printf(_('Welcome %1$s, your login has been delayed, you can access the chat as soon as a moderator lets you in.'), 
                style_this(htmlspecialchars($U['nickname']), $U['style']));
        }
        
        echo '</p><br><p>';
        printf(_("If this page doesn't refresh every %d seconds, use the button below to reload it manually!"), $refresh);
        echo '</p><br><br>';
        
        echo '<hr>' . form('wait');
        echo submit(_('Reload')) . '</form><br>';
        
        echo '<div class="nav-item logout">';
        echo form_target('_self', '', 'logout') . '<button type="submit" title="' . _('Exit Chat') . '" id="exitbutton"><i class="fas fa-sign-out-alt"></i> <span class="nav-text">' . _('Exit') . '</span></button></form>';
        echo '</div>';
        
        // Display rules if available
        $rulestxt = get_setting('rulestxt');
        if (!empty($rulestxt)) {
            echo '<div id="rules"><h2>' . _('Rules') . "</h2><b>$rulestxt</b></div>";
        }
        
        print_end();
    }
}