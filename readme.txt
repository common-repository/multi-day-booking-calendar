=== Multi-day Booking Calendar ===
Contributors: matorel
Tags: booking,calendar,reserve,schedule,form,multi-day,contact form7,予約,予定,カレンダー,フォーム,複数日
Requires at least: 4.9
Tested up to: 6.0
Stable tag: 1.0.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Text Domain: multi-day-booking-calendar
Requires PHP: 7.0

This plugin allows you to link an existing form with a reservation calendar that allows multiple day and time selections.

== Description ==

This plugin allows you to link an existing form with a reservation calendar that allows multiple day and time selections.
The administration screen is a simple configuration screen that can be used to set business days, business hours, and reserved dates and times, and can issue embedded shortcodes.
Since the form itself does not provide any functionality, it can be linked directly to existing forms.
The simplicity and lack of extra features makes it easy to get started without confusion.
It can be used for car rental reservations, lodging reservations, event reservations, seminar reservations, meeting room reservations, lesson reservations, etc.

このプラグインは複数日の選択・時間の選択ができる予約カレンダーを既存のフォームに連携させることができます。
管理画面はシンプルな設定画面で営業日や営業時間、予約済みの日時などの設定ができ、埋め込みショートコードの発行が可能です。
フォーム自体の機能は提供しないため、既存のフォームにそのまま連携させることができます。
シンプルで余計な機能がないので混乱せずに簡単に始めることができます。
レンタカー予約、宿泊予約、イベント予約、セミナー予約、会議室予約、レッスン予約など様々な用途でご利用いただけます。

**How to Use**

1. After activating the plugin, open the Common Settings page of the Calendar. The Common Settings page allows you to set holidays, regular holidays, and periods and times when appointments can be made.
プラグインを有効化したらカレンダーの共通設定ページを開きます。共通設定画面では、休日や定休日、予約可能な期間や時間帯を設定することができます。
1. The new submission page of the Calendar allows for day and time status management for each event.
カレンダーの新規投稿ページでは、イベントごとに日にち・時間帯のステータス管理が可能です。
1. Once the shortcode is issued on the above page, insert it into a form submission containing inputs, such as Contact Form 7. Refer to "Shortcode Settings" below for the attributes of the shortcode, and only the calendar for viewing will be displayed on article pages without inputs. In such cases, do not enter any attribute values other than id.
上記ページでショートコードが発行されたら、Contact Form 7 などのinputの含まれたフォーム投稿に挿入します。ショートコードの各属性は下記の「Shortcode Settings」を参照してください。inputのない記事ページなどでは閲覧用のカレンダーのみが表示されます。その場合、id以外の属性値は記述しないでください。
1. The front screen displays the registered calendar, and selecting a day and time is reflected in the input.
フロント画面では登録したカレンダーを表示させ、日にち・時間を選択するとinputに反映されます。

**Shortcode Settings**
`
[multi-day-booking-calendar id='' start-name='' end-name='' start-time-name='' end-time-name='' mode='static']
`

* **id**
Submission ID of registered calendar. Required.
* **start-name**
The name attribute of the input tag for the start date.
* **start-time-name**
The name attribute of the input tag for the start time.
* **end-name**
The name attribute of the input tag for the end date.
* **end-time-name**
The name attribute of the input tag for the end time.
* **mode**
*static* : Embed the calendar in the page. default.
*inline* : A calendar dialog box appears when you click on the input tag.

**API**
By the time the form is submitted, the reservation may have been available or changed.
The plugin does not provide form functionality, so to find out exactly what is happening, please hit the API provided by the plugin before submitting the form.

フォームを送信するまでに、予約が可能か変更されている可能性があります。
当プラグインではフォーム機能は提供していないため、厳密に調べるためには、フォーム送信前に当プラグインが提供しているAPIを叩いてください。

***Example for Contact Form 7***
Please insert the ID of the registered calendar in the form content.
フォーム内容に下記のように登録カレンダーのIDを仕込んでください。

`
[hidden mdbc-id "2115"]
`
---
`
add_filter('wpcf7_validate', array($this, 'custom_wpcf7_validate'), 11, 2);

function custom_wpcf7_validate($result, $tags)
  {
    $mdbc_id = null;
    foreach ($tags as $tag) {
      $name = $tag['name'];
      switch ($name) {
        case 'start-day': //input tag name
          $start_date = Date('Y-m-d', strtotime($_POST[$name]));
          break;
        case 'start-time': //input tag name
          $start_time = $_POST[$name] ? $_POST[$name] : "00:00";
          break;
        case 'end-day': //input tag name
          $end_date =  Date('Y-m-d', strtotime($_POST[$name]));
          break;
        case 'end-time': //input tag name
          $end_time = $_POST[$name] ? $_POST[$name] : "00:00";
          break;
        case 'mdbc-id': //ID of registered calendar
          $mdbc_id =  $_POST[$name];
        default:
          break;
      }
    }
    if ($mdbc_id) {
      $request = WP_REST_Request::from_url(home_url('/?rest_route=/mdbc/v1/check_reserve'));
      $request->set_method('POST');
      $request->set_param("postid", $mdbc_id);
      $request->set_param("start", $start_date . " " . $start_time);
      $request->set_param("end", $end_date . " " . $end_time);
      $response = rest_do_request($request);
      if ($response->is_error()) {
        $result->invalidate("start-day", 'You cannot make reservations during that specified time period.');//Output error.
      } else {
        $data = $response->get_data();
        if (!$data["can_reserve"])
          $result->invalidate("start-day", 'You cannot make reservations during that specified time period.');//Output error.
      }
    }
    return $result;
  }
`

== Frequently Asked Questions ==
= 開始日のみ選択させたい =

可能です。ショートコードのend-nameとend-time-nameを削除して下さい。
[multi-day-booking-calendar id='' start-name='' end-name='' start-time-name='' end-time-name='' mode='static']

= 予約指定間隔を変更したい =

プロ版で可能です。 [こちら](https://matorel.com/multi-day-booking-calendar-pro) より購入してください。

= 予約可能期間を変更したい =

プロ版で可能です。 [こちら](https://matorel.com/multi-day-booking-calendar-pro) より購入してください。

== Installation ==

1. From the WP admin panel, click “Plugins” -> “Add new”.
2. In the browser input box, type “Multi-day Booking Calendar”.
3. Select the “Multi-day Booking Calendar” plugin and click “Install”.
4. Activate the plugin.

OR…

1. Download the plugin from this page.
2. Save the .zip file to a location on your computer.
3. Open the WP admin panel, and click “Plugins” -> “Add new”.
4. Click “upload”.. then browse to the .zip file downloaded from this page.
5. Click “Install”.. and then “Activate plugin”.

== Screenshots ==
1. The common settings screen allows you to set holidays, regular holidays, and available reservation periods and hours. 共通設定画面では、休日や定休日、予約可能な期間や時間帯を設定することができます。
2. The calendar registration screen allows status management by date and time. カレンダー登録画面では、日にち・時間帯のステータス管理が可能です。
3. Insert shortcodes into article submission pages, Contact Form 7 submission pages, etc. ショートコードを記事投稿ページや Contact Form 7 投稿ページなどに挿入します。
4. The front screen displays the registered calendar, and the day and time can be selected. フロント画面では登録したカレンダーを表示させ、日にち・時間が選択できます。

== Changelog ==
= 1.0.0 =
First commit.

= 1.0.1 =
終了時間も選択可能に修正。

== Upgrade Notice ==
