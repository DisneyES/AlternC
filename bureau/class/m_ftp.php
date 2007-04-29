<?php
/*
 $Id: m_ftp.php,v 1.12 2005/12/18 09:51:32 benjamin Exp $
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
 Purpose of file: Manage FTP accounts
 ----------------------------------------------------------------------
*/
/**
* Classe de gestion des comptes FTP de l'h�berg�.
*
* Cette classe permet de g�rer les Comptes FTP d'un membre h�berg�.<br />
* Copyleft {@link http://alternc.net/ AlternC Team}
*
* @copyright    AlternC-Team 2002-11-01 http://alternc.net/
*
*/
class m_ftp {

  /* ----------------------------------------------------------------- */
  /**
   * Constructeur
   */
  function m_ftp() {
  }

  /* ----------------------------------------------------------------- */
  /**
   * Quota name
   */
  function alternc_quota_names() {
    return "ftp";
  }

  /* ----------------------------------------------------------------- */
  /** Retourne la liste des comptes FTP du compte h�berg�
   * Retourne la liste des comptes FTP sous forme de tableau index� de
   * tableaus associatifs comme suit :
   * $a["id"]= ID du compte ftp
   * $a["login"]= Nom de login du compte
   * $a["pass"]= Mot de passe du compte
   * $a["dir"]= Dossier relatif � la racine du compte de l'utilisateur
   * @return array Retourne le tableau des comptes ou FALSE si une erreur s'est produite.
   */
  function get_list() {
    global $db,$err,$cuid;
    $err->log("ftp","get_list");
    $r=array();
    $db->query("SELECT id, name, homedir FROM ftpusers WHERE uid='$cuid' ORDER BY homedir;");
    if ($db->num_rows()) {
      while ($db->next_record()) {
	// On passe /var/alternc/html/u/user
	$tr=preg_match("/^\/var\/alternc\/html\/.\/[^\/]*\/(.*)$/", $db->f("homedir"),$match);    /* " */
	$r[]=array(
		   "id"=>$db->f("id"),
		   "login"=>$db->f("name"),
		   "dir"=>$match[1]
		   );
      }
      return $r;
    } else {
      $err->raise("ftp",1);
      return false;
    }
  }

  /* ----------------------------------------------------------------- */
  /** Retourne les d�tails d'un compte FTP (voir get_list)
   * Le tableau est celui du compte d'id sp�cifi�
   * @param integer $id Num�ro du compte dont on souhaite obtenir les d�tails
   * @return array Tableau associatif contenant les infos du comptes ftp
   */
  function get_ftp_details($id) {
    global $db,$err,$cuid;
    $err->log("ftp","get_ftp_details",$id);
    $r=array();
    $db->query("SELECT id, name, homedir FROM ftpusers WHERE uid='$cuid' AND id='$id';");
    if ($db->num_rows()) {
      $db->next_record();
      $tr=preg_match("/^\/var\/alternc\/html\/.\/[^\/]*\/(.*)$/", $db->f("homedir"),$match);                  /*"*/
      $lg=explode("_",$db->f("name"));
      if (!is_array($lg)) {
	$lg[0]=$db->f("name");
	$lg[1]="";
      }
      return array(
		   "id"      => $db->f("id"),
		   "prefixe" => $lg[0],
		   "login"   => isset($lg[1]) ? $lg[1] : "",
		   "dir"     => $match[1]
		   );
    } else {
      $err->raise("ftp",2);
      return false;
    }
  }

  /* ----------------------------------------------------------------- */
  /** Retourne la liste des prefixes utilisables par le compte courant
   * @return array tableau contenant la liste des prefixes (domaines + login)
   *  du compte actuel.
   */
  function prefix_list() {
    global $db,$mem,$cuid;
    $r=array();
    $r[]=$mem->user["login"];
    $db->query("SELECT domaine FROM domaines WHERE compte='$cuid' ORDER BY domaine;");
    while ($db->next_record()) {
      $r[]=$db->f("domaine");
    }
    return $r;
  }

