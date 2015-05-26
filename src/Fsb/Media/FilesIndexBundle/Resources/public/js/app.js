(function ($) {
    $(window).on('load', function (event) {
    		// Init Bootstrap
    		$('[data-toggle="tooltip"]').tooltip();
    		$('[data-toggle="popover"]').popover();

        // Init material design
        $.material.init();
    });
})(window.jQuery);