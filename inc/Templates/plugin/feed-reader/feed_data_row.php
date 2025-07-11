<?php
defined( 'ABSPATH' ) or die();

if ( ! isset( $args ) ) {
	return;
}

echo '<span class="wa-feed-' . $args['field'] . '">' . $args['value'] . '</span>';