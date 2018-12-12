<?php

include_once( dirname(__FILE__) . '/Logger.php');
Logger::configure(array(
    'rootLogger' => array(
        'appenders' => array('default'),
		'level' => "TRACE"
		// 'level' => "ERROR"
    ),
    'appenders' => array(
        'default' => array(
            'class' => 'LoggerAppenderFile',
            'layout' => array(
                'class' => 'LoggerLayoutSimple'
            ),
            'params' => array(
				'file' => '/var/www/html/log/log4php.log',
				// 'file' => 'D:\log\log4php.log',
            	'append' => true
            )
        )
    )
));

$log = Logger::getLogger("main");

function log_info($message) {
	global $log;
	try {
		
		$log->info($message);
	} catch (Exception $e) {
        	error_log("Error on Log. ".$e->getMessage());
	}
}
function log_trace($message) {
	global $log;
	try {
		$log->trace($message);
	} catch (Exception $e) {
		die("Error on Log. ".$e->getMessage());
        	error_log("Error on Log. ".$e->getMessage());
	}
}
function log_debug($message) {
	global $log;
	try {
		$log->debug($message);
	} catch (Exception $e) {
        	error_log("Error on Log. ".$e->getMessage());
	}
}
function log_warn($message) {
	global $log;
	try {
		$log->warn($message);
	} catch (Exception $e) {
        	error_log("Error on Log. ".$e->getMessage());
	}
}
function log_error($message) {
	global $log;
	try {
		$log->error($message);
	} catch (Exception $e) {
        	error_log("Error on Log. ".$e->getMessage());
	}
}
function log_fatal($message) {
	global $log;
	try {
		$log->fatal($message);
	} catch (Exception $e) {
		error_log("Error on Log. ".$e->getMessage());
	}
}
?>