  /* ----------------------------------------------------------------- */
  /** Affiche (ECHO) la liste des prefixes disponibles sous forme de champs d'option
   * Les champs sont affich�s sous la forme <option>prefixe</option>...
   * La valeur $current se voit affubl�e de la balise SELECTED.
   * @param string $current Prefixe s�lectionn� par d�faut
   * @return boolean TRUE.
   */
  function select_prefix_list($current) {
    $r=$this->prefix_list();
    reset($r);
    while (list($key,$val)=each($r)) {
      if ($current==$val) $c=" selected=\"selected\""; else $c="";
      echo "<option$c>$val</option>";
    }
    return true;
  }

  /* ----------------------------------------------------------------- */
  /** Modifie les param�tres du comptes FTP $id.
   * @param integer $id Num�ro du compte dont on veut modifier les param�tres
   * @param string $prefixe Prefixe du compte FTP
   * @param string $login login ajout� au pr�fixe ($prefixe_$login)
   * @param string $pass mot de passe
   * @param string $dir R�pertoire racine du compte
   * @return boolean TRUE si le compte a �t� modifi�, FALSE si une erreur est survenue.
   */
  function put_ftp_details($id,$prefixe,$login,$pass,$dir) {
    global $mem,$db,$err,$bro,$cuid;
    $err->log("ftp","put_ftp_details",$id);
    $db->query("SELECT count(*) AS cnt FROM ftpusers WHERE id='$id' and uid='$cuid';");
    $db->next_record();
    if (!$db->f("cnt")) {
      $err->raise("ftp",2);
      return false;
    }
    $dir=$bro->convertabsolute($dir);
    if (substr($dir,0,1)=="/") {
      $dir=substr($dir,1);
    }
    $r=$this->prefix_list();
    if (!in_array($prefixe,$r)) {
      $err->raise("ftp",3);
      return false;
    }
    $lo=$mem->user["login"];
    $l=substr($lo,0,1);
    if ($login) $login="_".$login;
    $db->query("SELECT COUNT(*) AS cnt FROM ftpusers WHERE id!='$id' AND name='$prefixe$login';");
    $db->next_record();
    if ($db->f("cnt")) {
      $err->raise("ftp",4);
      return false;
    }
    $absolute="/var/alternc/html/".substr($mem->user["login"],0,1)."/".$mem->user["login"]."/$dir";
    if (!file_exists($absolute)) {
      system("/bin/mkdir -p $absolute");
    }
    if (!is_dir($absolute)) {
      $err->raise("ftp",6);
      return false;
    }
    if (trim($pass)!="********") {
      $db->query("UPDATE ftpusers SET name='".$prefixe.$login."', password='', encrypted_password=ENCRYPT('$pass'), homedir='/var/alternc/html/$l/$lo/$dir', uid='$cuid' WHERE id='$id';");
    } else {
      $db->query("UPDATE ftpusers SET name='".$prefixe.$login."', homedir='/var/alternc/html/$l/$lo/$dir', uid='$cuid' WHERE id='$id';");
    }
    return true;
  }

  /* ----------------------------------------------------------------- */
  /** Efface le compte ftp sp�cifi�.
   * @param integer $id Num�ro du compte FTP � supprimer.
   * @return boolean TRUE si le compte a �t� effac�, FALSE sinon.
   */
  function delete_ftp($id) {
    global $db,$err,$cuid;
    $err->log("ftp","delete_ftp",$id);
    $db->query("SELECT name FROM ftpusers WHERE id='$id' and uid='$cuid';");
    $db->next_record();
    $name=$db->f("name");
    if (!$name) {
      $err->raise("ftp",2);
      return false;
    }
    $db->query("DELETE FROM ftpusers WHERE id='$id'");
    return $name;
  }

