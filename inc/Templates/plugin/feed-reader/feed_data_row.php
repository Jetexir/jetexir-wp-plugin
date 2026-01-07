<?php
defined( 'ABSPATH' ) or die();

if ( ! isset( $args ) ) {
  return;
}

echo '<span class="jetexir-feed-' . esc_html( $args['field'] ) . '">' . wp_kses_post( $args['value'] ) . '</span>';
