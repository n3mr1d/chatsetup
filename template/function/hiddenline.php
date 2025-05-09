
<?php
function send_hidden_line(){
    global $db;
    $hiddenline = get_setting('hiddenlink');
    if (!empty($hiddenline)) {
        send_redirect($hiddenline);
    } else {
        send_redirect('/');
    }
}
?>