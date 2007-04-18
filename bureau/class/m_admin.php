<?php
/*
 $Id: m_admin.php,v 1.16 2006/02/09 20:12:22 benjamin Exp $
 ----------------------------------------------------------------------
 AlternC - Web Hosting System
 Copyright (C) 2006 Le r�seau Koumbit Inc.
 http://koumbit.org/
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
 Purpose of file: Administrate members and rights.
 ----------------------------------------------------------------------
*/
/* ----------------------------------------------------------------- */
/**
* Classe de gestion de l'administration du serveur par les super-admin.
*
* Cette classe permet de cr�er / modifier / d�truire les comptes, ainsi que de
* modifier les param�tres du serveur.<br />
* Copyleft {@link http://alternc.net/ AlternC Team}
*
*
*/
class m_admin {

  /* ----------------------------------------------------------------- */
  /**
   * $enabled precises if the logged user is super-admin or not
   */
  var $enabled=0;

  /* ----------------------------------------------------------------- */
    /** List of the controls made for each TLD
    *
    * $tldmode is used by the administration panel, while choosing
    * the authorized TLDs. It's an array of strings explaining the current state of the TLD.
    */

  var $tldmode=array(
		     0 => "This TLD is forbidden",
		     1 => "primary DNS is checked in WHOIS db",
		     2 => "primary & secondary DNS are checked in WHOIS db",
		     3 => "Domain must exist, but don't do any DNS check",
		     4 => "Domain can be installed, no check at all",
		     5 => "Domain can be installed, force NO DNS hosting"
		     );

  /* ----------------------------------------------------------------- */
  /**
   * Constructeur
   */
  function m_admin() {
    global $db,$cuid;
    $db->query("SELECT su FROM membres WHERE uid='$cuid';");
    $db->next_record();
    $this->enabled=$db->f("su");
  }

  /* ----------------------------------------------------------------- */
  /**
   * Returns the known information about a hosted account
   *
   * Returns all what we know about an account (contents of the tables
   *  <code>membres</code> et <code>local</code>)
   * Ckecks if the account is super-admin
   * @param integer $uid a unique integer identifying the account
   * @return an associative array containing all the fields of the
   * table <code>membres</code> and <code>local</code> of the corresponding account.
   * Returns FALSE if an error occurs.
   *
   * Retourne tout ce que l'on sait sur un membre (contenu des tables <code>membres et local</code>)
   * v�rifie que le compte appelant est super-admin
   * @param integer $uid Num�ro de l'utilisateur dont on veut les informations.
   * @return array Retourne un tableau associatif contenant l'ensemble des champs des tables 'membres'
   *  et 'local' pour le membre demand�. Retourne FALSE si une erreur s'est produite.
   *
   */
  function get($uid) {
    global $err,$db;
    //    $err->log("admin","get",$uid);
    if (!$this->enabled) {
      $err->raise("admin",1);
      return false;
    }
    $db->query("SELECT * FROM membres WHERE uid='$uid';");
    if ($db->num_rows()) {
      $db->next_record();
      $c=$db->Record;
    } else {
      $err->raise("admin",2);
      return false;
    }
    $db->query("SELECT * FROM local WHERE uid='$uid';");
    if ($db->num_rows()) {
      $db->next_record();
      reset($db->Record);
      while (list($key,$val)=each($db->Record)) {
	$c[$key]=$val;
      }
    }
    return $c;
  }

  /* ----------------------------------------------------------------- */
  /**
   * @return TRUE if there's only ONE admin account
   * Retourne true s'il n'existe qu'un seul compte administrateur
   */
  function onesu() {
    global $db;
    $db->query("SELECT COUNT(*) AS cnt FROM membres WHERE su=1");
    $db->next_record();
    return ($db->f("cnt")==1);
  }

