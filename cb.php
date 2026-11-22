<?php
# кодуванння UTF-8
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

require_once(__DIR__ . '/db/db.php');

########################################################################

function cb_error($msg, $code = 1) {
	echo json_encode([
		'error' => [
			'error_code' => (int)$code,
			'error_msg'  => (string)$msg
		]
	], JSON_UNESCAPED_UNICODE);
	exit;
}

if (!preg_match('/^[a-zA-Z][a-zA-Z0-9]{7,}$/', $secret)) {
	cb_error('bad secret format', 3);
}

function cb_ok($data = 'ok') {
	echo json_encode([
		'response' => $data
	], JSON_UNESCAPED_UNICODE);
	exit;
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
	if ($user_id <= 0 || !preg_match('/^[a-zA-Z0-9]{8,30}$/', $secret)) {
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
$user_id = isset($data['user_id']) ? (int)$data['user_id'] : 0;
$secret  = isset($data['secret']) ? trim((string)$data['secret']) : '';
$object  = isset($data['object']) && is_array($data['object']) ? $data['object'] : [];
$message = isset($data['message']) && is_array($data['message']) ? $data['message'] : null;

$handler = cb_resolve($method);
if (!$handler) {
	cb_error('no method found', 2);
}

if ($method === 'ping') {
if(!empty($object['check_auth'])) 
{ cb_require_trusted($userbot); }
	require_once($handler);
	exit;
}

if (!Db::link()) {
	cb_error('db connect error', 5);
}

$userbot = null;
if ($secret !== '') {
	$userbot = cb_auth($user_id, $secret);
	if (!$userbot) {
		cb_error('wrong secret or no such user', 3);
	}
}

require_once($handler);
