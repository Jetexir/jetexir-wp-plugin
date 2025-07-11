<?php
defined( 'ABSPATH' ) or die();

if ( ! isset( $args ) ) {
	return;
}

if ( empty( $args['link'] ) ) {
	echo $args['title'];
} else {
	echo '<a href="' . $args['link'] . '" class="wa-feed-link" target="_blank">' . $args['title'] . '</a>';
}