  /* ----------------------------------------------------------------- */
  /**
   * Returns the list of the hosted accounts
   * Retourne la liste des membres h�berg�s
   *
   * Returns all what we know about ALL the accounts (contents of the tables
   *  <code>membres</code> et <code>local</code>)
   * Check for super-admin accounts
   * @param
   * @return an associative array containing all the fields of the
   * table <code>membres</code> and <code>local</code> of all the accounts.
   * Returns FALSE if an error occurs.
   *
   * Retourne tout ce que l'on sait sur LES membres (contenu de membres et local)
   * v�rifie que le compte appelant est super-admin
   * @return array Retourne un tableau index� de tableaux associatifs contenant l'ensemble des
   *  champs des tables 'membres' et 'local' pour les membre. Retourne FALSE si une erreur s'est
   *  produite.
   *
   */
  function get_list($all=0) {
    // PATCHBEN pour ne voir que les comptes que l'on a cr�� (sauf admin)
    global $err,$mem,$cuid;
    $err->log("admin","get_list");
    if (!$this->enabled) {
      $err->raise("admin",1);
      return false;
    }
    $db=new DB_System();
    if ($mem->user[uid]==2000 || $all) {
      $db->query("SELECT uid FROM membres ORDER BY login;");
    } else {
      $db->query("SELECT uid FROM membres WHERE creator='".$cuid."' ORDER BY login;");
    }
    if ($db->num_rows()) {
      while ($db->next_record()) {
	$c[]=$this->get($db->f("uid"));
      }
      return $c;
    } else {
      return false;
    }
  }

  /* ----------------------------------------------------------------- */
  /**
   * Check if I am the creator of the member $uid
   *
   * @param integer $uid a unique integer identifying the account
   */
  function checkcreator($uid) {
    global $err,$mem,$db,$cuid;
    // DONE PATCHBEN Check that the current user is editing one of it's own account !
    // but ADMIN (always uid 2000) is almighty
    if ($cuid==2000) {
      return true;
    }
    $db->query("SELECT creator FROM membres WHERE uid='$uid';");
    $db->next_record();
    if ($db->Record[creator]!=$cuid) {
      $err->raise("admin",1);
      return false;
    }
    return true;
  }

  /* ----------------------------------------------------------------- */
  /**
   * Creates a new hosted account
   *
   * Creates a new hosted account (in the tables <code>membres</code>
   * and <code>local</code>). Prevents any manipulation of the account if
   * the account $mid is not super-admin.
   *
   * @param $login string Login name like [a-z][a-z0-9]*
   * @param $pass string Password (max. 64 characters)
   * @param $nom string Name of the account owner
   * @param $prenom string First name of the account owner
   * @param $mail string Email address of the account owner, useful to get
   * one's lost password
   * @pararm $type string Account type for quotas
   * @return boolean Returns FALSE if an error occurs, TRUE if not.
   *
   *
   * Cr�e un nouveau membre h�berg�
   * Cr�ation d'un nouveau membre (dans membres et local) Refuse l'utilisation de l'objet
   * si le compte $mid n'est pas super-admin
   *
   * @param $login Nom d'utilisateur, de la forme [a-z][a-z0-9]*
   * @param $pass Mot de passe, maxi 64 caract�res
   * @param $nom Nom de la personne ou structure
   * @param $prenom Pr�nom de la personne ou structure
   * @param $mail Adresse email du propri�taire du compte, permet de r�cup�rer son mot de passe
   * @param $type Type de compte pour les quotas
   * @param $duration integer Dur�e du compte en mois
   * @return boolean Retourne FALSE si une erreur s'est produite, TRUE sinon.
   *
   */
  function add_mem($login, $pass, $nom, $prenom, $mail, $canpass=1, $type='default', $duration=0) {
    global $err,$quota,$classes,$cuid,$mem,$L_MYSQL_DATABASE,$L_MYSQL_LOGIN;
    $err->log("admin","add_mem",$login."/".$mail);
    if (!$this->enabled) {
      $err->raise("admin",1);
      return false;
    }
    if (($login=="")||($pass=="")||($mail=="")){
      $err->raise("admin",6);
      return false;
    }
    if (checkmail($mail)!=0){
      $err->raise("admin",5);
      return false;
    }
    // V�rification de la conformit� du login
    $login=strtolower($login);
    if (!ereg("^[a-z0-9-]*$",$login)) { //$
      $err->raise("admin",10);
      return false;
    }
    if (strlen($login) > 16) {
      $err->raise("admin",13);
      return false;
    }
    // Il ne peut pas �tre �gal au login ou au nom de base systeme !
    if ($login==$L_MYSQL_DATABASE || $login==$L_MYSQL_LOGIN || $login=="mysql" || $login=="root") {
      $err->raise("admin",10);
      return false;
    }
    //$pass=stripslashes($pass);
    $pass=_md5cr($pass);
    $db=new DB_System();
    // v�rification de l'inexistence du membre dans system.membres
    $db->query("SELECT count(*) AS cnt FROM membres WHERE login='$login';");
    $db->next_record();
    if (!$db->f("cnt")) {
      $db->query("SELECT m.uid+1 as nextid FROM membres m LEFT JOIN membres n ON m.uid=n.uid-1 WHERE n.uid IS NULL ORDER BY 1 LIMIT 0,1");
      if (!$db->next_record()) {
	$uid=2000;
      } else {
	$uid=$db->Record["nextid"];
	if ($uid<=2000) $uid=2000;
      }
      // on le cr�� ensuite dans system.membres et system.local
      $db->query("INSERT INTO membres (uid,login,pass,mail,creator,canpass,type,created) VALUES ('$uid','$login','$pass','$mail','$cuid','$canpass', '$type', NOW());");
      $db->query("INSERT INTO local(uid,nom,prenom) VALUES('$uid','$nom','$prenom');");
      $this->renew_update($uid, $duration);
      exec("/usr/lib/alternc/mem_add ".$login." ".$uid);
      // Declenchons les autres classes.
      $mem->su($uid);
      foreach($classes as $c) {
	if (method_exists($GLOBALS[$c],"alternc_add_member")) {
	  $GLOBALS[$c]->alternc_add_member();
	}
      }
      $mem->unsu();
      return $uid;
    } else {
      $err->raise("admin",3);
      return false;
    }
  }

