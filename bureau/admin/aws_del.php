<?php
/*
 $Id: aws_del.php 6 2004-09-06 18:33:24Z anonymous $
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
 Purpose of file: Delete requested awstats statistics.
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");

$error="";
// On parcours les POST_VARS et on repere les del_.
reset($_POST);
while (list($key,$val)=each($_POST)) {
	if (substr($key,0,4)=="del_") {
		// Effacement du jeu de stats $val
		$r=$aws->delete_stats($val);
		if (!$r) {
			$error.=$err->errstr()."<br />";
		} else {
			$error.=sprintf(_("The statistics %s has been successfully deleted"),$r)."<br />";
		}
	}
}

include("aws_list.php");
exit();

?>