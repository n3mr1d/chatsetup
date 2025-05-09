<?php
// Modern PHP navbar with icons and enhanced styling

    global $U, $language, $session, $db;
    
    // Check if user is AFK to highlight the AFK button
    $is_afk = false;
    if (isset($U['nickname'])) {
        $stmt = $db->prepare('SELECT is_afk FROM ' . PREFIX . 'afk_status WHERE nickname = ?');
        $stmt->execute([$U['nickname']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $is_afk = ($result && $result['is_afk']);
    }
    
   
    print_css('navbar.css');
    echo '<nav class="modern-navbar">';
    echo '<div class="navbar-container">';
    
    echo '<div class="navbar-brand">';
    echo form_target('_parent', 'login') . '<button type="submit" class="brand-button"> DanChat</button></form>';
    echo '</div>';
    
    echo '<div class="navbar-links">';
	
    // Profile button
    echo '<div class="nav-item">';
    echo form_target('view', 'profile') . '<button type="submit" title="' . _('Profile') . '"><i class="fas fa-user"></i> <span class="nav-text">' . _('Profile') . '</span></button></form>';
    echo '</div>';

    // Notes button - only show if user has appropriate permissions
    if (!isset($U['status']) || $U['status'] >= 3) {
        echo '<div class="nav-item">';
        echo form_target('view', 'notes') . '<button type="submit" title="' . _('Notes') . '"><i class="fas fa-sticky-note"></i> <span class="nav-text">' . _('Notes') . '</span></button></form>';
        echo '</div>';
    }
    
    // Public Notes button
    echo '<div class="nav-item">';
    echo form_target('view', 'viewpublicnotes') . '<button type="submit" title="' . _('Public Notes') . '"><i class="fas fa-clipboard"></i> <span class="nav-text">' . _('Public Notes') . '</span></button></form>';
    echo '</div>';
    
    // Admin button - only show for admins
    if (isset($U['status']) && $U['status'] >= 5) {
        echo '<div class="nav-item">';
        echo form_target('view', 'admin') . '<button type="submit" title="' . _('Admin') . '"><i class="fas fa-shield-alt"></i> <span class="nav-text">' . _('Admin') . '</span></button></form>';
        echo '</div>';
    }
    

    
    echo '</div>'; // End navbar-links
    
    // User controls on the right
    echo '<div class="navbar-user">';
    
    // User info if logged in
    if (isset($U['nickname'])) {
        echo '<div class="user-info">';
        echo '<span class="username" style="' . $U['style'] . '"> ' . htmlspecialchars($U['nickname']) . '<i class="fas fa-user-circle"></i></span>';
        echo '</div>';
    }
    
    // Logout button
    echo '<div class="nav-item logout">';
    echo form_target('_parent', 'logout') . '<button type="submit" title="' . _('Exit Chat') . '" id="exitbutton"><i class="fas fa-sign-out-alt"></i> <span class="nav-text">' . _('Exit') . '</span></button></form>';
    echo '</div>';
    
    echo '</div>'; // End navbar-user
    
    echo '</div>'; // End navbar-container
    
    echo '</nav>';
