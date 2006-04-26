<?php
/*
 $Id: dom_dodel.php,v 1.3 2003/06/10 11:18:27 root Exp $
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
 Purpose of file: Delete a domain, confirm the deletion
 ----------------------------------------------------------------------
*/
require_once("../class/config.php");

$dom->lock();
if ($del_confirm=="y")
	if (!$dom->del_domain($domain)) {
		$error=$err->errstr();
		include("dom_edit.php");
		$dom->unlock();
		exit();
	}

$dom->unlock();
if ($del_confirm!="y") {

include("head.php");
?>
</head>
<body>
<h3><?php printf(_("Confirm the deletion of domain %s"),$domain); ?></h3>
<p class="error"><?php __("WARNING"); ?><br /><?php printf(_("Confirm the deletion of domain %s"),$domain); ?><br />
<?php __("This will delete the related sub-domains too."); ?></p>
<form method="post" action="dom_dodel.php" id="main">
<p>
<input type="hidden" name="del_confirm" value="y" />
<input type="hidden" name="domain" value="<?php echo $domain ?>" />
<input type="submit" class="inb" name="submit" value="<?php __("Yes"); ?>" /> - <input type="button" class="inb" name="non" value="<?php __("No"); ?>" onclick="history.back()" />
</form>
</body>
</html>
<?php
	exit();
	}
include("head.php");
?>
</head>
<body bgcolor="#FFFFF0">
<h3><?php printf(_("Domain %s deleted"),$domain); ?></h3>
<p>
<?php printf(_("The domain %s has been successfully deleted."),$domain); ?><br />
<a href="login.php" target="_parent"><?php __("Click here to continue"); ?></a>
<?php $mem->show_help("del_domain"); ?>
</p>
</body>
</html>
