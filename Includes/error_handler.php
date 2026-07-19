<?php
// Includes/error_handler.php

function friendly_error_page() {
    if (!headers_sent()) {
        http_response_code(500);
    }

    // Check if the request is an AJAX request
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    
    // Also check HTTP_ACCEPT for json
    if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
        $isAjax = true;
    }

    if ($isAjax) {
        if (!headers_sent()) {
            header('Content-Type: application/json');
        }
        echo json_encode([
            'success' => false, 
            'message' => 'A server error occurred. Please try again later.'
        ]);
        exit;
    }
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Oops! Something went wrong.</title>
        <style>
            body { 
                font-family: 'Roboto', sans-serif, Arial; 
                background-color: #f8f9fa; 
                color: #333; 
                text-align: center; 
                padding: 10vh 20px; 
                margin: 0; 
            }
            .error-container { 
                max-width: 600px; 
                margin: 0 auto; 
                background: white; 
                padding: 40px; 
                border-radius: 12px; 
                box-shadow: 0 8px 16px rgba(0,0,0,0.1); 
            }
            .icon {
                font-size: 60px;
                color: #e74c3c;
                margin-bottom: 20px;
            }
            h1 { color: #2c3e50; font-size: 2em; margin-bottom: 10px; }
            p { font-size: 1.1em; color: #7f8c8d; line-height: 1.6; margin-bottom: 30px; }
            .btn { 
                display: inline-block; 
                padding: 12px 28px; 
                background-color: #e74c3c; 
                color: white; 
                text-decoration: none; 
                border-radius: 6px; 
                font-weight: 500;
                transition: background-color 0.3s;
            }
            .btn:hover { background-color: #c0392b; }
        </style>
    </head>
    <body>
        <div class="error-container">
            <div class="icon">⚠️</div>
            <h1>Oops! Something went wrong.</h1>
            <p>We're experiencing some technical difficulties right now. Our team has been notified. Please try again later.</p>
            <a href="/movie_ticket_booking_system/index.php" class="btn">Return to Home</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Global Exception Handler
set_exception_handler(function($e) {
    error_log("Uncaught Exception: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    friendly_error_page();
});

// Global Error Handler
set_error_handler(function($level, $message, $file, $line) {
    if (error_reporting() & $level) {
        error_log("Error [$level]: $message in $file on line $line");
        // Trigger friendly page for severe errors
        $fatal_errors = [E_USER_ERROR, E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_RECOVERABLE_ERROR, E_PARSE];
        if (in_array($level, $fatal_errors)) {
            friendly_error_page();
        }
    }
    return false; // Let default PHP error handler run for warnings/notices
});

// Fatal Error Handler (Shutdown function)
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null) {
        $fatal_errors = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR];
        if (in_array($error['type'], $fatal_errors)) {
            error_log("Fatal Error: " . $error['message'] . " in " . $error['file'] . " on line " . $error['line']);
            // Clean output buffer so we don't render a broken half-page
            while (ob_get_level()) {
                ob_end_clean();
            }
            friendly_error_page();
        }
    }
});

// Disable displaying raw errors to the user in production
ini_set('display_errors', '0');
ini_set('log_errors', '1');
?>
