<?php

/*  script start time, for those who keep track of app performance  */
$sevenStartTime = microtime(true);

/*  get the composer autoloader  */
require_once('./vendor/autoload.php');

/*  get the config settings  */
$config = json_decode(file_get_contents('./config.json'));

date_default_timezone_set($config->timezone);

/*  setup error reporting  */
if(file_exists('./src/ErrorHandlingExtension.php')) {
    require_once('./src/ErrorHandlingExtension.php');
} else {
    require_once('./Seven/ErrorHandling.php');
}

/*  start the session  */
session_start();

/*  route the request  */
$Router = new Seven\Router();
$Router->route();

/*  script end time  */
$sevenEndTime = microtime(true);

/*  necessary for handle_shutdown() in ErrorHandling.php  */
$sevenIsComplete = true;

/*  that's it!  */
