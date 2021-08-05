/**
 * A+ helper function for elements that show source code with syntax highlighting.
 * Syntax highlighting uses the highlight.js library.
 * This function adds line numbers
 * (and a copy-to-clipboard button (copying disabled since it requires clipboard.js)).
 * 
 * Usage:
 * $(element).highlightCode();
 * 
 * Source: A+ (a-plus/assets/js/aplus.js)
 * License: GNU GPL v3
 * 
 * @module mod_astra/aplus_highlightcode
 */
import $ from 'jquery';
import hljs from './highlight';

/**
 * Highlights code element.
 */

//var copyTargetCounter = 0;

$.fn.highlightCode = function(options) {

  return this.each(function() {
    var codeBlock = $(this).clone();
    var wrapper = $('<div></div>');
    wrapper.append(codeBlock);
    $(this).replaceWith(wrapper);

    // Use $(element).highlightCode{noCopy: true} to prevent copy button
    /* // disabled due to dependency on clipboard.js
    if (!options || !options.noCopy) {
      var buttonContainer = $('<p></p>').prependTo(wrapper);
      var copyButtonContent = $('<span class="glyphicon glyphicon-copy" aria-hidden="true"></span>');
      var copyButtonText = $('<span></span>').text('Copy to clipboard');
      var copyButton = $('<button data-clipboard-target="#clipboard-content-' + copyTargetCounter + '" class="btn btn-xs btn-primary" id="copy-button-' + copyTargetCounter + '"></button>');
      copyButtonContent.appendTo(copyButton);
      copyButtonText.appendTo(copyButton);
      copyButton.appendTo(buttonContainer);

      var hiddenTextarea = $('<textarea id="clipboard-content-' + copyTargetCounter + '" style="display: none; width: 1px; height: 1px;"></textarea>').text(codeBlock.text());
      hiddenTextarea.appendTo(buttonContainer);

      // clipboard.js cannot copy from invisible elements
      copyButton.click(function() {
        hiddenTextarea.show();
      });

      var clipboard = new Clipboard('#copy-button-' + copyTargetCounter);
      clipboard.on("error", function(e) {
          hiddenTextarea.hide();
      });
      clipboard.on("success", function(e) {
          hiddenTextarea.hide();
      });

      copyTargetCounter += 1;

    }
    */

    hljs.highlightElement(codeBlock[0]);

    // Add line numbers.
    var pre = $(codeBlock);
    var lines = pre.html().split(/\r\n|\r|\n/g);
    var list = $("<table/>").addClass("src");
    for (var i = 1; i <= lines.length; i++) {
        list.append('<tr><td class="num unselectable">' + i + '</td><td class="src">' + lines[i - 1] + '</td></tr>');
    }
    pre.html(list);
  });
};

