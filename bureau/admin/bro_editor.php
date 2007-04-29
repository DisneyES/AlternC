<?php
/*
 $Id: bro_editor.php,v 1.5 2005/05/03 14:49:06 anarcat Exp $
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
 Purpose of file: Editor of the browser
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");

$fields = array (
	"R"         => array ("request", "string", ""),
	"file"      => array ("request", "string", ""),
	"texte"     => array ("request", "string", ""),
	"cancel"    => array ("request", "string", ""),
	"saveret"   => array ("request", "string", ""),
	"save"      => array ("request", "string", ""),
);
getFields($fields);

$file=ssla($file);
$texte=ssla($texte);

$R=$bro->convertabsolute($R,1);
$p=$bro->GetPrefs();

if ($cancel) {
	include("bro_main.php");
	exit();
}
if ($saveret) {
	$bro->Save($file,$R,$texte);
	$error=sprintf(_("Your file %s has been saved"),$file)." (".format_date('%3$d-%2$d-%1$d %4$d:%5$d',date("Y-m-d H:i:s")).")";
	include("bro_main.php");
	exit();
}
if ($save) {
	$bro->Save($file,$R,$texte);
	$error=sprintf(_("Your file %s has been saved"),$file)." (".format_date('%3$d-%2$d-%1$d %4$d:%5$d',date("Y-m-d H:i:s")).")";
}

include_once("head.php");

?>
<p>
<?php if ($error) echo "<font color=\"red\">$error</font><br />"; ?>
<?php echo _("File editing")." <code>$R/<b>$file</b></code><br />"; ?>
</p>
<form action="bro_editor.php" method="post"><br />
<div id="resizer" style="left: 0px; top: 0px; z-index: 54; width: <?php echo $p["editsizex"]; ?>px; height: <?php echo $p["editsizey"]; ?>px; cursor: auto;"><textarea class="int" style="font-family: <?php echo $p["editor_font"]; ?>; font-size: <?php echo $p["editor_size"]; ?>; width: 90%; height: 90%;" cols="<?php echo $p["editsizex"]; ?>" rows="<?php echo $p["editsizey"]; ?>" name="texte"><?php
$bro->content($R,$file);
?></textarea><img src="/admin/icon/winresize.gif" alt="shift+click and drag to resize textarea" title="shift+click and drag to resize textarea" height="20" width="20" /></div><br />
	<input type="hidden" name="file" value="<?php echo str_replace("\"","&quot;",$file); ?>" />
	<input type="hidden" name="R" value="<?php echo str_replace("\"","&quot;",$R); ?>" />

	<input type="submit" class="inb" value="<?php __("Save"); ?>" name="save" />
	<input type="submit" class="inb" value="<?php __("Save &amp; Quit"); ?>" name="saveret" />
	<input type="submit" class="inb" value="<?php __("Quit"); ?>" name="cancel" />
<br />
<script type="text/javascript">
<!--
SET_DHTML("resizer"+RESIZABLE);
//-->
</script>
</form>
<?php include_once("foot.php"); ?>