<?PHP
/* Copyright 2005-2023, Lime Technology
 * Copyright 2012-2023, Bergware International.
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
  curl_setopt_array($com, [CURLOPT_UNIX_SOCKET_PATH => "/var/run/nginx.socket", CURLOPT_HTTPHEADER => ['Accept:text/json'], CURLOPT_RETURNTRANSFER => 1]);
  $reply = json_decode(curl_exec($com),true);
  // only send message when active subscribers are present
  if (($reply['subscribers']??0)>0) {
    curl_setopt_array($com, [CURLOPT_POST => 1, CURLOPT_POSTFIELDS => $message]);
    $reply = json_decode(curl_exec($com),true);
  }
  curl_close($com);
  // return number of active subscribers
  return $reply['subscribers']??0;
}
?>
