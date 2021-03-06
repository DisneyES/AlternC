<?php

require_once("../class/config.php");
if (!defined("QUOTASONE")) return;

//FIXME missing getfield for $mode
if (!isset($mode)) { # when included from adm_login, mode is not set
  $mode = 0;
}
// $mode = 4; // Pour Debuguer le mode "graphique" des quotas
?>
<center>

<h3 style="text-align:center;"><?php printf(_("<b>%s</b> account"),$mem->user["login"]); ?></h3>

<div style="width: 600px">

<!-- Webspaces -->

<?php

  $totalweb = $quota->getquota('web');
  if ( $totalweb['u'] > 0 ) {
    $t=$quota->get_size_unit($totalweb['u'] * 1024);
    echo "<p>"._("quota_web")." "; // use quota_web because it's the magically translated string
    echo sprintf("%.1f", $t['size'])."&nbsp;".$t['unit'];
    echo "</p>";
  }
?>

<!-- Mails -->

<table class="tedit" width="100%">
<thead>
<tr>
   <th><?php __("Domains"); ?></th>
   <th><?php __("Emails"); ?></th>
   <th><?php __("Space"); ?></th>
</tr>
</thead>
<tbody>
<?php

  $domaines_user = $dom->enum_domains($mem->user["uid"]);
  $totalmail=0;
  foreach ($domaines_user as $domaine) {
    $mstmp = $quota->get_size_mail_sum_domain($domaine);
    $totalmail+=$mstmp;
  }

  $t=$quota->get_size_unit($totalmail);

  foreach ($domaines_user as $domaine) { 
    $alias_sizes = $quota->get_size_mail_details_domain($domaine);
    $domsize = 0; 
    foreach ($alias_sizes as $e) {
      if($e['size'] > 0) {
        $domsize += $e['size'];
        echo "<tr><td>{$domaine}</td>";
        echo "<td>".str_replace("_","@",$e["alias"])."</td>";
        echo "<td"; if ($mode!=2) echo " style=\"text-align: right\""; echo ">";
        $ms = $quota->get_size_unit($e['size']);
        if ($totalmail) {
          $pc=intval(100*($e['size']/$totalmail));
        } else {
          $pc=0;
        }
        if ($mode==0) {
          echo sprintf("%.1f", $ms['size'])."&nbsp;".$ms['unit'];
        } elseif ($mode==1) {
          echo sprintf("%.1f", $pc)."&nbsp;%";
        } else {
          $quota->quota_displaybar($pc);
        }
        echo "</td></tr>";
      }
    }

    $d = $quota->get_size_unit($domsize);

    if ($totalmail) {
      $tpc = intval(100 * $domsize / $totalmail);
    } else {
      $tpc = 0;
    }
    if (count($alias_sizes) > 0) {
    echo "<tr><td><i>". _('Total'). " {$domaine}</i></td><td></td>";
    echo "<td";
    if ($mode!=2) echo " style=\"text-align: right\"";
    echo "><i>";
    if ($mode==0) {
      echo sprintf("%.1f", $d['size'])."&nbsp;".$d['unit'];
    } elseif ($mode==1) {
      echo sprintf("%.1f", $tpc)."&nbsp;%";
    } else {
      $quota->quota_displaybar($tpc);
    }
    echo "</i></td></tr>";
  }
}
?>
</tbody>
</table>

<p>&nbsp;</p>
<!-- Databases -->

<?php
  $totaldb = $quota->get_size_db_sum_user($mem->user["login"]);

  $t = $quota->get_size_unit($totaldb);
  echo "<p>"._("Databases:")." ";
  echo sprintf("%.1f", $t['size'])."&nbsp;".$t['unit'];
  echo "</p>";
?>

<table class="tedit" width="100%">
<thead>
<tr>
  <th width='50%'><?php __("DB"); ?></th>
  <th width='50%'><?php __("Space"); ?></th>
</tr>
</thead>
<tbody>
<?php

  $db_sizes = $quota->get_size_db_details_user($mem->user["login"]);
  foreach ($db_sizes as $d) {
    echo "<tr><td>".$d["db"]."</td><td";
    if ($mode!=2) echo " style=\"text-align: right\"";
    echo ">";
    $ds = $quota->get_size_unit($d["size"]);
    if ($totaldb) {
      $pc=intval(100*$d['size']/$totaldb);
    } else {
      $pc=0;
    }
    if (isset($mode) && $mode==0) {
      echo sprintf("%.1f", $ds['size'])."&nbsp;".$ds['unit'];
    } elseif (isset($mode) &&$mode==1) {
      echo sprintf("%.1f", $pc)."&nbsp;%";
    } else {
      $quota->quota_displaybar(2*$pc, 0);
    }
    echo "</td></tr>";
  }
?>
</tbody>
</table>

<!-- Mailing lists -->

<?php
  $totallist = $quota->get_size_mailman_sum_user($mem->user["uid"]);
  if ($totallist) {
    // $totalweb is in KB, so we call get_size_unit() with it in Bytes
    $t=$quota->get_size_unit($totallist * 1024);
    echo "<p>"._("Mailman lists:")." ";
    echo sprintf("%.1f", $t['size'])."&nbsp;".$t['unit'];
    echo "</p>";
?>

<table class="tedit" width='60%'>
<thead>
<tr>
  <th><?php __("Lists"); ?></th>
  <th><?php __("Space"); ?></th>
</tr>
</thead>
<tbody>
<?php

  $mailman_size = $quota->get_size_mailman_details_user($mem->user["uid"]);
  foreach ($mailman_size as $d) {
    echo "<tr><td>".$d["list"]."</td><td";
    if ($mode!=2) echo " style=\"text-align: right\"";
    echo ">";
    $ds = $quota->get_size_unit($d["size"] * 1024);
    if ($totallist) {
      $pc=intval(100*$ds['size']/$totallist);
    } else {
      $pc=0;
    }
    if ($mode==0) {
      echo sprintf("%.1f", $ds['size'])."&nbsp;".$ds['unit'];
    } elseif ($mode==1) {
      echo sprintf("%.1f", $pc)."&nbsp;%";
    } else {
      $quota->quota_displaybar($pc);
    }
    echo "</td></tr>";
  }
?>
</tbody>
</table>

    <?php } /* totallist */ ?>
</div>
</center>
