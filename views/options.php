<?php

/**
 * 設定ページのHTML
 *
 */
if (!defined('ABSPATH')) {
  exit;
}

?>
<?php
settings_errors('mdbc_options');

$options = get_option('mdbc_options');

?>
<div class="mdbc-admin-toolbar">
  <h2>Multi-day Booking Calendar</h2>
  <a target="_blank" href="<?php echo PRO_URL; ?>" class="mdbc-btn-upgrade mdbc-only-normal">
    <p><?php echo __('Upgrade to Pro', 'multi-day-booking-calendar'); ?></p>
  </a>
</div>

<div class="wrap">

  <h1><?php echo __('MDBC Common Settings', 'multi-day-booking-calendar'); ?></h1>

  <form method="post" action="options.php">

    <?php
    wp_nonce_field(wp_create_nonce(MDBC_URL), 'schedule_nonce');
    settings_fields('mdbc_options_group');
    do_settings_sections('default');
    $usetime = isset($options['usetime']) ? esc_attr($options['usetime']) : "false";
    $starttime = isset($options['starttime']) ?  esc_attr($options['starttime']) : "";
    $endtime = isset($options['endtime']) ?  esc_attr($options['endtime']) : "";
    $interval = isset($options['interval']) ?  esc_attr($options['interval']) : "60";
    $offday = isset($options['offday']) ? $options['offday'] : array();
    $offday = MDBC::sanitize_item_checkbox($offday);  // サニタイズ
    $canover = isset($options['canover']) ?  $options['canover'] : array();
    $canover = MDBC::sanitize_item_checkbox($canover, "false");  // サニタイズ
    $startdate = isset($options['startdate']) ?  $options['startdate'] :  '+1 day';
    $enddate = isset($options['enddate']) ?  $options['enddate'] : '+1 month';
    ?>
    <div class="card">
      <h2 class="title"><?php echo __('Common Calendar', 'multi-day-booking-calendar'); ?></h2>
      <div id="mdbc-option" data-postid="option"></div>
    </div>
    <br>
    <h2 class="title"><?php echo __('Common Settings', 'multi-day-booking-calendar'); ?></h2>
    <table class="form-table">
      <tbody>
        <tr>
          <th scope="row"><?php echo __('Reserved Entry', 'multi-day-booking-calendar'); ?></th>
          <td>
            <fieldset>
              <legend class="screen-reader-text"><span><?php echo __('Reserved Entry', 'multi-day-booking-calendar'); ?></span></legend>
              <p><label>
                  <input name="mdbc_options[usetime]" type="radio" value="false" <?php checked($usetime, 'false'); ?>><?php echo __('Day only', 'multi-day-booking-calendar'); ?></label>
              </p>
              <p><label>
                  <input name="mdbc_options[usetime]" type="radio" value="true" <?php checked($usetime, 'true'); ?>><?php echo __('Include Time', 'multi-day-booking-calendar'); ?></label>
              </p>
            </fieldset>
          </td>
        </tr>
        <tr>
          <th scope="row"><?php echo __('Bookable Times', 'multi-day-booking-calendar'); ?></th>
          <td>
            <fieldset>
              <legend class="screen-reader-text"><span><?php echo __('Bookable Times', 'multi-day-booking-calendar'); ?></span></legend>
              <p><label for="mdbc_options-starttime">
                  <?php echo __('Start Times', 'multi-day-booking-calendar'); ?>: <select name="mdbc_options[starttime]" id="mdbc_options-starttime" <?php if ($usetime == "false") echo  "disabled"; ?>>
                    <option value=""><?php echo __('Always', 'multi-day-booking-calendar'); ?></option>
                    <?php
                    for ($i = 0; $i < 24; $i++) {
                      $is_checked = ($starttime === sprintf('%02d', $i) . "00") ? "selected" : "";
                      echo "<option value='" . esc_html(sprintf('%02d', $i)) . "00' " . esc_html($is_checked) . ">" . esc_html($i) . ":00〜</option>";
                      $is_checked = ($starttime === sprintf('%02d', $i) . "30") ? "selected" : "";
                      echo "<option value='" . esc_html(sprintf('%02d', $i)) . "30' " . esc_html($is_checked) . ">" . esc_html($i) . ":30〜</option>";
                    }
                    ?>
                  </select>
                </label></p>
              <p><label for=" mdbc_options-endtime">
                  <?php echo __('End Time', 'multi-day-booking-calendar'); ?>: <select name="mdbc_options[endtime]" id="mdbc_options-endtime" <?php if ($usetime == "false") echo  "disabled"; ?>>
                    <option value=""><?php echo __('Always', 'multi-day-booking-calendar'); ?></option>
                    <option value='0030' <?php $endtime === "0030" ? print "selected" : "" ?>>〜0:30</option>
                    <?php
                    for ($i = 1; $i < 24; $i++) {
                      $is_checked = ($endtime === sprintf('%02d', $i) . "00") ? "selected" : "";
                      echo "<option value='" . esc_html(sprintf('%02d', $i)) . "00' " . esc_html($is_checked) . ">〜" . esc_html($i) . ":00</option>";
                      $is_checked = ($endtime === sprintf('%02d', $i) . "30") ? "selected" : "";
                      echo "<option value='" . esc_html(sprintf('%02d', $i)) . "30' " . esc_html($is_checked) . ">〜" . esc_html($i) . ":30</option>";
                    }
                    ?>
                    <option value='2359' <?php $endtime === "2359" ? print "selected" : "" ?>>〜0:00</option>
                  </select>
                </label>
              </p>
            </fieldset>
          </td>
        </tr>
        <tr>
          <th scope="row"><?php echo __('Reservation Interval', 'multi-day-booking-calendar'); ?></th>
          <td>
            <fieldset>
              <legend class="screen-reader-text"><span><label for="mdbc_options-interval"><?php echo __('Reservation Interval', 'multi-day-booking-calendar'); ?></label></span></legend>
              <p>
                <select name="mdbc_options[interval]" id="mdbc_options-interval" <?php if ($usetime == "false") echo  "disabled"; ?>>
                  <option value='15' <?php selected($interval, '15'); ?>><?php echo __('15 min', 'multi-day-booking-calendar'); ?></option>
                  <option value='30' <?php selected($interval, '30'); ?>><?php echo __('30 min', 'multi-day-booking-calendar'); ?></option>
                  <option value='60' <?php selected($interval, '60'); ?>><?php echo __('1 hour', 'multi-day-booking-calendar'); ?></option>
                </select>
              </p>
            </fieldset>
            <p class="description mdbc-only-normal">
              <a target="_blank" href="<?php echo PRO_URL; ?>"><?php echo __('The Pro version', 'multi-day-booking-calendar'); ?></a> <?php echo __(', you can choose from 15 minutes/30 minutes/1 hour.', 'multi-day-booking-calendar'); ?>
            </p>
          </td>
        </tr>
        <tr>
          <th scope="row"><?php echo __('Regular Holiday', 'multi-day-booking-calendar'); ?></th>
          <td>
            <fieldset>
              <legend class="screen-reader-text"><span><?php echo __('15 min', 'multi-day-booking-calendar'); ?></span></legend>
              <p><label><input name="mdbc_options[offday][]" id="mdbc_options-offday-01" type="checkbox" <?php checked(in_array('0', $offday), true); ?> value="0" /><?php echo __('Sunday', 'multi-day-booking-calendar'); ?></label></p>
              <p><label><input name="mdbc_options[offday][]" id="mdbc_options-offday-02" type="checkbox" <?php checked(in_array('1', $offday), true); ?> value="1" /><?php echo __('Monday', 'multi-day-booking-calendar'); ?></label></p>
              <p><label><input name="mdbc_options[offday][]" id="mdbc_options-offday-03" type="checkbox" <?php checked(in_array('2', $offday), true); ?> value="2" /><?php echo __('Tuesday', 'multi-day-booking-calendar'); ?></label></p>
              <p><label><input name="mdbc_options[offday][]" id="mdbc_options-offday-04" type="checkbox" <?php checked(in_array('3', $offday), true); ?> value="3" /><?php echo __('Wednesday', 'multi-day-booking-calendar'); ?></label></p>
              <p><label><input name="mdbc_options[offday][]" id="mdbc_options-offday-05" type="checkbox" <?php checked(in_array('4', $offday), true); ?> value="4" /><?php echo __('Thursday', 'multi-day-booking-calendar'); ?></label></p>
              <p><label><input name="mdbc_options[offday][]" id="mdbc_options-offday-06" type="checkbox" <?php checked(in_array('5', $offday), true); ?> value="5" /><?php echo __('Friday', 'multi-day-booking-calendar'); ?></label></p>
              <p><label><input name="mdbc_options[offday][]" id="mdbc_options-offday-07" type="checkbox" <?php checked(in_array('6', $offday), true); ?> value="6" /><?php echo __('Saturday', 'multi-day-booking-calendar'); ?></label></p>
            </fieldset>
            <hr>
            <fieldset>
              <legend class="screen-reader-text"><span><?php echo __('Reservations across holidays', 'multi-day-booking-calendar'); ?></span></legend>
              <label for="mdbc_options-canover"><input name="mdbc_options[canover][]" type="checkbox" id="mdbc_options-canover" <?php checked($canover[0], "true"); ?> value="true">
                <?php echo __('Reservations can be made across holidays.', 'multi-day-booking-calendar'); ?></label>
            </fieldset>
          </td>
        </tr>
        <tr>
          <th scope="row"><?php echo __('Reservation Period', 'multi-day-booking-calendar'); ?></th>
          <td>
            <fieldset>
              <legend class="screen-reader-text"><span><?php echo __('Reservation Period', 'multi-day-booking-calendar'); ?></span></legend>
              <input name="mdbc_options[startdate]" type="text" id="mdbc_options-startdate" value="<?php echo esc_html($startdate); ?>" size="12"> 〜
              <input name="mdbc_options[enddate]" type="text" id="mdbc_options-enddate" value="<?php echo esc_html($enddate); ?>" size="12">
            </fieldset>
            <p class="description mdbc-only-normal">
              <a target="_blank" href="<?php echo PRO_URL; ?>"><?php echo __('The Pro version', 'multi-day-booking-calendar'); ?></a> <?php echo __('allows you to change the value.', 'multi-day-booking-calendar'); ?>
              <br><br>
            </p>

            <p class="description">
              <?php echo __('Please enter in English format.', 'multi-day-booking-calendar'); ?>
            </p>
            <p class="description"><?php echo __('1 day later', 'multi-day-booking-calendar'); ?>: <code>+1 day</code></p>
            <p class="description"><?php echo __('After 2 weeks', 'multi-day-booking-calendar'); ?>: <code>+2 week</code></p>
            <p class="description"><?php echo __('After 3 months', 'multi-day-booking-calendar'); ?> : <code>+3 month</code></p>
            <p class="description"><?php echo __('After 4 years', 'multi-day-booking-calendar'); ?> : <code>+4 year</code></p>
          </td>
        </tr>
      </tbody>
    </table>

    <?php submit_button(); // 送信ボタン
    ?>

  </form>

</div><!-- .wrap -->

<script>
  (() => {
    const start = document.getElementById('mdbc_options-starttime');
    const end = document.getElementById('mdbc_options-endtime');
    const interval = document.getElementById('mdbc_options-interval');
    document.getElementsByName("mdbc_options[usetime]").forEach(
      r => r.addEventListener("change",
        (e) => {
          if (e.target.value == "false") {
            start.disabled = end.disabled = interval.disabled = true;
          } else {
            start.disabled = end.disabled = false;
            <?php if (MDBC_PRO) : ?>
              interval.disabled = false;
            <?php endif; ?>
          }
        }
      )
    );
  })();
</script>