<?php
defined( 'ABSPATH' ) or die();

if ( ! isset( $args ) ) {
  return;
}

echo '<ul class="jetexir-list-links">';
foreach ( $args['items'] as $feedItem ) {
  echo '<li>' . wp_kses_post( $feedItem ) . '</li>';
}
echo '</ul>';
