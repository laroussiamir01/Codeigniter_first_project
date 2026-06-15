<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>Account locked notification</title>
</head>
<body>
	<p>An account was locked after repeated failed login attempts.</p>
	<p>Username: <strong><?= html_escape($username) ?></strong></p>
	<p>Email: <?= html_escape($email) ?></p>
	<p>Locked until: <strong><?= html_escape($locked_until) ?></strong></p>
	<p>Source IP: <?= html_escape($ip_address) ?></p>
</body>
</html>
