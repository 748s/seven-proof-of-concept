<?php

configureErrorHandling();

function configureErrorHandling()
{
    global $config;
    $environments = array(
        'development' => array(
            'error_reporting' => E_ALL,
            'display_errors' => true
        ),
        'production' => array(
            'error_reporting' => E_ALL,
            'display_errors' => false
        )
    );
    /*    default to production  */
    $environment = (array_key_exists($config->environment, $environments)) ? $environments[$config->environment] : $environments['production'];
    ini_set('display_errors', $environment['display_errors']);
    ini_set('error_reporting', $environment['error_reporting']);
    set_error_handler('handleErrors', E_ALL);
    register_shutdown_function('handleShutdown');
}

function handleShutdown()
{
    global $sevenIsComplete;
    if(!$sevenIsComplete) {
        $error = error_get_last();
        $type = 'SHUTDOWN/FATAL ERROR';
        logError($type, $error['message'], $error['line'], $error['file']);
    }
}

function handleErrors($number, $message, $file, $line)
{
    $errorNumbers = array(
        1    =>    'E_ERROR - fatal runtime error',
        2    =>    'E_WARNING - warning',
        4    =>    'E_PARSE - parse error',
        8    =>    'E_NOTICE',
        16    =>    'E_CORE_ERROR',
        32    =>    'E_CORE_WARNING',
        64    =>    'E_COMPILE_ERROR',
        128    =>    'E_COMPILE_WARNING',
        256    =>    'E_USER_ERROR',
        512    =>    'E_USER_WARNING',
        1024=>    'E_USER_NOTICE',
        2048=>    'E_STRICT',
        4096=>    'E_RECOVERABLE_ERROR',
    );
    switch($number)    {
        case E_NOTICE:
            /*  ignore notices  */
            break;
        default:
            $type = (array_key_exists($number, $errorNumbers)) ? $errorNumbers[$number] : $number;
            $file = str_replace($_SERVER['DOCUMENT_ROOT'], '', $file);
            logError($type, $message, $line, $file);
            break;
    }
}

function logError($type, $message, $line, $file)
{
    global $config;
    $query =
        'INSERT INTO errors (
            type,
            message,
            file,
            line,
            uri,
            userId,
            ipAddress,
            userAgent,
            referer,
            created)
        VALUES (
            :type,
            :message,
            :file,
            :line,
            :uri,
            :userId,
            :ipAddress,
            :userAgent,
            :referer,
            :created)'
    ;
    $db = new PDO("mysql:host={$config->database->dataSource->hostname};dbname={$config->database->dataSource->database}",
        $config->database->credentials->username,
        $config->database->credentials->password
    );
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
    $stmt = $db->prepare($query);
    $result = $stmt->execute(array(
        ':type' => $type,
        ':message' => $message,
        ':file' => $file,
        ':line' => $line,
        ':uri' => (isset($_SERVER['REQUEST_URI'])) ? $_SERVER['REQUEST_URI'] : null,
        ':userId' => (isset($_SESSION['id'])) ? $_SESSION['id'] : null,
        ':ipAddress' => (isset($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : null,
        ':userAgent' => (isset($_SERVER['HTTP_USER_AGENT'])) ? $_SERVER['HTTP_USER_AGENT'] : null,
        ':referer' => (isset($_SERVER['HTTP_REFERER'])) ? $_SERVER['HTTP_REFERER'] : null,
        ':created' => date('Y-m-d H:i:s')
    ));
}
