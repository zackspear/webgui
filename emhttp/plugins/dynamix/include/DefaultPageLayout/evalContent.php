<?php
// This evaluates the contents of PHP code.  Has to be "included" because the code is evaluated in the context of the calling page.
// $evalContent is the PHP code to evaluate.
// $evalFile is the file that the code is being evaluated in
// If an error occurs, a banner warning (disappearing in 10 seconds) is added to the page.
// The PHP error logged will also include the path of the .page file for easier debugging
ob_start();
try {
    set_error_handler(function($severity, $message, $file, $line) {
        throw new ErrorException($message, 0, $severity, $file, $line);
    });
    eval($evalContent);
    restore_error_handler();
    ob_end_flush();
} catch (Throwable $e) {
    restore_error_handler();
    error_log("Error evaluating content in $evalFile: " . $e->getMessage() . "\nStack trace:\n" . $e->getTraceAsString());
    ob_clean();
    echo "
        <script>
        $(function() {
            try {
                console.log('Fatal error in ".htmlspecialchars($evalFile)."  Code not executed.');
                let phpErrorBanner = addBannerWarning('Fatal error in ".htmlspecialchars($evalFile)."  Code not executed.',true,true);
                setTimeout(function() {
                    removeBannerWarning(phpErrorBanner);
                }, 10000);
            } catch (e) {
                console.error('Failed to add banner warning: ' + e);
            }
        });
        </script>";
    ob_end_flush();   
}
?>      