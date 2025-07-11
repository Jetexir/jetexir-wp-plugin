<?php
defined( 'ABSPATH' ) or die();

if ( ! isset( $args ) ) {
	return;
}

echo '<ul class="wa-list-links">';
foreach ( $args['items'] as $feedItem ) {
	echo '<li>' . $feedItem . '</li>';
}
echo '</ul>';