<?php
function send_login(): void
{
	global $err;
	$ga=(int) get_setting('guestaccess');
	if($ga===4){
		send_chat_disabled();
	}
	print_start('login');
	print_css('loginpage.css');
        // icon login 
	$englobal=(int) get_setting('englobalpass');

	
	echo form_target('_parent', 'login');
	if($englobal===1 && isset($_POST['globalpass'])){
		echo hidden('globalpass', htmlspecialchars($_POST['globalpass']));
	}
	echo '<div id="login-form">';

	if($englobal!==1 || (isset($_POST['globalpass']) && $_POST['globalpass']==get_setting('globalpass'))){
		echo '<div class="form-header"><h1 id="danchat-title">'.get_setting('chatname'). '</h1></div>';
		

		
		if(isset($err)) {
			echo '<div class="error-message">
				<div class="error-icon">
					<svg xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 0 16 16">
						<path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
					</svg>
				</div>
				<div class="error-text">
					' . htmlspecialchars($err) . '
				</div>
			</div>';
		}
		echo '<div class="form-group">';
		echo '<i class="fa fa-user"></i>';
		echo '<input id="input_nickname" type="text" name="nick" autocomplete="off" autofocus placeholder="'._('Nickname').'">';
		echo '</div>';
		
		echo '<div class="form-group">';
		echo '<i class="fas fa-lock"></i>';
		echo '<input id="password_nickname" type="password" name="pass" autocomplete="off" placeholder="'._('Password').'">';
		echo '</div>';
	
		
		send_captcha();

		if($ga!==0){
			if(get_setting('guestreg')!=0){
				echo '<div class="form-group">';
				echo '<input id="regpass_input" type="password" name="regpass" placeholder="'._('Repeat password to register (optional)').'" autocomplete="off">';
				echo '</div>';
			}
			
			if($englobal===2){
				echo '<div class="form-group">';
				echo '<input id="globalpass_input" type="password" name="globalpass" autocomplete="off" placeholder="'._('Global Password').'">';
				echo '</div>';
			}
			
			echo '<div class="form-group color-select">';
			echo '<label>'._('Guests, choose a colour:').'</label>';
			echo '<select name="colour"><option value="">* '._('Random Colour').' *</option>';
			print_colours();
			echo '</select>';
			echo '</div>';
		}else{
			echo '<div class="members-only">'._('Sorry, currently members only!').'</div>';
		}
		
		echo '<div class="form-group">';
		echo submit(_('Enter Chat'), 'id="submit_button" class="btn-primary"');
		echo '</div>';

		echo '</div>'.		get_nowchatting()
.'</form>';
		
	}else{
		echo '<tr><td><label id="globalpass_label">'._('Global Password:').'</label></td><td><input id="globalpass_input" type="password" name="globalpass" size="15" autocomplete="off" autofocus></td></tr>';
		if($ga===0){
			echo '<tr><td colspan="2">'._('Sorry, currently members only!').'</td></tr>';
		}
		echo '<tr><td colspan="2">'.submit(_('Enter Chat'), 'id="submit_btn"').'</td></tr></table></form>';
	}
	print_end();
}
// admin login page
function send_alogin(): void
{
    print_start('alogin');
    echo '<div class="admin-login">';
	print_css('adminlogin.css');
    echo '<h2>'._('Admin Login').'</h2>';
    
    echo form('setup');
    echo '<div class="login-form">';
    
    // Username field with validation
    echo '<div class="form-group">';
    echo '<label for="admin_nick">'._('Admin Username:').'</label>';
    echo '<input type="text" id="admin_nick" name="nick" maxlength="50" required 
          pattern="[A-Za-z0-9_-]{3,50}"
          title="'._('Username must be 3-50 characters and contain only letters, numbers, underscore or hyphen').'"
          autocomplete="username" autofocus>';
    echo '</div>';

    // Password field with requirements
    echo '<div class="form-group">';
    echo '<label for="admin_pass">'._('Admin Password:').'</label>';
    echo '<input type="password" id="admin_pass" name="pass"
          title="'._('Password must contain at least 8 characters with numbers, uppercase and lowercase letters').'"
          autocomplete="current-password">';
    echo '</div>';


    // CAPTCHA
    send_captcha();


    // Submit button
    echo '<div class="form-actions">';
    echo submit(_('Secure Login'), 'class="btn-admin-login"');
    echo '</div>';

    echo '</div></form>';


    echo '</div>';
    print_end();
}
?>