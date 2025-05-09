<?php
function send_logout(): void
{
	global $U, $db, $language, $dir;

	// Clear any active sessions for this user
	if (isset($U['nickname']) && isset($db) && $db instanceof PDO) {
		try {
			$stmt = $db->prepare('DELETE FROM ' . PREFIX . 'sessions WHERE nickname = ?');
			$stmt->execute([$U['nickname']]);
			
			// Also clear any AFK status
			$stmt = $db->prepare('DELETE FROM ' . PREFIX . 'afk_status WHERE nickname = ?');
			$stmt->execute([$U['nickname']]);
		} catch (Exception $e) {
		
		}
	}
	
	// Clear cookies if they exist
	$cookie_params = session_get_cookie_params();
	if (isset($_COOKIE['nickname'])) {
		setcookie('nickname', '', time() - 3600, $cookie_params['path'], $cookie_params['domain'], $cookie_params['secure'], $cookie_params['httponly']);
	}
	if (isset($_COOKIE['password'])) {
		setcookie('password', '', time() - 3600, $cookie_params['path'], $cookie_params['domain'], $cookie_params['secure'], $cookie_params['httponly']);
	}
	
	// Start the logout page
	print_start('logout');
	print_css('error.css');
	
	echo '<div class="error-container">';
	echo '<div class="error-icon">👋</div>';
	echo '<h2 class="error-title">'.sprintf(_('Goodbye, %s!'), style_this(htmlspecialchars($U['nickname']), $U['style'])).'</h2>';
	echo '<div class="error-message">';
	echo '<p>'._('You have been successfully logged out.').'</p>';
	echo '<p>'._('Thank you for visiting. We hope to see you again soon!').'</p>';
	
	// Show last login information if available
	if (isset($U['last_login']) && $U['last_login'] > 0) {
		echo '<p class="login-info">'._('Your last login was: ').date('Y-m-d H:i:s', $U['last_login']).'</p>';
	}
	
	echo '</div>';
	echo '<div class="error-actions">';
	echo form_target('_parent', '').submit(_('Back to the login page.'), 'class="backbutton"').'</form>';
	echo '</div>';
	
	// Add timestamp
	echo '<div class="error-timestamp">';
	echo '<small>'._('Logged out at: ').date('Y-m-d H:i:s').'</small>';
	echo '</div>';
	echo '</div>';
	
	print_end();
	
	// Destroy the PHP session if active
	if (session_status() === PHP_SESSION_ACTIVE) {
		session_destroy();
	}
}
?>