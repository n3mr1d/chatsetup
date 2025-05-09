<?php
function send_notes(){
global $U, $db;
	print_start('notes');
	$personalnotes=(bool) get_setting('personalnotes');
	$publicnotes=(bool) get_setting('publicnotes');
	print_css('editnot.css');
	
	if($type===1){
		echo '<h2>'._('Staff notes').'</h2><p>';
		$hiddendo=hidden('do', 'staff');
	}elseif($type===0){
		echo '<h2>'._('Admin notes').'</h2><p>';
		$hiddendo=hidden('do', 'admin');
	}elseif($type===2){
		echo '<h2>'._('Personal notes').'</h2><p>';
		$hiddendo='';
	}elseif($type===3){
		echo '<h2>'._('Public notes').'</h2><p>';
		$hiddendo=hidden('do', 'public');
	}elseif($type===4){
		echo '<h2>'._('Announcement').'</h2><p>';
		$hiddendo=hidden('do', 'announcement');
	}
	if($U['status']>=3 && ($personalnotes || $publicnotes)){
		echo '<table><tr>';
		if($U['status']>6){
			echo '<td>'.form_target('view', 'notes', 'admin').submit(_('Admin notes')).'</form></td>';
		}
		if($U['status']>=5){
			echo '<td>'.form_target('view', 'notes', 'staff').submit(_('Staff notes')).'</form></td>';
			echo '<td>'.form_target('view', 'notes', 'announcement').submit(_('Announcement')).'</form></td>';
		}
		if($personalnotes){
			echo '<td>'.form_target('view', 'notes').submit(_('Personal notes')).'</form></td>';
		}
		if($publicnotes){
			echo '<td>'.form_target('view', 'notes', 'public').submit(_('Public notes')).'</form></td>';
		}
		echo '</tr></table>';
	}
	if(isset($_POST['text'])){
		if(MSGENCRYPTED){
			try {
				$_POST['text']=base64_encode(sodium_crypto_aead_aes256gcm_encrypt($_POST['text'], '', AES_IV, ENCRYPTKEY));
			} catch (SodiumException $e){
				send_error($e->getMessage());
			}
		}
		$time=time();
		$stmt=$db->prepare('INSERT INTO ' . PREFIX . 'notes (type, lastedited, editedby, text) VALUES (?, ?, ?, ?);');
		$stmt->execute([$type, $time, $U['nickname'], $_POST['text']]);
		echo '<b>'._('Notes saved!').'</b> ';
	}
	$dateformat=get_setting('dateformat');
	if(($type!==2) && ($type !==3)){
		$stmt=$db->prepare('SELECT COUNT(*) FROM ' . PREFIX . 'notes WHERE type=?;');
		$stmt->execute([$type]);
	}else{
		$stmt=$db->prepare('SELECT COUNT(*) FROM ' . PREFIX . 'notes WHERE type=? AND editedby=?;');
		$stmt->execute([$type, $U['nickname']]);
	}
	$num=$stmt->fetch(PDO::FETCH_NUM);
	if(!empty($_POST['revision'])){
		$revision=intval($_POST['revision']);
	}else{
		$revision=0;
	}
	if(($type!==2) && ($type !==3)){
		$stmt=$db->prepare('SELECT * FROM ' . PREFIX . "notes WHERE type=? ORDER BY id DESC LIMIT 1 OFFSET $revision;");
		$stmt->execute([$type]);
	}else{
		$stmt=$db->prepare('SELECT * FROM ' . PREFIX . "notes WHERE type=? AND editedby=? ORDER BY id DESC LIMIT 1 OFFSET $revision;");
		$stmt->execute([$type, $U['nickname']]);
	}
	if($note=$stmt->fetch(PDO::FETCH_ASSOC)){
		$stmt2 = $db->prepare('SELECT style FROM ' . PREFIX . 'sessions WHERE nickname=? UNION SELECT style FROM ' . PREFIX . 'members WHERE nickname=? ORDER BY style DESC LIMIT 1');
		$stmt2->execute([$note['editedby'], $note['editedby']]);
		$style = $stmt2->fetch(PDO::FETCH_NUM);
		$style = $style ? $style[0] : '';
		printf(_(' <span class="note-editor"> Last edited by <span style="%3$s">%1$s</span> at <span class="note-editor-date">%2$s</span></span>'), htmlspecialchars($note['editedby']), date($dateformat, $note['lastedited']), $style);
	}else{
		$note['text']='';
	}
	if(MSGENCRYPTED){
		try {
			$note['text']=sodium_crypto_aead_aes256gcm_decrypt(base64_decode($note['text']), null, AES_IV, ENCRYPTKEY);
		} catch (SodiumException $e){
			send_error($e->getMessage());
		}
	}
	echo "</p>".form('notes');
	echo "$hiddendo<textarea name=\"text\">".htmlspecialchars($note['text']).'</textarea><br>';
	echo submit(_('Save notes')).'</form><br>';
	if($num[0]>1){
		echo '<br><table><tr><td>'._('<span class="note-editor">Revisions:</span>').'</td>';
		if($revision<$num[0]-1){
			echo '<td>'.form('notes').hidden('revision', $revision+1);
			echo $hiddendo.submit(_('Older')).'</form></td>';
		}
		if($revision>0){
			echo '<td>'.form('notes').hidden('revision', $revision-1);
			echo $hiddendo.submit(_('Newer')).'</form></td>';
		}
		echo '</tr></table>';
	}
	print_end();
}
	?>