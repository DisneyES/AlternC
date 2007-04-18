<?php
/*
 $Id: menu_webalizer.php 69 2006-03-15 20:37:35Z anarcat $
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
 Original Author of file:
 Purpose of file:
 ----------------------------------------------------------------------
*/

/* ############# STATS ############# */
$q=$quota->getquota("stats");
$r=$quota->getquota("dom");
if ($q["t"]>0 && $r["u"]>0) {
?>
<div class="menu-box">
<div class="menu-title"><img src="images/stat.png" alt="<?php __("Web Statistics"); ?>" />&nbsp;<a href="webalizer_list.php"><?php __("Web Statistics"); ?></a></div>
</div>
<?php } ?>