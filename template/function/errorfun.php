<?php
function send_error(string $err): void
{
	global $language, $dir;
	print_start('error');
	print_css('error.css');
	echo '<div class="error-container">';
	echo '<div class="error-icon">&#9888;</div>';
	echo '<h2 class="error-title">'.sprintf(_('Error: %s'), $err).'</h2>';
	echo '<div class="error-message">';
	echo '<p>'._('An error has occurred while processing your request.').'</p>';
	echo '<p>'._('Please try again or contact the administrator if the problem persists.').'</p>';
	echo '</div>';
	echo '<div class="error-actions">';
	echo form_target('_parent', '').submit(_('Back to the login page.'), 'class="backbutton"').'</form>';
	echo '</div>';
	
	// Add timestamp for debugging purposes
	echo '<div class="error-timestamp">';
	echo '<small>'._('Error occurred at: ').date('Y-m-d H:i:s').'</small>';
	echo '</div>';
	echo '</div>';
	
	print_end();
}

function send_fatal_error(string $err, int $error_code = 500, ?string $additional_info = null, bool $log_error = true): void
{
	global $language, $styles, $dir, $db;
	
	// Set appropriate HTTP response code
	http_response_code($error_code);
	
	
	
	// Send headers and start HTML output
	send_headers();
	echo '<!DOCTYPE html><html lang="'.$language.'" dir="'.$dir.'"><head>'.meta_html();
	echo '<title>'._('Fatal error').'</title>';
	print_css('error.css');
	echo '</head><body>';
	
	echo '<div class="fatal-error-container">';
	echo '<h2 class="fatal-error-title">'.sprintf(_('Fatal error: %s'), $err).'</h2>';
	echo '<div class="fatal-error-code">'._('Error code:').' '.$error_code.'</div>';
	
	if ($additional_info) {
		echo '<div class="fatal-error-details">';
		echo '<p>'._('Additional information:').'</p>';
		echo '<p>'.htmlspecialchars($additional_info).'</p>';
		echo '</div>';
	}
	
	echo '<div class="fatal-error-message">';
	echo '<p>'._('A fatal error has occurred that prevents the application from continuing.').'</p>';
	echo '<p>'._('Please contact the system administrator with the information displayed on this page.').'</p>';
	echo '</div>';
	
	echo '<div class="fatal-error-actions">';
	echo form_target('_parent', '').submit(_('Return to homepage'), 'class="backbutton"').'</form>';
	echo '</div>';
	
	echo '<div class="fatal-error-timestamp">';
	echo '<small>'._('Error occurred at:').' '.date('Y-m-d H:i:s').'</small>';
	echo '<br><small>'._('Server time:').' '.date('T (P)').'</small>';
	echo '</div>';
	echo '</div>';
	
	// Debug information for administrators
	if (isset($GLOBALS['U']) && isset($GLOBALS['U']['status']) && $GLOBALS['U']['status'] >= 7) {
		echo '<div class="fatal-error-debug">';
		echo '<h3>'._('Debug Information (Admin Only)').'</h3>';
		echo '<pre>';
		echo 'PHP Version: ' . PHP_VERSION . "\n";
		echo 'Server Software: ' . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "\n";
		echo 'Request URI: ' . ($_SERVER['REQUEST_URI'] ?? 'Unknown') . "\n";
		echo 'Request Method: ' . ($_SERVER['REQUEST_METHOD'] ?? 'Unknown') . "\n";
		echo '</pre>';
		echo '</div>';
	}
	
	print_end();
}
function show_fails(): void
{
	global $db, $U;
	$stmt = $db->prepare('SELECT loginfails, lastlogin FROM ' . PREFIX . 'members WHERE nickname=?;');
	$stmt->execute([$U['nickname']]);
	print_css('failed.css');
	$temp = $stmt->fetch(PDO::FETCH_ASSOC);

	if ($temp && $temp['loginfails'] > 0) {
		print_start('failednotice');
		
		echo '<div class="login-alert">';
		echo '<div class="alert-icon">⚠️</div>';
		
		echo '<div class="alert-content">';
		echo '<h3>' . _('Security Alert') . '</h3>';
		
		echo '<div class="alert-details">';
		echo '<p class="failed-count">' . 
			sprintf(_('There were <span style="color:white"> %d </span> failed login attempts on your account'), $temp['loginfails']) .
		'</p>';
		
		if ($temp['lastlogin']) {
			echo '<p class="last-login">' .
				sprintf(_('Last successful login: %s'), date('Y-m-d H:i:s', $temp['lastlogin'])) .
			'</p>';
		}
		
		echo '<p class="security-tip">' .
			_('For security, please consider:') .
		'</p>';
		echo '<ul>';
		echo '<li>' . _('Using a strong, unique password') . '</li>';
		echo '<li>' . _('Checking for any suspicious activity') . '</li>';
		echo '</ul>';
		echo '</div>';

		echo '<div class="alert-actions">';
		$stmt = $db->prepare('UPDATE ' . PREFIX . 'members SET loginfails=? WHERE nickname=?;');
		$stmt->execute([0, $U['nickname']]);
		echo form_target('_self', 'login');
		echo '<button type="submit" class="dismiss-btn">' . _('Acknowledge & Dismiss') . '</button>';
		echo '</form>';
		echo '</div>';

		echo '</div>'; // alert-content
		echo '</div>'; // login-alert
		
		print_end();
	}
}