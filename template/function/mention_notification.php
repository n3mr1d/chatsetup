<?php
/**
 * Sistem Notifikasi Mention DanChat
 * 
 * Implementasi kompleks untuk mendeteksi dan mengirimkan notifikasi
 * saat pengguna disebutkan dalam pesan chat menggunakan format @username.
 * 
 * @author DanChat Developer
 * @version 1.0
 */

// Constants for notification settings
define('MENTION_NOTIFICATION_INTERVAL', 300); // 5 minutes in seconds
define('MENTION_NOTIFICATION_SOUND', '../template/sounds/sound1.mp3');

/**
 * Mendeteksi dan memproses mention (@username) dalam pesan
 * 
 * @param string $message Pesan chat
 * @param string $sender Pengirim pesan
 * @param int $messageId ID pesan
 * @return array Array berisi username yang ter-mention dan valid
 */
function detect_mentions(string $message, string $sender, int $messageId): array {
    global $db;
    
    // Ekstrasi semua @mentions dari pesan menggunakan regex kompleks
    preg_match_all('/@([a-zA-Z0-9_-]+)(?![a-zA-Z0-9_-])/u', $message, $matches);
    
    if (empty($matches[1])) {
        return [];
    }
    
    $mentioned_users = [];
    $potential_mentions = array_unique($matches[1]);
    
    // Validasi setiap mention terhadap database pengguna aktif
    foreach ($potential_mentions as $username) {
        // Jangan notifikasi self-mention atau string kosong
        if ($username === $sender || empty($username)) {
            continue;
        }
        
        // Periksa apakah username valid (ada dalam database)
        $stmt = $db->prepare('SELECT nickname FROM ' . PREFIX . 'sessions 
                             WHERE nickname = ? AND status > 0 AND entry != 0 LIMIT 1');
        $stmt->execute([$username]);
        
        if ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $mentioned_users[] = $user['nickname'];
            
            // Rekam mention di database untuk tracking
            record_mention($user['nickname'], $sender, $messageId);
        }
    }
    
    return $mentioned_users;
}

/**
 * Rekam mention dalam database
 * 
 * @param string $mentioned Username yang di-mention
 * @param string $mentioner Username yang mention
 * @param int $messageId ID pesan yang berisi mention
 * @return bool Sukses/gagal penyimpanan record
 */