  /* ----------------------------------------------------------------- */
  /**
   * Modifies an account
   *
   * Modifies an account (in the tables <code>membres</code>
   * and <code>local</code>). Prevents any manipulation of the account if
   * the account $mid is not super-admin.
   *
   * @param $uid integer the uid number of the account we want to modify
   * @param login string new login name like [a-z][a-z0-9]*
   * @param $pass string new password (max. 64 characters)
   * @param $nom string new name of the account owner
   * @param $prenom string new first name of the account owner
   * @param $mail string new email address of the account owner
   * @param $enabled integer (value: 0 or 1) activates or desactivates the
   * @param $type string new type of account
   * access to the virtual desktop of this account.
   * @return boolean Returns FALSE if an error occurs, TRUE if not.
   *
   * Modifie un membre h�berg�
   *
   * modifie les donn�es d'un membre. Refuse l'utilisation de l'objet
   * si le compte $mid n'est pas super-admin
   *
   * @param $uid integer Num�ro uid de l'utilisateur que l'on souhaite modifier.
   * @param $mail string Nouvelle adresse email
   * @param $nom $prenom string Nouveaux nom et pr�nom de l'utilisateur
   * @param $pass string Nouveau mot de passe.
   * @param $enabled integer vaut 0 ou 1, active ou d�sactive l'acc�s au bureau virtuel de ce compte.
   * @param $type string Nouveau type de compte
   * @param $duration integer Dur�e du compte en mois
   * @return boolean Retourne FALSE si une erreur s'est produite, TRUE sinon.
   *
   */
  function update_mem($uid, $mail, $nom, $prenom, $pass, $enabled, $canpass, $type='default', $duration=0) {
    global $err,$db;
    global $cuid, $quota;

    //$cuid = $uid;
    $err->log("admin","update_mem",$uid);
    if (!$this->enabled) {
      $err->raise("admin",1);
      return false;
    }
    $db=new DB_System();
    if ($pass) {
      // on modifie aussi le password :
      $pass=_md5cr($pass);
      $ssq=" ,pass='$pass' ";
    } else {
      $ssq="";
    }
    if (($db->query("UPDATE local SET nom='$nom', prenom='$prenom' WHERE uid='$uid';"))
	&&($db->query("UPDATE membres SET mail='$mail', canpass='$canpass', enabled='$enabled', type='$type' $ssq WHERE uid='$uid';"))){
      if($_POST['reset_quotas'] == "on")
	$quota->addquotas();
      $this->renew_update($uid, $duration);
      return true;
    }
    else {
      $err->raise("admin",2);
      return false;
    }
  }

