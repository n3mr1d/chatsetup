<?php
function send_welcome(){

    print_start('welcome');
    print_css('welcomepage.css');

    $rulestxt    = get_setting('rulestxt');
    $forumLink   = get_setting('forums');
    $hiddenLink  = get_setting('hiddenlink');

    global $db;
    $active_users = 0;
    $stmt = $db->query('SELECT COUNT(*) FROM ' . PREFIX . 'sessions WHERE entry!=0 AND status>0');
    if ($stmt) {
        $active_users = $stmt->fetchColumn();
    }
    $stmt = $db->query('SELECT COUNT(*) FROM ' . PREFIX . 'members');
    if ($stmt) {
        $total_members = $stmt->fetchColumn();
    }

    echo '<div class="welcome-container">';

    // Header Section
    echo '<div class="header-section">';
    echo '<h1>' . get_setting('chatname') . '</h1>';
    echo '<p class="tagline">No CP - No Spamming - No Gore - Respect Others</p>';
    echo '<div class="stats">';
    echo '<div class="stat-box"><i class="fas fa-users"></i> <span>' . number_format($total_members) . '</span> Members</div>';
    echo '<div class="stat-box"><i class="fas fa-user-clock"></i> <span>' . number_format($active_users) . '</span> Active</div>';
    echo '</div>';
    echo '</div>';

    echo '<div class="cards-container">';

    // About
    echo '<div class="card">';
    echo '<h2><i class="fas fa-info-circle"></i> About DanChat</h2>';
    echo '<div class="welcome-section welcome-about">';
    echo '<p>';
    echo 'Welcome to <strong>DanChat</strong> — a borderless communication hub forged for the <em>underground minds</em>. ';
    echo 'Built for hackers, coders, cyberpunks, and digital explorers, DanChat aims to become the <strong>largest and most active chat room in the Dark Web</strong>. ';
    echo 'Our mission is simple: <strong>Freedom of knowledge</strong>, <strong>respect among outcasts</strong>, and <strong>unfiltered curiosity</strong>.';
    echo '</p>';
    echo '<p>';
    echo 'Here, you’ll find live discussions on programming, ethical hacking, exploit development, OSINT, privacy tools, and more. ';
    echo 'Every byte of your input helps shape a thriving underground collective.';
    echo '</p>';
    echo '<p class="mutlak">';
    echo '<i class="fas fa-user-shield"></i> Respect is mandatory. Kindness is revolutionary. Curiosity is endless.';
    echo '</p>';
    echo '</div>';
        echo '</div>';

    // Rules
    echo '<div class="card">';
    echo '<h2><i class="fas fa-gavel"></i> System Protocol</h2>';
    if (!empty($rulestxt)) {
        echo '<div class="rules">' . $rulestxt . '</div>';
    } else {
        echo '<ul class="rules">';
        echo '<li><i class="fas fa-check"></i> No JavaScript (noscript)</li>';
        echo '<li><i class="fas fa-check"></i> Respect others</li>';
        echo '<li><i class="fas fa-check"></i> No spamming or self-promotion</li>';
        echo '<li><i class="fas fa-check"></i> Keep it clean and safe for everyone</li>';
        echo '<li><i class="fas fa-check"></i> Protect your privacy</li>';
        echo '<li><i class="fas fa-check"></i> Report violations to staff</li>';
        echo '</ul>';
    }
    echo '</div>';
    echo '<div class="contact-section">';
    echo '<p style="text-align:center; margin-top: 2em;">';
    echo 'Interested in registering? Contact us at <a href="mailto:idrift@dnmx.su">idrift@dnmx.su</a>';
    echo '</p>';
    echo '</div>';

    echo '</div>'; // close welcome-container

    echo '<div class="cards-grid">';

    // Forum Card (Icon only)
    echo '<div class="icon-card">';
    echo '<a href="' . htmlspecialchars($forumLink) . '" class="icon-link" title="Forums">';
    echo '<i class="fas fa-comments fa-3x"></i>';
    echo '<span>Forums</span>';
    echo '</a>';
    echo '</div>';


    echo '<div class="icon-card">';
    echo '<a href="' . htmlspecialchars($hiddenLink) . '" class="icon-link" title="Forums">';
    echo '<i class="fas fa-eye-slash fa-3x"></i>';
    echo '<span>Hidden Line</span>';
    echo '</a>';
    echo '</div>';

    // Chat Room/Login Card
    echo '<div class="icon-card">';
    // ensure the form lands on /?action=login so route() doesn’t treat it as "/" again
    echo '<form method="post" action="?action=login" title="Join Chat">';
    echo form('login');
    echo '<button type="submit" class="icon-link">';
    echo '<i class="fas fa-comments fa-3x"></i>';
    echo '<span>Enter Chat</span>';
    echo '</button>';
    echo '</form>';
    echo '</div>';

    echo '</div>'; // close cards-grid

    print_end();
}
