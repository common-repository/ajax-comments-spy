<?php
/**
 * @file
 * Javascript
 * @author Muhammad Haris
 */

require('../../../wp-config.php'); // invoke WordPress bootstrap
@header('Content-Type: text/javascript');
$options['spy_update'] = get_option('ajax_comments_spy_update') * 1000;
$options['fetch_items'] = get_option('ajax_comments_spy_fetch_items');
?>
// Javascript for AJAX Comments Spy Widget
var ajax_comments_spy_widget = {

  error_count: 0,

  onload: function() {
    var date = new Date();
    var date_timestamp = date.getTime() - <?php echo $options['spy_update']; ?>;

    // Initiate AJAX Request
    jQuery.ajax({
        type: 'GET',
        url: '<?php echo AJAX_COMMENTS_SPY_URL; ?>/ajax-comments-spy-post.php?timestamp=' + date_timestamp,
        dataType: 'json',
        timeout: 60000,

        success: function(responseText) {

          // If there are no errors, prepend responseText to html
          if(ajax_comments_spy_widget.error_count === 0) {

            var list_items = jQuery('#ajax-comments-spy-widget').children();
            var total_items = jQuery(list_items).length + 1;

            if(total_items > <?php echo $options['fetch_items']; ?>) {
              jQuery('#ajax-comments-spy-widget li:last').remove();
            }

            jQuery.each(responseText, function(key, comment) {
              comment = '<li id="spy-comment-' + key + '"><a href="' + comment.guid + '#comment-' + comment.comment_ID + '">' + comment.comment_author + ': ' + comment.comment_content + '</a></li>';
              jQuery('#ajax-comments-spy-widget').prepend(comment);
              jQuery('#spy-comment-' + key).hide();
              jQuery('#spy-comment-' + key).fadeIn();
            });

            // Timeout to repeat AJAX
            ajax_comments_spy_widget.timeout();
          }
        },

        error: function(XMLHttpRequest, textStatus, errorThrown) {
          /**
           *  Increase error counter if there's an error.
           *  Until and unless, error_count is 0. No new comment will
           *  be prepended.
           */
          ajax_comments_spy_widget.error_count++;

          // Initialize error variable
          var error = '';
          if (typeof(XMLHttpRequest.responseText) === 'string' && XMLHttpRequest.responseText !== '') {
            error = 'Unknown response: ' + XMLHttpRequest.responseText;
            jQuery('#ajax-comments-spy-widget-error').html('<strong>RESPONSETEXT ERROR</strong>: ' + error);
          }
          else if (XMLHttpRequest.responseText == 'null' || XMLHttpRequest.responseText === '') {
            // Timeout to recheck if there's a new comment available now.
            ajax_comments_spy_widget.timeout();
          }
          else if (textStatus == 'timeout') {
            // Timeout error
            error = '<?php echo htmlspecialchars(__('Please try again later.')); ?>';
            jQuery('#ajax-comments-spy-widget-error').html('<strong>TIMEOUT ERROR</strong>: ' + error);
          }
          else {
            // When responseText is not what was expected.
            error = '<?php echo htmlspecialchars(__('Try again?')); ?>';
            jQuery('#ajax-comments-spy-widget-error').html('<strong>UNKNOWN ERROR</strong>: ' + error);
          }
        }
    });
  },

  timeout: function() {
    // Before timing out, set error counter to 0.
    ajax_comments_spy_widget.error_count = 0;

    /**
     * Resent AJAX Request after the timeout time depending on spy
     * interval settings.
     */
    var timeInterval = <?php echo $options['spy_update']; ?>;
    setTimeout('ajax_comments_spy_widget.onload()', timeInterval);
  }
};

// When document is ready, execute javascript.
jQuery(document).ready(function() {
  /* Check for the presence of widget */
  var ajax_comments_spy_widget_present = jQuery("#ajax-comments-spy").length;
  if(ajax_comments_spy_widget_present == 1) {
    jQuery('.ajax_comments_spy_widget :first').after('<ul id="ajax-comments-spy-widget"></ul>');
    jQuery('#ajax-comments-spy-widget').before('<span id="ajax-comments-spy-widget-error"></span>');
    ajax_comments_spy_widget.onload();
  }
});