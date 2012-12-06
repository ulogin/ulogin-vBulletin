<?php

/** 
 * Attach uLogin profile
 * @package vBulletin
 * @subpackage uLogin Product
 * @author uLogin http://ulogin.ru team@ulogin.ru
 */
error_reporting(E_ALL & ~E_NOTICE);

define('THIS_SCRIPT', 'uloginattach');
define('CSRF_PROTECTION', false);
define('CONTENT_PAGE', true);

require_once('./global.php');
require_once(DIR . '/includes/class_ulogin.php');

$uLogin = new uLogin($vbulletin);

if (!$uLogin->check_profile_attach_access())
{
	print_no_permission();

}

$uLogin->attach();

$vbulletin->url = $uLogin->get_url();

exec_header_redirect($vbulletin->url);

?>
