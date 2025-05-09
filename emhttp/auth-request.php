<?php
// only start the session if a session cookie exists
if (isset($_COOKIE[session_name()])) {
  session_start();
  // authorized?
  if (isset($_SESSION["unraid_login"])) {
    if (time() - $_SESSION['unraid_login'] > 300) {
      $_SESSION['unraid_login'] = time();
    }
    session_write_close();
    http_response_code(200);
    exit;
  }
  session_write_close();
}

// Function to recursively find JS files in a directory
function findJsFiles($directory) {
  if (!is_dir($directory)) {
    return [];
  }
  
  $jsFiles = [];
  $iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)
  );
  
  foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'js') {
      $path = $file->getPathname();
      $baseDir = '/usr/local/emhttp';
      if (strpos($path, $baseDir) === 0) {
        $path = substr($path, strlen($baseDir));
      }
      $jsFiles[] = $path;
    }
  }
  
  return $jsFiles;
}

// Base whitelist of files
$arrWhitelist = [
  '/webGui/styles/clear-sans-bold-italic.eot',
  '/webGui/styles/clear-sans-bold-italic.woff',
  '/webGui/styles/clear-sans-bold-italic.ttf',
  '/webGui/styles/clear-sans-bold-italic.svg',
  '/webGui/styles/clear-sans-bold.eot',
  '/webGui/styles/clear-sans-bold.woff',
  '/webGui/styles/clear-sans-bold.ttf',
  '/webGui/styles/clear-sans-bold.svg',
  '/webGui/styles/clear-sans-italic.eot',
  '/webGui/styles/clear-sans-italic.woff',
  '/webGui/styles/clear-sans-italic.ttf',
  '/webGui/styles/clear-sans-italic.svg',
  '/webGui/styles/clear-sans.eot',
  '/webGui/styles/clear-sans.woff',
  '/webGui/styles/clear-sans.ttf',
  '/webGui/styles/clear-sans.svg',
  '/webGui/styles/default-cases.css',
  '/webGui/styles/font-cases.eot',
  '/webGui/styles/font-cases.woff',
  '/webGui/styles/font-cases.ttf',
  '/webGui/styles/font-cases.svg',
  '/webGui/images/case-model.png',
  '/webGui/images/green-on.png',
  '/webGui/images/red-on.png',
  '/webGui/images/yellow-on.png',
  '/webGui/images/UN-logotype-gradient.svg'
];

// Add JS files from the unraid-components directory
$webComponentsDirectory = '/usr/local/emhttp/plugins/dynamix.my.servers/unraid-components/';
$jsFiles = findJsFiles($webComponentsDirectory);

// Add logo asset if needed
// Note: In the original JS, there was a getters.paths().webgui.logo.assetPath that we'd add
// Since we don't have access to that here, you may need to add the specific logo path if known

// Merge the JS files with the whitelist
$arrWhitelist = array_merge($arrWhitelist, $jsFiles);

if (in_array(preg_replace(['/\?v=\d+$/','/\?\d+$/'],'',$_SERVER['REQUEST_URI']),$arrWhitelist)) {
  // authorized
  http_response_code(200);
} else {
  // non-authorized
  //error_log(print_r($_SERVER, true));
  http_response_code(401);
}
exit;
