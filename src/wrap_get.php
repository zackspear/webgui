<?php
// wrapper script to fill in $_GET and $_POST
// for GET when emhttp renders <fname.php> it invokes php-cli like this:
// cd /usr/local/emhttp ; /usr/bin/php ../sbin/wrap_get.php <fname.php> <querystring>
//                                     argv[0]               argv[1]     argv[2]
// A similar thing happens when a webGui "page" is rendered.  In this case, any fname that begins
// with a capital letter and has no extension is assumed to be a Page.  For example, for GET, when emhttp
// renfers <Main/Disk> it invokes php-cli like this:
// cd /usr/local/emhttp ; /usr/bin/php ../sbin/wrap_get.php webGui/template.php <querystring>
//                                     argv[0]               argv[1]                     argv[2]
// Included in <querystring> is "path=Main/Disk&prev=Main" where "prev" is the previous page rendered.
// Note: we place 'wrap_get.php' outside webGui root so user can't invoke directly (or else OOM crash!)
  $_SERVER['REQUEST_METHOD'] = "GET";
  $_SERVER['DOCUMENT_ROOT'] = "/usr/local/emhttp";
  parse_str($argv[2], $_GET);
  include($argv[1]);
?>
