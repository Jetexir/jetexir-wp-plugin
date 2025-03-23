jQuery(document).ready(function ($) {
    let relatedProducts = $('.related .products');

    if (relatedProducts.length === 0)
        return;

    relatedProducts.addClass('wa-related-products owl-carousel owl-theme');

    relatedProducts.owlCarousel({
        rtl: WooAssistant.direction === 'rtl',
        loop: WooAssistantProductRelated.loop === '1',
        center: WooAssistantProductRelated.center === '1',
        margin: parseInt(WooAssistantProductRelated.margin),
        nav: WooAssistantProductRelated.arrow === '1',
        dots: WooAssistantProductRelated.dots === '1',
        autoplay: WooAssistantProductRelated.autoplay === '1',
        autoplayTimeout: parseInt(WooAssistantProductRelated.autoplayTimeout),
        autoplayHoverPause: WooAssistantProductRelated.autoplay === '1',
        responsiveClass: true,
        lazyLoad: true,
        navText: ['<i class="wa-icon-chevron-left"></i>', '<i class="wa-icon-chevron-right"></i>'],
        responsive: {
            0: {
                items: parseInt(WooAssistantProductRelated.mobileLimit),
            },
            600: {
                items: parseInt(WooAssistantProductRelated.tabletLimit),
            },
            1200: {
                items: parseInt(WooAssistantProductRelated.desktopLimit),
            }
        }
    })
});