function record_mention(string $mentioned, string $mentioner, int $messageId): bool {
    global $db;
    
    try {
        // Periksa apakah mention ini sudah ada
        $stmt = $db->prepare('SELECT id FROM ' . PREFIX . 'mentions 
                             WHERE mentioned = ? AND message_id = ? LIMIT 1');
        $stmt->execute([$mentioned, $messageId]);
        
        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
            // Tambahkan mention baru dengan timestamp
            $stmt = $db->prepare('INSERT INTO ' . PREFIX . 'mentions 
                                 (mentioned, mentioner, message_id, mention_time, is_notified)
                                 VALUES (?, ?, ?, ?, 0)');
            $result = $stmt->execute([$mentioned, $mentioner, $messageId, time()]);
            return $result;
        }
        
        return true; // Mention sudah ada
    } catch (Exception $e) {
        // Log error
        error_log("Failed to record mention: " . $e->getMessage());
        return false;
    }
}

/**
 * Periksa apakah pengguna memiliki notifikasi mention baru
 * 
 * @param string $username Username yang diperiksa
 * @return array Informasi mention baru yang belum dinotifikasi
 */
function check_new_mentions(string $username): array {
    global $db;
    
    $mentions = [];
    
    try {
        // Ambil mention yang belum dinotifikasi dan masih dalam interval waktu valid
        $cutoffTime = time() - MENTION_NOTIFICATION_INTERVAL;
        
        $stmt = $db->prepare('SELECT m.id, m.mentioner, m.message_id, m.mention_time, msg.text 
                             FROM ' . PREFIX . 'mentions m
                             LEFT JOIN ' . PREFIX . 'messages msg ON m.message_id = msg.id
                             WHERE m.mentioned = ? 
                             AND m.is_notified = 0 
                             AND m.mention_time > ?
                             ORDER BY m.mention_time DESC');
        $stmt->execute([$username, $cutoffTime]);
        
        while ($mention = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $mentions[] = $mention;
        }
    } catch (Exception $e) {
        error_log("Failed to check mentions: " . $e->getMessage());
    }
    
    return $mentions;
}

/**
 * Tandai mention sebagai sudah dinotifikasi
 * 
 * @param int $mentionId ID mention
 * @return bool Sukses/gagal update status
 */
function mark_mention_as_notified(int $mentionId): bool {
    global $db;
    
    try {
        $stmt = $db->prepare('UPDATE ' . PREFIX . 'mentions SET is_notified = 1 
                             WHERE id = ? LIMIT 1');
        return $stmt->execute([$mentionId]);
    } catch (Exception $e) {
        error_log("Failed to mark mention as notified: " . $e->getMessage());
        return false;
    }
}

/**
 * Render notifikasi audio untuk mentions baru
 * 
 * @param string $username Username yang sedang login
 * @return string HTML untuk audio notification
 */
function render_mention_notification(string $username): string {
    $mentions = check_new_mentions($username);
    
    if (empty($mentions)) {
        return '';
    }
    
    $output = '';
    $shouldPlaySound = false;
    
    foreach ($mentions as $mention) {
        // Tandai setiap mention sebagai sudah dinotifikasi
        mark_mention_as_notified($mention['id']);
        $shouldPlaySound = true;
        
        // Tambahkan notifikasi visual (akan ditampilkan di interface)
        $mentioner = htmlspecialchars($mention['mentioner']);
        $time = date('H:i:s', $mention['mention_time']);
        
        $output .= '<div class="mention-notification" style="background-color: #1f1f1f; padding: 8px; margin: 5px 0; border-left: 3px solid #e67e22; border-radius: 3px;">';
        $output .= "<strong>@{$mentioner}</strong> mentioned you at {$time}";
        $output .= '</div>';
    }
    
    // Hanya tambahkan audio jika ada mentions baru
    if ($shouldPlaySound) {
        // Play sound notification directly from template/sounds/sound1.mp3
        $output .= '<audio autoplay><source src="template/sounds/sound1.mp3" type="audio/mpeg"></audio>';
    }
    
    return $output;
}



/**
 * Setup database table untuk sistem mention notification
 */
function setup_mention_notification_system(): void {
    global $db;
    
    try {
        // Cek apakah tabel sudah ada
        $tableExists = false;
        
        try {
            $db->query('SELECT 1 FROM ' . PREFIX . 'mentions LIMIT 1');
            $tableExists = true;
        } catch (Exception $e) {
            $tableExists = false;
        }
        
        if (!$tableExists) {
            // Buat tabel untuk mention tracking
            $sql = 'CREATE TABLE ' . PREFIX . 'mentions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                mentioned VARCHAR(255) NOT NULL,
                mentioner VARCHAR(255) NOT NULL,
                message_id INT NOT NULL,
                mention_time INT NOT NULL,
                is_notified TINYINT(1) NOT NULL DEFAULT 0,
                INDEX (mentioned),
                INDEX (message_id),
                INDEX (mention_time)
            )';
            
            $db->exec($sql);
            
            // Log setup berhasil
            error_log("Mention notification system setup completed successfully");
        }
    } catch (Exception $e) {
        error_log("Failed to setup mention notification system: " . $e->getMessage());
    }
}

/**
 * Integrasi fungsi deteksi mention dengan sistem pesan
 * 
 * @param string $message Pesan mentah
 * @param int $messageId ID pesan
 * @param string $sender Pengirim pesan
 * @return string Pesan yang sudah diproses
 */
function process_message_mentions(string $message, int $messageId, string $sender): string {
    // Detect mentions dan rekam ke database
    $mentions = detect_mentions($message, $sender, $messageId);
    
    // Implementasi highlighting mentions dalam pesan
    if (!empty($mentions)) {
        foreach ($mentions as $username) {
            // Highlight setiap mention di pesan
            $message = preg_replace(
                '/@' . preg_quote($username, '/') . '(?![a-zA-Z0-9_-])/u',
                '<span style="background-color: rgba(230, 126, 34, 0.2); color: #e67e22; padding: 2px; border-radius: 3px;">@' . $username . '</span>',
                $message
            );
        }
    }
    
    return $message;
}

/**
 * Clean up mentions lama dari database
 */
function cleanup_old_mentions(): void {
    global $db;
    
    try {
        // Hapus mention yang lebih tua dari 7 hari
        $cutoffTime = time() - (7 * 24 * 60 * 60);
        
        $stmt = $db->prepare('DELETE FROM ' . PREFIX . 'mentions WHERE mention_time < ?');
        $stmt->execute([$cutoffTime]);
        
    } catch (Exception $e) {
        error_log("Failed to cleanup old mentions: " . $e->getMessage());
    }
}