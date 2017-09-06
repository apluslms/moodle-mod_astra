/**
 * A+ modal dialog classes.
 * 
 * Source: A+ (a-plus/assets/js/aplus.js)
 * License: GNU GPL v3
 * 
 * @module mod_astra/aplus_modal
 */
define(['jquery', 'core/event', 'mod_astra/aplus_highlightcode', 'theme_boost/dropdown', 'theme_boost/modal'], function(jQuery, moodleEvent) {

/**
 * Handle common modal dialog.
 */
(function($, window, document, undefined) {
  //"use strict";

  var pluginName = "aplusModal";
  var defaults = {
    loader_selector: ".modal-progress",
    loader_text_selector: ".progress-bar",
    title_selector: ".modal-title",
    content_selector: ".modal-body",
    error_message_attribute: "data-msg-error",
  };

  function AplusModal(element, options) {
    this.element = $(element);
    this.settings = $.extend({}, defaults, options);
    this.init();
  }

  $.extend(AplusModal.prototype, {

    init: function() {
      this.loader = this.element.find(this.settings.loader_selector);
      this.loaderText = this.loader.find(this.settings.loader_text_selector);
      this.title = this.element.find(this.settings.title_selector);
      this.content = this.element.find(this.settings.content_selector);
      this.messages = {
        loading: this.loaderText.text(),
        error: this.loaderText.attr(this.settings.error_message_attribute)
      };
    },

    run: function(command, data) {
      switch(command) {
        case "open":
          this.open(data);
          break;
        case "error":
          this.showError(data);
          break;
        case "content":
          this.showContent(data);
          break;
      }
    },

    open: function(data) {
      this.title.hide();
      this.content.hide();
      this.loaderText
        .removeClass('bg-danger').addClass('progress-bar-animated')
        .text(data || this.messages.loading);
      this.loader.show();
      this.element.on("hidden.bs.modal", function(event) {
        $(".dropdown-toggle").dropdown();
      });
      this.element.modal("show");
    },

    showError: function(data) {
      this.loaderText
        .removeClass('progress-bar-animated').addClass('bg-danger')
        .text(data || this.messages.error);
    },

    showContent: function(data) {
      this.loader.hide();
      if (data.title) {
        this.title.text(data.title);
        this.title.show();
      }
      if (data.content instanceof jQuery) {
        this.content.empty().append(data.content);
      } else {
        this.content.html(data.content);
      }
      this.content.show();
      return this.content;
    }
  });

  $.fn[pluginName] = function(command, data, options) {
    return this.each(function() {
      var modal = $.data(this, "plugin_" + pluginName);
      if (!modal) {
        modal = new AplusModal(this, options);
        $.data(this, "plugin_" + pluginName, modal);
      }
      return modal.run(command, data);
    });
  };
})(jQuery, window, document);

/**
 * Open links in a modal.
 */
(function($, moodleEvent, window, document, undefined) {
    //"use strict";

    var pluginName = "aplusModalLink";
    var defaults = {
        modal_selector: "#page-modal",
        file_modal_selector: "#file-modal",
        body_regexp: /<body[^>]*>([\s\S]*)<\/body>/i,
        file: false
    };

  function AplusModalLink(element, options) {
    this.element = $(element);
    this.settings = $.extend({}, defaults, options);
    this.init();
  }

  $.extend(AplusModalLink.prototype, {
    init: function() {
      var link = this.element;
      var settings = this.settings;
      link.on("click", function(event) {
        event.preventDefault();
        var url = link.attr("href");
        if (url === "" || url == "#") {
          return false;
        }
        var modal = $(settings.file ? settings.file_modal_selector : settings.modal_selector);
        modal.aplusModal("open");
        $.get(url, function(data) {
          if (settings.file) {
            var text = $("<pre/>").text(data);
            modal.aplusModal("content", {
              title: link.text(),
              content: text,
            });
            text.highlightCode();
          } else {
            var match = data.match(settings.body_regexp);
            if (match !== null && match.length == 2) {
              data = match[1];
            }
            var c = modal.aplusModal("content", { content: data });
            c.find('.file-modal').aplusModalLink({file:true});
            c.find('pre.hljs').highlightCode();
            // changed from A+: render MathJax formulas in the modal content retrieved from the URL
            moodleEvent.notifyFilterContentUpdated(settings.modal_selector);
          }
        }).fail(function() {
          modal.aplusModal("error");
        });
      });
    }
  });

  $.fn[pluginName] = function(options) {
    return this.each(function() {
      if (!$.data(this, "plugin_" + pluginName)) {
        $.data(this, "plugin_" + pluginName, new AplusModalLink(this, options));
      }
    });
  };
})(jQuery, moodleEvent, window, document);

return {}; // for AMD, no names are exported to the outside
}); // end define
