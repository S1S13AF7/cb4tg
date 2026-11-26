<?php
# кодування UTF-8
/* Захист від прямого доступу */
$guard_file = __DIR__ . '/hs.php';
if (is_file($guard_file)) {
	require_once $guard_file;
} elseif (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === realpath(__FILE__)) {
	header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1') . ' 403 Forbidden', true, 403);
	header('Content-Type: text/plain; charset=utf-8', true);
	exit('403 Forbidden');
}

version_compare(PHP_VERSION, '7.0', '>=') or die('Потрібен PHP >= 7.0');

/* Пошук конфігурації БД: спочатку conf.php (поруч — пріоритет), далі фолбек на conf.example.php */
$root = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/\\');
$conf_dirs = [
	__DIR__,
	__DIR__ . '/../tg/cb4tg/db',
	__DIR__ . '/../tg/cb4tg',
	__DIR__ . '/../cb4tg/db',
	__DIR__ . '/../cb4tg',
];
if ($root !== '') {
	$conf_dirs[] = $root . '/tg/cb4tg/db';
	$conf_dirs[] = $root . '/tg/cb4tg';
	$conf_dirs[] = $root . '/cb4tg/db';
	$conf_dirs[] = $root . '/cb4tg';
}

$conf_loaded = false;
foreach (['conf.php', 'conf.example.php'] as $conf_name) {
	foreach ($conf_dirs as $c_dir) {
		$candidate = $c_dir . '/' . $conf_name;
		if (is_file($candidate)) {
			require_once $candidate;
			$conf_loaded = true;
			break 2;
		}
	}
}

class Db {
	private static $conn = null;
	
	public static function link() {
		if (self::$conn === null) {
			if (!defined('DB_HOST') || !defined('DB_USER') || !defined('DB_PASS') || !defined('DB_NAME')) {
				return null;
			}
			mysqli_report(MYSQLI_REPORT_OFF);
			$db = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
			if ($db->connect_errno) {
				return null;
			}
			$db->set_charset('utf8mb4');
			self::$conn = $db;
			self::install();
		}
		return self::$conn;
	}
	
	private static function install() {
		$lock = __DIR__ . '/.installed';
		$sql_file = __DIR__ . '/db.sql';
		if (is_file($lock) || !is_file($sql_file) || !self::$conn) {
			return;
		}
		$sql = file_get_contents($sql_file);
		if ($sql && self::$conn->multi_query($sql)) {
			while (self::$conn->more_results()) {
				self::$conn->next_result();
			}
			@file_put_contents($lock, (string)time());
		}
	}
	
	public static function run($sql, $types = '', $params = []) {
		$db = self::link();
		if (!$db) {
			return false;
		}
		if ($types === '' || empty($params)) {
			return $db->query($sql);
		}
		$stmt = $db->prepare($sql);
		if (!$stmt) {
			return false;
		}
		$stmt->bind_param($types, ...$params);
		if (!$stmt->execute()) {
			$stmt->close();
			return false;
		}
		$res = $stmt->get_result();
		$out = $res ? $res : true;
		$stmt->close();
		return $out;
	}
	
	public static function select($sql, $types = '', $params = []) {
		$res = self::run($sql, $types, $params);
		if ($res instanceof mysqli_result) {
			return $res->fetch_all(MYSQLI_ASSOC);
		}
		return [];
	}
	
	public static function selectOne($sql, $types = '', $params = []) {
		$rows = self::select($sql, $types, $params);
		return isset($rows[0]) ? $rows[0] : null;
	}
	
	public static function exec($sql, $types = '', $params = []) {
		$db = self::link();
		if (!$db) {
			return -1;
		}
		$ok = self::run($sql, $types, $params);
		return $ok ? $db->affected_rows : -1;
	}
}
