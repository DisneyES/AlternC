<?php
/*
 $Id: adm_dorenew.php,v 1.6 2006/01/24 05:03:30 joe Exp $
 ----------------------------------------------------------------------
 AlternC - Web Hosting System
 Copyright (C) 2006 Le r�seau Koumbit Inc.
 http://koumbit.org/
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
 Purpose of file: Renew an account for the specified period
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");

$fields = array (
	"uid"      => array ("request", "integer", 0),
	"periods"  => array ("request", "integer", 0),
);
getFields($fields);

if (!$admin->enabled)
{
	__("This page is restricted to authorized staff");
	exit();
}

if (!$admin->checkcreator($uid))
{
	__("This page is restricted to authorized staff");
	exit();
}

if (!$admin->renew_mem($uid, $periods))
{
	$error = $err->errstr();
	include ("adm_edit.php");
}
else
{
	$error = _("The member has been successfully renewed");
	include ("adm_list.php");
}

?>