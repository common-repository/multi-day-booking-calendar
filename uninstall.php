<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
  exit();
}

function mdbc_delete_plugin()
{
  global $wpdb;

  delete_option('mdbc_options');
  delete_option('mdbc_db_version');

  $posts = get_posts(
    array(
      'numberposts' => -1,
      'post_type' => 'mdbc',
      'post_status' => 'any',
    )
  );

  foreach ($posts as $post) {
    wp_delete_post($post->ID, true);
  }

  $wpdb->query(sprintf(
    "DROP TABLE IF EXISTS %s",
    $wpdb->prefix . 'mdbc'
  ));
}

if (!defined('MDBC_VERSION')) {
  mdbc_delete_plugin();
}
