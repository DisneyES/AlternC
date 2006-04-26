<?php
/*
 $Id: m_quota.php,v 1.17 2006/02/09 19:48:30 benjamin Exp $
 ----------------------------------------------------------------------
 AlternC - Web Hosting System
 Copyright (C) 2006 Le r�seau Koumbit Inc.
 http://koumbit.org/
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
 Purpose of file: Manage user quota
 ----------------------------------------------------------------------
*/
/*
# Structure de la table `defquotas`
CREATE TABLE `defquotas` (
  `quota` varchar(128) NOT NULL default '',
  `value` bigint(20) unsigned NOT NULL default '0'
  `type`  varchar(128) NOT NULL default ''
) TYPE=MyISAM COMMENT='Quotas par d�faut (nouveaux comptes)';
# Structure de la table `quotas`
CREATE TABLE `quotas` (
  `uid` int(10) unsigned NOT NULL default '0',
  `name` varchar(64) NOT NULL default '',
  `total` int(11) NOT NULL default '0',
  PRIMARY KEY  (`uid`,`name`)
) TYPE=MyISAM COMMENT='Quotas des Membres';
*/

/**
* Class for hosting quotas management
*
* This class manages services' quotas for each user of AlternC.
* The available quotas for each service is stored in the system.quotas
* mysql table. The used value is computed by the class using a
* callback function <code>alternc_quota_check($uid)</code> that
* may by exported by each service class.<br>
* each class may also export a function <code>alternc_quota_names()</code>
* that returns an array with the quotas names managed by this class.
*
* @copyright    AlternC-Team 2001-2005 http://alternc.org/
*
*/


class m_quota {

  var $disk=Array(  /* Liste des ressources disque soumises a quota */
		  "web"=>"web");

  var $quotas;
  var $clquota; // Which class manage which quota.

  /* ----------------------------------------------------------------- */
  /**
   * Constructor
   */
  function m_quota() {
  }

  /* ----------------------------------------------------------------- */
  /** Check if a user can use a ressource.
   * @Return TRUE if the user can create a ressource (= is there any quota left ?)
   */
  function cancreate($ressource="") {
    $t=$this->getquota($ressource);
    return $t["u"]<$t["t"];
  }

  /* ----------------------------------------------------------------- */
  /**
   * @Return an array with the list of quota-managed services in the server
   */
  function qlist() {
    global $classes;
    $qlist=array();
    reset($this->disk);
    while (list($key,$val)=each($this->disk)) {
      $qlist[$key]=_("quota_".$key); // those are specific disks quotas.
    }
    for($i=0;$i<count($classes);$i++) {
      if (method_exists($GLOBALS[$classes[$i]],"alternc_quota_names")) {
	$res=$GLOBALS[$classes[$i]]->alternc_quota_names(); // returns a string or an array.
	if($res != "") {
	  if (is_array($res)) {
	    foreach($res as $k) {
	      $qlist[$k]=_("quota_".$k);
	      $this->clquota[$k]=$classes[$i];
	    }
	  } else {
	    $qlist[$res]=_("quota_".$res);
	    $this->clquota[$res]=$classes[$i];
	  }
	}
      }
    }
    return $qlist;
  }

  /* ----------------------------------------------------------------- */
  /**
   * @param string ressource to get quota of
   * @Return the quota used and total for this ressource (or for all ressource if unspecified)
   */
  function getquota($ressource="") {
    global $db,$err,$cuid;
    $err->log("quota","getquota",$ressource);
    $this->qlist(); // Generate the quota list.
    $db->query("select * from quotas where uid='$cuid';");
    if ($db->num_rows()==0) {
      return array("t"=>0, "u"=>0);
    } else {
      while ($db->next_record()) {
	$ttmp[]=$db->Record;
      }
      foreach ($ttmp as $tt) {
	$g=array("t"=>$tt["total"],"u"=>0);
	if (method_exists($GLOBALS[$this->clquota[$tt["name"]]],"alternc_get_quota")) {
	  $g["u"]=$GLOBALS[$this->clquota[$tt["name"]]]->alternc_get_quota($tt["name"]);
	}
	$this->quotas[$tt["name"]]=$g;
      }
    }
    reset($this->disk);
    while (list($key,$val)=each($this->disk)) {
      $a=array();
      exec("/usr/lib/alternc/quota_get ".$cuid." ".$val,$a);
      $this->quotas[$val]=array("t"=>$a[1],"u"=>$a[0]);
    }

    if ($ressource) {
      return $this->quotas[$ressource];
    } else {
      return $this->quotas;
    }
  }

  /* ----------------------------------------------------------------- */
  /** Set the quota for a user (and for a ressource)
   * @param string ressource to set quota of
   * @param integer size of the quota (available or used)
   */
  function setquota($ressource,$size) {
    global $err,$db,$cuid;
    $err->log("quota","setquota",$ressource."/".$size);
    if (intval($size)==0) $size="0";
    if ($this->disk[$ressource]) {
      // It's a disk resource, update it with shell command
      exec("/usr/lib/alternc/quota_edit $cuid $size");
    }
    // We check that this ressource exists for this client :
    $db->query("SELECT * FROM quotas WHERE uid='$cuid' AND name='$ressource'");
    if ($db->num_rows()) {
	$db->query("UPDATE quotas SET total='$size' WHERE uid='$cuid' AND name='$ressource';");
    } else {
	$db->query("INSERT INTO quotas (uid,name,total) VALUES ('$cuid','$ressource','$size');");
    }
    return true;
  }

