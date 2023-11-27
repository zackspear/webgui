<?PHP
/* Copyright 2005-2023, Lime Technology
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 */
?>
<?
function publish($endpoint, $message, $len=1) {
  $com = curl_init("http://localhost/pub/$endpoint?buffer_length=$len");
  curl_setopt_array($com, [CURLOPT_UNIX_SOCKET_PATH => "/var/run/nginx.socket", CURLOPT_RETURNTRANSFER => true]);
  preg_match('/subscribers: (\d+)/', curl_exec($com), $subs);
  // only send message when active subscribers are present
  if (!empty($subs[1]) && $subs[1]>0) {
    curl_setopt_array($com, [CURLOPT_POST => 1, CURLOPT_POSTFIELDS => $message]);
    $reply = curl_exec($com);
  }
  curl_close($com);
  return $reply ?? '';
}
?>
