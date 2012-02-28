<?php

/** 
 * Auth via uLogin.ru
 * @package vBulletin
 * @subpackage uLogin Product
 * @author uLogin http://ulogin.ru team@ulogin.ru
 */

error_reporting(E_ALL & ~E_NOTICE);

define('THIS_SCRIPT', 'ulogin');
define('CSRF_PROTECTION', false);
define('CONTENT_PAGE', true);

require_once('./global.php');
require_once(DIR . '/includes/class_ulogin.php');

$uLogin = new uLogin($vbulletin);

if (!$uLogin->check_access())
{
	print_no_permission();
}

if (!$uLogin->auth())
{
	$uLogin->register();
}

$vbulletin->url = $uLogin->get_url();

do_login_redirect();

?>
