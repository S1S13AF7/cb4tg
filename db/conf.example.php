<?php
# кодування UTF-8

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === realpath(__FILE__)) {
	header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1') . ' 403 Forbidden', true, 403);
	header('Content-Type: text/plain; charset=utf-8', true);
	exit('403 Forbidden');
}

version_compare(PHP_VERSION, '7.0', '>=') or die('Потрібен PHP >= 7.0');

################################################################################
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'password');
define('DB_NAME', 'db');
################################################################################
