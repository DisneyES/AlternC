<?php
/*
 $Id: m_mysql.php,v 1.35 2005/12/18 09:51:32 benjamin Exp $
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
 Purpose of file: Manage mysql database for users.
 ----------------------------------------------------------------------
*/
/**
 * MySQL user database management for AlternC.
 * This class manage user's databases in MySQL, and user's MySQL accounts.
 *
 * @copyright    AlternC-Team 2002-2005 http://alternc.org/
 */
class m_mysql {

  var $server;
  var $client;

  /*---------------------------------------------------------------------------*/
  /** Constructor
  * m_mysql([$mid]) Constructeur de la classe m_mysql, initialise le membre concerne
  */
  function m_mysql() {
      $this->server = $GLOBALS['L_MYSQL_HOST'];
      $this->client = $GLOBALS['L_MYSQL_CLIENT'];
  }

  /* ----------------------------------------------------------------- */
  /** Hook called by m_quota to obtain the quota managed by this class.
   * Quota name
   */
  function alternc_quota_names() {
    return array("mysql","mysql_users");
  }

  /*---------------------------------------------------------------------------*/
  /** Get the list of the database for the current user.
   * @return array returns an associative array as follow : <br>
   *  "db" => database name "bck" => backup mode for this db
   *  "dir" => Backup folder.
   *  "size" => Size of the database (in bytes)
   *  Returns FALSE if the user has no database.
   */
  function get_dblist() {
    global $db,$err,$bro,$cuid;
    $err->log("mysql","get_dblist");
    $db->query("SELECT login,pass,db, bck_mode, bck_dir FROM db WHERE uid='$cuid';");
    if (!$db->num_rows()) {
      $err->raise("mysql",11);
      return false;
    }
    $c=array();
    while ($db->next_record()) {
      list($dbu,$dbn)=split_mysql_database_name($db->f("db"));
      $c[]=array("db"=>$db->f("db"), "name"=>$dbn,"bck"=>$db->f("bck_mode"), "dir"=>$db->f("bck_dir"), "login"=>$db->f("login"), "pass"=>$db->f("pass"));
    }

    /* find the size of each database */
    foreach ($c as $key => $val) {
      $c[$key]['size'] = $this->get_db_size($c[$key]['db']);
    }
    return $c;
  }

  /*---------------------------------------------------------------------------*/
  /** Returns the details of a user's database.
   * $dbn is the name of the database (after the _) or nothing for the database "$user"
   * @return array returns an associative array as follow :
   *  "db" => Name of the database
   *  "bck" => Current bckup mode
   *  "dir" => Backup directory
   *  "size" => Size of the database (in bytes)
   *  "pass" => Password of the user
   *  "history" => Number of backup we keep
   *  "gzip" => Does we compress the dumps ?
   *  Returns FALSE if the user has no database of if the database does not exist.
   */
  function get_mysql_details($dbn) {
    global $db,$err,$bro,$mem,$cuid;
    $root="/var/alternc/html/".substr($mem->user["login"],0,1)."/".$mem->user["login"];
    $err->log("mysql","get_mysql_details");
    $dbname=$mem->user["login"].($dbn?"_":"").$dbn;
    $size=$this->get_db_size($dbname);
    $db->query("SELECT login,pass,db, bck_mode, bck_gzip, bck_dir, bck_history FROM db WHERE uid='$cuid' AND db='$dbname';");
    if (!$db->num_rows()) {
      $err->raise("mysql",4);
      return array("enabled"=>false);
    }
    $c=array();
    $db->next_record();
    list($dbu,$dbn)=split_mysql_database_name($db->f("db"));
    return array("enabled"=>true,"login"=>$db->f("login"),"db"=>$db->f("db"), "name"=>$dbn,"bck"=>$db->f("bck_mode"), "dir"=>substr($db->f("bck_dir"),strlen($root)), "size"=>$size, "pass"=>$db->f("pass"), "history"=>$db->f("bck_history"), "gzip"=>$db->f("bck_gzip"));
  }