  /* ----------------------------------------------------------------- */
  /** Cr�e un nouveau compte FTP.
   * @param string $prefixe Prefixe au login
   * @param string $login Login ftp (login=prefixe_login)
   * @param string $pass Mot de passe FTP
   * @param string $dir R�pertoire racine du compte relatif � la racine du membre
   * @return boolean TRUE si le compte a �t� cr��, FALSE sinon.
   *
   */
  function add_ftp($prefixe,$login,$pass,$dir) {
    global $mem,$db,$err,$quota,$bro,$cuid;
    $err->log("ftp","add_ftp",$prefixe."_".$login);
    $dir=$bro->convertabsolute($dir);
    if (substr($dir,0,1)=="/") {
      $dir=substr($dir,1);
    }
    $r=$this->prefix_list();
    if (!in_array($prefixe,$r) || $prefixe=="") {
      $err->raise("ftp",3);
      return false;
    }
    if ($login) $login="_".$login;
    $db->query("SELECT count(*) AS cnt FROM ftpusers WHERE name='".$prefixe.$login."'");
    $db->next_record();
    if ($db->f("cnt")) {
      $err->raise("ftp",4);
      return false;
    }
    $db->query("SELECT login FROM membres WHERE uid='$cuid';");
    $db->next_record();
    $lo=$db->f("login");
    $l=substr($lo,0,1);
    $absolute="/var/alternc/html/".substr($mem->user["login"],0,1)."/".$mem->user["login"]."/$dir";
    if (!file_exists($absolute)) {
      system("/bin/mkdir -p $absolute");
    }
    if (!is_dir($absolute)) {
      $err->raise("ftp",6);
      return false;
    }
    if ($quota->cancreate("ftp")) {
      $db->query("INSERT INTO ftpusers (name,password, encrypted_password,homedir,uid) VALUES ('".$prefixe.$login."', '', ENCRYPT('$pass'), '/var/alternc/html/$l/$lo/$dir', '$cuid')");
      return true;
    } else {
      $err->raise("ftp",5);
      return false;
    }
  }

  /* ----------------------------------------------------------------- */
  /** Retourne TRUE si $dir poss�de un compte FTP
   * @param string $dir Dossier � tester, relatif � la racine du compte courant
   * @return boolean retourne TRUE si $dir � un compte FTP, FALSE sinon.
   */
  function is_ftp($dir) {
    global $mem,$db,$err;
    $err->log("ftp","is_ftp",$dir);
    $lo=$mem->user["login"];
    $l=substr($lo,0,1);
    if (substr($dir,0,1)=="/") $dir=substr($dir,1);
    $db->query("SELECT id FROM ftpusers WHERE homedir='/var/alternc/html/$l/$lo/$dir';");
    if ($db->num_rows()) {
      $db->next_record();
      return $db->f("id");
    } else {
      return false;
    }
  }

  /* ----------------------------------------------------------------- */
  /** Fonction appell�e par domains quand un deomaine est supprim� pour le membre
   * @param string $dom Domaine � d�truire.
   * @access private
   */
  function alternc_del_domain($dom) {
    global $db,$err,$cuid;
    $err->log("ftp","del_dom",$dom);
    $db->query("SELECT COUNT(*) AS cnt FROM ftpusers WHERE uid='$cuid' AND name LIKE '$dom%'");
    $db->next_record();
    $cnt=$db->Record["cnt"];
    $db->query("DELETE FROM ftpusers WHERE uid='$cuid' AND name LIKE '$dom%'");
    return true;
  }

  /* ----------------------------------------------------------------- */
  /** Fonction appell�e par membres quand un membre est effac�.
   * @param integer $uid Num�ro de membre effac�.
   * @access private
   */
  function alternc_del_member() {
    global $db,$err,$cuid;
    $err->log("ftp","del_member");
    $db->query("DELETE FROM ftpusers WHERE uid='$cuid'");
    return true;
  }

  /* ----------------------------------------------------------------- */
  /**
   * Returns the used quota for the $name service for the current user.
   * @param $name string name of the quota
   * @return integer the number of service used or false if an error occured
   * @access private
   */
  function alternc_get_quota($name) {
    global $db,$err,$cuid;
    if ($name=="ftp") {
      $err->log("ftp","getquota");
      $db->query("SELECT COUNT(*) AS cnt FROM ftpusers WHERE uid='$cuid'");
      $db->next_record();
      return $db->f("cnt");
    } else return false;
  }


  /* ----------------------------------------------------------------- */
  /**
   * Exporte toutes les informations ftp du compte AlternC
   * @access private
   * EXPERIMENTAL 'sid' function ;)
   */
  function alternc_export() {
    global $db,$err;
    $err->log("ftp","export");
    $f=$this->get_list();
    $str="<ftp>\n";
    foreach ($f as $d) {
      $str.="  <account>\n";
      $str.="    <login>".xml_entities($s[login])."</login>\n";
      $str.="    <pass>".xml_entities($s[pass])."</pass>\n";
      $str.="    <dir>".xml_entities($s[dir])."</dir>\n";
      $str.="  </account>\n";
    }
    $str.="</ftp>\n";
    return $str;
  }


} /* Class m_ftp */

?>