  /* ----------------------------------------------------------------- */
  /**
   * Lock an account
   *
   * Lock an account and prevent the user to access its account.
   *
   * @param $uid integer the uid number of the account we want to lock
   * @return boolean Returns FALSE if an error occurs, TRUE if not.
   */
  function lock_mem($uid) {
    global $err,$db;
    $err->log("admin","lock_mem",$uid);
    if (!$this->enabled) {
      $err->raise("admin",1);
      return false;
    }
    $db=new DB_System();
    if ($db->query("UPDATE membres SET enabled='0' WHERE uid='$uid';")) {
      return true;
    }
    else {
      $err->raise("admin",2);
      return false;
    }
  }


  /* ----------------------------------------------------------------- */
  /**
   * UnLock an account
   *
   * UnLock an account and prevent the user to access its account.
   *
   * @param $uid integer the uid number of the account we want to unlock
   * @return boolean Returns FALSE if an error occurs, TRUE if not.
   */
  function unlock_mem($uid) {
    global $err,$db;
    $err->log("admin","unlock_mem",$uid);
    if (!$this->enabled) {
      $err->raise("admin",1);
      return false;
    }
    $db=new DB_System();
    if ($db->query("UPDATE membres SET enabled='1' WHERE uid='$uid';")) {
      return true;
    }
    else {
      $err->raise("admin",2);
      return false;
    }
  }



  /* ----------------------------------------------------------------- */
  /**
   * Deletes an account
   *
   * Deletes the specified account. Prevents any manipulation of the account if
   * the account $mid is not super-admin.
   *
   * @param $uid integer the uid number of the account we want to delete
   * @return boolean Returns FALSE if an error occurs, TRUE if not.
   *
   *
   * Efface un membre h�berg�
   *
   * Supprime le membre sp�cifi�. Refuse l'utilisation de l'objet si le compte $mid n'est pas super-admin
   * @param $uid Num�ro du membre � supprimer.
   * @return Retourne FALSE si une erreur s'est produite, TRUE sinon.
   *
   */
  function del_mem($uid) {
    global $err,$quota,$classes,$cuid,$mem,$dom;
    $err->log("admin","del_mem",$uid);

    if (!$this->enabled) {
      $err->raise("admin",1);
      return false;
    }
    $db=new DB_System();
    $tt=$this->get($uid);

    // On devient l'utilisateur :
    $mem->su($uid);

    // WE MUST call m_dom before all others because of conflicts ...
    $dom->alternc_del_member();

      // Send the event to the other classes :
      foreach($classes as $c) {
	if (method_exists($GLOBALS[$c],"alternc_del_member")) {
	  $GLOBALS[$c]->alternc_del_member();
	}
      }
      if (($db->query("DELETE FROM membres WHERE uid='$uid';")) &&
	  ($db->query("DELETE FROM local WHERE uid='$uid';"))) {
	exec("/usr/lib/alternc/mem_del ".$tt["login"]);
	$mem->unsu();
	return true;
      } else {
	$err->raise("admin",2);
	$mem->unsu();
	return false;
      }
  }

  /* ----------------------------------------------------------------- */
  /**
   * Renew an account
   *
   * Renew an account for its duration
   *
   * @param $uid integer the uid number of the account we want to renew
   * @param $periods integer the number of periods we renew for
   * @return boolean Returns FALSE if an error occurs, TRUE if not.
   */
  function renew_mem($uid, $periods=1) {
    global $err,$db;

    $periods = intval($periods);
    if($periods == 0)
      return false;

    $query =
      "UPDATE membres SET renewed = renewed + INTERVAL (duration * $periods) MONTH WHERE uid=${uid};";

    if ($db->query($query)) {
      return true;
    } else {
      $err->raise("admin",2);
      return false;
    }
  }

  /* ----------------------------------------------------------------- */
  /**
   * Update the duration information for an account
   *
   * @param $uid integer the uid number of the account we want to update
   * @param $duration integer the new duration, in months, of the account
   * @return boolean Returns FALSE if an error occurs, TRUE if not.
   */
  function renew_update($uid, $duration) {
    global $err,$db;

    if($duration == 0) {
      if($db->query("UPDATE membres SET duration = NULL, renewed = NULL WHERE uid=$uid;"))
	return true;
    } else {
      if($db->query("UPDATE membres SET duration = $duration WHERE uid=$uid") &&
	 $db->query("UPDATE membres SET renewed = NOW() WHERE uid=$uid and renewed is null;"))
	return true;
    }

    $err->raise("admin",2);
    return false;
  }

