<?php
/*  Copyright 2008  Kieran O'Shea  (email : kieran@kieranoshea.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// Direct access shouldn't be allowed
if ( ! defined( 'ABSPATH' ) ) exit;

// Should we allow the feed to go out?
if (calendar_get_config_value('enable_feed') == 'true') {
  // Output the headers
  header("Content-type: text/calendar", true);
  header('Content-Disposition: attachment; filename="calendar.ics"');

  // Head up the file
  echo "BEGIN:VCALENDAR
VERSION:2.0
METHOD:PUBLISH
PRODID:-//wordpress.org/plugins calendar//EN
CALSCALE:GREGORIAN
";

  // Add support for time zones, assume UTC if no date found
  $found_timezone = get_option('timezone_string');
  $tz_prefix = "";
  $utc_tail = "Z";
  if (!empty($found_timezone)) {
    $tz_prefix = ";TZID=".$found_timezone;
    $utc_tail = "";
    echo "BEGIN:VTIMEZONE
TZID:".esc_html($found_timezone)."
TZURL:http://tzurl.org/zoneinfo/".esc_html($found_timezone)."
X-LIC-LOCATION:".esc_html($found_timezone)."
END:VTIMEZONE
";
  }

  // Hard code future days to protect server load
  $future_days = 30;
  $day_count = 0;
  while ($day_count < $future_days+1)
  {
    // Craft our days into the future with the current one as a reference, get the eligible event on that day
    list($y,$m,$d) = explode("-",gmdate("Y-m-d",mktime($day_count*24,0,0,gmdate("m"),gmdate("d"),gmdate("Y"))));
    $events = grab_events($y,$m,$d,null);
    usort($events, "calendar_time_cmp");

    // Iterate through the events list and define a iCalendar VEVENT for each
    foreach($events as $event) {
      if ($event->event_time == '00:00:00') {
        $start = gmdate('Ymd',mktime($day_count*24,0,0,gmdate("m"),gmdate("d"),gmdate("Y")));
        $end = gmdate('Ymd',mktime(($day_count+1)*24,0,0,gmdate("m"),gmdate("d"),gmdate("Y")));;
      } else {
        // A little fudge on the end time here; we assume all events are 1 hour as end time isn't a field in calendar
        $start = gmdate('Ymd',mktime($day_count*24,0,0,gmdate("m"),gmdate("d"),gmdate("Y")))."T".gmdate('His',strtotime($event->event_time)).$utc_tail;
        $end = gmdate('Ymd',mktime($day_count*24,0,0,gmdate("m"),gmdate("d"),gmdate("Y")))."T".gmdate('His',strtotime($event->event_time)+3600).$utc_tail;
      }
      echo "BEGIN:VEVENT
DTSTART".esc_html($tz_prefix).":".esc_html($start)."
DTEND".esc_html($tz_prefix).":".esc_html($end)."
SUMMARY:".esc_html($event->event_title)."
DESCRIPTION:".esc_html($event->event_desc)."
UID:eventId=".esc_html($event->event_id)."eventInstance=".esc_html($day_count)."@".esc_html($_SERVER['SERVER_NAME'])."
SEQUENCE:0
DTSTAMP:".esc_html(gmdate("Ymd\THis\Z"))."
END:VEVENT
"; // Note the UID definition; event id is unique per install, each install has it's own domain (in theory), day count
   // prevents dupe UIDs where there are repeats of the same event
    }
    $day_count++;
  }

  // Tail of the file, the mime type and return
  echo "END:VCALENDAR";
}
?>
