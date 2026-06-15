<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>Account locked</title>
</head>
<body>
	<p>Hello <?= html_escape($username) ?>,</p>
	<p>Your account was temporarily locked after repeated failed login attempts.</p>
	<p>Locked until: <strong><?= html_escape($locked_until) ?></strong></p>
	<p>Source IP: <?= html_escape($ip_address) ?></p>
	<p>If this was not you, please contact an administrator.</p>
</body>
</html>
