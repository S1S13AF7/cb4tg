<?php
# кодування UTF-8

$win_dir = 'D:\\Misc\\projects\\Python';
$db_dir  = __DIR__ . '/../db';
if (is_dir($win_dir)) {
	$noeb_path = $win_dir . '\\noeb.json';
} else {
	if (!is_dir($db_dir)) {
		@mkdir($db_dir, 0755, true);
	}
	$noeb_path = $db_dir . '/noeb.json';
}

$default_noeb = [
	707693258, 5137994780, 5226378684,
	5434504334, 5443619563, 6333102398,
	7959200286, 1124824021
];

$server_noeb = $default_noeb;
if (is_file($noeb_path)) {
	$raw_json = json_decode((string)file_get_contents($noeb_path), true);
	if (is_array($raw_json)) {
		$server_noeb = array_values(array_unique(array_map('intval', $raw_json)));
	}
} else {
	file_put_contents(
		$noeb_path,
		json_encode($server_noeb, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
		LOCK_EX
	);
}

$in_noeb = $object['noeb'] ?? ($data['noeb'] ?? null);
if (is_string($in_noeb) && strlen($in_noeb) > 0 && $in_noeb[0] === '[') {
	$in_noeb = json_decode($in_noeb, true);
}

$is_rw = !empty($userbot) && (string)($userbot['trusted'] ?? '0') === '1';

// Якщо передано ОДИН ID (числом) і клієнт trusted — тихо записуємо на сервер
if (!is_array($in_noeb) && is_numeric($in_noeb)) {
	$one_id = (int)$in_noeb;
	if ($is_rw && $one_id > 0 && !in_array($one_id, $server_noeb, true)) {
		$server_noeb[] = $one_id;
		file_put_contents(
			$noeb_path,
			json_encode($server_noeb, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
			LOCK_EX
		);
	}
	cb_ok('ok');
}

// Якщо передано масив або пусто — віддаємо весь список або різницю
$client_noeb = [];
if (is_array($in_noeb)) {
	foreach ($in_noeb as $id) {
		$id = (int)$id;
		if ($id > 0) {
			$client_noeb[] = $id;
		}
	}
	$client_noeb = array_values(array_unique($client_noeb));
}

$diff_for_client = empty($client_noeb)
	? $server_noeb
	: array_values(array_diff($server_noeb, $client_noeb));

cb_ok([
	'noeb'  => $diff_for_client,
	'total' => count($server_noeb)
]);
