/**
 * A+ select widget (multiple select with search and remove) as an AMD module for Moodle.
 * In an HTML page, run the following JS code once to activate it:
 * $('.search-select').aplusSearchSelect();
 * (The targeted element should be a <select> element.)
 * 
 * @module mod_stratumtwo/aplus_searchselect
 */
define(['jquery'], function(jQuery) {

/**
 * Multiple select as search and remove.
 * Source: A+ (a-plus/assets/js/aplus.js)
 */
(function($, window, document, undefined) {
    "use strict";

    var pluginName = "aplusSearchSelect";
    var defaults = {
        widget_selector: "#search-select-widget",
        field_selector: 'input[type="text"]',
        search_selector: '.dropdown-toggle',
        result_selector: '.search-options',
        selection_selector: '.search-selected',
    };

    function AplusSearchSelect(element, options) {
        this.element = $(element);
        this.timeout = null;
        if (this.element.prop("tagName") == "SELECT" && this.element.prop("multiple")) {
            this.settings = $.extend({}, defaults, options);
            this.init();
        }
    }

    $.extend(AplusSearchSelect.prototype, {

        init: function() {
            this.widget = $(this.settings.widget_selector).clone()
                .removeAttr("id").removeClass("hide").insertBefore(this.element);
            this.element.hide();
            var self = this;
            this.selection = this.widget.find(this.settings.selection_selector);
            this.selection_li = this.selection.find("li").remove();
            this.element.find("option:selected").each(function(index) {
                self.add_selection($(this).attr("value"), $(this).text());
            });
            this.result = this.widget.find(this.settings.result_selector);
            this.field = this.widget.find(this.settings.field_selector)
                .on("keypress", function(event) {
                    if (event.keyCode == 13) {
                        event.preventDefault();
                        self.search_options(true);
                    }
                }).on("keyup", function(event) {
                    if (event.keyCode != 13) {
                        clearTimeout(self.timeout);
                        self.timeout = setTimeout(function() {
                            self.search_options(true);
                            self.field.focus();
                        }, 500);
                    }
                });
            this.search = this.widget.find(this.settings.search_selector)
                .on("show.bs.dropdown", function(event) {
                    self.search_options();
                });
            this.element.parents("form").on("submit", function(event) {
                self.finish();
            });
        },

        search_options: function(show_dropdown) {
            if (show_dropdown && this.result.is(":visible") === false) {
                this.search.find("button").dropdown("toggle");
                return;
            }
            this.result.find("li:not(.not-found)").remove();
            this.result.find("li.not-found").hide();
            var selector = "option";
            var query = this.field.val().trim();
            if (query.length > 0) {
                selector += ":contains(" + this.field.val() + ")";
            }
            var opt = this.element.find(selector);
            if (opt.size() === 0) {
                this.result.find("li.not-found").show();
            } else {
                var self = this;
                opt.slice(0,20).each(function(index) {
                    var li = $('<li><a data-value="'+$(this).attr("value")+'">'+$(this).text()+'</a></li>');
                    li.find("a").on("click", function(event) {
                        self.add_selection($(this).attr("data-value"), $(this).text());
                    });
                    self.result.append(li);
                });
            }
        },

        add_selection: function(value, name) {
            if (this.selection.find('[data-value="'+value+'"]').size() === 0) {
                var li = this.selection_li.clone();
                var self = this;
                li.find(".name").text(name);
                li.find("button").attr("data-value", value).on('click', function(event) {
                    $(this).parent("li").remove();
                });
                this.selection.append(li);
            }
        },

        finish: function() {
            this.widget.remove();
            var select = this.element.show();
            select.find("option:selected").prop("selected", false);
            this.selection.find("button").each(function(index) {
                select.find('option[value="'+$(this).attr("data-value")+'"]').prop("selected", true);
            });
        }
    });

    $.fn[pluginName] = function(options) {
        return this.each(function() {
            if (!$.data(this, "plugin_" + pluginName)) {
                $.data(this, "plugin_" + pluginName, new AplusSearchSelect(this, options));
            }
        });
    };
})(jQuery, window, document);

return {}; // for AMD, no names are exported to the outside
}); // end define