  /*---------------------------------------------------------------------------*/
  /** Create a new database for the current user.
   * @param $dbn string Database name ($user_$dbn is the mysql db name)
   * @return TRUE if the database $user_$db has been successfully created, or FALSE if
   * an error occured, such as over quota user.
   */
  function add_db($dbn) {
    global $db,$err,$quota,$mem,$cuid;
    $err->log("mysql","add_db",$dbn);
    if (!$quota->cancreate("mysql")) {
      $err->raise("mysql",1);
      return false;
    }
    if (!ereg("^[0-9a-z]*$",$dbn)) {
      $err->raise("mysql",2);
      return false;
    }
    $dbname=$mem->user["login"].($dbn?"_":"").$dbn;
    if (strlen($dbname) > 64) {
      $err->raise("mysql",12);
      return false;
    }
    $db->query("SELECT * FROM db WHERE db='$dbname';");
    if ($db->num_rows()) {
      $err->raise("mysql",3);
      return false;
    }
    // find the login/pass for this user :
    $db->query("SELECT login,pass FROM db WHERE uid='$cuid' LIMIT 0,1;");
    if (!$db->num_rows()) {
      $lo=$mem->user["login"];
      $pa="";
    } else {
      $db->next_record();
      $lo=addslashes($db->f("login"));
      $pa=addslashes($db->f("pass"));
    }
    if ($db->query("CREATE DATABASE $dbname;")) {
      // Ok, database does not exist, quota is ok and dbname is compliant. Let's proceed
      $db->query("INSERT INTO db (uid,login,pass,db,bck_mode) VALUES ('$cuid','$lo','$pa','$dbname',0);");
      // give everything but GRANT on db.*
      // we assume there's already a user
      $db->query("GRANT ALL PRIVILEGES ON `".$dbname."`.* TO '".$lo."'@'$this->client'");
      return true;
    } else {
      $err->raise("mysql",3);
      return false;
    }
  }

  /*---------------------------------------------------------------------------*/
  /** Delete a database for the current user.
   * @param $dbn string Name of the database to delete. The db name is $user_$dbn
   * @return TRUE if the database $user_$db has been successfully deleted, or FALSE if
   *  an error occured, such as db does not exist.
   */
  function del_db($dbn) {
    global $db,$err,$mem,$cuid;
    $err->log("mysql","del_db",$dbn);

		$dbname=addslashes($mem->user["login"].($dbn?"_":"").$dbn);
    $db->query("SELECT login FROM db WHERE db='$dbname';");
    if (!$db->num_rows()) {
      $err->raise("mysql",4);
      return false;
    }
    $db->next_record();
    $login=$db->f("login");

    // Ok, database exists and dbname is compliant. Let's proceed
    $db->query("DELETE FROM db WHERE uid='$cuid' AND db='$dbname';");
    $db->query("DROP DATABASE `$dbname`;");
    $db->query("SELECT COUNT(*) AS cnt FROM db WHERE uid='$cuid';");
    $db->next_record();
    $db->query("REVOKE ALL PRIVILEGES ON `".$dbname."`.* FROM '".$login."'@'$this->client'");
    if ($db->f("cnt")==0) {
      $db->query("DELETE FROM mysql.user WHERE User='".$login."';");
      $db->query("FLUSH PRIVILEGES;");
    }
    return true;
  }

  /*---------------------------------------------------------------------------*/
  /** Set the backup parameters for the database $db
   * @param $db string database name
   * @param $bck_mode integer Backup mode (0 = none 1 = daily 2 = weekly)
   * @param $bck_history integer How many backup should we keep ?
   * @param $bck_gzip boolean shall we compress the backup ?
   * @param $bck_dir string Directory relative to the user account where the backup will be stored
   * @return boolean true if the backup parameters has been successfully changed, false if not.
   */
  function put_mysql_backup($dbn,$bck_mode,$bck_history,$bck_gzip,$bck_dir) {
    global $db,$err,$mem,$bro,$cuid;
    $err->log("mysql","put_mysql_backup");
    if (!ereg("^[0-9a-z]*$",$dbn)) {
      $err->raise("mysql",2);
      return false;
    }
    $dbname=$mem->user["login"].($dbn?"_":"").$dbn;
    $db->query("SELECT * FROM db WHERE uid='$cuid' AND db='$dbname';");
    if (!$db->num_rows()) {
      $err->raise("mysql",4);
      return false;
    }
    $db->next_record();
    $bck_mode=intval($bck_mode);
    $bck_history=intval($bck_history);
    if ($bck_gzip)
      $bck_gzip="1";
    else
      $bck_gzip="0";
    if (!$bck_mode)
      $bck_mode="0";
    if (!$bck_history) {
      $err->raise("mysql",5);
      return false;
    }
    if (($bck_dir=$bro->convertabsolute($bck_dir,0))===false) { // return a full path or FALSE
      $err->raise("mysql",6);
      return false;
    }
    $db->query("UPDATE db SET bck_mode='$bck_mode', bck_history='$bck_history', bck_gzip='$bck_gzip', bck_dir='$bck_dir' WHERE uid='$cuid' AND db='$dbname';");
    return true;
  }

