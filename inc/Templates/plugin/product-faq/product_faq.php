<?php
defined( 'ABSPATH' ) or die();

if ( ! isset( $args ) ) {
  return;
}

echo '<h2>' . esc_html( $args['title'] ) . '</h2>';

if ( ! empty( $args['items'] ) ) {
  echo '<div class="jetexir-faqs-wrap">';
  foreach ( $args['items'] as $faq ) {
    if ( empty( $faq['question'] ) || empty( $faq['answer'] ) ) {
      continue;
    }

    echo '<div class="jetexir-faq-item">';
    echo '<button class="jetexir-faq-question" type="button">' . wp_kses_post( $faq['question'] . $args['icon'] ) . '</button>';
    echo '<div class="jetexir-faq-answer">' . wp_kses_post( $faq['answer'] ) . '</div>';
    echo '</div>';
  }
  echo '</div>';
}
