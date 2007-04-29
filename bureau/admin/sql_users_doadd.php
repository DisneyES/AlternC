<?php
/*
 $Id: sql_users_doadd.php,v 1.2 2003/06/10 07:20:29 nahuel Exp $
 ----------------------------------------------------------------------
 AlternC - Web Hosting System
 Copyright (C) 2002 by the AlternC Development Team.
 http://alternc.org/
 ----------------------------------------------------------------------
 Based on:
 Valentin Lacambre's web hosting softwares: http://altern.org/
 ----------------------------------------------------------------------
 LICENSE

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License (GPL)
 as published by the Free Software Foundation; either version 2
 of the License, or (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 To read the license please visit http://www.gnu.org/copyleft/gpl.html
 ----------------------------------------------------------------------
 Original Author of file: Nahuel ANGELINETTI
 Purpose of file:
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");

$fields = array (
	"usern"        => array ("request", "string", ""),
	"password"     => array ("request", "string", ""),
	"passwordconf" => array ("request", "string", ""),
);
getFields($fields);

if (!$quota->cancreate("mysql_users"))
{
// 	$error=_("err_mysql_1");
	include ("sql_users_add.php");
	exit;
}

if (!$mysql->add_user($usern, $password, $passconf))
{
  $error = $err->errstr();
  include ("sql_users_add.php");
  exit;
}

include("sql_users_list.php");

?>