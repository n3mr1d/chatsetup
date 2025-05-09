<?php
function send_toggle_afk(): void {
	global $U, $db;


	// Get current nickname and AFK status
	$nickname = $U['nickname'];
	$stmt = $db->prepare('SELECT is_afk FROM ' . PREFIX . 'afk_status WHERE nickname = ?');
	$stmt->execute([$nickname]);
	$result = $stmt->fetch(PDO::FETCH_ASSOC);
	
	if ($result) {
		// Toggle existing AFK status
		$new_afk = !$result['is_afk'];
		$stmt = $db->prepare('DELETE FROM ' . PREFIX . 'afk_status WHERE nickname = ?');
		$stmt->execute([$nickname]);
		$new_afk = !$result['is_afk']; // Set new_afk value after update
	} else {
		// Insert new AFK status as true
		$stmt = $db->prepare('INSERT INTO ' . PREFIX . 'afk_status (nickname, is_afk) VALUES (?, TRUE)');
		$stmt->execute([$nickname]);
		$new_afk = true; // Set new_afk value after insert
	}
}
