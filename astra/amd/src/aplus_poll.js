/**
 * Submission status poller from A+. It polls Moodle about the status of
 * a submission, i.e., if it has been graded yet. The JS code is wrapped
 * into an ES6 module for Moodle.
 * 
 * In an HTML page, run the following JS code once to activate it:
 * // Initialize the submission status poller
 * // if $(".exercise-wait") is the poller element in the page
 * jQuery(function() { jQuery.aplusExerciseDetectWaits(); });
 * // otherwise
 * jQuery(function() { $(".elementselector").aplusExercisePoll(callback); });
 * 
 * Source: A+ (a-plus/exercise/static/exercise/poll.js)
 * License: GNU GPL v3
 * 
 * @module mod_astra/aplus_poll
 */
import jQuery from 'jquery';

/**
 * Polling for exercise status.
 *
 */
;(function($, window, document, undefined) {
	"use strict";

	var pluginName = "aplusExercisePoll";
	var defaults = {
		poll_url_attr: "data-poll-url",
		ready_url_attr: "data-ready-url", // new: not in A+ version
		poll_delays: [2,3,5,5,5,10,10,10,10],
		message_selector: ".progress-bar",
		message_attr: {
			error: "data-msg-error",
			timeout: "data-msg-timeout"
		}
	};

	function AplusExercisePoll(element, callback, options) {
		this.element = $(element);
		this.callback = callback;
		this.settings = $.extend({}, defaults, options);
		this.url = null;
		this.count = 0;
		this.init();
	}

	$.extend(AplusExercisePoll.prototype, {

		/**
		 * Constructs contained exercise elements.
		 */
		init: function() {
			this.element.show();
			this.url = this.element.attr(this.settings.poll_url_attr);
			this.schedule();
		},

		poll: function(firstTime) {
			var poller = this;
			$.ajax(this.url, {dataType: "html"})
				.fail(function() {
					poller.message("error");
					poller.ready(true);
				})
				.done(function(data) {
					poller.count++;
					if (data.trim() === "ready" || data.trim() === "error" || data.trim() === "unofficial") {
						poller.ready();
					} else if (poller.element.is(":visible")) {
						if (poller.count < poller.settings.poll_delays.length) {
							poller.schedule();
						} else {
							poller.message("timeout");
							poller.ready(true);
						}
					}
				});
		},

		schedule: function() {
			var poller = this;
			setTimeout(function() { poller.poll(); },
				this.settings.poll_delays[this.count] * 1000);
		},

		ready: function(error) {
			// If there were no errors, the error parameter is undefined.
			//this.element.hide();

			// For active elements the element to which the poll plugin is attached remains the same, so to
			// be able to submit the same form several times the plugin data needs to be removed when the
			// evaluation and polling is finished.
			if ($.data(this.element.get(0), "plugin_" + pluginName)) $.removeData(this.element.get(0), "plugin_" + pluginName);

			//var suburl = this.url.substr(0, this.url.length - "poll/".length); // changed from A+
			// added a data attribute for reading the final target URL since in Moodle it can not be a substring of the poll URL
			var ready_url = this.element.attr(this.settings.ready_url_attr);
			if (this.callback) {
				this.callback(ready_url, error);
			} else {
				window.location = ready_url;
			}
	    },

		message: function(messageType) {
			this.element.find(this.settings.message_selector).removeClass("progress-bar-animated")
				.text(this.element.attr(this.settings.message_attr[messageType]));
			if (this.element.attr('data-aplus-active-element')) {
				var message = "There was an error while evaluating the element.";
				if (messageType == "timeout") {
					message = "Evaluation was timed out.";
				}
				var res_elem = this.element.find(".ae_result").text(message);
				if (res_elem.height() === 0) res_elem.height("auto");
				if ($.data(this.element.get(0), "plugin_" + pluginName)) {
					$.removeData(this.element.get(0), "plugin_" + pluginName);
				}
			} else if (messageType == "error") {
				this.element.find(this.settings.message_selector).addClass("bg-danger");
			}
		},
	});

	$.fn[pluginName] = function(callback, options) {
		return this.each(function() {
			if (!$.data(this, "plugin_" + pluginName)) { // This apparently blocks re-polling for a same exercise multiple times
				$.data(this, "plugin_" + pluginName, new AplusExercisePoll(this, callback, options));
			}
		});
	};

	$.aplusExerciseDetectWaits = function(callback, selector) {
		selector = selector || ".exercise-wait";
		var $selector = $(selector);
		if ($selector.length) {
			$selector.aplusExercisePoll(callback);
			return true;
		}
		return false;
	};

})(jQuery, window, document);

