<?php
defined( 'ABSPATH' ) or die();

if ( ! isset( $args ) ) {
	return;
}

echo '<ul class="asfowoo-list-links">';
foreach ( $args['items'] as $feedItem ) {
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '<li>' . $feedItem . '</li>';
}
echo '</ul>';