<?php
/**
 * Single certificate dynamic styles.
 *
 * Output in the header, via the `wp_head` action.
 *
 * @package LifterLMS/Templates/Certificates
 *
 * @since [version]
 * @version [version]
 *
 * @param LLMS_User_Certificate $certificate      Certificate object.
 * @param string                $width            Width (with unit) accounting for the orientation value.
 * @param string                $height           Height (with unit) accounting for the orientation value.
 * @param string                $background_color Background color value.
 * @param string                $background_img   Image source URL for the background image.
 * @param string                $padding          Internal margin value with units, ready to be used in CSS.
 * @param array[]               $fonts            Array of custom certificate fonts used by the certificate.
 */

defined( 'ABSPATH' ) || exit;
?>
<style type="text/css">
	html, body {
		background-color: <?php echo $background_color; ?> !important;
	}
	.llms-certificate-container {
		background-image: <?php echo "url( {$background_img} )"; ?>;
		height: <?php echo $height; ?>;
		padding: <?php echo $padding; ?>;
		width: <?php echo $width; ?>;
	}
	<?php foreach ( $fonts as $font ) : ?>
	.has-<?php echo $font['id']; ?>-font-family {
		font-family: <?php echo $font['css']; ?>
	}
	<?php endforeach; ?>
</style>
<style type="text/css" media="print">
	@page {
		size: <?php echo $width; ?> <?php echo $height; ?>;
		margin: 0;
	}
</style>
