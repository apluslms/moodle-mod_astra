/**
 * A+ chapter learning object with embedded exercises as an ES6 module for Moodle.
 * The exercises are downloaded asynchronously (AJAX) and inserted in the DOM.
 * 
 * In an HTML page, run the following JS code once to activate it:
 * // Construct the page chapter element.
 * jQuery(function() { jQuery("#exercise-page-content").aplusChapter(); });
 * 
 * Source: A+ (a-plus/exercise/static/exercise/chapter.js)
 * License: GNU GPL v3
 * 
 * @module mod_astra/aplus_chapter
 */

import jQuery from 'jquery';
import * as moodleEvent from 'core/event';
import './aplus_poll';
import 'theme_boost/bootstrap/dropdown';
import './aplus_modal';

/** Add CustomEvent for IE 11
 *  Source: A+ (a-plus/assets/js/aplus.js)
 */
(function () {
	if (typeof window.CustomEvent === "function") return false;
	function CustomEvent(event, params) {
		var bubbles = params.bubbles !== undefined ? params.bubbles : false;
		var cancelable = params.cancelable !== undefined ? params.cancelable : false;
		var detail = params.detail !== undefined ? params.detail : undefined;
		var evt = document.createEvent( 'CustomEvent' );
		evt.initCustomEvent(event, bubbles, cancelable, detail);
		return evt;
	}
	CustomEvent.prototype = window.Event.prototype;
	window.CustomEvent = CustomEvent;
})();

/**
 * Chapter element containing number of exercise elements.
 *
 */
;(function($, moodleEvent, window, document, undefined) {
	"use strict";

	var pluginName = "aplusChapter";
	var defaults = {
		chapter_url_attr: "data-aplus-chapter",
		exercise_url_attr: "data-aplus-exercise",
		active_element_attr: "data-aplus-active-element",
		loading_selector: "#loading-indicator",
		quiz_success_selector: "#quiz-success",
		message_attr: {
			load: "data-msg-load",
			submit: "data-msg-submit",
			error: "data-msg-error"
		},
		modal_selector: "#page-modal",
	};

	function AplusChapter(element, options) {
		this.dom_element = element;
		this.element = $(element);
		this.settings = $.extend({}, defaults, options);
		this.ajaxForms = false;
		this.url = null;
		this.modalElement = null;
		this.loader = null;
		this.messages = null;
		this.quizSuccess = null;
		this.aeOutputs = {}; // Add active element outputs to chapter so they can be found by id later.
		this.init();
	}

	$.extend(AplusChapter.prototype, {

		/**
		 * Constructs contained exercise elements.
		 */
		init: function() {
			this.ajaxForms = window.FormData ? true : false;
			this.url = this.element.attr(this.settings.chapter_url_attr);
			this.modalElement = $(this.settings.modal_selector);
			this.loader = $(this.settings.loading_selector);
			this.messages = this.readMessages();
			this.quizSuccess = $(this.settings.quiz_success_selector);
			
			// do not include active element inputs to exercise groups
			this.element.find("[" + this.settings.active_element_attr + "='in']").aplusExercise(this, {input: true});
			
			var exercises = this.element.find("[" + this.settings.exercise_url_attr + "]");
			if (exercises.length > 0) {
				this.dom_element.dispatchEvent(
					new CustomEvent("aplus:chapter-loaded", {bubbles: true}));

				exercises.aplusExercise(this);
				this.exercises = exercises;
				this.exercisesIndex = 0;
				this.exercisesSize = exercises.length;
				this.nextExercise();
			} else {
				var type = 'text/x.aplus-exercise';
				this.dom_element.dispatchEvent(
					new CustomEvent("aplus:exercise-loaded", {bubbles: true, detail: {type: type}}));
				//$.augmentSubmitButton($(".exercise-column"));
				// changed from A+: no group submissions and no group selection UI
				this.dom_element.dispatchEvent(
					new CustomEvent("aplus:exercise-ready", {bubbles: true, detail: {type: type}}));
			}
		},

		nextExercise: function() {
			if (this.exercisesIndex < this.exercisesSize) {
				this.exercises.eq(this.exercisesIndex).aplusExerciseLoad();
				this.exercisesIndex++;
			}
			this.dom_element.dispatchEvent(
				new CustomEvent("aplus:chapter-ready", {bubbles: true}));
		},

		readMessages: function() {
			var messages = {};
			for (var key in this.settings.message_attr) {
				messages[key] = this.loader.attr(this.settings.message_attr[key]);
			}
			return messages;
		},

		cloneLoader: function(msgType) {
			return $(this.settings.loading_selector)
				.clone().removeAttr("id").show();
		},

		openModal: function(message) {
			this.modalElement.aplusModal("open", message);
		},

		modalError: function(message) {
			this.modalElement.aplusModal("error", message);
		},

		modalContent: function(content) {
			this.modalElement.aplusModal("content", { content: content });
			this.renderMath();
		},

		modalSuccess: function(exercise, badge) {
			/*this.modalElement.one("hidden.bs.modal", function(event) {
				$(document.body).animate({
					'scrollTop': exercise.offset().top
				}, 300);
			});*/
			var content = this.quizSuccess.clone()
				.attr("class", exercise.attr("class"))
				.removeClass("exercise")
				.removeAttr("id")
				.show();
			content.find('.badge-placeholder').empty().append(badge);
			if (badge.hasClass("badge-success") || badge.hasClass("badge-warning")) {
				content.find('.btn-success').css('display', 'block');
			} else {
				content.find('.btn-success').hide();
			}
			this.modalContent(content);
		},

		renderMath: function() {
			// changed from A+: Moodle has its own API for accessing MathJax.
			// Trigger Moodle JS event so that MathJax renders new formulas
			// that are inserted into the modal dialog (openModal appends the modal content
			// at runtime).
			// This uses the Moodle filter MathJax loader.
			moodleEvent.notifyFilterContentUpdated(this.settings.modal_selector);
		},
	});

	$.fn[pluginName] = function(options) {
		return this.each(function() {
			if (!$.data(this, "plugin_" + pluginName)) {
				$.data(this, "plugin_" + pluginName, new AplusChapter(this, options));
			}
		});
	};

})(jQuery, moodleEvent, window, document);

