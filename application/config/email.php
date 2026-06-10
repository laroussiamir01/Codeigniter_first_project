<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| Email Configuration
| -------------------------------------------------------------------------
| Placeholders for SMTP credentials. Override values in
| application/config/email.local.php (which is .gitignored) so secrets
| never land in version control.
*/

$config['useragent']        = 'CodeIgniter';
$config['protocol']         = 'smtp';
$config['mailtype']         = 'html';
$config['charset']          = 'utf-8';
$config['smtp_host']        = 'smtp.example.com';
$config['smtp_user']        = 'no-reply@example.com';
$config['smtp_pass']        = '';
$config['smtp_port']        = 587;
$config['smtp_timeout']     = 10;
$config['smtp_crypto']      = 'tls';
$config['smtp_keepalive']   = FALSE;
$config['wordwrap']         = TRUE;
$config['wrapchars']        = 76;
$config['mailpath']         = '/usr/sbin/sendmail';
$config['newline']          = "\r\n";
$config['crlf']             = "\r\n";
$config['validate']         = TRUE;
$config['bcc_batch_mode']   = FALSE;
$config['bcc_batch_size']   = 200;

if (file_exists(APPPATH . 'config/email.local.php')) {
    require_once(APPPATH . 'config/email.local.php');
}
