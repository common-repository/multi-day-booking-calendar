<?php

/**
 * Plugin Name: Multi-day Booking Calendar
 * Plugin URI:
 * Description: This plugin allows you to integrate a multi-day booking calendar into your existing form.
 * Version: 1.0.1
 * Author: matorel
 * License: GPL2
 * Text Domain: multi-day-booking-calendar
 * Domain Path: /languages/
 *
 */


/* Copyright 2022- matorel (email: info@matorel.com)
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.


*/

defined('ABSPATH') || exit;

define('MDBC_PRO', false);

include_once(ABSPATH . 'wp-admin/includes/plugin.php');

if (!MDBC_PRO || !is_plugin_active('multi-day-booking-calendar/multi-day-booking-calendar.php')) :

  define('MDBC_VERSION', '1.0.1');
  define('MDBC_DB_VERSION', '1.0.0');
  define('MDBC_PATH', plugin_dir_path(__FILE__));
  define('MDBC_URL', plugins_url('/', __FILE__));
  define('PRO_URL', "https://matorel.com/multi-day-booking-calendar-pro");

  // require_once MDBC_PATH . 'plugin-update-checker/plugin-update-checker.php';
  // $myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
  //     'https://wp-avenue.com/plugin_file/multi-day-booking-calendar/plugin.json',
  //     __FILE__,
  //     'multi-day-booking-calendar'
  // );


  if (!class_exists('MDBC')) :

    require_once MDBC_PATH . 'includes/endpoints.php';


    //翻訳ファイル取得設定
    function mdbc_plugin_load_textdomain()
    {
      load_plugin_textdomain(
        'multi-day-booking-calendar',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages'
      );
    }
    add_action('plugins_loaded', 'mdbc_plugin_load_textdomain', 11);


    //プラグイン有効時DBテーブル設置
    register_activation_hook(__FILE__, function () {
      global $wpdb;

      MDBC::register_options();

      $table_name = $wpdb->prefix . 'mdbc';
      if ($wpdb->get_var("SHOW TABLES LIKE '" . $table_name . "'") != $table_name) {
        MDBC::create_tables();
      }
    });


    /**
     * プラグイン全体のネームスペース用クラス
     */
    class MDBC
    {
      private static $instance;

      public static function get_instance()
      {
        if (is_null(self::$instance)) {
          self::$instance = new self();
        }
        return self::$instance;
      }

      private function __construct()
      {
        add_action('wp_enqueue_scripts', array($this, 'load_scripts_and_styles'));
        add_action('admin_enqueue_scripts', array($this, 'load_scripts_and_styles'));
        add_action('init', array($this, 'register_post_type'));
        add_action('admin_menu', array($this, 'add_schedule_field'));
        add_action('admin_menu', array($this, 'add_plugin_admin_menu'));
        add_action('save_post', array($this, 'save_schedule'));
        add_action('save_post', array($this, 'save_period'));
        add_shortcode('multi-day-booking-calendar', array($this, 'view_from_shortcode'));
      }

      /**
       * js と css 読み込み (js内でCSS Moduleあり)
       */
      public function load_scripts_and_styles()
      {
        // JS
        wp_enqueue_script(
          'mdbc-script',
          MDBC_URL . 'assets/mdbc.min.js',
          array("wp-api-fetch", 'wp-i18n'),
          MDBC_VERSION,
          true
        );

        //NONCE
        wp_localize_script('mdbc-script', 'mdbcvars', [
          'nonce' => wp_create_nonce('wp_rest')
        ]);

        //JS翻訳
        //https://ja.wordpress.org/team/handbook/block-editor/how-to-guides/internationalization/
        if (function_exists('wp_set_script_translations')) {
          // WordPress 5.0 以降
          wp_set_script_translations('mdbc-script', 'multi-day-booking-calendar',  plugin_dir_path(__FILE__) . 'languages');
        } else if (function_exists('gutenberg_get_jed_locale_data')) {
          // WordPress 4.9.8 以下(Gutenberg プラグインの場合)

          // load_textdomain に指定したドメイン名を指定
          $locale  = gutenberg_get_jed_locale_data('multi-day-booking-calendar');

          // 翻訳データを Javascriptオブジェクト(setLocaleData)に追加
          $content = 'wp.i18n.setLocaleData(' . json_encode($locale) . ', "multi-day-booking-calendar" );';

          // 指定したハンドル名のJavascriptの前にインラインで翻訳データ(JS)を挿入
          wp_script_add_data('mdbc-script', 'data', $content);
        }

        // CSS
        wp_enqueue_style(
          'mdbc-style',
          MDBC_URL . 'assets/style.css',
          array(),
          MDBC_VERSION
        );
      }


      /**
       * カスタムポストタイプ登録
       */
      public function register_post_type()
      {

        $labels = [
          "name" => __('Calendar', 'multi-day-booking-calendar'),
          "singular_name" => __("Calendar", "multi-day-booking-calendar"),
          "menu_name" => __("Calendar", "multi-day-booking-calendar"),
        ];

        $menu_icon = file_get_contents(MDBC_PATH . 'assets/images/icon-menu.svg');

        $args = [
          "label" => "Multi-day Booking Calendar",
          "labels" => $labels,
          "description" => "",
          "public" => false,
          "show_ui" => true,
          "show_in_rest" => true,
          "rest_base" => "",
          "rest_controller_class" => "WP_REST_Posts_Controller",
          "has_archive" => false,
          "show_in_menu" => true,
          "show_in_nav_menus" => true,
          "delete_with_user" => false,
          "exclude_from_search" => false,
          'capability_type' => 'page',
          "map_meta_cap" => true,
          "hierarchical" => false,
          'menu_icon' => 'data:image/svg+xml;base64,' . base64_encode($menu_icon),
          "supports" => ["title"],
          'show_in_rest'  => true,
        ];
        register_post_type("mdbc", $args);
      }

      /**
       * 編集画面に追加
       */
      public function add_schedule_field()
      {
        //カレンダー用ショートコード表示メタボックス
        add_meta_box('mdbc_shortcode', __('Shortcode', 'multi-day-booking-calendar'), array($this, 'insert_shorcode'), 'mdbc', 'normal');

        //カレンダー登録メタボックス
        add_meta_box('mdbc_schedule', __('Booking status', 'multi-day-booking-calendar'), array($this, 'insert_schedule'), 'mdbc', 'normal', 'high');

        //イベント日時登録メタボックス
        add_meta_box('mdbc_period', __('Event period', 'multi-day-booking-calendar'), array($this, 'insert_period'), 'mdbc', 'normal');
      }

      /**
       * プラグイン設定ページ追加
       */
      function add_plugin_admin_menu()
      {
        add_submenu_page(
          'edit.php?post_type=mdbc',
          __('MDBC Common Settings', 'multi-day-booking-calendar'), // page_title（オプションページのHTMLのタイトル）
          __('Common Settings', 'multi-day-booking-calendar'), // menu_title（メニューで表示されるタイトル）
          'administrator', // capability
          'mdbc', // menu_slug
          array($this, 'display_plugin_option_page') // callback
        );

        register_setting(
          'mdbc_options_group', // option_group
          'mdbc_options', // option_name
          array('sanitize_callback' =>  array($this, 'item_sanitize'))
        );
      }

      public function item_sanitize($args)
      {
        $errors = "";
        foreach ($args as $key => $value) {
          switch ($key) {
            case 'usetime':      // raio
              break;
            case 'interval':      // select
              break;
            case 'starttime':  // select
              if (!$value && $args['endtime']) {
                $errors .=  __('Time is an invalid value.', 'multi-day-booking-calendar') . "/";
              }
              break;
            case 'endtime':    // select
              if (!$value && $args['starttime']) {
                $errors .=  __('Time is an invalid value.', 'multi-day-booking-calendar') . "/";
              }
              break;
            case 'canover':    // check boolean
              $args[$key]     = $this->sanitize_item_checkbox($value, "false");
              break;
            case 'offday':    // input checkbos
              $args[$key]     = $this->sanitize_item_checkbox($value);
              break;
            case 'startdate':  // input 英文形式フォーマット
              if (!strtotime($value)) $errors .= __('The beginning of the reservable range is an invalid value.', 'multi-day-booking-calendar') . "/";
              break;
            case 'enddate':  // input 英文形式フォーマット startdate以降へ
              if (!strtotime($value)) $errors .= __('The end of the reservable range is an invalid value.', 'multi-day-booking-calendar') . "/";
              break;
            default:
          }
        }

        if ($errors) {
          add_settings_error(
            'mdbc_options',
            'mdbc_options_error',
            $errors,
            'error'
          );
          return get_option('mdbc_options');
        } else {
          $this->save_schedule("option");
          return $args;
        }
      }
      /**
       *
       */
      public static function sanitize_item_checkbox($args, $initial = "")
      {
        $args = isset($args) ? (array) $args : [$initial];
        $args = array_map('esc_attr', $args);
        return $args;
      }

      /**
       * 編集画面のカレンダー用ショートコード表示メタボックス
       *
       * @param object $post    現在のページオブジェクト
       */
      public function insert_shorcode($post)
      {
        if ($post->post_status !== "auto-draft") {
          $options = get_option('mdbc_options');
          $usetime = $options['usetime'][0];
          if ($usetime) {
            echo "<div id='mdbc-shorcode__text'>[multi-day-booking-calendar id='" . esc_html($post->ID) . "' start-name='' end-name='' start-time-name='' end-time-name='' mode='static']</div>";
          } else {
            echo "<div id='mdbc-shorcode__text'>[multi-day-booking-calendar id='" . esc_html($post->ID) . "' start-name='' end-name='' mode='static']</div>";
          }
        }
      }

      /**
       * 編集画面のカレンダー登録メタボックス
       *
       * @param object $post    現在のページオブジェクト
       */
      public function insert_schedule($post)
      {

        $period_start = get_post_meta($post->ID, "period-start", true) ? get_post_meta($post->ID, "period-start", true) : "";
        $period_end = get_post_meta($post->ID, "period-end", true) ? get_post_meta($post->ID, "period-end", true) : "";

        $is_autodraft = $post->post_status === "auto-draft" ? "data-isautodraft='true'" : "";
        wp_nonce_field(wp_create_nonce(MDBC_URL), 'schedule_nonce');

        echo '<div id="mdbc-edit" data-postid="' . esc_html($post->ID) . '" data-periodstart="' . esc_html($period_start) . '" data-periodend="' . esc_html($period_end) . '" ' . esc_html($is_autodraft) . '></div>';
      }

      /**
       * 編集画面の開催期間登録メタボックス
       *
       * @param object $post    現在のページオブジェクト
       */
      public function insert_period($post)
      {
        wp_nonce_field(wp_create_nonce(MDBC_URL), 'period_nonce');
?>
        <table class="form-table">
          <tbody>
            <tr>
              <th scope="row"><label for="title"><?php echo __('Reservable period', 'multi-day-booking-calendar'); ?></label></th>
              <td>
                <fieldset>
                  <legend class="screen-reader-text"><span><?php echo __('Reservable period', 'multi-day-booking-calendar'); ?></span></legend>
                </fieldset>
                <div>
                  <input type="date" name="period-start" autocomplete="off" value="<?php echo esc_html(get_post_meta($post->ID, 'period-start', true)); ?>">〜<input type="date" name="period-end" autocomplete="off" value="<?php echo esc_html(get_post_meta($post->ID, 'period-end', true)); ?>">
                </div>
                <p class="discription">
                  <?php echo __('If you do not set a period, you do not need to set it.', 'multi-day-booking-calendar'); ?>
                </p>
              </td>
            </tr>
          </tbody>
        </table>
<?php
      }

      /**
       * プラグイン設定ページ追加内容
       */
      function display_plugin_option_page()
      {
        include_once(MDBC_PATH . 'views/options.php');
      }

      /**
       * プラグイン有効時オプション登録
       */
      public static function register_options()
      {

        $default_options = array(
          "usetime"  => "false",
          "starttime" => "",
          "endtime"   => "",
          "interval"   => "60",
          "offday"    => array(),
          "canover"   => array("true"),
          "startdate" => '+1 day',
          "enddate"   => '+3 month',
        );
        if (get_option('mdbc_options') == false) {
          update_option('mdbc_options', $default_options);
        }
      }

      /**
       * プラグイン有効時DBテーブル設置
       */
      public static function create_tables()
      {
        global $wpdb;
        $sql = "";
        $charset_collate = "";
        // 接頭辞の追加
        $table_name = $wpdb->prefix . 'mdbc';
        // charsetを指定する
        if (!empty($wpdb->charset))
          $charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset} ";

        // 照合順序を指定する（ある場合。通常デフォルトのutf8_general_ci）
        if (!empty($wpdb->collate))
          $charset_collate .= "COLLATE {$wpdb->collate}";

        // SQL文でテーブルを作る
        $sql = "
         CREATE TABLE {$table_name} (
              id bigint(20) NOT NULL AUTO_INCREMENT,
              date date,
              reservedtimes varchar(128),
              memo varchar(128),
              status tinyint,
              price int,
              postid varchar(128),
              PRIMARY KEY (id)
         ) {$charset_collate};";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        update_option('mdbc_db_version', MDBC_DB_VERSION);
      }

      /**
       * 編集画面保存
       *
       * @param object $post_id    保存されたページID
       */
      public function save_schedule($post_id)
      {
        global $wpdb;

        $schedule_nonce = isset($_POST['schedule_nonce']) ? $_POST['schedule_nonce'] : null;
        if (!wp_verify_nonce($schedule_nonce, wp_create_nonce(MDBC_URL))) {
          return $post_id;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
          return $post_id;
        }
        if ($post_id !== "option" && !current_user_can('edit_post', $post_id)) {
          return $post_id;
        }

        //$_POSTからmdbcschedule_から始まるキーのみをフィルタ
        $updates = array_filter($_POST, function ($key) {
          return 0 === strpos($key, "mdbcschedule_");
        }, ARRAY_FILTER_USE_KEY);

        foreach ($updates as $key => $value) {
          $decode_value = json_decode(preg_replace('/\\\/u', '', $value));

          //sanitizes
          $filtered_value = array(
            "status" => "", //int
            "reservedtimes" => "", //strings
            "action" => "", //strings [insert or update]
            "id" => "", //int
            "date" => "", //date
          );
          if ($decode_value) {
            foreach ($decode_value as $key => $value) {
              switch ($key) {
                case "status":
                  $filtered_value[$key] = filter_var($value, FILTER_SANITIZE_NUMBER_INT);
                  break;
                case "reservedtimes":
                  if (!is_array($value)) {
                    $filtered_value[$key] = null;
                  } else {
                    $reservedtimes = array_map(function ($time) {
                      return filter_var($time, FILTER_SANITIZE_NUMBER_INT);
                    }, $value);
                    $filtered_value[$key] = json_encode($reservedtimes);
                  }
                  break;
                case "action":
                  $filtered_value[$key] = in_array($value, array('update', 'insert')) ? $value : null;
                  break;
                case "id":
                  $filtered_value[$key] = filter_var($value, FILTER_SANITIZE_NUMBER_INT);
                  break;
                case "date":
                  $filtered_value[$key] = strtotime($value) ? Date('Y-m-d', strtotime($value)) : null;
                  break;
                default:
                  $filtered_value[$key] = null;
              }
            }
          };

          $res = null;
          if ($filtered_value["action"] == "update") {
            $res = $wpdb->update(
              $wpdb->prefix . 'mdbc',
              array(
                'status' => $filtered_value["status"],
                'reservedtimes' => $filtered_value["reservedtimes"],
              ),
              array(
                'id' => $filtered_value["id"],
              ),
              array(
                '%d',
                '%s'
              ),
              array(
                '%d'
              )
            );
          } else if ($filtered_value["action"] == "insert") {
            $res = $wpdb->insert(
              $wpdb->prefix . 'mdbc',
              array(
                "date" =>  $filtered_value["date"],
                "status" => $filtered_value["status"],
                "postid" => $post_id,
                'reservedtimes' => $filtered_value["reservedtimes"],
              ),
              array(
                '%s',
                '%d',
                '%s',
                '%s',
              )
            );
          }
        }
      }

      /**
       * 編集画面予約可能日保存
       *
       * @param object $post_id    保存されたページID
       */
      public function save_period($post_id)
      {
        $period_nonce = isset($_POST['period_nonce']) ? $_POST['period_nonce'] : null;
        if (!wp_verify_nonce($period_nonce, wp_create_nonce(MDBC_URL))) {
          return $post_id;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
          return $post_id;
        }
        if (!current_user_can('edit_post', $post_id)) {
          return $post_id;
        }

        $keys = ["period-start", "period-end"];
        foreach ($keys as $key) {
          //date sanitizes
          $data = isset($_POST[$key]) && strtotime($_POST[$key]) ? Date('Y-m-d', strtotime($_POST[$key])) : "";
          if (get_post_meta($post_id, $key) == "" && $data != "") {
            add_post_meta($post_id, $key, $data, true);
          } elseif ($data != get_post_meta($post_id, $key, true) && $data != "") {
            update_post_meta($post_id, $key, $data);
          } elseif ($data == "") {
            delete_post_meta($post_id, $key, get_post_meta($post_id, $key, true));
          }
        }
      }


      /**
       * フロント画面:ショートコードがあった場合カレンダー表示
       *
       * @param object $args    ショートコードのオプション
       * @param strng カレンダー用タグ
       */
      public function view_from_shortcode($args)
      {

        $options = shortcode_atts(array(
          'start-name'  => null,
          'end-name'  => null,
          'start-time-name'  => null,
          'end-time-name'  => null,
          'id'  => null,
          'mode' => "static" //or inline
        ), $args);


        if ($options["id"] === "option") return null;

        $status = get_post_status($options["id"]);
        $can_get = false;
        if ($status === "publish" || current_user_can('publish_posts')) {
          //非公開投稿
          //編集者以上でなければfalse
          $can_get = true;
        }
        if (!$can_get) return null;

        $period_start = get_post_meta($options["id"], "period-start", true) ? get_post_meta($options["id"], "period-start", true) : "";
        $period_end = get_post_meta($options["id"], "period-end", true) ? get_post_meta($options["id"], "period-end", true) : "";

        return '<div id="mdbc-front" class="mdbc-front" data-postid="' . $options["id"] . '" data-start="' . $options["start-name"] . '" data-end="' . $options["end-name"] . '" data-starttime="' . $options["start-time-name"] . '" data-endtime="' . $options["end-time-name"] . '" data-periodstart="' . $period_start . '" data-periodend="' . $period_end . '" data-mode="' . $options["mode"] . '"></div>';
      }
    }

    MDBC::get_instance();

    add_action('wpcf7_init', function () {
      add_filter('wpcf7_form_elements', 'do_shortcode');
    });

  endif; // class_exists check

endif; // pro only check