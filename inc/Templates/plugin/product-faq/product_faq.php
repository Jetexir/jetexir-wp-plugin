<?php
defined( 'ABSPATH' ) or die();

if ( ! isset( $args ) ) {
	return;
}

echo '<h2>' . esc_html( $args['title'] ) . '</h2>';

if ( ! empty( $args['items'] ) ) {
	echo '<div class="asfowoo-faqs-wrap">';
	foreach ( $args['items'] as $faq ) {
		if ( empty( $faq['question'] ) || empty( $faq['answer'] ) ) {
			continue;
		}

		echo '<div class="asfowoo-faq-item">';
		echo '<button class="asfowoo-faq-question" type="button">' . wp_kses_post( $faq['question'] . $args['icon'] ) . '</button>';
		echo '<div class="asfowoo-faq-answer">' . wp_kses_post( $faq['answer'] ) . '</div>';
		echo '</div>';
	}
	echo '</div>';
}