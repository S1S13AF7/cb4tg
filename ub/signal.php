<?php
cb_require_trusted($userbot);

$from_id  = isset($object['from_id'])  ? (int)$object['from_id'] : 0;
$chat_id  = isset($object['chat_id'])  ? (int)$object['chat_id'] : 0;
$reply_id = isset($object['reply_id']) ? (int)$object['reply_id'] : 0;
$text     = isset($object['text'])     ? trim((string)$object['text']) : '';

if ($text === '') {
	cb_error('empty signal', 20);
}

$parts = preg_split('/\s+/u', $text, 2);
$cmd   = mb_strtolower((string)($parts[0] ?? ''), 'UTF-8');
$args  = trim((string)($parts[1] ?? ''));

if (in_array($cmd, ['пинг', 'пінг', 'ping'], true)) {
	cb_ok([
		'status' => 'ok',
		'cmd'    => 'ping',
		'server_ts' => microtime(true)
	]);
}

cb_ok([
	'status' => 'unknown',
	'cmd'    => $cmd
]);
