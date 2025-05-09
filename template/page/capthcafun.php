<?php
function send_captcha() {
    global $db, $memcached;
    $difficulty = (int) get_setting('captcha');
    if ($difficulty === 0 || !extension_loaded('gd')) {
        return;
    }
    
    $captchachars = get_setting('captchachars');
    $length = strlen($captchachars) - 1;
    $code = '';
    for ($i = 0; $i < 5; ++$i) {
        $code .= $captchachars[mt_rand(0, $length)];
    }
    
    $randid = mt_rand();
    $time = time();
    if (MEMCACHED) {
        $memcached->set(DBNAME . '-' . PREFIX . "captcha-$randid", $code, get_setting('captchatime'));
    } else {
        $stmt = $db->prepare('INSERT INTO ' . PREFIX . 'captcha (id, time, code) VALUES (?, ?, ?);');
        $stmt->execute([$randid, $time, $code]);
    }
	echo'<div id="container-captcha">';
    echo '<span id="captcha">';
    
    // Create image dimensions - larger for better visibility
    $img_width = 210;
    $img_height = 60;
    
    // Check for GD version and create base image
    if (function_exists('imagecreatetruecolor')) {
        $im = imagecreatetruecolor($img_width, $img_height);
    } else {
        $im = imagecreate($img_width, $img_height);
    }
    
    if (!$im) {
        echo 'GD Library Error';
        return;
    }
    
    // Fill background with dark color
    $bg_color = imagecolorallocate($im, 30, 30, 30);
    imagefill($im, 0, 0, $bg_color);
    
    // Add visual noise based on difficulty
    if ($difficulty >= 2) {
        // Draw random elements based on difficulty
        $choice = mt_rand(0, 2);
        
        if ($choice == 0) {
            // Draw lines
            for ($i = 10; $i < $img_width; $i += 10) {
                $color = imagecolorallocate($im, mt_rand(100, 180), mt_rand(100, 180), mt_rand(100, 180));
                imageline($im, $i, 0, $i, $img_height, $color);
            }
            for ($i = 10; $i < $img_height; $i += 10) {
                $color = imagecolorallocate($im, mt_rand(100, 180), mt_rand(100, 180), mt_rand(100, 180));
                imageline($im, 0, $i, $img_width, $i, $color);
            }
        } elseif ($choice == 1) {
            // Draw circles
            $circles = $img_width * $img_height / 20;
            for ($i = 0; $i <= $circles; ++$i) {
                $color = imagecolorallocate($im, mt_rand(100, 180), mt_rand(100, 180), mt_rand(100, 180));
                $pos_x = mt_rand(1, $img_width);
                $pos_y = mt_rand(1, $img_height);
                $circ_width = ceil(mt_rand(1, $img_width) / 3);
                $circ_height = mt_rand(1, $img_height);
                imagearc($im, $pos_x, $pos_y, $circ_width, $circ_height, 0, mt_rand(200, 360), $color);
            }
        } else {
            // Draw squares
            $square_count = 20;
            for ($i = 0; $i <= $square_count; ++$i) {
                $color = imagecolorallocate($im, mt_rand(100, 180), mt_rand(100, 180), mt_rand(100, 180));
                $pos_x = mt_rand(1, $img_width);
                $pos_y = mt_rand(1, $img_height);
                $sq_width = $sq_height = mt_rand(5, 10);
                $pos_x2 = $pos_x + $sq_height;
                $pos_y2 = $pos_y + $sq_width;
                imagefilledrectangle($im, $pos_x, $pos_y, $pos_x2, $pos_y2, $color);
            }
        }
    }
    
    // Add dots for all difficulty levels
    $dot_count = $img_width * $img_height / 8;
    for ($i = 0; $i <= $dot_count; ++$i) {
        $color = imagecolorallocate($im, mt_rand(150, 200), mt_rand(150, 200), mt_rand(150, 200));
        imagesetpixel($im, mt_rand(0, $img_width), mt_rand(0, $img_height), $color);
    }
    
    // Write the code to the image - using much larger font size for better visibility
    $spacing = $img_width / strlen($code);
    for ($i = 0; $i < strlen($code); ++$i) {
        // Set random bright color for each character
        $r = mt_rand(200, 255);
        $g = mt_rand(200, 255);
        $b = mt_rand(200, 255);
        $color = imagecolorallocate($im, $r, $g, $b);
        
        // Calculate position - centered better for larger characters
        $pos_x = ($spacing / 4) + $i * $spacing;
        $pos_y = ($img_height / 2) - 10 + mt_rand(-5, 5);
        
        // Add shadow effect
        $shadow_x = $pos_x + 2;
        $shadow_y = $pos_y + 2;
        $shadow_color = imagecolorallocate($im, max($r - 100, 0), max($g - 100, 0), max($b - 100, 0));
        
		$font_path = __DIR__ . '/../font/Arial.ttf'; 
		$font_size = 16; 
	
		imagettftext($im, $font_size, 0, $shadow_x, $shadow_y + $font_size, $shadow_color, $font_path, $code[$i]);
		imagettftext($im, $font_size, 0, $pos_x, $pos_y + $font_size, $color, $font_path, $code[$i]);
		
        
        if ($difficulty >= 3 && function_exists('imagerotate')) {
            // Advanced effects for higher difficulties would go here
            // But we'll keep it simple for compatibility
        }
    }

    // Draw border around the image
    $border_color = imagecolorallocate($im, 80, 80, 80);
    imagerectangle($im, 0, 0, $img_width - 1, $img_height - 1, $border_color);
    
    // Output the image
    ob_start();
    imagepng($im);
    imagedestroy($im);
    echo '<img alt="" width="' . $img_width . '" height="' . $img_height . '" src="data:image/png;base64,' . base64_encode(ob_get_clean()) . '">';

    echo '</span>';
	echo hidden('challenge', $randid) . '<input type="text" name="captcha" size="10" autocomplete="off" placeholder="' . _('captcha') . '" style="font-size: 16px; padding: 8px;">';

	echo'</div>';
}