  /* ----------------------------------------------------------------- */
  /**
   *
   * Get the expiry date for an account
   *
   * @param $uid integer The uid number of the account
   * @return string The expiry date, a string as printed by MySQL
   */
  function renew_get_expiry($uid) {
    global $db;

    $db->query("SELECT renewed + INTERVAL duration MONTH 'expiry' FROM membres WHERE uid='$uid' ;");
    if ($db->num_rows()) {
      $db->next_record();
      return $db->Record['expiry'];
    }

    return '';
  }

  /* ----------------------------------------------------------------- */
  /**
   *
   * Get the expiry status for an account
   *
   * @param $uid integer The uid number of the account
   * @return integer The expiry status:
   *  0: account does not expire
   *  1: expires in more than duration,
   *  2: expires within the duration
   *  3: has expired past the duration
   */
  function renew_get_status($uid) {
    global $db;

    $db->query(
      "SELECT CASE" .
      " WHEN duration IS NULL THEN 0" .
      " WHEN renewed + INTERVAL duration MONTH <= NOW() THEN 3" .
      " WHEN renewed <= NOW() THEN 2" .
      " ELSE 1 END 'status' FROM membres where uid=$uid;");

    if($db->num_rows()) {
      $db->next_record();
      return $db->Record['status'];
    }

    return 0;
  }

  /* ----------------------------------------------------------------- */
  /**
   *
   * Get the expired/about to expire accounts.
   *
   * @return resource The recordset of the corresponding accounts
   */
  function renew_get_expiring_accounts() {
    global $db;

    if(!$db->query("SELECT *, m.renewed + INTERVAL duration MONTH 'expiry'," .
		   " CASE WHEN m.duration IS NULL THEN 0" .
		   " WHEN m.renewed + INTERVAL m.duration MONTH <= NOW() THEN 3" .
		   " WHEN m.renewed <= NOW() THEN 2" .
		   " ELSE 1 END 'status' FROM membres m, local l" .
		   " WHERE m.uid = l.uid" .
		   " HAVING status=2 or status=3 ORDER BY status DESC, expiry;"))
      return false;
    else {
      while($db->next_record())
	$res[] = $db->Record;
      return $res;
    }
  }



  /* ----------------------------------------------------------------- */
  /**
   * Turns a common account into a super-admin account
   *
   * @param $uid integer the uid number of the common account we want to turn into a
   *  super-admin account.
   * @return Returns FALSE if an error occurs, TRUE if not.
   *
   *
   * Transforme un membre Normal en membre Administrateur
   *
   * @param $uid Num�ro du compte � transformer
   * @return Retourne FALSE si une erreur s'est produite.
   *
   */
  function normal2su($uid) {
    global $err,$db;
    $db->query("SELECT su FROM membres WHERE uid='$uid';");
    if (!$db->next_record()) {
      $err->raise("admin",2);
      return false;
    }
    if ($db->Record["su"]!=0) {
      $err->raise("admin",8);
      return false;
    }
    $db->query("UPDATE membres SET su=1 WHERE uid='$uid';");
    return true;
  }

  /* ----------------------------------------------------------------- */
  /**
   * Turns a super-admin account into a common account
   *
   * @param $uid integer the uid number of the super-admin account we want to turn into a
   * common account.
   * @return boolean Returns FALSE if an error occurs, TRUE if not.
   *
   *
   * Transforme un membre Administrateur en membre Normal
   * @param integer $uid Num�ro du compte � transformer
   * @return boolean Retourne FALSE si une erreur s'est produite.
   *
   */
  function su2normal($uid) {
    global $err,$db;
    $db->query("SELECT su FROM membres WHERE uid='$uid';");
    if (!$db->next_record()) {
      $err->raise("admin",2);
      return false;
    }
    if ($db->Record["su"]!=1) {
      $err->raise("admin",9);
      return false;
    }
    $db->query("UPDATE membres SET su=0 WHERE uid='$uid';");
    return true;
  }

