<?php
/**
 * jQuery File Tree PHP Connector
 *
 * Version 1.2.0
 *
 * @author - Cory S.N. LaViska A Beautiful Site (http://abeautifulsite.net/)
 * @author - Dave Rogers - https://github.com/daverogers/jQueryFileTree
 *
 * History:
 *
 * 1.2.0 - adapted by Bergware for use in Unraid - support UTF-8 encoding
 * 1.1.1 - SECURITY: forcing root to prevent users from determining system's file structure (per DaveBrad)
 * 1.1.0 - adding multiSelect (checkbox) support (08/22/2014)
 * 1.0.2 - fixes undefined 'dir' error - by itsyash (06/09/2014)
 * 1.0.1 - updated to work with foreign characters in directory/file names (12 April 2008)
 * 1.0.0 - released (24 March 2008)
 *
 * Output a list of files for jQuery File Tree
 */

/**
 * filesystem root - USER needs to set this!
 * -> prevents debug users from exploring system's directory structure
 * ex: $root = $_SERVER['DOCUMENT_ROOT'];
 */
$root = '/';
if (!$root) exit("ERROR: Root filesystem directory not set in jqueryFileTree.php");

$rootdir = preg_replace("#[\/]+#","/",$root.($_POST['dir'] ?? ''));
$filters = (array)($_POST['filter'] ?? '');
$match   = ($_POST['match'] ?? '.*');

// set checkbox if multiSelect set to true
$checkbox = (isset($_POST['multiSelect']) && $_POST['multiSelect']=='true') ? "<input type='checkbox'>" : "";

echo "<ul class='jqueryFileTree'>";
// Parent dirs
if ($_POST['show_parent']=='true') echo "<li class='directory collapsed'>$checkbox<a href='#' rel=\"".htmlspecialchars(dirname($rootdir))."/\">..</a></li>";

if (is_dir($rootdir)) {
  if (mb_substr($rootdir,-1)!='/') $rootdir .= '/';
  $names = array_filter(scandir($rootdir),function($n){return $n!='.' && $n!='..';});
  if (count($names)) {
    natcasesort($names);
    foreach ($names as $dir) if (is_dir($rootdir.$dir)) {
      $htmlRel  = htmlspecialchars($rootdir.$dir);
      $htmlName = htmlspecialchars(mb_strlen($dir)<=33 ? $dir : mb_substr($dir,0,33).'...');
      echo "<li class='directory collapsed'>$checkbox<a href='#' rel=\"$htmlRel/\">$htmlName</a></li>";
    }
    foreach ($names as $file) if (is_file($rootdir.$file)) {
      $htmlRel  = htmlspecialchars($rootdir.$file);
      $htmlName = htmlspecialchars($file);
      $ext      = mb_strtolower(pathinfo($file)['extension']);
      foreach ($filters as $filter) if (empty($filter)||$ext==$filter) {
        if (empty($match)||preg_match("#$match#",$file)) echo "<li class='file ext_$ext'>$checkbox<a href='#' rel=\"$htmlRel\">$htmlName</a></li>";
      }
    }
  }
}
echo "</ul>";
?>
