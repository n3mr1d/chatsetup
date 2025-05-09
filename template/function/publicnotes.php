<?php
function view_publicnotes(): void
{
	global $db, $U;
	$dateformat = get_setting('dateformat');
	print_start('publicnotes');
	print_css('publicnot.css');
	
	echo '<div class="public-notes-container">';
	
	// Display announcement notes at the top
	echo '<div class="announcement-section">';
	echo '<h2>'._('Announcement').'</h2>';
	
	$announcement_query = $db->query('SELECT lastedited, editedby, text FROM ' . PREFIX . 'notes WHERE type=4 ORDER BY id DESC LIMIT 1;');
	$announcement = $announcement_query->fetch(PDO::FETCH_OBJ);
	
	if ($announcement && !empty($announcement->text)) {
		if(MSGENCRYPTED){
			try {
				$announcement->text = sodium_crypto_aead_aes256gcm_decrypt(base64_decode($announcement->text), null, AES_IV, ENCRYPTKEY);
			} catch (SodiumException $e){
				send_error($e->getMessage());
			}
		}
		echo '<div class="note-card announcement">';
		echo '<div class="note-header">';
		$stmt2 = $db->prepare('SELECT style FROM ' . PREFIX . 'sessions WHERE nickname=? UNION SELECT style FROM ' . PREFIX . 'members WHERE nickname=? ORDER BY style DESC LIMIT 1');
		$stmt2->execute([$announcement->editedby, $announcement->editedby]); 
		$style = $stmt2->fetch(PDO::FETCH_NUM);
		$style = $style ? $style[0] : '';
		printf(_(' <span class="note-editor"> Last edited by <span style="%3$s">%1$s</span> at <span class="note-editor-date">%2$s</span></span>'), htmlspecialchars($announcement->editedby), date($dateformat, $announcement->lastedited), $style);
		echo '</div>';
		echo '<div class="note-content">';
		echo '<textarea class="note-textarea" readonly="readonly">' . htmlspecialchars($announcement->text) . '</textarea>';
		echo '</div>';
		echo '</div>';
	} else {
		echo '<div class="no-notes">'._('No announcements available').'</div>';
	}
	echo '</div>';
	
	// Display public notes
	echo '<h2>'._('Public notes').'</h2>';
	
	$query = $db->query('SELECT lastedited, editedby, text FROM ' . PREFIX . 'notes INNER JOIN (SELECT MAX(id) AS latest FROM ' . PREFIX . 'notes WHERE type=3 GROUP BY editedby) AS t ON t.latest = id ORDER BY lastedited DESC;');
	$notes_found = false;
	
	while($result = $query->fetch(PDO::FETCH_OBJ)){
		if (!empty($result->text)) {
			$notes_found = true;
			if(MSGENCRYPTED){
				try {
					$result->text = sodium_crypto_aead_aes256gcm_decrypt(base64_decode($result->text), null, AES_IV, ENCRYPTKEY);
				} catch (SodiumException $e){
					send_error($e->getMessage());
				}
			}
			echo '<div class="note-card">';
			echo '<div class="note-header">';
			$stmt2 = $db->prepare('SELECT style FROM ' . PREFIX . 'sessions WHERE nickname=? UNION SELECT style FROM ' . PREFIX . 'members WHERE nickname=? ORDER BY style DESC LIMIT 1');
			$stmt2->execute([$result->editedby, $result->editedby]);
			$style = $stmt2->fetch(PDO::FETCH_NUM);
			$style = $style ? $style[0] : '';
			printf(_(' <span class="note-editor"> Last edited by <span style="%3$s">%1$s</span> at <span class="note-editor-date">%2$s</span></span>'), htmlspecialchars($result->editedby), date($dateformat, $result->lastedited), $style);
			echo '</div>';
			echo '<div class="note-content">';
			echo '<textarea class="note-textarea" readonly="readonly">' . htmlspecialchars($result->text) . '</textarea>';
			echo '</div>';
			echo '</div>';
		}
	}
	
	if (!$notes_found) {
		echo '<div class="no-notes">'._('No public notes available').'</div>';
	}
	
	echo '</div>';
	
	// Add back button
	echo '<div class="back-button-container">';
	echo form('view').submit(_('Back to Chat'), 'class="back-button"').'</form>';
	echo '</div>';
	
	print_end();
}
?>