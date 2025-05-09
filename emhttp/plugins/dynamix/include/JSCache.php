<?php
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

// Function to get JS files with caching
function getCachedJSFiles($directory) {
  $cacheFile = '/tmp/js_files_cache.php';
  $cacheLifetime = 300; // Cache lifetime in seconds (5 minutes)

  // Check if cache exists and is still valid
  $useCache = false;
  if (file_exists($cacheFile)) {
    $cacheTime = filemtime($cacheFile);
    if (time() - $cacheTime < $cacheLifetime) {
      $useCache = true;
    }
  }

  if ($useCache) {
    // Use cached JS files list
    return include $cacheFile;
  } else {
    // Generate new JS files list
    $jsFiles = findJsFiles($directory);
    
    // Store in cache file
    file_put_contents($cacheFile, '<?php return ' . var_export($jsFiles, true) . ';');
    
    return $jsFiles;
  }
} 