  /*---------------------------------------------------------------------------*/
  /** Change the password of the user in MySQL
   * @param $password string new password (cleartext)
   * @return boolean TRUE if the password has been successfully changed, FALSE else.
   */
  function put_mysql_details($password) {
    global $db,$err,$mem,$cuid;
    $err->log("mysql","put_mysql_details");
    $db->query("SELECT * FROM db WHERE uid='$cuid';");
    if (!$db->num_rows()) {
      $err->raise("mysql",7);
      return false;
    }
    $db->next_record();
    $login=$db->f("login");

    if (strlen($password)>16) {
      $err->raise("mysql",8);
      return false;
    }
    // Update all the "pass" fields for this user :
    $db->query("UPDATE db SET pass='$password' WHERE uid='$cuid';");
    $db->query("SET PASSWORD FOR '$login'@'$this->client' = PASSWORD('$password')");
    return true;
  }

  /* ----------------------------------------------------------------- */
  /** Create a new mysql account for this user
   * @param string cleartext password for the new account
   * It also create the first database.
   */
  function new_mysql($password) {
    global $db,$err,$mem,$cuid;
    $err->log("mysql","new_mysql");
    if (strlen($password)>16) {
      $err->raise("mysql",8);
      return false;
    }
    $db->query("SELECT * FROM db WHERE uid='$cuid';");
    if ($db->num_rows()) {
      $err->raise("mysql",10);
      return false;
    }
    $login=$mem->user["login"];
    $dbname=$mem->user["login"];
    // OK, creation now...
    $db->query("INSERT INTO db (uid,login,pass,db) VALUES ('$cuid','".$login."','$password','".$dbname."');");
    // give everything but GRANT on $user.*
    $db->query("GRANT ALL PRIVILEGES ON `".$dbname."`.* TO '".$login."'@'$this->client' IDENTIFIED BY '".$password."'");
    $db->query("CREATE DATABASE `".$dbname."`;");
    return true;
  }


  /* ----------------------------------------------------------------- */
  /** Restore a sql backup script on a user's database.
   */
  function restore($file,$stdout,$id) {
    global $err,$bro,$mem,$L_MYSQL_HOST;
    if (!$r=$this->get_mysql_details($id)) {
      return false;
    }
    if (!($fi=$bro->convertabsolute($file,0))) {
      $err->raise("mysql",9);
      return false;
    }
    if (substr($fi,-3)==".gz") {
      $exe="/bin/gzip -d -c <".escapeshellarg($fi)." | /usr/bin/mysql -h".escapeshellarg($L_MYSQL_HOST)." -u".escapeshellarg($r["login"])." -p".escapeshellarg($r["pass"])." ".escapeshellarg($r["db"]);
    } elseif (substr($fi,-4)==".bz2") {
      $exe="/usr/bin/bunzip2 -d -c <".escapeshellarg($fi)." | /usr/bin/mysql -h".escapeshellarg($L_MYSQL_HOST)." -u".escapeshellarg($r["login"])." -p".escapeshellarg($r["pass"])." ".escapeshellarg($r["db"]);
    } else {
      $exe="/usr/bin/mysql -h".escapeshellarg($L_MYSQL_HOST)." -u".escapeshellarg($r["login"])." -p".escapeshellarg($r["pass"])." ".escapeshellarg($r["db"])." <".escapeshellarg($fi);
    }
    $exe .= " 2>&1";

    echo "<code><pre>" ;
    if ($stdout) {
      passthru($exe,$ret);
    } else {
      exec ($exe,$ret);
    }
    echo "</pre></code>" ;
    if ($ret != 0) {
      return false ;
    } else {
      return true ;
    }
  }

  /* ----------------------------------------------------------------- */
  /** Get size of a database
   * @param $dbname name of the database
   * @return integer database size
   * @access private
   */
 function get_db_size($dbname) {
   global $db,$err;

   $db->query("SHOW TABLE STATUS FROM `$dbname`;");
   $size = 0;
   while ($db->next_record()) {
     $size += $db->f('Data_length') + $db->f('Index_length')
              + $db->f('Data_free');
   }
   return $size;
 }

