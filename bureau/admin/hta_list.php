<?php
/*
 $Id: hta_list.php,v 1.5 2003/08/20 13:08:28 root Exp $
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
 Original Author of file: Franck Missoum
 Purpose of file: List the users in a protected folder
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");
include_once("head.php");

if (!$r=$hta->ListDir()) {
	$error=$err->errstr();
}
else {
	reset($r);
}

?>
<h3><?php __("Protected folders list"); ?></h3>
<?php
	if ($error) {
		echo "<p class=\"error\">$error</p>";
	}

	if (!is_array($r)) {
		echo "<p><a href=\"hta_add.php\">"._("Protect a folder")."</a><br />";
		$mem->show_help("hta_list");
		echo "</p>";
		include_once("foot.php");
		exit();
	}

?>

<p>
<?php __("help_hta_list");
$mem->show_help("hta_list2");
?>
</p>

<form method="post" action="hta_del.php">
<table cellspacing="0" cellpadding="4">
<tr><th colspan="2">&nbsp;</th><th><?php __("Folder"); ?></th></tr>
<?php
$col=1;


for($i=0;$i<count($r);$i++){
	$col=3-$col;
?>
	<tr  class="lst<?php echo $col; ?>">
		<td align="center"><input type="checkbox" class="inc" name="del_<?php echo $r[$i] ?>" value="<?php echo $r[$i] ?>" /></td>
		<td><a href="hta_edit.php?dir=<?php echo $r[$i]?>"><img src="images/edit.png" alt="<?php __("Edit"); ?>" /></a></td>
		<td><code><?php echo "$r[$i]"?></code></td>
	</tr>
    <?php
	}
?>
<tr><td colspan="3"><input type="submit" class="inb" name="submit" value="<?php __("Unprotect the checked folders"); ?>" /></td></tr>
</table>
</form>
<p>
<a href="hta_add.php"><?php __("Protect a folder"); ?></a>
</p>
<p>
<?php $mem->show_help("hta_list"); ?>
</p>
<?php include_once("foot.php"); ?>