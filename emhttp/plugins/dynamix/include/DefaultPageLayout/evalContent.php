<?php
// This evaluates the contents of PHP code.  Has to be "included" because the code is evaluated in the context of the calling page.
// $evalContent is the PHP code to evaluate.
// $evalFile is the file that the code is being evaluated in
// If an error occurs, a banner warning (disappearing in 10 seconds) is added to the page.
// The PHP error logged will also include the path of the .page file for easier debugging

$evalSuccess = false;
ob_start();
try {
    set_error_handler(function($severity, $message, $file, $line) use ($evalFile) {
        // Only convert errors (not warnings, notices, etc.) to exceptions
        if ($severity & (E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR)) {
            throw new ErrorException($message, 0, $severity, $file, $line);
        } else {
            error_log("PHP Warning/notice in $evalFile");
        }
        // Let warnings and notices be handled normally
        return false;
    });
    eval($evalContent);
    restore_error_handler();
    $evalSuccess = true;
    ob_end_flush();
} catch (Throwable $e) {
    restore_error_handler();
    error_log("Error evaluating content in $evalFile): ".$e->getMessage()."\nStack trace:\n".$e->getTraceAsString());
    ob_clean();
    ob_end_flush();   
}
?>      