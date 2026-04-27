<?php
/**
 * The template for displaying singular post-types: posts, pages and user-defined custom post types.
 *
 * @package HelloElementor
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

while ( have_posts() ) :
	the_post();
	?>

<main id="content" <?php post_class( 'site-main' ); ?>>
	<style>
.site-main {
    padding: 40px 20px;
}

.page-header {
    padding: 40px 20px 20px;
}

.page-content {
    padding: 20px;
}

.featured-image img {
    width: 100%;
    height: 550px;
    object-fit: cover;
}
		h1.entry-title{
			margin-bottom: 25px;
		}

@media (max-width: 768px) {
    .site-main {
        padding: 20px 15px;
    }
}
</style>

	<?php if ( apply_filters( 'hello_elementor_page_title', true ) ) : ?>
	<div class="page-header">
		<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>

		<?php if ( has_post_thumbnail() ) : ?>
			<div class="featured-image">
				<?php the_post_thumbnail('full'); ?>
			</div>
		<?php endif; ?>
	</div>
<?php endif; ?>

	<div class="page-content">
		<?php the_content(); ?>

		<?php wp_link_pages(); ?>

		<?php if ( has_tag() ) : ?>
		<div class="post-tags">
			<?php the_tags( '<span class="tag-links">' . esc_html__( 'Tagged ', 'hello-elementor' ), ', ', '</span>' ); ?>
		</div>
		<?php endif; ?>
	</div>

	

</main>

	<?php
endwhile;
