<?php

/**
 * @file
 * The plugin file
 * @author Muhammad Haris
 * @version 1.1.1
 *
 * @cond
 * Plugin Name: AJAX Comments Spy
 * Version: 1.1.1
 * Plugin URI: http://www.mharis.net/2008/01/14/wordpress-ajax-comments-spy/
 * Description: Digg-style spy widget for wordpress comments on posts or pages or both.
 * Author: Muhammad Haris
 * Author URI: http://www.mharis.net
 * @endcond
 */

/**
 * Path to the plugin
 */
define('AJAX_COMMENTS_SPY_PATH', dirname(__FILE__));

/**
 * URL to the plugin
 */
define('AJAX_COMMENTS_SPY_URL', get_settings('siteurl') .'/'. str_replace(str_replace('\\', '/', ABSPATH), '', str_replace('\\', '/', dirname(__FILE__))));

// Register hooks with wordpress core
register_activation_hook( __FILE__, 'ajax_comments_spy_activate');
register_deactivation_hook( __FILE__, 'ajax_comments_spy_deactivate');
add_action('comment_post', 'ajax_comments_spy_comment_post');
add_action('wp_set_comment_status', 'ajax_comments_spy_comment_status');
add_action('wp_head', 'ajax_comments_head');
add_action('admin_menu', 'ajax_comments_spy_admin_menu');
add_action('plugins_loaded', 'ajax_comments_spy_plugins_loaded');

// Time in GMT
date_default_timezone_set('UTC');

/**
 * Implementation of plugins_loaded()
 * Adds widget when plugin is loaded
 */

function ajax_comments_spy_plugins_loaded() {
  register_sidebar_widget('AJAX Comments Spy', 'ajax_comments_spy_widget');
}

/**
 * Implementation of register_activation_hook()
 * Adds required options to the database when plugin is activated
 */

function ajax_comments_spy_activate() {
  global $wpdb;

  $options = array(
    'ajax_comments_spy_type' => 'Both',
    'ajax_comments_spy_password' => 'false',
    'ajax_comments_spy_fetch_items' => '10',
    'ajax_comments_spy_update' => '60',
    'ajax_comments_spy_widget_title' => 'LIVE Comments',
  );

  foreach($options as $option => $value) {
    update_option($option, $value);
  }

  $sql = 'ALTER TABLE ' . $wpdb->comments .
    ' ADD acs_approval_date VARCHAR (255)
    DEFAULT "00000"
    NOT NULL;';
  $wpdb->query($sql);
}

/**
 * Implementation of register_deactivation_hook()
 * Delete options and acs_approval_date column when the plugin is deactivated
 */

function ajax_comments_spy_deactivate() {
  global $wpdb;

  $options = array(
    'ajax_comments_spy_type',
    'ajax_comments_spy_password',
    'ajax_comments_spy_fetch_items',
    'ajax_comments_spy_update',
    'ajax_comments_spy_widget_title',
  );

  foreach($options as $key => $option) {
    delete_option($option);
  }

  $sql = 'ALTER TABLE ' . $wpdb->comments .
    ' DROP acs_approval_date;';
  $wpdb->query($sql);
}

/**
 * Implementation of comment_post() hook
 * Copies date from comment_date_gmt field to acs_approval_date when comment is posted
 */

function ajax_comments_spy_comment_post($comment_ID) {
  global $wpdb;

  $sql = 'SELECT comment_date_gmt FROM ' . $wpdb->comments .
    ' WHERE comment_ID = ' . $comment_ID;
  $comment_date = $wpdb->get_col($sql);
  $sql = 'UPDATE '. $wpdb->comments .
    ' SET acs_approval_date = "' . strtotime($comment_date[0]) .
    '" WHERE comment_ID = "' . $comment_ID . '"';

  $wpdb->query($sql);
}

/**
 * Implementation of wp_set_comment_status() hook
 * Sets current date to acs_approval_date when comment is approved
 * Sets comment_date_gmt to acs_approval_date when comment is on hold
 */

function ajax_comments_spy_comment_status($comment_ID) {
  global $wpdb;

  $comment_status = wp_get_comment_status($comment_ID);
  if($comment_status == 'unapproved') {
    $date = date('Y-m-d H:i:s');
    $sql = 'UPDATE '. $wpdb->comments .
    ' SET acs_approval_date = "' . strtotime($date) .
    '" WHERE comment_ID = "' . $comment_ID . '"';
  }
  elseif($comment_status == 'approved') {
    $sql = 'SELECT comment_date_gmt FROM ' . $wpdb->comments .
      ' WHERE comment_ID = ' . $comment_ID;
    $comment_date = $wpdb->get_col($sql);
    $sql = 'UPDATE '. $wpdb->comments .
      ' SET acs_approval_date = "' . strtotime($comment_date[0]) .
      '" WHERE comment_ID = "' . $comment_ID . '"';
  }
  $query = $wpdb->query($sql);
  // mail('isharis@gmail.com', $comment_ID . ' ' . $comment_status, $sql . ' ' . $query); debug via email
}

/**
 * Implementation of wp_head action.
 * Includes javascript libraries to the header.
 */

function ajax_comments_head() {
  // jQuery library
  $output = "\n" . '<script type="text/javascript" src="' . AJAX_COMMENTS_SPY_URL . '/jquery/jquery-1.2.1.js"></script>' . "\n";

  // Ajax comments spy javascript
  $output .= '<script type="text/javascript" src="' . AJAX_COMMENTS_SPY_URL . '/ajax-comments-spy-js.php"></script>' . "\n";

  echo $output;
}

/**
 * Implementation of plugins_loaded function
 * Widgetize the plugin
 * @ingroup themeable
 */