/**
 * Exercise element inside chapter.
 *
 */
;(function($, moodleEvent, window, document, undefined) {
	"use strict";

	var pluginName = "aplusExercise";
	var loadName = "aplusExerciseLoad";
	var defaults = {
		quiz_attr: "data-aplus-quiz",
		ajax_attr: "data-aplus-ajax",
		message_selector: ".progress-bar",
		content_element: '<div class="exercise-content"></div>',
		content_selector: '.exercise-content',
		exercise_selector: '#exercise-all',
		summary_selector: '.exercise-summary',
		response_selector: '.exercise-response',
		navigation_selector: 'ul.exercise-nav a[class!="dropdown-toggle"]',
		dropdown_selector: 'ul.exercise-nav .dropdown-toggle',
		last_submission_selector: 'ul.exercise-nav .dropdown-menu a:first-child',
		// For active elements:
		active_element_attr: "data-aplus-active-element",
		ae_result_selector: '.ae_result',
		input: false, // determines whether the active element is an input element or not
		submission_point_badges_selector: "[data-points-badge] .badge", // summary and latest submission points in submission_plain.mustache
	};

	function AplusExercise(element, chapter, options) {
		this.dom_element = element;
		this.element = $(element);
		this.chapter = chapter;
		this.settings = $.extend({}, defaults, options);
		this.url = null;
		this.quiz = false;
		this.ajax = false;
		this.active_element = false;
		this.loader = null;
		this.messages = {};
		this.init();
	}

	$.extend(AplusExercise.prototype, {

		init: function() {
			this.chapterID = this.element.attr("id");
			this.url = this.element.attr(this.chapter.settings.exercise_url_attr);
			//this.url = this.url + "?__r=" + encodeURIComponent(
			//	window.location.href + "#" + this.element.attr("id"));
			// changed from A+: URL has no __r GET query parameter

			// In quiz mode feedback replaces the exercise.
			this.quiz = (this.element.attr(this.settings.quiz_attr) !== undefined);

			// Do not mess up events in an Ajax exercise.
			this.ajax = (this.element.attr(this.settings.ajax_attr) !== undefined);
			
			// Check if the exercise is an active element.
			this.active_element = (this.element.attr(this.settings.active_element_attr) !== undefined);

			// set exercise mime type
			this.exercise_type =
				this.quiz ? 'text/x.aplus-exercise.quiz.v1' :
				this.ajax ? 'text/x.aplus-exercise.iframe.v1' :
				this.active_element ? 'text/x.aplus-exercise.active-element.v1' :
				'text/x.aplus-exercise';

			// Add the active element outputs to a list so that the element can be found later
			if (this.active_element && !this.settings.input) this.chapter.aeOutputs[this.chapterID] = this;

			this.loader = this.chapter.cloneLoader();
			this.element.height(this.element.height()).empty();
			this.element.append(this.settings.content_element);
			this.element.append(this.loader);
			
			// Inputs are different from actual exercises and need only be loaded.
			if (this.settings.input) this.load();

			if (!this.active_element && this.ajax) {
				// Add an Ajax exercise event listener to refresh the summary.
				var exercise = this;
				window.addEventListener("message", function (event) {
					if (event.data.type === "a-plus-refresh-stats") {
						$.ajax(exercise.url, {dataType: "html"})
							.done(function(data) {
								exercise.updateSummary($(data));
							});
					}
				});
			}
		},

		// Construct an active element input form
		makeInputForm: function(id, title, type, def_val) {
			var wrap = $("<div>");
			wrap.attr("id", "exercise-all");

			var form = $("<form>");
			form.attr("action", "");
			form.attr("method", "post");

			var first_div = $("<div>");
			first_div.attr("class", "form-group");

			var label = $("<label>");
			label.attr("class", "control-label");
			label.attr("for", id + "_input");
			label.html(title);

			var form_field;
			if (!type || type === "clickable") {
				form_field = $("<textarea>");
				form_field.val(def_val);
			} else if (type === "file") {
				form_field = $("<input>");
				form_field.attr("type", "file");
				form.attr("enctype", "multipart/form-data");
			} else if (type.substring(0, 8) == "dropdown") {
				form_field = $("<select>");
				// If the type is dropdown, the format of the type attribute
				// should be "dropdown:option1,option2,option2,.."
				var options = type.split(":").pop().split(",");
				$.each(options, function(i, opt) {
					var option = $("<option>");
					option.text(opt);
					option.val(opt);
					form_field.append(option);
				});
			}

			form_field.attr("class", "form-control");
			form_field.attr("id", id + "_input_id");
			form_field.attr("name", id + "_input");

			var second_div = $("<div>");
			first_div.attr("class", "form-group");

			var button = $("<input>");
			button.attr("class", "btn btn-primary");
			button.attr("value", "Submit");
			button.attr("type", "submit");

			$(first_div).append(label, form_field);
			$(second_div).append(button);
			$(form).append(first_div, second_div);
			$(wrap).append(form);

			return $(wrap);
		},

		load: function(onlyThis) {
			this.showLoader("load");
			var exercise = this;

			if (exercise.settings.input) {
				var title = exercise.element.data("title");
				var type = exercise.element.data("type");
				var def_val = exercise.element.data("default");

				if (!title) title = '';
				if (!def_val) def_val = '';

				exercise.hideLoader();
				var input_form = exercise.makeInputForm(exercise.chapterID, title, type, def_val);
				exercise.update(input_form);
				exercise.loadLastSubmission(input_form);
				if (!onlyThis) exercise.chapter.nextExercise();
			} else {
				$.ajax(this.url, {dataType: "html"})
					.fail(function() {
						exercise.showLoader("error");
						if (!onlyThis) exercise.chapter.nextExercise();
					})
					.done(function(data) {
						exercise.hideLoader();
						exercise.update($(data));
						if (exercise.quiz || exercise.active_element) {
							exercise.loadLastSubmission($(data));
						} else {
							exercise.renderMath();
							if (!onlyThis) exercise.chapter.nextExercise();
						}
					});
			}
		},

		update: function(input) {
			var exercise = this;
			input = input.filter(exercise.settings.exercise_selector).contents();
			var content = exercise.element.find(exercise.settings.content_selector)
				.empty().append(input).hide();

			this.dom_element.dispatchEvent(
				new CustomEvent("aplus:exercise-loaded", {bubbles: true, detail: {type: this.exercise_type}}));

			if (exercise.active_element) {
				var title = "";
				if (exercise.element.attr("data-title"))
					title = "<p><b>" + exercise.element.attr("data-title") + "</b></p>";
				exercise.element.find(exercise.settings.summary_selector).remove();
				$(title).prependTo(exercise.element.find(exercise.settings.response_selector));
			}

			content.show();

			// Active element can have height settings in the A+ exercise div that need to be
			// attached to correct DOM-elements before setting the exercise container div height to auto
			var cur_height = exercise.element.css('height');
			if (exercise.active_element) {
				if (exercise.settings.input) {
					$("#" + exercise.chapterID + " textarea").css("height", cur_height);
				} else {
					if (typeof $("#" + exercise.chapterID).data("scale") != "undefined") {
						var cont_height = $(exercise.settings.ae_result_selector, exercise.element)[0].scrollHeight;
						$(exercise.settings.ae_result_selector, exercise.element).css({ "height" : cont_height +"px"});
					} else {
						$(exercise.settings.ae_result_selector, exercise.element).css("height", cur_height);
					}
				}
			}
			
			this.element.height("auto");
			this.bindNavEvents();
			this.bindFormEvents(content);
			this.dom_element.dispatchEvent(
				new CustomEvent("aplus:exercise-ready", {bubbles: true, detail: {type: this.exercise_type}}));
		},

		bindNavEvents: function() {
			this.element.find(this.settings.navigation_selector).aplusModalLink();
			this.element.find(this.settings.dropdown_selector).dropdown();
			this.element.find('.page-modal').aplusModalLink();
		},

		bindFormEvents: function(content) {
			if (!this.ajax) {
				var forms = content.find("form").attr("action", this.url);
				// changed from A+: no overlay messages on top of the exercise in Astra.
				// In addition, no need to check that the form does not belong
				// to an external LTI exercise since Astra does not support LTI exercises.
				var exercise = this;
				if (this.chapter.ajaxForms) {
					forms.on("submit", function(event) {
						event.preventDefault();
						exercise.submit(this);
					});
				}
			}
			//$.augmentSubmitButton(content);
			// changed from A+: no group submissions and no group selection UI
			window.postMessage({
				type: "a-plus-bind-exercise",
				id: this.chapterID
			}, "*");
		},

		// Submit the formData to given url and then execute the callback.
		submitAjax: function(url, formData, callback, retry) {
			var exercise = this;
			$.ajax(url, {
				type: "POST",
				data: formData,
				contentType: false,
				processData: false,
				dataType: "html",
			}).fail(function(xhr, textStatus, errorThrown) {
				// Retry a few times if submission is not successful
				retry = retry || 0;
				if (xhr.status !== 200 && retry < 5) {
					setTimeout(
						function() {
							exercise.submitAjax(url, formData, callback, retry + 1);
						}, 100);
				}
				//$(form_element).find(":input").prop("disabled", false);
				//exercise.showLoader("error");

				if (!exercise.active_element) {
					exercise.dom_element.dispatchEvent(
						new CustomEvent("aplus:exercise-submission-failure",
							{bubbles: true, detail: {type: exercise.exercise_type}}));
					exercise.chapter.modalError(exercise.chapter.messages.error);
				} else {
					// active elements don't use loadbar so the error message must be shown
					// in the element container
					var feedback = $("<div>");
					feedback.attr('id', 'feedback');
					feedback.append(exercise.chapter.messages.error);
					exercise.updateOutput(feedback);
				}
			}).done(function (data) {
				callback(data);
			});
		},

		// Construct form data from input element values
		collectFormData: function(output, form_element) {
			output = $(output);

			//var [exercise, inputs, expected_inputs] = this.matchInputs(output); // ECMAScript 6
			var tmpInputs = this.matchInputs(output);
			var exercise        = tmpInputs[0],
			    inputs          = tmpInputs[1],
			    expected_inputs = tmpInputs[2];

			// Form data to be sent for evaluation
			var formData = new FormData();
			var input_id = this.chapterID;
			var valid = true;

			$.each(inputs, function(i, id) {
				var input_val;
				var input_elem = $.find("#" + id);
				var input_field = $("#" + id + "_input_id");

				// Input can be also an output element, in which case the content must be
				// retrieved differently
				if (input_elem[0].hasAttribute("data-inputs")) {

					// If an output uses another output as an input, the output used as an input can
					// be in evaluation which means this output cannot be evaluated yet
					if ($(input_elem).data("evaluating")) {
						valid = false;
						formData = false;
						return;
					}

					input_val = $(input_elem).find(".ae_result").text().trim();

				} else if ($(input_elem).data("type") === "file") {
					input_val = input_field.get(0).files[0];

				} else if (id !== input_id) {
					input_val = input_field.val();
					// Because changing an input value without submitting said input is possible,
					// use the latest input value that has been submitted before, if there is one,
					// for other inputs than the one being submitted now.
					if ($(input_elem).data("value")) input_val = $(input_elem).data("value");
					// Update the input box back to the value used in evaluation
					input_field.val(input_val);
				} else {
					input_val = input_field.val();
					// Update the saved value data
					$(input_elem).data("value", input_val);
				}
				if (!input_val) valid = false;
				if (formData) formData.append(expected_inputs[i], input_val);
			});

			return [exercise, valid, formData];
		},

		submit: function(form_element) {
			var input = this;
			var chapter = this.chapter;
			if (this.active_element) {
				var input_id = this.chapterID;
				// For every output related to this input, try to evaluate the outputs
				var outputs = $.find('[data-inputs~="' + input_id + '"]');

				$.each(outputs,	function(i, element) {
					//var [exercise, valid, formData] = input.collectFormData(element, form_element); // ECMAScript 6
					var tmpCollect = input.collectFormData(element, form_element);
					var exercise = tmpCollect[0],
					    valid    = tmpCollect[1],
					    formData = tmpCollect[2];

					var output_id = exercise.chapterID;
					var output = $("#" + output_id);
					var out_content = output.find(exercise.settings.ae_result_selector);
					// Indicates that one of inputs has not finished evaluation
					if (!valid && !formData) {
						return; // TODO should this do something else?
					}

					if (!valid) {
						$("#" + output_id).find(exercise.settings.ae_result_selector)
							.html('<p style="color:red;">Fill out all the inputs</p>');
						// Abort submission because some required active element input has no value.
						input.dom_element.dispatchEvent(
							new CustomEvent("aplus:submission-aborted",
								{bubbles: true, detail: {type: input.exercise_type}}));
						return;
					}

					output.data('evaluating', true);
					// If the element has no height defined it should keep the height it had with content
					if (typeof output.data("scale") != "undefined") {
						out_content.css({ 'height' : (out_content.height())});
					}
					out_content.html("<p>Evaluating</p>");

					var url = exercise.url;
					exercise.submitAjax(url, formData, function(data) {
						var content = $(data);
						if (! content.find('.alert-danger').length) {
							// changed from A+: must provide the poll URL and the final download URL separately.
							var pollerElem = content.find(".exercise-wait");
							var poll_url = pollerElem.attr("data-poll-url");
							var ready_url = pollerElem.attr("data-ready-url");
							output.attr('data-poll-url', poll_url);
							output.attr('data-ready-url', ready_url);

							exercise.updateSubmission(content);
						} else if (content.find('.alert-danger').contents().text()
								.indexOf("The grading queue is not configured.") >= 0) {
							output.find(exercise.settings.ae_result_selector)
								.html(content.find(".alert").text());
							output.find(exercise.settings.ae_result_selector).append(content.find(".grading-task").text());
						} else {
							output.find(exercise.settings.ae_result_selector)
								.html(content.find('.alert-danger').contents());
						}
					});
				});
			} else {
				chapter.openModal(chapter.messages.submit);
				var exercise = this;
				var url = $(form_element).attr("action");
				var formData = new FormData(form_element);

				exercise.submitAjax(url, formData, function(data) {
					//$(form_element).find(":input").prop("disabled", false);
					//exercise.hideLoader();
					var input = $(data);
					if (exercise.quiz) {
						var badge = input.find('.badge').eq(2).clone();
						exercise.update(input);
						chapter.modalSuccess(exercise.element, badge);
						exercise.renderMath();
						exercise.dom_element.dispatchEvent(
							new CustomEvent("aplus:submission-finished",
								{bubbles: true, detail: {type: exercise.exercise_type}}));
					} else {
						exercise.updateSubmission(input);
					}
				});
			}
		},

		// Find for an active element the names of the input fields required and
		// the corresponding names that are used in mooc-grader exercise type config
		matchInputs: function(element) {
			var output_id = element.attr("id");
			var exercise = this.chapter.aeOutputs[output_id];
			// Find the ids of input elements required for this output
			var inputs = element.attr("data-inputs").split(" ");
			// Find the form field names the grader is expecting
			var expected_inputs = element.find(exercise.settings.ae_result_selector).attr("data-expected-inputs");
			// make sure there are expected inputs
			if (expected_inputs) {
				expected_inputs = expected_inputs.trim().split(" ");
				// There might be extra whitespace or line breaks in the expected inputs data-attribute
				// because of how the template is generated
				expected_inputs = $.grep(expected_inputs, function( a ) {
					return a !== "" || a !== "\n";
				});
			} else {
				expected_inputs = [];
			}
			return [exercise, inputs, expected_inputs];
		},

		updateSummary: function(input) {
			this.element.find(this.settings.summary_selector)
				.empty().append(
					input.find(this.settings.summary_selector).remove().contents()
				);
			this.bindNavEvents();
		},

		updateSubmission: function(input) {
			if (!this.active_element) {
				this.updateSummary(input);
				this.chapter.modalContent(
					input.filter(this.settings.exercise_selector).contents()
				);
			}

			// Update asynchronous feedback.
			if (typeof($.aplusExerciseDetectWaits) == "function") {
				var exercise = this;
				var id;
				if (this.active_element) id = "#" + this.chapterID;

				var did_wait = $.aplusExerciseDetectWaits(function(suburl, error) {
					if (error) {
						// Polling for the final feedback failed, possibly because
						// the grading takes a lot of time.
						if (exercise.active_element) {
							exercise.dispatchEventToActiveElem("aplus:exercise-submission-failure");
						} else {
							exercise.dom_element.dispatchEvent(
								new CustomEvent("aplus:exercise-submission-failure",
									{bubbles: true, detail: {type: exercise.exercise_type}}));
							// Reload the exercise (description) in case it changes after submitting.
							// This also resets the disabled submit button.
							exercise.load(true);
						}
						return;
					}
					$.ajax(suburl).done(function(data) {
						if (exercise.active_element) {
							exercise.updateOutput(data);
							exercise.dispatchEventToActiveElem("aplus:submission-finished");
							exercise.submit(); // Active element outputs can be chained
						} else {
							var input2 = $(data);
							var new_badges = input2.find(exercise.settings.submission_point_badges_selector);
							var old_badges = exercise.element.find(exercise.settings.summary_selector + " .badge");
							old_badges.eq(0).replaceWith(new_badges.eq(0).clone()); // summary points badge (best submission)
							old_badges.eq(2).replaceWith(new_badges.eq(1).clone()); // points badge of this submission
							var content = input2.filter(exercise.settings.exercise_selector).contents();
							if (content.text().trim() == "") {
								exercise.chapter.modalSuccess(exercise.element, new_badges.eq(1).clone());
							} else {
								exercise.chapter.modalContent(content);
							}
							exercise.dom_element.dispatchEvent(
								new CustomEvent("aplus:submission-finished",
									{bubbles: true, detail: {type: exercise.exercise_type}}));
							// Reload the exercise (description) in case it changes after submitting.
							// This also resets the disabled submit button.
							exercise.load(true);
						}
					}).fail(function() {
						exercise.dom_element.dispatchEvent(
							new CustomEvent("aplus:exercise-submission-failure",
								{bubbles: true, detail: {type: exercise.exercise_type}}));
						exercise.chapter.modalError(exercise.chapter.messages.error);
						exercise.load(true);
					});
				}, id);
				if (!did_wait) {
					// No asynchronous waiting and polling were needed to retrieve
					// the feedback for the submission.
					// Reload the exercise (description) in case it changes after submitting.
					// This also resets the disabled submit button.
					exercise.load(true);
				}
			}
		},

		updateOutput: function(data) {
			// Put data in this output box
			var exercise = this;
			var type = exercise.element.attr("data-type") || "text"; // default output type is text
			var content = $(data);

			// The data organisation is different depending on whether it is a new submission or
			// the latest submission that is loaded back
			if (!content.is("#feedback")) {
				content = content.find("#feedback");
			}

			if (type == "text") {
				content = content.text();
			} else if (type == "image") {
				content = '<img src="data:image/png;base64, ' + content.text() + '" />';
			}

			var output_container = exercise.element.find(exercise.settings.ae_result_selector);
			output_container.html(content);
			exercise.element.data('evaluating', false);
			// Some result divs should scale to match the content
			if (typeof exercise.element.data("scale") != "undefined" ) {
				output_container.css({ "height" : "auto"});
			}
		},

		// Retrieve and update latest values of the input elements related to this element
		updateInputs: function(data) {
			data = data.submission_data;
			var exercise = this;
			//var [exer, input_list, grader_inputs] = exercise.matchInputs(exercise.element); // ECMAScript 6
			var tmpInputs = exercise.matchInputs(exercise.element);
			var exer          = tmpInputs[0],
			    input_list    = tmpInputs[1],
			    grader_inputs = tmpInputs[2];

			// Submission data can contain many inputs
			// Changed from A+: submission_data returned from the server has a different format
			// A+ uses arrays like [["name", "value"], []...] while Astra uses an object: {name: value}
			for (var input in data) {
				if (!data.hasOwnProperty(input)) {
					continue;
				}
				var grader_id = input;
				var input_id = input_list[$.inArray(grader_id, grader_inputs)];
				var input_data = data[input];
				if ($("#" + input_id).data("type") !== "file") {
					// Store the value of the input to be used later for submitting active
					// element evaluation requests
					$($.find("#" + input_id)).data("value", input_data);
					$("#" +input_id + "_input_id").val(input_data).trigger('change');
				}
			}
		},

		dispatchEventToActiveElem: function(event) {
			// Send the event to this active element (output) and all related inputs.
			this.dom_element.dispatchEvent(
				new CustomEvent(event, {bubbles: true, detail: {type: this.exercise_type}}));
			// Send the event to the inputs related to this output.
			//const [, inputIds, ] = this.matchInputs(this.element); // ECMAScript 6
			var tmpInputs = this.matchInputs(this.element);
			var inputIds = tmpInputs[1];

			//for (const inputId of inputIds) { // ECMAScript 6, loop over the values of an array
			for (var i = 0; i < inputIds.length; ++i) {
				var inputId = inputIds[i];
				var inputElem = $('#' + inputId).data('plugin_' + pluginName);
				if (inputElem) {
					inputElem.dom_element.dispatchEvent(
						new CustomEvent(event,
							{bubbles: true, detail: {type: inputElem.exercise_type}}));
				}
			}
		},

		loadLastSubmission: function(input) {
			var link = input.find(this.settings.last_submission_selector);
			var exercise = this;
			if (link.length > 0) {
				var url = link.attr("href");

				if (url && url !== "#") {
					var data_type = "html";
					if (exercise.active_element) {
						// Active element input values are retrieved from the API, so
						// we must extract the submission number from the submission url
						// TODO these URLs are hardcoded here, but the server could save
						// them to HTML data attributes in order to avoid hardcoding
						var m = url.match(/submission\.php\?id=(\d+)$/);
						if (m) {
							url = 'submission_data.php?id=' + m[1];
							data_type = "json";
						} else {
							console.error('Can not read the submission data URL');
						}
					}

					this.showLoader("load");
					$.ajax(url, {dataType: data_type})
						.fail(function() {
							exercise.showLoader("error");
						})
						.done(function(data) {
							exercise.hideLoader();

							if (!exercise.active_element) {
								var f = exercise.element.find(exercise.settings.response_selector)
									.empty().append(
										$(data).filter(exercise.settings.exercise_selector).contents()
									);
								exercise.dom_element.dispatchEvent(
									new CustomEvent("aplus:exercise-loaded",
										{bubbles: true, detail: {type: exercise.exercise_type}}));
								//f.removeClass('group-augmented'); // changed from A+: no group submissions in Astra
								exercise.bindFormEvents(exercise.element);
								exercise.dom_element.dispatchEvent(
									new CustomEvent("aplus:exercise-ready",
										{bubbles: true, detail: {type: exercise.exercise_type}}));
							} else {
								// Update the output box values
								exercise.updateOutput(data.feedback);

								// Update the input values
								exercise.updateInputs(data);
							}

							exercise.renderMath();
						});
				} else {
					// the math must be rendered here even if there is no submission to load
					exercise.renderMath();
				}
			}
			exercise.chapter.nextExercise();
		},

		showLoader: function(messageType) {
			this.loader.show().find(this.settings.message_selector)
				.text(this.chapter.messages[messageType]);
			if (messageType == "error") {
				this.loader.removeClass("progress-bar-animated").addClass("bg-danger");
			} else {
				this.loader.addClass("progress-bar-animated").removeClass("bg-danger");
			}
		},

		hideLoader: function() {
			this.loader.hide();
		},
		
		renderMath: function() {
			// changed from A+: Moodle has its own API for accessing MathJax.
			// Trigger Moodle JS event so that MathJax renders new formulas
			// that were modified/inserted (usually) by AJAX after the initial page load.
			// This uses the Moodle filter MathJax loader.
			// The selector id is like "chapter-exercise-1", the element that contains
			// the embedded exercise in the chapter page.
			moodleEvent.notifyFilterContentUpdated('#' + this.element.attr('id'));
		},
	});

	$.fn[pluginName] = function(chapter, options) {
		return this.each(function() {
			if (!$.data(this, "plugin_" + pluginName)) {
				$.data(this, "plugin_" + pluginName, new AplusExercise(this, chapter, options));
			}
		});
	};

	$.fn[loadName] = function() {
		return this.each(function() {
			var exercise = $.data(this, "plugin_" + pluginName);
			if (exercise) {
				exercise.load();
			}
		});
	};

})(jQuery, moodleEvent, window, document);

/**
 * Prevent double submit of exercise forms
 */
(function ($) {
	$(document).on('aplus:exercise-loaded', function(e) {
		$(e.target).find('form').each(function () {
			var $form = $(this);
			if ($form.prop('method') == 'post') {
				$form.on('submit', function (e) {
					$(this).find('[type="submit"]')
						.prop('disabled', true)
						.attr('data-aplus-submit-disabled', 'yes');
				});
			}
		});
	});
	$(document).on('aplus:exercise-submission-failure'
			+ ' aplus:submission-finished aplus:submission-aborted', function(e) {
		$(e.target).find('[data-aplus-submit-disabled]')
			.prop('disabled', false)
			.removeAttr('data-aplus-submit-disabled');
	});
	$(document).on('hidden.bs.modal', function(e) {
		// If the user closes the feedback modal while it is polling for
		// the grading of the submission, the submit button remains disabled.
		// The modal hidden event does not specify which exercise is open in
		// the modal, so we can only enable all disabled buttons in the page.
		$('[data-aplus-submit-disabled]')
			.prop('disabled', false)
			.removeAttr('data-aplus-submit-disabled');
	});
})(jQuery);

