<?php
defined( 'ABSPATH' ) or die();

if ( ! isset( $args ) ) {
  return;
}

$style = [];
if ( ! empty( $args['progress_bar_height'] ) ) {
  $style[] = '--asfowoo-progress-bar-height: ' . esc_html( $args['progress_bar_height'] ) . 'px';
}
if ( ! empty( $args['progress_bar_bg_color'] ) ) {
  $style[] = '--asfowoo-progress-bar-bg-color: ' . esc_html( $args['progress_bar_bg_color'] );
}
?>

<div class="asfowoo-sale-progress-bar"
     style="<?php echo wp_kses_post( implode( '; ', $style ) ); ?>">
  <div class="asfowoo-spb-stock-info">
    <div class="asfowoo-spb-total-sold">
      <?php echo esc_html( $args['sold_title'] ) ?>
      <span><?php echo esc_html( $args['sold'] ) ?></span>
    </div>
    <div class="asfowoo-spb-current-stock">
      <?php echo esc_html( $args['remaining_title'] ) ?>
      <span><?php echo esc_html( $args['stock'] ) ?></span>
    </div>
  </div>
  <div class="asfowoo-spb-progress-area">
    <div class="asfowoo-spb-progress-bar" style="width:<?php echo esc_html( $args['sold_percent'] ) ?>%;"></div>
  </div>
</div>
