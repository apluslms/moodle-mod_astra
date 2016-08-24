/**
 * A+ table row filter as an AMD module for Moodle.
 * In an HTML page, run the following JS code once to activate it:
 * $('.filtered-table').aplusTableFilter();
 * 
 * Source: A+ (a-plus/assets/js/aplus.js)
 * License: GNU GPL v3
 * 
 * @module mod_astra/aplus_tablefilter
 */
define(['jquery'], function(jQuery) {

/**
 * Table row filter.
 */
(function($, window, document, undefined) {
    "use strict";

    var pluginName = "aplusTableFilter";
    var defaults = {};

    function AplusTableFilter(element, options) {
        this.element = $(element);
        this.filters = null;
        this.timeout = null;
        if (this.element.prop("tagName") == "TABLE") {
            this.settings = $.extend({}, defaults, options);
            this.init();
        }
    }

    $.extend(AplusTableFilter.prototype, {

        init: function() {
          var columnCount = 0;
          this.element.find('thead').find('tr').each(function() {
            var count = $(this).find('th').size();
            columnCount = count > columnCount ? count : columnCount;
          });

          var self = this;
          var filterDelay = function(event) {
            var input = $(this);
            clearTimeout(self.timeout);
            self.timeout = setTimeout(function() {
              self.filterColumn(input);
            }, 500);
          };

          this.filters = [];
          var filterRow = $('<tr></tr>');
          for (var i = 0; i < columnCount; i++) {
            this.filters.push('');
            var filterInput = $('<input type="text" data-column="'+i+'">')
              .on('keyup', filterDelay).on('change', filterDelay);
            var filterCell = $('<td></td>');
            filterCell.append(filterInput);
            filterRow.append(filterCell);
          }
          this.element.find('thead').append(filterRow);
        },

        filterColumn: function(input) {
          var column = input.attr('data-column');
          var query = input.val();
          this.filters[column] = query.trim();
          this.filterTable();
        },

        filterTable: function() {
          var self = this;
          this.element.find('tbody').find('tr').hide().filter(function() {
            var pass = true;
            $(this).find('td').each(function(i) {
              if (self.filters[i] && $(this).text().toLowerCase().indexOf(self.filters[i].toLowerCase()) < 0) {
                pass = false;
                return false;
              }
            });
            return pass;
          }).show();
        }
    });

    $.fn[pluginName] = function(options) {
        return this.each(function() {
            if (!$.data(this, "plugin_" + pluginName)) {
                $.data(this, "plugin_" + pluginName, new AplusTableFilter(this, options));
            }
        });
    };
})(jQuery, window, document);

return {}; // for AMD, no names are exported to the outside
}); // end define
