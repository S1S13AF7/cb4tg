<?php
# кодування UTF-8
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

function cb_error($msg, $code = 1) {
	while (ob_get_level() > 0) {
		ob_end_clean();
	}
	echo json_encode([
		'error' => [
			'error_code' => (int)$code,
			'error_msg'  => (string)$msg
		]
	], JSON_UNESCAPED_UNICODE);
	exit;
}

function cb_ok($data = 'ok') {
	while (ob_get_level() > 0) {
		ob_end_clean();
	}
	echo json_encode([
		'response' => $data
	], JSON_UNESCAPED_UNICODE);
	exit;
}

if (!version_compare(PHP_VERSION, '7.0', '>=')) {
	cb_error('Потрібен PHP >= 7.0', 500);
}

$root = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/\\');
$db_paths = [
	__DIR__ . '/db/db.php',
	__DIR__ . '/db/db.class.php',
	__DIR__ . '/../sys/inc/db.class.php',
	__DIR__ . '/../../sys/inc/db.class.php',
];
if ($root !== '') {
	$db_paths[] = $root . '/sys/inc/db.class.php';
}

$db_found = false;
foreach ($db_paths as $db_file) {
	if (is_file($db_file)) {
		require_once $db_file;
		if (class_exists('Db')) {
			$db_found = true;
			break;
		}
	}
}

if (!$db_found) {
	cb_error('Не знайдено модуль БД', 500);
}

if (!defined('DB_HOST') || !defined('DB_USER') || !defined('DB_PASS') || !defined('DB_NAME')) {
	cb_error('Константи БД не визначені (перевір conf.php)', 500);
}

if (Db::link() === null) {
	mysqli_report(MYSQLI_REPORT_OFF);
	$probe = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	$err = $probe->connect_errno ? $probe->connect_error : 'Невідома помилка підключення';
	cb_error('Помилка підключення до БД: ' . $err, 500);
}

function cb_resolve($method) {
	$list = __DIR__ . '/ub/methods.txt';
	if (!is_file($list)) {
		return null;
	}
	$lines = file($list, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
	foreach ($lines as $line) {
		$line = trim($line);
		if ($line === '' || $line[0] === '#') {
			continue;
		}
		$parts  = preg_split('/\s+/', $line, 2);
		$m_name = $parts[0];
		$m_file = isset($parts[1]) ? $parts[1] : ($m_name . '.php');
		if (substr($m_file, -4) !== '.php') {
			$m_file .= '.php';
		}
		if ($m_name === $method) {
			$path = __DIR__ . '/ub/' . ltrim($m_file, '/\\');
			return is_file($path) ? $path : null;
		}
	}
	return null;
}

function cb_auth($user_id, $secret) {
	if ($user_id <= 0 || !preg_match('/^[a-zA-Z][a-zA-Z0-9]{7,30}$/', $secret)) {
		return null;
	}
	$row = Db::selectOne(
		"SELECT `user_id`, `f_name`, `secret`, `trusted` " .
		"FROM `tg_bot_users` WHERE `user_id` = ? LIMIT 1",
		'i',
		[$user_id]
	);
	if ($row && hash_equals((string)$row['secret'], $secret)) {
		return $row;
	}
	return null;
}

function cb_require_trusted($userbot) {
	if (!$userbot) {
		cb_error('auth required', 3);
	}
	if ((string)$userbot['trusted'] !== '1') {
		cb_error('not trusted', 4);
	}
	return $userbot;
}

$raw = file_get_contents('php://input');
if (!$raw) {
	cb_error('no data', 1);
}

$data = json_decode($raw, true);
if (!is_array($data) || empty($data['method'])) {
	cb_error('bad json or no method', 1);
}

$method  = preg_replace('/[^a-zA-Z0-9_.-]/', '', (string)$data['method']);
$user_id = isset($data['user_id'])
	? (int)$data['user_id']
	: (isset($data['id']) ? (int)$data['id'] : 0);
$secret  = isset($data['secret']) ? trim((string)$data['secret']) : '';
$object  = isset($data['object']) && is_array($data['object']) ? $data['object'] : [];
$message = isset($data['message']) && is_array($data['message']) ? $data['message'] : null;

$handler = cb_resolve($method);
if (!$handler) {
	cb_error('no method found', 2);
}

if ($method === 'ping' && empty($object['check_auth'])) {
	require_once($handler);
	exit;
}

if (!Db::link()) {
	cb_error('db connect error', 5);
}

$userbot = null;
if ($secret !== '') {
	if (!preg_match('/^[a-zA-Z][a-zA-Z0-9]{7,30}$/', $secret)) {
		cb_error('bad secret format', 3);
	}
	$userbot = cb_auth($user_id, $secret);
	if (!$userbot) {
		cb_error('wrong secret or no such user', 3);
	}
}

if ($method === 'ping') {
	cb_require_trusted($userbot);
	require_once($handler);
	exit;
}

require_once($handler);
