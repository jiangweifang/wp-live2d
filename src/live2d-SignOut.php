<?php
require_once(rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/wp-load.php');
delete_option( 'live_2d_settings_user_token' );
?>