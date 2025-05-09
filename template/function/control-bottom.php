<?php
function send_controls(): void
{
	global $U, $active_page;
	print_start('controls');
	print_css('control.css');
	$personalnotes = (bool) get_setting('personalnotes');
	$publicnotes = (bool) get_setting('publicnotes');
	$hide_reload_post_box = (bool) get_setting('hide_reload_post_box');
	$hide_reload_messages = (bool) get_setting('hide_reload_messages');
	$hide_profile = (bool) get_setting('hide_profile');
	$hide_admin = (bool) get_setting('hide_admin');
	$hide_notes = (bool) get_setting('hide_notes');
	$hide_clone = (bool) get_setting('hide_clone');
	$hide_rearrange = (bool) get_setting('hide_rearrange');
	
	echo '<div class="control-buttons">';

	// Messages button
	if (!$hide_reload_messages) {
		echo '<div class="nav-item' . ($active_page == 'messages' ? ' active' : '') . '">';
		echo form_target('view', 'view') . '<button type="submit" title="' . _('Messages') . '"><i class="fas fa-envelope"></i> <span class="nav-text">' . _('Reload Messages') . '</span></button></form>';
		echo '</div>';
	}
	
	// Hidden Line button
	echo '<div class="nav-item">';
	echo form_target('view', 'help') . '<button type="submit" title="' . _('Rules & Help') . '"><i class="fas fa-question-circle"></i> <span class="nav-text">' . _('Rules & Help') . '</span></button></form>';
	echo '</div>';
	// AFK Toggle button
	echo '<div class="nav-item' . (isset($is_afk) && $is_afk ? ' afk-active' : '') . '">';
	echo form_target('view', 'send_toggle_afk') . '<button type="submit" title="' . _('AFK') . '"><i class="fas fa-user-clock"></i> <span class="nav-text">' . _('Toggle AFK') . '</span></button></form>';
	echo '</div>';
		// Rules & Help button
	
	// Clone button
	if (!$hide_clone) {
		echo '<div class="nav-item">';
		echo form_target('_blank', 'login') . '<button type="submit" title="' . _('Clone') . '"><i class="fas fa-clone"></i> <span class="nav-text">' . _('Clone') . '</span></button></form>';
		echo '</div>';
	}
    echo '<div class="navbar-item">';
    echo '<div class="nav-item">';
	echo form_target('_blank', 'hidden_line') . '<button type="submit" title="' . _('Link') . '"><i class="fas fa-eye-slash"></i> <span class="nav-text">' . _('Hidden Line') . '</span></button></form>';
	echo '</div>';
	

	
	echo '</div>'; // End control-buttons
	
	print_end();
}
?>