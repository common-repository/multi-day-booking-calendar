<?php

/**
 * Regisgter API Endpoints
 */

if (!class_exists('MDBC_Custom_Route')) :

  class MDBC_Custom_Route extends WP_REST_Controller
  {

    /**
     * Register the routes for the objects of the controller.
     */
    public function register_routes()
    {
      $version = '1';
      $namespace = 'mdbc/v' . $version;

      //月ごとにスケジュール取得
      //public以外は管理者のみ
      register_rest_route($namespace, '/get_schedule', array(
        'methods' => WP_REST_Server::CREATABLE,
        'permission_callback' => '__return_true',
        'callback' => [$this, 'get_schedule'],
      ));

      //指定期間内は有効か
      //public以外は管理者のみ
      register_rest_route($namespace, '/check_reserve', array(
        'methods' => WP_REST_Server::CREATABLE,
        'permission_callback' => '__return_true',
        'callback' => [$this, 'check_reserve'],
      ));

      //プラグインオプション取得
      //ステータス関係なく取得可能
      register_rest_route($namespace, '/get_options', array(
        'methods' => WP_REST_Server::CREATABLE,
        'permission_callback' => '__return_true',
        'callback' => [$this, 'get_options'],
      ));
    }

    /**
     * 投稿ステータスによってユーザー権限で取得可否判断
     *
     * @param string
     * @return boolean
     */
    function can_get_api($postid)
    {
      if ($postid === "option") return true;

      $status = get_post_status($postid);
      $can_get = false;
      if ($status === "publish" ||  current_user_can('publish_posts')) {
        //非公開投稿
        //編集者以上でなければfalse
        $can_get = true;
      }
      return $can_get;
    }

    /**
     * 指定期間内のスケジュール取得api
     *
     * @param object WP_REST_Reques POSTオブジェクト(postid start[YYYY-MM-DD] end[YYYY-MM-DD])
     * @return string WP_Error|WP_REST_Response 指定期間内のスケジュールjson
     */
    public function get_schedule(WP_REST_Request $request)
    {
      global $wpdb;
      $table_name = $wpdb->prefix . 'mdbc';
      $errors = [];

      if (!isset($request['start']) || $request['start'] === '') {
        $errors[] = __('The start date has not been sent.', 'multi-day-booking-calendar');
      } else if (!strtotime($request['start'])) {
        $errors[] =  __('The start date is an invalid value.', 'multi-day-booking-calendar');
      } else {
        $req_start = $request["start"];
      }
      if (!isset($request['end']) || $request['end'] === '') {
        $errors[] = __('The end date has not been sent.', 'multi-day-booking-calendar');
      } else if (!strtotime($request['end'])) {
        $errors[] = __('The end date is an invalid value.', 'multi-day-booking-calendar');
      } else {
        $req_end = $request["end"];
      }
      if (!isset($request['postid']) || $request['postid'] === '') {
        $errors[] = __('The calendar ID has not been sent.', 'multi-day-booking-calendar');
      } else {
        $postid = $request["postid"];

        if (!$this->can_get_api($postid)) {
          $errors[] = __('The calendar has not been published.', 'multi-day-booking-calendar');
        }
      }
      if (!count($errors)) {
        $query = "SELECT * FROM {$table_name} WHERE postid = %s AND date BETWEEN %s AND %s";
        $results = $wpdb->get_results($wpdb->prepare($query, $postid, $req_start, $req_end));
      }
      //reservedtimes:空き時間配列 0〜48 30分刻み
      //price: タイプを数字で
      //status: 削除予定 0:○ 1:▲ 2:✗

      if (!count($errors)) {
        $response = new WP_REST_Response($results);
        $response->set_status(200);
        return $response;
      } else {
        $error_response = new WP_Error();
        foreach ($errors as $error) {
          $error_response->add('', $error);
        }
        return $error_response;
      }
    }

    /**
     * プラグイン設定取得
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return string WP_Error|WP_REST_Response プラグイン設定json
     */
    public function get_options(WP_REST_Request $request)
    {
      $options = get_option('mdbc_options');
      if (isset($options["startdate"])) {
        $options["startdate"] = date("Y-m-d", strtotime($options["startdate"]));
      }
      if (isset($options["enddate"])) {
        $options["enddate"] =  date("Y-m-d", strtotime($options["enddate"]));
      }
      $response = new WP_REST_Response($options);
      $response->set_status(200);
      return $response;
    }

    /**
     * 指定期間内に指定できない日があるか取得
     *
     * @param WP_REST_Request $request  POSTオブジェクト(postid start[YYYY-MM-DD hh:mm] end[YYYY-MM-DD hh:mm])
     * @return boolean WP_Error|WP_REST_Response boolean 指定できるか
     */
    public function check_reserve(WP_REST_Request $request)
    {
      global $wpdb;
      $errors = [];
      $messages = [];
      $table_name = $wpdb->prefix . 'mdbc';
      if (!isset($request['start']) || $request['start'] === '') {
        $errors[] = __('The start date has not been sent.', 'multi-day-booking-calendar');
      } else if (!strtotime($request['start'])) {
        $errors[] =  __('The start date is an invalid value.', 'multi-day-booking-calendar');
      } else {
        $req_start = $request["start"];
      }
      if (!isset($request['end']) || $request['end'] === '') {
        $errors[] = __('The end date has not been sent.', 'multi-day-booking-calendar');
      } else if (!strtotime($request['end'])) {
        $errors[] = __('The end date is an invalid value.', 'multi-day-booking-calendar');
      } else {
        $req_end = $request["end"];
      }
      if (!isset($request['postid']) || $request['postid'] === '') {
        $errors[] = __('The calendar ID has not been sent.', 'multi-day-booking-calendar');
      } else {
        $postid = $request["postid"];

        if (!$this->can_get_api($postid)) {
          $errors[] = __('The calendar has not been published.', 'multi-day-booking-calendar');
        }
      }

      if (!count($errors)) {
        $can_reserve = true;

        $req_start_datetime = strtotime($req_start);
        $req_end_datetime = strtotime($req_end);
        $req_start_date = date('Ymd', $req_start_datetime);
        $req_end_date = date('Ymd', $req_end_datetime);

        //登録カレンダーの期間内status取得
        $query = "SELECT status,date,reservedtimes FROM {$table_name} WHERE postid = %s AND date BETWEEN %s AND %s";
        $results = $wpdb->get_results($wpdb->prepare($query, $postid, $req_start_date, $req_end_date));

        //共通カレンダーの期間内status取得
        $common_query = "SELECT status,date FROM {$table_name} WHERE postid = %s AND date BETWEEN %s AND %s";
        $common_results = $wpdb->get_results($wpdb->prepare($common_query, "option", $req_start_date, $req_end_date));

        //プラグイン設定取得
        $options = get_option('mdbc_options');
        $usetime = isset($options['usetime']) ? $options['usetime'] : false;
        $starttime = isset($options['starttime']) ?  $options['starttime'] : "0000";
        $endtime = isset($options['endtime']) ?  $options['endtime'] : "2359";
        $interval = isset($options['interval']) ?  $options['interval'] : "60";
        $canover = isset($options['canover']) ?  $options['canover'] : array("false");
        $offday = isset($options['offday']) ? $options['offday'] : array();
        $startdate = isset($options['startdate']) ? date("Y-m-d", strtotime($options["startdate"])) : date("Y-m-d", strtotime("+1 day"));
        $enddate = isset($options['enddate']) ? date("Y-m-d", strtotime($options["enddate"])) : date("Y-m-d", strtotime("+1 year"));
        $period_start = get_post_meta($postid, "period-start", true) ? get_post_meta($postid, "period-start", true) : "";
        $period_end = get_post_meta($postid, "period-end", true) ? get_post_meta($postid, "period-end", true) : "2100-01-01";
        $min_date = max($startdate, $period_start); //Y-m-d
        $max_date = min($enddate, $period_end); //Y-m-d

        //指定期間より前
        if (strtotime($min_date) > strtotime($req_start_date)) {
          $can_reserve = false;
          $messages[] = __('The start date is a date that cannot be reserved.', 'multi-day-booking-calendar');
        }
        //指定期間より後
        if (strtotime($max_date) < strtotime($req_end_date)) {
          $can_reserve = false;
          $messages[] = __('The end date is a date that cannot be reserved.', 'multi-day-booking-calendar');
        }

        if ($can_reserve) {
          //期間を配列に
          $days = [];
          // 期間内の日付をすべて取得
          for ($i = $req_start_date; $i <= $req_end_date; $i++) {
            $year = substr($i, 0, 4);
            $month = substr($i, 4, 2);
            $day = substr($i, 6, 2);
            if (checkdate($month, $day, $year))
              $days[] = array("date" => date('Y-m-d', strtotime($i)), "status" => 0);
          }

          //日付ごとにステータス設定
          foreach ($days as $day) {
            $status = 0;
            $datetime = new DateTime($day["date"]);
            $w = $datetime->format('w');

            //まず定休日12
            if (in_array($w, $offday)) {
              $status = 12;
            }

            //共通カレンダーのステータスが1,13
            foreach ($common_results as $row) {
              if ($row->date == $day["date"]) {
                if (in_array($row->status, array(1, 13))) {
                  $status = $row->status;
                }
                break;
              }
            }

            //登録カレンダー走査
            foreach ($results as $row) {
              if ($row->date == $day["date"]) {

                $opening_time = strtotime($day["date"] . " " . substr_replace($starttime, ':', 2, 0));
                $closing_time = strtotime($day["date"] . " " . substr_replace($endtime, ':', 2, 0));
                $isNightDay = $opening_time > $closing_time;
                if ($usetime === "true") {
                  //時間指定あり設定の場合
                  //指定期間内のreservedtimesが
                  //営業時間内にあれば$can_reserve = false;
                  $reserved_times = json_decode($row->reservedtimes, true);
                  if ($reserved_times) {
                    foreach ($reserved_times as $reserved_time) {
                      $_reserved_time = strtotime($day["date"] . " " . substr_replace($reserved_time, ':', 2, 0));
                      $include_time = false;
                      // $reservedtimeが営業時間内でなければスキップ
                      if ($isNightDay) {
                        $include_time =  $_reserved_time < $closing_time || $opening_time <=  $_reserved_time;
                      } else {
                        $include_time = $opening_time <=  $_reserved_time &&  $_reserved_time < $closing_time;
                      }

                      if ($include_time && $req_start_datetime <= $_reserved_time && $_reserved_time < $req_end_datetime) {
                        $can_reserve = false;
                        $messages[] = __('Includes time that cannot be reserved.', 'multi-day-booking-calendar');
                        break 2;
                      }
                    }
                  }
                }

                //登録カレンダーのステータスが1,11
                if (in_array($row->status, array(1, 11))) {
                  $status = $row->status;
                }
                break;
              }
            }

            //12(定休日),13(休日)があって、またげなければ(当日も)予約できない
            if (in_array($status, array(12, 13))) {
              if ($canover[0] !== "true" ||  date('Ymd', strtotime($day["date"])) === $req_start_date || date('Ymd', strtotime($day["date"])) === $req_end_date) {
                $can_reserve = false;
                $messages[] = __('Reservations cannot be made on holidays.', 'multi-day-booking-calendar');
                break;
              }
            }
            //11(不可能)があれば予約できない
            if (in_array($status, array(11))) {
              $can_reserve = false;
              $messages[] = __('Non-reservable dates are included.', 'multi-day-booking-calendar');
              break;
            }
          }
        }
      }

      if (!count($errors)) {
        $response = new WP_REST_Response(array("can_reserve" => $can_reserve, "message" => $messages));
        $response->set_status(200);
        return $response;
      } else {
        $error_response = new WP_Error();
        foreach ($errors as $error) {
          $error_response->add('', $error);
        }
        return $error_response;
      }
    }
  }


  add_action('rest_api_init', function () {
    $controller = new MDBC_Custom_Route();
    $controller->register_routes();
  });

endif; // class_exists check