  /* ----------------------------------------------------------------- */
  /**
   * List of the authorized TLDs
   *
   * Returns the list of the authorized TLDs and also the way they are
   * authorized. A TLD is the last members (or the last two) of a
   * domain. For example, "com", "org" etc... AlternC keeps a table
   * containing the list of the TLDs authorized to be installed on the
   * server with the instructions to validate the installation of a
   * domain for each TLD (if necessary).
   *
   * @return array An associative array like $r["tld"], $r["mode"] where tld
   * is the tld and mode is the authorized mode.
   *
   *
   * Liste des TLD autoris�s
   *
   * Retourne la liste des tld autoris�s, ainsi que la fa�on dont ils sont autoris�s.
   * Les tld sont le dernier membre (ou parfois les 2 derniers membres) d'un domaine.
   * Par exemple "org" "com" etc. AlternC conserve une table "tld" qui contient la liste
   * des TLD autoris�s � �tre install� sur le serveur, accompagn� des v�rifications � effectuer
   * pour chaque TLD. Par exemple, on peux v�rifier que les DNS du domaine pointent bien vers
   * notre serveur, ou juste que le domaine existe etc.
   * <p><b>Note</b> : Il faudrait pouvoir effectuer une requete DNS, et pouvoir juste v�rifier les DNS
   * via DIG et pas seulement via Whois</p>
   *
   * @return array Retourne un tableau de tableau associatif du type $r["tld"], $r["mode"].
   *  TLD est le tld et MODE est le mode autoris�
   *
   */
  function listtld() {
    global $db;
    $db->query("SELECT tld,mode FROM tld ORDER BY tld;");
    while ($db->next_record()) {
      $c[]=$db->Record;
    }
    return $c;
  }

  /* ----------------------------------------------------------------- */
  /**
   * List the hosted domains on this server
   *
   * Return the list of hosted domains on this server, (an array of associative arrays)
   * @return array $r[$i] / [domaine][member][noerase][gesdns][gesmx]
   */
  function dom_list() {
    global $db;
    $db->query("SELECT m.login,d.domaine,d.gesdns,d.gesmx,d.noerase FROM domaines d LEFT JOIN membres m ON m.uid=d.compte ORDER BY domaine;");
    while ($db->next_record()) {
      $c[]=$db->Record;
    }
    return $c;
  }

  /* ----------------------------------------------------------------- */
  /**
   * Lock / Unlock a domain
   *
   * Lock (or unlock) a domain, so that the member will be (not be) able to delete it
   * from its account
   * @param $dom string Domain name to lock / unlock
   * @return boolean TRUE if the domain has been locked/unlocked or FALSE if it does not exist.
   */
  function dom_lock($domain) {
    global $db,$err;
    $db->query("SELECT compte FROM domaines WHERE domaine='$domain';");
    if (!$db->next_record()) {
      $err->raise("dom",1);
      return false;
    }
    $db->query("UPDATE domaines SET noerase=1-noerase WHERE domaine='$domain';");
    return true;
  }


  /* ----------------------------------------------------------------- */
  /**
   * Add a new TLD to the list of the authorized TLDs
   *
   * @param $tld string top-level domain to add (org, com...)
   * @param $mode integer number of the authorized mode (0 to 5)
   * @return boolean TRUE if the tld has been successfully added, FALSE if not.
   *
   *
   * Ajoute un nouveau TLD autoris� � la liste des tld autoris�s.
   *
   * @param $tld Top-Level Domain � ajouter (org, com ...)
   * @param $mode Num�ro du mode autoris� (0->5)
   * @return boolean True si le tld a bien �t� ajout�, False sinon.
   *
   */
  function gettld($tld) {
    global $db,$err;
    $db->query("SELECT mode FROM tld WHERE tld='$tld';");
    if (!$db->next_record()) {
      $err->raise("admin",11);
      return false;
    }
    return $db->Record["mode"];
  }

