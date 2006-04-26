<?php
/*
 $Id: adm_tldedit.php,v 1.2 2003/06/10 12:14:09 root Exp $
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

$mode=$admin->gettld($tld);
if ($mode===false) {
	$error=$err->errstr();
	include("adm_tld.php");
	exit();
}

include("head.php");
?>
</head>
<body>
<h3><?php __("Manage allowed domains (TLD)"); ?></h3>
<?php
        if ($error) {
                echo "<p class=\"error\">$error</p>";
        }
?>
<h3><?php __("Edit a TLD"); ?></h3>

<form method="post" action="adm_tlddoedit.php">
<table border="0" cellpadding="4" cellspacing="0">
<tr><th><label for="tld"><?php __("TLD"); ?></label></th><td><code><?php echo $tld; ?></code><input type="hidden" name="tld" id="tld" value="<?php echo $tld; ?>" /></td></tr>
<tr><th><label for="mode"><?php __("Allowed Mode"); ?></label></th><td><select name="mode" class="inl" id="mode">
        <?php $admin->selecttldmode($mode); ?>
</select></td></tr>
<tr><td colspan="2"><input type="submit" class="inb" value="<?php __("Edit this TLD"); ?>" /></td></tr>
</table>
</form>

</body>
</html>
