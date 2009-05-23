<?php
/*
 $Id: adm_variables.php,v 1.1 2005/01/19 06:09:36 anarcat Exp $
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
 Purpose of file: Manage allowed TLD on the server
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");

if (!$admin->enabled) {
	__("This page is restricted to authorized staff");
	exit();
}

$conf = variable_init();
foreach ($conf as $name => $val) {
  if (isset($$name)) {
    variable_set($name, $$name);
  }
}

include_once ("head.php");

?>
<h3><?php __("Configure AlternC variables"); ?></h3>

<p>
<?php __("Here are the internal AlternC variables that are currently being used."); ?>
</p>

<form method="post" action="adm_variables.php">
<table border="0" cellpadding="4" cellspacing="0">
<tr><th><?php __("Names"); ?></th><th><?php __("Value"); ?></th><th><?php __("Comment"); ?></th></tr>
<?php
$col=1;
$db->query("SELECT * FROM `variable` WHERE `comment` IS NOT NULL ORDER BY `name`");

while ($db->next_record()) {
 $vars = $db->Record;
 $col=3-$col;
 ?>

 <tr class="lst<?php echo $col; ?>">
 <td><?php echo $vars['name']; ?></td>
 <td><input type="text" name="<?php echo $vars['name']?>" value="<?php echo $vars['value']?>" /></td>
 <td><?php echo $vars['comment']; ?></td>
 </tr>
<? } ?>
</table>
<p><input type="submit" class="inb" value="<?php __("Save variables"); ?>" /></p>
</form>
<?php include_once("foot.php"); ?>
