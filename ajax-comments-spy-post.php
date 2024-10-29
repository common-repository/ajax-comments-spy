<?php

/**
 * @file
 * Javascript is posted to this file. This file returns responseText.
 * @author Muhammad Haris
 */

// Invoke WordPress bootstrap
require_once('../../../wp-config.php');
require_once('../../../wp-includes/wp-db.php');

// Time in GMT
date_default_timezone_set('UTC');

/**
 * grab_comment executes query to get 10 latest comments from the database
 * @return
 *  Array of latest comments
 */
function ajax_comments_spy_grab_comment() {
  global $wpdb, $wp_locale;

  // Fetch options from the database
  $options_name = array(
    'spy_type',
    'spy_password',
    'spy_update',
  );
  foreach($options_name as $key => $option_name) {
    $options[$option_name] = get_option('ajax_comments_' . $option_name);
  }

  $_GET['timestamp'] = $_GET['timestamp'] / 1000;
  $ajax_timestamp = (int) $_GET['timestamp'];
  $current_timestamp = strtotime('now');

  // Setting spy type
  switch($options['spy_type']) {
    case 'Pages':
      $type = $wpdb->posts . '.post_type = "page"';
      break;
    case 'Posts':
      $type = $wpdb->posts . '.post_type = "post"';
      break;
  }

  // Query for retriving comments
  $sql = 'SELECT ' . $wpdb->comments . '.comment_ID, ' .
    $wpdb->comments . '.comment_author, ' .
    $wpdb->comments . '.comment_content, ' .
    $wpdb->posts . '.guid' .
    ' FROM ' . $wpdb->comments .
    ' LEFT JOIN ' . $wpdb->posts . ' ON (' . $wpdb->posts . '.ID = ' . $wpdb->comments . '.comment_post_ID' . ')' .
    ' WHERE ' . $type .
    ' ' . $wpdb->comments . '.acs_approval_date >= "' . $ajax_timestamp .
    '" && ' . $wpdb->comments . '.acs_approval_date <= "' . $current_timestamp .
    '" && ' . $wpdb->comments . '.comment_approved = "1"';
    $sql .= ($options['spy_password'] == 'false') ? ' && ' . $wpdb->posts . '.post_password = ""' : '';
  $comments = $wpdb->get_results($sql, ARRAY_A);

  return $comments;
}

/**
 * Encodes the latest comments array into json string
 * @return
 *  Json string
 */
function ajax_comments_spy_comment_tojson () {
  $latest_comment =  ajax_comments_spy_grab_comment();
  $latest_comment_json = json_encode($latest_comment);

  // Return nothing on null
  if($latest_comment_json == 'null') {
    $latest_comment_json = '';
  }

  return $latest_comment_json;
}

// Set header content type to JSON.
//@header('Content-Type: text/x-json; charset=utf-8', TRUE);

echo ajax_comments_spy_comment_tojson();