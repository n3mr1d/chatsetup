<?php
function send_redirect(string $url): void
{
	// Sanitize and decode the URL
	$url = trim(htmlspecialchars_decode(rawurldecode($url)));
	
	// Extract protocol from URL
	preg_match('~^(.*)://~u', $url, $match);
	$protocol = isset($match[1]) ? $match[1] : '';
	
	// Remove protocol from URL for further processing
	$url = preg_replace('~^(.*)://~u', '', $url);
	$escaped = htmlspecialchars($url);
	
	// Check if URL has valid HTTP/HTTPS protocol
	if(isset($match[1]) && ($match[1] === 'http' || $match[1] === 'https')) {
		// Safe URL with HTTP/HTTPS protocol
		print_start('redirect', 0, $match[0].$escaped);
        print_css('redirectpage.css');

		echo '<div class="redirect-container">';
		echo '<div class="redirect-header"><i class="fa fa-external-link-alt"></i> ' . _('External Link') . '</div>';
		echo '<div class="redirect-content">';
		echo '<p class="redirect-message">' . sprintf(_('Redirecting to: %s'), 
			"<a href=\"$match[0]$escaped\" class=\"redirect-link\">$match[0]$escaped</a>") . '</p>';
		echo '<div class="redirect-loading"><div class="redirect-spinner"></div></div>';
		echo '</div></div>';
	} else {
		// Non-HTTP/HTTPS URL
		print_start('redirect');
		
		// Set empty protocol if not defined
		if(!isset($match[0])) {
			$match[0] = '';
		}
        print_css('redirectpage.css');

		echo '<div class="redirect-container redirect-warning">';
		echo '<div class="redirect-header"><i class="fa fa-exclamation-triangle"></i> ' . _('Warning') . '</div>';
		echo '<div class="redirect-content">';
		
		// Check for potentially dangerous protocols
		if(preg_match('~^(javascript|blob|data):~', $url)) {
			echo '<p class="redirect-message redirect-danger">' . 
				sprintf(_('Dangerous non-http link requested, copy paste this link if you are really sure: %s'), 
				"<span class=\"redirect-dangerous-link\">$match[0]$escaped</span>") . '</p>';
			echo '<p class="redirect-security-notice">' . _('This link may contain malicious code. Proceed with caution.') . '</p>';
		} else {
			echo '<p class="redirect-message">' . 
				sprintf(_('Non-http link requested: %s'), 
				"<a href=\"$match[0]$escaped\" class=\"redirect-link\">$match[0]$escaped</a>") . '</p>';
		}
		
		echo '<p class="redirect-alternative">' . 
			sprintf(_("If it's not working, try this one: %s"), 
			"<a href=\"http://$escaped\" class=\"redirect-link redirect-alternative-link\">http://$escaped</a>") . '</p>';
		
		echo '<div class="redirect-buttons">';
		echo '<a href="' . $_SERVER['HTTP_REFERER'] . '" class="redirect-button redirect-back-button"><i class="fa fa-arrow-left"></i> ' . _('Go Back') . '</a>';
		echo '</div>';
		echo '</div></div>';
	}
	
	
	print_end();
}
