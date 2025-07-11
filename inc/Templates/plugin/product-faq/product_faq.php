<?php
defined( 'ABSPATH' ) or die();

if ( ! isset( $args ) ) {
	return;
}

echo '<h2>' . $args['title'] . '</h2>';

if ( ! empty( $args['items'] ) ) {
	echo '<div class="wa-faqs-wrap">';
	foreach ( $args['items'] as $faq ) {
		if ( empty( $faq['question'] ) || empty( $faq['answer'] ) ) {
			continue;
		}

		echo '<div class="wa-faq-item">';
		echo '<button class="wa-faq-question" type="button">' . $faq['question'] . $args['icon'] . '</button>';
		echo '<div class="wa-faq-answer">' . $faq['answer'] . '</div>';
		echo '</div>';
	}
	echo '</div>';
}