  /* ----------------------------------------------------------------- */
  /** Hook function called by the quota class to compute user used quota
   * Returns the used quota for the $name service for the current user.
   * @param $name string name of the quota
   * @return integer the number of service used or false if an error occured
   * @access private
   */
  function alternc_get_quota($name) {
    global $err,$db,$cuid;
    if ($name=="mysql") {
      $err->log("mysql","alternc_get_quota");
      $c=$this->get_dblist();
      if (is_array($c)) {
	return count($c);
      } else {
	return 0;
      }
    } elseif ($name=="mysql_users") {
      $err->log("mysql","alternc_get_quota");
      $c=$this->get_userslist();
      if(is_array($c))
        return count($c);
      else
        return 0;
    } else return false;
  }


  /* ----------------------------------------------------------------- */
  /** Hook function called when a user is deleted.
   * AlternC's standard function that delete a member
   */
  function alternc_del_member() {
    global $db,$err,$cuid;
    $err->log("mysql","alternc_del_member");
    $c=$this->get_dblist();
    if (is_array($c)) {
      for($i=0;$i<count($c);$i++) {
	$this->del_db($c[$i]["name"]);
      }
    }
    return true;
  }


  /* ----------------------------------------------------------------- */
  /**
   * Exporte toutes les informations mysql du compte.
   * @access private
   * EXPERIMENTAL 'sid' function ;)
   */
  function alternc_export($tmpdir) {
    global $db,$err,$cuid;
    $err->log("mysql","export");
    $db->query("SELECT login, pass, db, bck_mode, bck_dir, bck_history, bck_gzip FROM db WHERE uid='$cuid';");
    if ($db->next_record()) {
      $str="<mysql>\n";
      $str.="  <login>".xml_entities($db->Record["login"])."</login>";
      $str.="  <pass>".xml_entities($db->Record["pass"])."</pass>";
      do {
	// Do the dump :
	$filename=$tmpdir."/mysql.".$db->Record["db"].".sql.gz";
	exec("/usr/bin/mysqldump --add-drop-table --allow-keywords -Q -f -q -a -e -u".escapeshellarg($db->Record["login"])." -p".escapeshellarg($db->Record["pass"])." ".escapeshellarg($db->Record["db"])." |/bin/gzip >".escapeshellarg($filename));
	$str.="  <db>\n";
	$str.="    <name>".xml_entities($db->Record["db"])."</name>\n";
	if ($s["bck_mode"]!=0) {
	  $str.="    <backup>\n";
	  $str.="      <mode>".xml_entities($db->Record["bck_mode"])."</mode>\n";
	  $str.="      <dir>".xml_entities($db->Record["bck_dir"])."</dir>\n";
	  $str.="      <history>".xml_entities($db->Record["bck_history"])."</history>\n";
	  $str.="      <gzip>".xml_entities($db->Record["bck_gzip"])."</gzip>\n";
	  $str.="    </backup>\n";
	}
	$str.="  </db>\n";
      } while ($db->next_record());
      $str.="</mysql>\n";
    }
    return $str;
  }

  function get_userslist() {
    global $db,$err,$bro,$cuid;
    $err->log("mysql","get_userslist");
    $db->query("SELECT name FROM dbusers WHERE uid='$cuid';");
    if (!$db->num_rows()) {
      $err->raise("mysql",19);
      return false;
    }
    $c=array();
    while ($db->next_record()) {
      $c[]=array("name"=>substr($db->f("name"),strpos($db->f("name"),"_")+1));
    }

    return $c;
  }

  function add_user($usern,$password,$passconf) {
    global $db,$err,$quota,$mem,$cuid;
    $err->log("mysql","add_user",$usern);

    $user=addslashes($mem->user["login"]."_$usern");
    $pass=addslashes($password);

    if (!$quota->cancreate("mysql_users")) {
      $err->raise("mysql",13);
      return false;
    }
    if (!ereg("^[0-9a-z]",$usern)) {
      $err->raise("mysql",14);
      return false;
    }

    if (strlen($user) > 16 || strlen($usern) == 0 ) {
      $err->raise("mysql",15);
      return false;
    }
    $db->query("SELECT * FROM dbusers WHERE name='$user';");
    if ($db->num_rows()) {
      $err->raise("mysql",16);
      return false;
    }
    if ($password != $passconf || !$password) {
      $err->raise("mysql",17);
      return false;
    }


    // On cr�� l'utilisateur
    $db->query("GRANT USAGE ON *.* TO '$user'@'$this->client' IDENTIFIED BY '$pass';");
    // On le rajoute dans la table des utilisateurs
    $db->query("INSERT INTO dbusers (uid,name) VALUES($cuid,'$user');");
    return true;
  }

