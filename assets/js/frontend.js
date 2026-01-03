(function($) {
    'use strict';
    
    var Headrix = {
        init: function() {
            this.bindEvents();
            this.initSticky();
            this.initMobileMenu();
        },
        
        bindEvents: function() {
            // رویدادهای کلیک
            $(document).on('click', this.handleDocumentClick);
        },
        
        handleDocumentClick: function(e) {
            // بستن منوی موبایل اگر بیرون کلیک شود
            var $target = $(e.target);
            if (!$target.closest('.hdrx-mobile-menu').length && 
                !$target.closest('.hdrx-mobile-toggle').length &&
                $('.hdrx-mobile-menu').hasClass('active')) {
                Headrix.closeMobileMenu();
            }
        },
        
        initSticky: function() {
            if (!HDRX_SETTINGS.stickyEnabled) {
                return;
            }
            
            var header = $('#headrix-header');
            if (!header.length) return;
            
            var offset = header.offset().top;
            var lastScroll = 0;
            
            $(window).on('scroll', function() {
                var scroll = $(this).scrollTop();
                
                if (scroll > offset) {
                    header.addClass('sticky');
                    $('body').addClass('hdrx-has-sticky');
                    
                    // پنهان/نمایش هنگام اسکرول
                    if (scroll > lastScroll) {
                        header.addClass('hdrx-header-hidden');
                    } else {
                        header.removeClass('hdrx-header-hidden');
                    }
                } else {
                    header.removeClass('sticky');
                    $('body').removeClass('hdrx-has-sticky');
                    header.removeClass('hdrx-header-hidden');
                }
                
                lastScroll = scroll;
            });
        },
        
        initMobileMenu: function() {
            var $toggle = $('.hdrx-mobile-toggle');
            var $close = $('.hdrx-mobile-close');
            var $menu = $('.hdrx-mobile-menu');
            var $overlay = $('.hdrx-mobile-menu-overlay');
            
            $toggle.on('click', function(e) {
                e.preventDefault();
                Headrix.openMobileMenu();
            });
            
            $close.on('click', function(e) {
                e.preventDefault();
                Headrix.closeMobileMenu();
            });
            
            $overlay.on('click', function() {
                Headrix.closeMobileMenu();
            });
            
            // دسترسی از کیبورد
            $toggle.on('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    Headrix.openMobileMenu();
                }
            });
        },
        
        openMobileMenu: function() {
            $('.hdrx-mobile-menu').addClass('active');
            $('.hdrx-mobile-menu-overlay').fadeIn(300);
            $('body').css('overflow', 'hidden');
        },
        
        closeMobileMenu: function() {
            $('.hdrx-mobile-menu').removeClass('active');
            $('.hdrx-mobile-menu-overlay').fadeOut(300);
            $('body').css('overflow', '');
        }
    };
    
    // راه‌اندازی وقتی DOM آماده است
    $(document).ready(function() {
        Headrix.init();
    });
    
})(jQuery);