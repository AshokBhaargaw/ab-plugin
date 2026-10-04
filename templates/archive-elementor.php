<?php
/**
 * Elementor Archive Template Wrapper
 *
 * Loaded by AB_Archive_Template_Manager when a Custom Post Type archive has an
 * assigned Elementor template. Renders the template between standard site header
 * and footer, preserving Astra Child Theme and Header/Footer Builder compatibility.
 *
 * @package AB_Addon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<div class="content-area ab-archive-template-area" id="primary">
	<main id="main" class="site-main" role="main">
		<?php echo \AB_Archive_Template_Manager::render_active_archive_template(); ?>
	</main>
</div>

<?php
get_footer();
