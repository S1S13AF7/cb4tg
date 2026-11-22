<?php
cb_require_trusted($userbot);
# бінд чата по аналогії з iris-callback-api (вкрадено не повністю але суть та ж)

$code = isset($object['chat']) ? strtolower(trim((string)$object['chat'])) : '';
if (!preg_match('/^[0-9a-f]{8}$/', $code)) {
	cb_error('bad chat code', 10);
}

$raw_chat = isset($object['id_chat']) ? (int)$object['id_chat'] : 0;
$id_chat  = abs($raw_chat);
if ($id_chat <= 0) {
	cb_error('bad id_chat', 11);
}

$date = isset($object['date']) && (int)$object['date'] > 0
	? (int)$object['date']
	: time();

$title = isset($object['title']) ? trim((string)$object['title']) : '';
$link  = isset($object['link'])  ? trim((string)$object['link'])  : '';

Db::exec(
	"INSERT INTO `userbot_bind` " .
	"(`platform`, `id_user`, `code`, `date`, `id_chat`, `title`, `link`) " .
	"VALUES ('tg', ?, ?, ?, ?, ?, ?) " .
	"ON DUPLICATE KEY UPDATE " .
	"`date` = VALUES(`date`), " .
	"`id_chat` = VALUES(`id_chat`), " .
	"`title` = IF(VALUES(`title`) != '', VALUES(`title`), `title`) " .
	'isiiss',
	[$user_id, $code, $date, $id_chat, $title, $link]
);

$u_row = Db::selectOne(
	"SELECT `lng_code` FROM `tg_bot_users` WHERE `user_id` = ? LIMIT 1",
	'i',
	[$user_id]
);
$lang = ($u_row && !empty($u_row['lng_code'])) ? (string)$u_row['lng_code'] : '';

if ($lang !== '') {
	cb_ok([
		'status' => 'ok',
		'lang'   => $lang
	]);
}

cb_ok('ok');