  /* ----------------------------------------------------------------- */
  /**
   * Prints the list of the actually authorized TLDs
   *
   * @param $current integer Value to select in the list
   *
   * Affiche (echo) la liste d�roulante des TLD actuellement autoris�s.
   *
   * @param $current Valeur par d�faut � s�lectionner dans la liste
   *
   */
  function selecttldmode($current=false) {
    for($i=0;$i<count($this->tldmode);$i++) {
      echo "<option value=\"$i\"";
      if ($current==$i) echo " selected=\"selected\"";
      echo ">"._($this->tldmode[$i])."</option>\n";
    }
  }

  /* ----------------------------------------------------------------- */
  /**
   * Deletes the specified tld in the list of the authorized TLDs
   * <b>Note</b> : This function does not delete the domains depending
   * on this TLD
   *
   * @param $tld string The TLD you want to delete
   * @return boolean returns true if the TLD has been deleted, or
   * false if an error occured.
   *
   *
   * Supprime le tld indiqu� de la liste des TLD autoris�s � l'installation
   *
   * <b>Note</b> : Cela ne supprime pas les domaines utilisant ce TLD !
   *
   * @param $tld TLD que l'on souhaite d�truire
   * @return boolean retourne true si le TLD a bien �t� effac�, false sinon
   *
   */
  function deltld($tld) {
    global $db,$err;
    $db->query("SELECT tld FROM tld WHERE tld='$tld';");
    if (!$db->next_record()) {
      $err->raise("admin",11);
      return false;
    }
    $db->query("DELETE FROM tld WHERE tld='$tld';");
    return true;
  }

  /* ----------------------------------------------------------------- */
  /**
   * Add a TLD to the list of the authorized TLDs during the
   * installation
   *
   * @param $tld string TLD we want to authorize
   * @param $mode integer Controls to make on this TLD.
   * <b>Note: </b> If you check in the whois, be sure that
   *  <code>m_domains</code> knows how to name the whois of the specified
   *  domain !
   * @return boolean TRUE if the TLD has been successfully
   *  added. FALSE if not.
   *
   *
   *
   * Ajoute un TLD � la liste des TLD autoris�s � l'installation
   *
   * @param $tld TLD que l'on souhaite autoriser.
   * @param $mode Contr�les � effectuer sur ce TLD. <b>Note : </b>
   *  Si vous demandez le controle dans le Whois, assurez-vous que m_domains
   *  connaisse bien comment appeler le whois du domaine correspondant !
   * @return boolean retourne true si le TLD a bien �t� ajout�, false sinon
   *
   */
  function addtld($tld,$mode) {
    global $db,$err;
    if (!$tld) {
      $err->raise("admin",12);
      return false;
    }
    $db->query("SELECT tld FROM tld WHERE tld='$tld';");
    if ($db->next_record()) {
      $err->raise("admin",12);
      return false;
    }
    if (substr($tld,0,1)==".") $tld=substr($tld,1);
    $mode=intval($mode);
    if ($mode==0) $mode="0";
    $db->query("INSERT INTO tld (tld,mode) VALUES ('$tld','$mode');");
    return true;
  }

  /* ----------------------------------------------------------------- */
  /**
   * Modify a TLD of the list of the authorized TLDs
   *
   * @param $tld string TLD we want to modify
   * @param $mode integer Controls to make on this TLD.
   * @return boolean TRUE if the TLD has been successfully
   * modified. FALSE if not.
   *
   *
   * Modifie un TLD autoris� de la liste des tld autoris�s.
   *
   * @param $tld Top-Level Domain � modifier (org, com ...)
   * @param $mode Num�ro du mode autoris� (0->5)
   * @return boolean True si le tld a bien �t� modifi�, False sinon.
   *
   */
  function edittld($tld,$mode) {
    global $db,$err;
    $db->query("SELECT tld FROM tld WHERE tld='$tld';");
    if (!$db->next_record()) {
      $err->raise("admin",11);
      return false;
    }
    $mode=intval($mode);
    if ($mode==0) $mode="0";
    $db->query("UPDATE tld SET mode='$mode' WHERE tld='$tld';");
    return true;
  }

  /* ----------------------------------------------------------------- */
  /**
   * Donne le login du compte administrateur principal d'AlternC
   *
   * @return string Retourne le login du compte admin ou root.
   *
   */
  function getadmin() {
    global $db;
    $db->query("SELECT login FROM membres WHERE uid = '2000';");
    $db->next_record();
    return $db->f("login");
  }

} /* Classe ADMIN */

?>
