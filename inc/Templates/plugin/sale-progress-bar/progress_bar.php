<?php
defined( 'ABSPATH' ) or die();

if ( ! isset( $args ) ) {
  return;
}

$style = [];
if ( ! empty( $args['progress_bar_height'] ) ) {
  $style[] = '--jetexir-progress-bar-height: ' . esc_html( $args['progress_bar_height'] ) . 'px';
}
if ( ! empty( $args['progress_bar_bg_color'] ) ) {
  $style[] = '--jetexir-progress-bar-bg-color: ' . esc_html( $args['progress_bar_bg_color'] );
}
?>

<div class="jetexir-sale-progress-bar"
     style="<?php echo wp_kses_post( implode( '; ', $style ) ); ?>">
  <div class="jetexir-spb-stock-info">
    <div class="jetexir-spb-total-sold">
      <?php echo esc_html( $args['sold_title'] ) ?>
      <span><?php echo esc_html( $args['sold'] ) ?></span>
    </div>
    <div class="jetexir-spb-current-stock">
      <?php echo esc_html( $args['remaining_title'] ) ?>
      <span><?php echo esc_html( $args['stock'] ) ?></span>
    </div>
  </div>
  <div class="jetexir-spb-progress-area">
    <div class="jetexir-spb-progress-bar" style="width:<?php echo esc_html( $args['sold_percent'] ) ?>%;"></div>
  </div>
</div>
