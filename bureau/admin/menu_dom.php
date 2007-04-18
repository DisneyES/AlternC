<?php
/*
 $Id: menu_dom.php,v 1.2 2003/06/10 06:42:25 root Exp $
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

		/* ############# DOMAINES ############# */
$q = $quota->getquota("dom");

if ($q["t"] > 0)
{

?>
<div class="menu-box">
<div class="menu-title" id="test" onclick="javascript:deploy('menu-dom');">
<script type="text/javascript">
<!--
	document.write('<img src="images/plus.png" alt="" style="float: right; padding: 4px; border: 0px;" id="img-menu-dom" />');
//-->
</script>
<img src="images/dom.png" alt="<?php __("Domains"); ?>" />&nbsp;<?php __("Domains"); ?> (<?= $q["u"]; ?>/<?= $q["t"]; ?>)</div>
<div class="menu-content" id="menu-dom">
<ul>
<?php if ($quota->cancreate("dom")) { ?>
	<li><a href="dom_add.php"><img src="images/new.png" alt="<?php __("Add a domain"); ?>" /><?php __("Add a domain"); ?></a></li>
<?php }

/* Enumeration des domaines : */
$domain = $dom->enum_domains();
reset($domain);
while (list($key, $val) = each($domain))
{
?>
	<li><a href="dom_edit.php?domain=<?php echo urlencode($val) ?>"><?php echo $val ?></a></li>
<?php } ?>
</ul>
</div>
</div>
<?php } ?>