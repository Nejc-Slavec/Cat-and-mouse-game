(function ($) {
    if (!NodeList.prototype.forEach) {
        NodeList.prototype.forEach = Array.prototype.forEach;
        HTMLCollection.prototype.forEach = Array.prototype.forEach; // Because of https://bugzilla.mozilla.org/show_bug.cgi?id=14869

    }

    if (!Array.from) {
        Array.from = function (object) {
            return [].slice.call(object);
        };
    }

    $(function () {

        // WebKit contentEditable focus bug workaround:
        if (/AppleWebKit\/([\d.]+)/.exec(navigator.userAgent)) {
            var editableFix = $('<input style="position:fixed;z-index-1;width:1px;height:1px;border:none;margin:0;padding:0;" tabIndex="-1">').appendTo('html');

            $('body').on('blur', "i.fa[contenteditable]", function () {
                editableFix[0].setSelectionRange(0, 0);
                editableFix.blur();
            });
        }
    });


})(jQuery);



wp.customize.bind('loading-initiated', function () {
     top.CP_Customizer.showLoader();
});

jQuery(document).ready(function ($) {

    if (wp.customize && wp.customize.mutationObserver) {
        wp.customize.mutationObserver.disconnect();
    }
    // if page is not maintainable with companion do not decorate
    if (!top.CP_Customizer.preview.data().maintainable) {
        return;
    }

	jQuery('a').on('click', function(event) {
	  event.preventDefault();
	  event.stopPropagation();
	});

});
