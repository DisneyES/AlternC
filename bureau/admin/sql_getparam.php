<?php
/*
 $Id: sql_getparam.php,v 1.4 2005/05/27 20:10:18 arnaud-lb Exp $
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
 Purpose of file: Return the current SQL settings
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");
include_once("head.php");

if (!$r = $mysql->get_dblist())
{
	$error = $err->errstr();
}

?>
<h3><?php __("MySQL Databases"); ?></h3>
<?php

if ($error)
{
	echo "<p class=\"error\">" . $error . "</p><p>&nbsp;</p>";
}

?>
<p><?php __("Your current settings are"); ?> : </p>
<table cellspacing="0" cellpadding="4">
	<tr class="lst2">
		<th><?php __("Username"); ?></th>
		<td><code><?php echo $mem->user["login"]; ?></code></td>
	</tr>
	<tr class="lst1">
		<th><?php __("Password"); ?></th>
		<td><code><?php echo $r[0]["pass"]; ?></code></td>
	</tr>
	<tr class="lst2">
		<th><?php __("SQL Server"); ?></th>
		<td><code><?php echo $mysql->server; ?></code></td>
	</tr>
	<tr class="lst1">
		<th><?php __("Database"); ?></th>
		<td><code><?php echo $r[0]["db"]; ?></code></td>
	</tr>
</table>
<script type="text/javascript">
deploy("menu-sql");
</script>
<?php include_once("foot.php"); ?>