/**
 * Open submitted file in a modal.
 * Source: A+ (a-plus/assets/js/aplus.js)
 */
(function($, window, document, undefined) {
 	"use strict";

    var pluginName = "aplusFileModal";
    var defaults = {
        modal_selector: "#default-modal",
        title_selector: ".modal-title",
        content_selector: ".modal-body"
    };

    function AplusFileModal(element, options) {
		this.element = $(element);
		this.settings = $.extend({}, defaults, options);
		this.init();
	}

    $.extend(AplusFileModal.prototype, {
		init: function() {
            var link  = this.element;
            var settings = this.settings;
            link.on("click", function(event) {
                event.preventDefault();
                $.get(link.attr("href"), function(data) {
                    var modal = $(settings.modal_selector);
                    var text = $("<pre/>").text(data);
                    modal.find(settings.title_selector).text(link.text());
                    modal.find(settings.content_selector).html(text);
                    hljs.highlightBlock(text[0]);

                    // Add line numbers.
                    var lines = text.html().split(/\r\n|\r|\n/g);
                    var list = $("<table/>").addClass("src");
                    for (var i = 1; i <= lines.length; i++) {
                        list.append('<tr><td class="num">' + i + '</td><td class="src">' + lines[i - 1] + '</td></tr>');
                    }
                    text.html(list);

        			modal.modal("show");
                });
            });
        }
	});

    $.fn[pluginName] = function(options) {
		return this.each(function() {
			if (!$.data(this, "plugin_" + pluginName)) {
				$.data(this, "plugin_" + pluginName, new AplusFileModal(this, options));
			}
		});
	};
})(jQuery, window, document);

$(function() {
  /* enable Bootstrap tooltips */
  $('[data-toggle="tooltip"]').tooltip();

  /* Enable file modal */
  $('.file-modal').aplusFileModal();
});
