<?php
/*
 $Id$
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
?>
<div class="menu-box">
 <div class="menu-title">
  <img src="images/lang.png" alt="<?php __("Languages"); ?>" />&nbsp;<?php __("Languages"); ?></div>
 <div class="menu-content" id="menu-lang">
  <ul>
   <?php foreach($locales as $l) { ?>
    <li><a href="login.php?setlang=<?php echo $l; ?>" target="_top"><?php __($l); ?></a></li>
   <?php } ?>
  </ul>
 </div>
</div>