  function del_user($user) {
    global $db,$err,$mem,$cuid,$L_MYSQL_DATABASE;
    $err->log("mysql","del_user",$user);
    if (!ereg("^[0-9a-z]",$user)) {
      $err->raise("mysql",14);
      return false;
    }
    $db->query("SELECT name FROM dbusers WHERE name='".$mem->user["login"]."_$user';");
    if (!$db->num_rows()) {
      $err->raise("mysql",18);
      return false;
    }
    $db->next_record();
    $login=$db->f("name");

    // Ok, database exists and dbname is compliant. Let's proceed
    $db->query("REVOKE ALL PRIVILEGES ON *.* FROM '".$mem->user["login"]."_$user'@'$this->client';");
    $db->query("DELETE FROM mysql.db WHERE User='".$mem->user["login"]."_$user' AND Host='$this->client';");
    $db->query("DELETE FROM mysql.user WHERE User='".$mem->user["login"]."_$user' AND Host='$this->client';");
    $db->query("FLUSH PRIVILEGES");
    $db->query("DELETE FROM dbusers WHERE uid='$cuid' AND name='".$mem->user["login"]."_$user';");
    return true;
  }

  function get_user_dblist($user) {
    global $db,$err,$mem,$cuid,$L_MYSQL_DATABASE;
    $err->log("mysql","get_user_dblist");

    $r=array();
    $dblist=$this->get_dblist();

    for ( $i=0 ; $i<count($dblist) ; $i++ ) {
      $db->query("SELECT Db, Select_priv, Insert_priv, Update_priv, Delete_priv, Create_priv, Drop_priv, References_priv, Index_priv, Alter_priv, Create_tmp_table_priv, Lock_tables_priv FROM mysql.db WHERE User='".$mem->user["login"].($user?"_":"").$user."' AND Host='$this->client' AND Db='".$dblist[$i]["db"]."';");
      if ($db->next_record())
        $r[]=array("db"=>$dblist[$i]["name"], "select"=>$db->f("Select_priv"), "insert"=>$db->f("Insert_priv"),	"update"=>$db->f("Update_priv"), "delete"=>$db->f("Delete_priv"), "create"=>$db->f("Create_priv"), "drop"=>$db->f("Drop_priv"), "references"=>$db->f("References_priv"), "index"=>$db->f("Index_priv"), "alter"=>$db->f("Alter_priv"), "create_tmp"=>$db->f("Create_tmp_table_priv"), "lock"=>$db->f("Lock_tables_priv"));
      else
        $r[]=array("db"=>$dblist[$i]["name"], "select"=>"N", "insert"=>"N", "update"=>"N", "delete"=>"N", "create"=>"N", "drop"=>"N", "references"=>"N", "index"=>"N", "alter"=>"N", "create_tmp"=>"N", "lock"=>"N" );
    }

    return $r;
  }

  function set_user_rights($user,$dbn,$rights) {
    global $mem, $db;
    $strrights = "";
    $usern=addslashes($mem->user["login"].($user?"_":"").$user);
    $dbname=addslashes($mem->user["login"].($dbn?"_":"").$dbn);
    // On g�n�re les droits en fonction du tableau de droits
    for( $i=0 ; $i<count($rights) ; $i++ ) {
      switch ($rights[$i]) {
        case "select":
          $strrights.="SELECT,";
          break;
        case "insert":
          $strrights.="INSERT,";
          break;
        case "update":
          $strrights.="UPDATE,";
          break;
        case "delete":
          $strrights.="DELETE,";
          break;
        case "create":
          $strrights.="CREATE,";
          break;
        case "drop":
          $strrights.="DROP,";
          break;
        case "references":
          $strrights.="REFERENCES,";
          break;
        case "index":
          $strrights.="INDEX,";
          break;
        case "alter":
          $strrights.="ALTER,";
          break;
        case "create_tmp":
          $strrights.="CREATE TEMPORARY TABLES,";
          break;
        case "lock":
          $strrights.="LOCK TABLES,";
          break;
      }
    }


    // On remet � z�ro tous les droits de l'utilisateur
    $db->query("SELECT * FROM mysql.db WHERE User = '$usern' AND Db = '$dbname';");
    if($db->num_rows())
      $db->query("REVOKE ALL PRIVILEGES ON $dbname.* FROM '$usern'@'$this->client';");
    if( $strrights ){
      $strrights=substr($strrights,0,strlen($strrights)-1);
      $db->query("GRANT $strrights ON $dbname.* TO '$usern'@'$this->client';");
    }
    $db->query("FLUSH PRIVILEGES");
    return TRUE;
  }

} /* Class m_mysql */

?>
