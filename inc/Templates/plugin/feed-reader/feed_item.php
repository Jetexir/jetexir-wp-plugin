<?php
defined( 'ABSPATH' ) or die();

if ( ! isset( $args ) ) {
  return;
}

if ( empty( $args['link'] ) ) {
  echo wp_kses_post( $args['title'] );
} else {
  echo '<a href="' . esc_url( $args['link'] ) . '" class="asfowoo-feed-link" target="_blank">' . wp_kses_post( $args['title'] ) . '</a>';
}
