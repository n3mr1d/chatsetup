<?php
/**
 * Function to display available commands based on user status
 * Shows different commands for different user levels
 */
function show_commands() {
    global $U;
    print_start('show_commands');
    print_css('showcomamnd.css');
    echo '<div class="commands-container">';
    echo '<h3>' . _('Available Commands') . '</h3>';
    echo '<ul class="command-list">';
    
    // Basic commands for all users
    echo '<li><code>/dall</code> - ' . _('Delete all your messages') . '</li>';
    echo '<li><code>/multi</code> - ' . _('Enable multi-line input mode') . '</li>';
    echo '<li><code>/single</code> - ' . _('Disable multi-line input mode') . '</li>';
    
    // Commands for moderators (status >= 3)
    if ($U['status'] >= 3) {
        echo '<li><code>/kick username [reason]</code> - ' . _('Kick a user from the chat') . '</li>';
    }
    
    // Commands for admins (status >= 5)
    if ($U['status'] >= 5) {
        echo '<li><code>/clean username</code> - ' . _('Delete all messages from a specific user') . '</li>';
    }
    
    echo '</ul>';
    echo '</div>';
    echo '<div class="control-buttons">';
    echo '<div class="nav-item">';
    echo form_target('view', 'view') . '<button type="submit" title="' . _('Back to Chat') . '">' . _('Back To Chat') . '</span></button></form>';
    echo '</div>';
    echo '</div>';
    print_end();
}
?>