<?php
/*
 $Id: aws_useradd.php 13 2004-09-08 17:14:14Z anonymous $
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
 Original Author of file: Benjamin Sonntag
 Purpose of file: Create a new awstat account
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");

$fields = array (
	"prefixe" => array ("request", "string", ""),
	"login"   => array ("request", "string", ""),
	"pass"    => array ("request", "string", ""),
);
getFields($fields);

$r=$aws->add_login($prefixe.(($login)?"_":"").$login,$pass);
if (!$r) {
	$error=$err->errstr();
} else {
	$error=_("The Awstat account has been successfully created");
}

include("aws_users.php");
exit();

?>