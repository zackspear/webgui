<?php
// wrapper script to fill in $_GET and $_POST
// for POST when emhttp renders <fname.php> it invokes php-cli like this:
// cd /usr/local/emhttp ; /usr/bin/php ../sbin/wrap_post.php <fname.php> <querystring> <postdata>
//                                     argv[0]               argv[1]     argv[2]       argv[3]
// A similar thing happens when a webGui "page" is rendered.  In this case, any fname that begins
// with a capital letter and has no extension is assumed to be a Page.  For example, for POST, when emhttp
// renfers <Main/Disk> it invokes php-cli like this:
// cd /usr/local/emhttp ; /usr/bin/php ../sbin/wrap_post.php webGui/template.php <querystring> <postdata>
//                                     argv[0]               argv[1]                     argv[2]       argv[3]
// Included in <querystring> is "path=Main/Disk&prev=Main" where "prev" is the previous page rendered.
// Note: we place 'wrap_post.php' outside webGui root so user can't invoke directly (or else OOM crash!)
  $_SERVER['REQUEST_METHOD'] = "POST";
  $_SERVER['DOCUMENT_ROOT'] = "/usr/local/emhttp";
  parse_str($argv[2], $_GET);
  parse_str($argv[3], $_POST);
  include($argv[1]);
?>