  /* ----------------------------------------------------------------- */
  /**
   * Increment the resource usage for the named resource
   * TODO : delete this function as it is useless... (and empty ;) )
   */
  function inc($ressource) {
    global $db,$err,$cuid;
    $err->log("quota","inc",$ressource);
    return true;
  }

  /* ----------------------------------------------------------------- */
  /**
   * Decrement the resource usage for the named resource
   * TODO : delete this function as it is useless... (and empty ;) )
   */
  function dec($ressource) {
    global $db,$err,$cuid;
    $err->log("quota","dec",$ressource);
    return true;
  }

  /* ----------------------------------------------------------------- */
  /**
   * Check a user's quota: call a function for each class.
   * TODO : delete this function as it is useless... (and empty ;) )
   */
  function checkquota() {
    global $err,$classes,$cuid;
    $err->log("quota","checkquota",$id);
    return true;
  }

  /* ----------------------------------------------------------------- */
  /**
   * Erase all quota information about the user.
   */
  function delquotas() {
    global $db,$err,$cuid;
    $err->log("quota","delquota");
    $db->query("DELETE FROM quotas WHERE uid='$cuid';");
    return true;
  }

  /* ----------------------------------------------------------------- */
  /**
   * Get the default quotas as an associative array
   * @return array the array of the default quotas
   */
  function getdefaults() {
    global $db;
    $c=array();

    $db->query("SELECT type,quota FROM defquotas WHERE type='default'");
    if(!$db->next_record())
      $this->addtype('default');

    $db->query("SELECT value,quota,type FROM defquotas ORDER BY type,quota");
    while($db->next_record()) {
      $type = $db->f("type");

      $c[$type][$db->f("quota")] = $db->f("value");
    }
    return $c;
  }

  /* ----------------------------------------------------------------- */
  /**
   * Set the default quotas
   * @param array associative array of quota (key=>val)
   */
  function setdefaults($newq) {
    global $db;
    $qlist=$this->qlist();

    foreach($newq as $type => $quotas) {
      reset($qlist);
      foreach($qlist as $qname => $val) {
	$db->query("SELECT value FROM defquotas WHERE type='$type' AND quota='$qname'");
	if (!$db->next_record()) {
	  $db->query("INSERT INTO defquotas (value,quota,type) VALUES ('".$quotas[$qname]."','$key','$type');");
	} else {
	  $db->query("UPDATE defquotas SET value='".$quotas[$qname]."' WHERE type='$type' AND quota='$qname';");
	}
      }
    }
    return true;
  }

  /* ----------------------------------------------------------------- */
  /**
   * Add an account type for quotas
   * @param string account type to be added
   */
  function addtype($type) {
    global $db;
    $qlist=$this->qlist();
    reset($qlist);
    while (list($key,$val)=each($qlist)) {
      $db->query("SELECT value FROM defquotas WHERE quota='$key' AND type='$type'");
      if (!$db->next_record()) {
	$db->query("INSERT INTO defquotas (quota,type) VALUES('$key', '$type');");
      }
    }
  }

  /* ----------------------------------------------------------------- */
  /**
   * Delete an account type for quotas
   * @param string account type to be deleted
   */
  function deltype($type) {
    global $db;
    $qlist=$this->qlist();
    reset($qlist);

    $db->query("UPDATE membres SET type='default' WHERE type='$type'");
    $db->query("DELETE FROM defquotas WHERE type='$type'");
  }

  /* ----------------------------------------------------------------- */
  /**
   * Create default quotas entries for a new user.
   */
  function addquotas() {
    global $db,$err,$cuid;
    $err->log("quota","addquota");
    $ql=$this->qlist();
    reset($ql);

    $db->query("SELECT type,quota FROM defquotas WHERE type='default'");
    if(!$db->next_record())
      $this->addtype('default');

    $db->query("SELECT type FROM membres WHERE uid='$cuid'");
    $db->next_record();
    $t = $db->f("type");

    foreach($ql as $res => $val) {
      $db->query("SELECT value FROM defquotas WHERE quota='$res' AND type='$t'");
      $q = $db->next_record() ? $db->f("value") : 0;
      $this->setquota($res, $q);
    }
    return true;
  }

  /* ----------------------------------------------------------------- */
  /** Return a quota value with its unit (when it is a space quota)
   * in MB, GB, TB ...
   * @param string $type The quota type
   * @param integer $value The quota value
   * @return string a quota value with its unit.
   */
  function display_val($type, $value) {
    switch ($type) {
    case 'bw_web':
    case 'web':
      return format_size($value);
    default:
      return $value;
    }
  }

  /* ----------------------------------------------------------------- */
  /** Hook function called when a user is created
   * This function initialize the user's quotas.
   */
  function alternc_del_member() {
    $this->delquotas();
  }

  /* ----------------------------------------------------------------- */
  /** Hook function call when a user is deleted
   * AlternC's standard function called when a user is deleted
   */
  function alternc_add_member() {
    $this->addquotas();
  }

} /* Class m_quota */

?>