function ajax_comments_spy_widget($args) {
  extract($args);
  $title = get_option('ajax_comments_spy_widget_title');
  $before_widget = '<li class="widget ajax_comments_spy_widget" id="ajax-comments-spy">';
  $after_widget = '</li>';
  if(empty($before_title)) {
    $before_title = '<h2>';
  }
  if(empty($after_title)) {
    $after_title =  '</h2>';
  }
  echo  "\n";
  echo $before_widget  . "\n" . $before_title . $title . $after_title . "\n" . $after_widget;
  echo  "\n";
}

/**
 * Implementation of admin_menu() action.
 * Adds configuration menu to wordpress admin panel.
 * The menu is located under options menu.
 */

function ajax_comments_spy_admin_menu() {
  add_options_page(__('Ajax Comments Spy'), __('Ajax Comments Spy'), 8,
  __FILE__, 'ajax_comments_spy_admin');
}

/**
 * Options menu page callback for Options > Ajax Comments Spy
 * Admin view for editing ajax comments spy settings
 * @ingroup forms
 */

function ajax_comments_spy_admin() {
  global $plugin_page;

  // Read existing configurations from the database
  $options = array(
    'spy_type' => get_option('ajax_comments_spy_type'),
    'spy_password' => get_option('ajax_comments_spy_password'),
    'spy_fetch_items' => get_option('ajax_comments_spy_fetch_items'),
    'spy_update' => get_option('ajax_comments_spy_update'),
    'spy_widget_title' => get_option('ajax_comments_spy_widget_title'),
  );

  /*
   * Checks if the form has been posted
   * Update database to the post values on form submission
   */

  if(isset($_POST['submit'])) {
    $options = array(
      'spy_type' => $_POST['spy_type'],
      'spy_password' => $_POST['spy_password'],
      'spy_widget_title' => $_POST['spy_widget_title'],
    );

      // Validate update interval
    if($_POST['spy_fetch_items'] <= 0) {
      $output .= '<div class="error"><p>' . __('<strong>ERROR</strong>: Fetch items per request should be greater than 0. Your settings are reverted. Refresh!') . '</p></div>';
    }
    else {
      $options['spy_fetch_items'] = (int) $_POST['spy_fetch_items'];
    }

    // Validate update interval
    if((int) $_POST['spy_update'] <= 0) {
      $output .= '<div class="error"><p>' . __('<strong>ERROR</strong>: Update interval should be greater than 0. Your settings are reverted. Refresh!') . '</p></div>';
    }
    else {
      $options['spy_update'] = (int) $_POST['spy_update'] * 60;
    }

    // Save the posted value to the database
    foreach($options as $option => $value) {
      update_option('ajax_comments_' . $option, $value);
    }

    // Notify user their settings were saved
    $output .= '<div class="updated"><p><strong>'. __('Settings saved.') . '</strong></p></div>';
  }

  $output .= '<div class="wrap">' . "\n";
  $output .= '  <h2>'. __('General Settings') . '</h2>' . "\n";
  $output .= '  <form method="post" action="options-general.php?page='. $plugin_page .'">' . "\n";
  $output .= '    <p><label for="spy_type">' . __('Spy type') . '</label>' . "\n";
  $output .= '      <select name="spy_type" id="spy_type">' . "\n";
  $output .= '        <option value="Pages"' . (($options['spy_type'] == 'Pages') ? ' selected="selected"' : '') . '>' . __('Pages') . '</option>' . "\n";
  $output .= '        <option value="Posts"' . (($options['spy_type'] == 'Posts') ? ' selected="selected"' : '') . '>' . __('Posts') . '</option>' . "\n";
  $output .= '        <option value="Both"' . (($options['spy_type'] == 'Both') ? ' selected="selected"' : '') . '>' . __('Both') . '</option>' . "\n";
  $output .= '      </select>' . "\n";
  $output .= '    </p>' . "\n";
  $output .= '    <p><label for="spy_widget_title">' .__('Widget title') . ' </label> <input type="text" name="spy_widget_title" id="spy_widget_title" value="'. $options['spy_widget_title'] . '" /> ' . __('(leave blank for no title)') . '</p>'  . "\n";
  $output .= '    <p><label for="spy_fetch_items">' . __('Fetch') . ' </label><input type="text" name="spy_fetch_items" id="spy_fetch_items" value="' . $options['spy_fetch_items'] . '" />' . __(' items per request') . '</p>'  . "\n";
  $output .= '    <p><label for="spy_update">' . __('Update interval') . ' </label><input type="text" name="spy_update" id="spy_update" value="' . $options['spy_update']/60 . '" />' . __(' (in minutes)') . '</p>'  . "\n";
  $output .= '    <h2>' . __('Permission Settings') . '</h2>' . "\n";
  $output .= '    <h3>' . __('Spy comments from password protected pages and posts?') . '</h3>' . "\n";
  $output .= '    <p>' . __('This part of the configuration depends on spy type.') . '</p>' . "\n";
  $output .= '    <p><input type="radio" name="spy_password" id="spy_password_no" value="false"' . (($options['spy_password'] == 'false') ? ' checked="checked"' : '') . ' /> <label for="spy_password_no">' . __('No') . '</label></p>' . "\n";
  $output .= '    <p><input type="radio" name="spy_password" id="spy_password_yes" value="true"' . (($options['spy_password'] == 'true') ? ' checked="checked"' : '') . ' /> <label for="spy_password_yes">'. __('Yes') . '</label></p>' . "\n";
  $output .= '    <p class="submit"><input type="submit" name="submit" value="' . __('Update Settings') .'&raquo;" /></p>'."\n";
  $output .= '  </form>' . "\n";
  $output .= '</div>' . "\n";

  echo $output;
}