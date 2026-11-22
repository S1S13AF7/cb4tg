<?php
if ($method === 'ping') {
	if (!empty($object['check_auth'])) {
		cb_require_trusted($userbot);
	}
	#пише 'ok'
	cb_ok('ok');
}
