<?php
/*
 $Id: m_sympa.php,v 1.2 2003/04/17 21:39:56 benjamin Exp $
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
 Original Author of file: Benjamin, Franck, Sylvain
 Purpose of file: Manage mailing-lists with Sympa
 ----------------------------------------------------------------------
*/
/**
* Classe de gestion des listes de discussion de l'h�berg�.
*
* Cette classe permet de g�rer les listes de discussion
* et de diffusion sous SYMPA d'un membre h�berg�.<br>
* Copyleft {@link http://alternc.net/ AlternC Team}
*
* @copyright    AlternC-Team 2002-11-01 http://alternc.net/
*
*/
class m_sympa {

  /** Membre dont on g�re les domaines */
  var $uid=0;

  var $alternc_quota_name="sympa";

  /** Racine du r�pertoire contenant les configs des listes
   * @access private
   */
  var $LST_ROOT="/var/lib/sympa/expl";

  /** Racine du r�pertoire contenant les virtuals bots des listes
   * @access private
   */
  var $LST_ROBOT="/etc/sympa";
  /** Langue par d�faut pour les listes sympa (TOSO : rendre configurable)
   * @access private
   */
  var $lang="fr";

  /** Liste des patrons de mails auto-envoy�s
   * @access private
   */
  var $textfiles=
    array(
	  "welcome.tpl"=> "Welcome message (sent when someone subscribes to the list)",
	  "bye.tpl"=>     "Unsubscribe message (sent when someone unsubscribes to the list)",
	  "removed.tpl"=> "Owner-unsubscribe message (sent when the owner manually unsubscribes somone)",
	  "reject.tpl"=>  "Reject message (sent when a message received on the list is refused)",
	  "message.header" => "Header added to all messages sent to the list",
	  "message.footer" => "Footer added to all messages sent to the list"
	  );
  
  /** Liste des modes de demande de liste d'abonn�s
   * @access private
   */
  var $review_mode=
    array(
	  "closed" => "Nobody can get the subscribed users list",
	  "owner" => "Only list owner can get the subscribed users list",
	  "private" => "Only subscribed users can get the subscribed users list",
	  "public" => "Anybody can get the subscribed users list"
	  );
  
  /** Liste des modes d'inscription
   * @access private
   */
  var $subscribe_mode=
    array(
	  "owner" =>         "Subscription must be approved by a list owner",
	  "open" =>          "Subscription is open to everybody by mail",
	  "open_notify" =>   "Subscription is open, but notify the owners" ,
	  "open_quiet" =>    "Subscription is open, but don't send welcome message" ,
	  "closed" =>        "No mail subscription is allowed",
	  );
  
  /** Liste des modes de d�sinscription
   * @access private
   */
  var $unsubscribe_mode=
    array(
	  "owner" =>       "Unsubscription must be allowed by one owner",
	  "open" =>        "Unsubscription by mail is open",
	  "open_notify" => "Unsubscription by mail is open, but notify the owners",
	  "closed" =>      "Unsubscription by mail is impossible",
	  );
  
  /** Liste des modes d'envoi possibles
   * @access private
   */
  var $send_mode=
    array(
	  "closed" =>               "List closed, no mail can be sent",
	  "editorkey" =>            "Moderated list, the owner accept / refuse the mails",
	  "newsletter" =>           "Mailing-list, owner only can post",
	  "newsletterkeyonly" =>    "Moderated Mailing-list : owner only can post, and they are moderated",
	  "private" =>              "Private list: only the subscribers can post",
	  "privateandeditorkey" =>  "Moderated list, and only the subscribers can post",
	  "public" =>               "Public list, everybody can post and no moderation",
	  "public_nobcc" =>         "Public list, BCC messages are forbidden (limit the spams)",
	  "publicnoattachment" =>   "Public list, mail attachment are moderated by the owners",
	  "publicnomultipart" =>    "Public list, complex messages are rejected",
	  );
  
  /** Liste des reply-to possibles
   * @access private
   */
  var $reply_to=
    array(
	  "sender" =>       "to the sender",
	  "list" =>         "to the list",
	  "all" =>          "to both sender and list",
	  "other_email" =>  "to another email, as follow...",
	  );
  
  /** Liste des tailles de message maxi possibles
   * @access private
   */
  var $msizes=
    array(
	  2048, 4096, 10240, 20480, 51200, 102400,
	  1048576, 2097152, 4194304, 5242880,10485760);
  
  /** Liste des textes des tailles de messages possibles
   * @access private
   */
  var $tsizes=
    array(
	  "2Ko","4Ko","10Ko","20Ko","50Ko","100Ko",
	  "1Mo","2Mo","4Mo","5Mo","10Mo");
  
  /** Param�tres de connexion LDAP
   * @access private
   */
  var $ldap=
    array(
	  "server"        =>      "",	  "binddn"        =>      "",
	  "bindpwd"       =>      "",	  "basedn"        =>      ""
	  );
  
  /** Handle de connexion LDAP
   * @access private
   */
  var $ds=-1;

  /** Nombre maximum de mails � afficher par page.
   * TODO : Transf�rer cette liste dans la config ?
   */
  var $nombre=15;

  /* ----------------------------------------------------------------- */
  /**
   * Constructeur
   */
  function m_sympa($membre=0) {
    global $L_LDAP_HOST,$L_LDAP_ROOT,$L_LDAP_ROOTPWD,$L_LDAP_POSTFIX;
    $this->uid=$membre;
    $this->ldap=
      array(
	    "server"	=>	$L_LDAP_HOST,	    "binddn"	=>	$L_LDAP_ROOT,
	    "bindpwd"	=>	$L_LDAP_ROOTPWD,    "basedn"	=>	$L_LDAP_POSTFIX
	    );
  }

  /* ----------------------------------------------------------------- */
  /** Retourne la liste des listes de discussions appartenent au membre courant.
   * @return array Tableau contenant la liste des listes sous forme de tableau
   *  index� de tableaux associatifs sous la forme $a["list"]=email de la liste
   *  et $a["id"] = num�ro de la liste. retourne FALSE si une erreur est survenue.
   */
  function enum_ml() {
    global $err,$db;
    $err->log("sympa","enum_ml");
    $db->query("SELECT list,id FROM ml WHERE uid=".$this->uid.";");
    if (!$db->num_rows()) {
      $err->raise("sympa",1);
      return false;
    }
    $mls=array();
    while ($db->next_record()) {
      $mls[]=$db->Record;
    }
    return $mls;
  }

  /* ----------------------------------------------------------------- */
  /** Retourne la liste des membres d'une liste. un maximum de mails est retourn�
   * @param integer $id Num�ro de la liste dont on souhaite obtenir les membres
   * @param integer $offset Num�ro du premier membre � retourner
   * @return array Tableau index� de tableaux associatifs contenant la liste
   *  des membres de la liste sous la forme : $a["mail"]=adresse mail du membre
   *  et $a["nom"]= Nom du membre (facultatif), retourne FALSE si une erreur
   *  est survenur
   */
  function get_ml_users($id,$offset) {
    global $err,$db;
    $err->log("sympa","get_ml_users",$id);
    $users["count"]=$this->count_users($id); // compteur qui servira � l'affichage des r�sultats
    if ($users["count"]==0) {
      $err->raise("sympa",15);
      return false;
    }
    $users["affiche"]=$this->nombre; // stockage du nombre de mail � afficher
    // Consultation de la table subscriber_table
    $r=$this->get_ml($id);
    $li=$this->listname($r);
    $db->query("SELECT subscriber_table.user_subscriber,user_table.gecos_user FROM subscriber_table,user_table 
              WHERE subscriber_table.list_subscriber='$li' 
              AND subscriber_table.user_subscriber=user_table.email_user 
              ORDER BY subscriber_table.user_subscriber ASC LIMIT $offset,$this->nombre;");
    $i=0;
    while ($db->next_record()) {
      $users["mail"][$i]=$db->f("user_subscriber");
      $users["nom"][$i]=$db->f("gecos_user");
      $i++;
    }
    $db->free();
    return $users;
  }

  /* ----------------------------------------------------------------- */
  /** Affiche (echo) les champs select de taille de message maxi
   * @param integer $max_size Taille actuelle maxi des messages
   */
  function size_list($max_size="") {
    for($i=0;$i<count($this->msizes);$i++) {
      echo "<option value=\"".$this->msizes[$i]."\"";
      if ($max_size==$this->msizes[$i]) echo " SELECTED";
      echo ">".$this->tsizes[$i]."</option>\n";
    }
  }

  /* ----------------------------------------------------------------- */
  /** Retourne TOUS les membres de la liste demand�e (voir get_ml_users)
   * @param integer $id Num�ro de la liste dont on souhaite obtenir la liste des
   *  membres
   * @return array tableau mixte (voir get_ml_users) ou FALSE si une erreur s'est
   *  produite.
   */
  function get_ml_all_users($id) {
    global $err,$db;
    $err->log("sympa","get_ml_all_users",$id);
    $users["count"]=$this->count_users($id); // compteur qui servira � l'affichage des r�sultats
    if ($users["count"]==0) {
      $err->raise("sympa",15);
      return false;
    }
    // Consultation de la table subscriber_table
    $r=$this->get_ml($id);
    $li=$this->listname($r);
    $db->query("SELECT subscriber_table.user_subscriber,user_table.gecos_user FROM subscriber_table,user_table 
          WHERE subscriber_table.list_subscriber='$li' 
          AND subscriber_table.user_subscriber=user_table.email_user 
          ORDER BY subscriber_table.user_subscriber ASC;");
    $i=0;
    while ($db->next_record()) {
      $users["mail"][$i]=$db->f("user_subscriber");
      $users["nom"][$i]=$db->f("gecos_user");
      $i++;
    }
    $db->free();
    return $users;
  }

  /* ----------------------------------------------------------------- */
  /** Retourne le nombre de membres d'une liste.
   * @param integer $id Num�ro de la liste
   * @return integer nombre de membres de la liste.
   */
  function count_users($id) {
    global $err,$db;
    $err->log("sympa","count_users",$id);
    $r=$this->get_ml($id);
    $li=$this->listname($r);
    $db->query("SELECT count(user_subscriber) AS cnt FROM subscriber_table WHERE list_subscriber='$li'");
    $db->next_record();
    $count=$db->Record["cnt"];
    return $count;
  }

  /* ----------------------------------------------------------------- */
  /** Ajoute un membre (inscrit) � une liste.
   * @param integer $id Num�ro de la liste � laquelle on souhaite ajouter un membre
   * @param string $mail Email du nouveau membre
   * @param string $nom Nom (facultatif) du nouveau membre
   * @return boolean TRUE si le membre a �t� ajout� avec succ�s, FALSE sinon.
   */
  function add_user($id,$mail,$nom="") {
    global $err,$db;
    $err->log("sympa","add_user",$mail." - ".$nom." - ".$id);
    // V�rification du mail
    if (checkmail($mail)) {
      $err->raise("sympa",7);
      return false;
    }
    // V�rification si pr�sence du mail dans user_table
    $db->query("SELECT email_user FROM user_table WHERE email_user='$mail';");
    if (!$db->num_rows()) {
      $pass=$this->sympa_pwd();
      $db->query("INSERT INTO user_table(email_user,gecos_user,password_user,cookie_delay_user,lang_user) 
            VALUES('$mail','$nom','$pass',NULL,'$this->lang');");
    }
    // v�rification si pr�sence dans subscriber_table
    // on r�cup�re le nom de la liste qui va bien
    $r=$this->get_ml($id);
    $li=$this->listname($r);
    $db->query("SELECT user_subscriber,list_subscriber FROM subscriber_table WHERE user_subscriber='$mail' 
             AND list_subscriber='$li';");
    if ($db->num_rows()) {
      $err->raise("sympa",16);
      return false;
    } else {
      // On rentre tout �a dans subscriber_table
      $db->query("INSERT INTO subscriber_table (list_subscriber,user_subscriber,date_subscriber,update_subscriber,
            visibility_subscriber,reception_subscriber,bounce_subscriber,comment_subscriber) 
            VALUES('$li','$mail',NOW(),NOW(),'noconceal','mail',NULL,NULL);");
    }
    return true;
  } // fin add_user

  /* ----------------------------------------------------------------- */
  /** Ajoute plusieurs membres � une liste existante.
   * TODO : permettre d'ajouter des membres et ne pas s'arr�ter au premier �chec.
   * Le nom ne peut donc pas �tre sp�cifi�.
   * @param integer $id num�ro de la liste � laquelle on doit ajouter des membres
   * @param string $list Liste des mails, un mail par ligne
   * @return boolean TRUE si les membres ont �t� ajout�s avec succ�s, FALSE sinon.
   */
  function add_user_multiple($id,$list) {
    global $err,$db;
    $err->log("sympa","add_user_multiple",$id);
    if ($list) {
      // r�cup�ration des emails dans un tableau
      $liste=explode("\n",$list);
      if (count($liste)>0) {
	reset($liste);
	// parcours du tableau et on v�rifie la validit� de tous les mails avant de les inscrires
	for($i=0;$i<count($liste);$i++) {
				// on ajoute l'email en le filtrant
	  $mail[$i]=strtolower(trim($liste[$i]));
	  if ($mail[$i]) { // skip empty lines
	    //V�rification du mail
	    if (checkmail($mail[$i])) {
	      $err->raise("sympa",18);
	      return false;
	    }
	  }
	} // fin boucle de v�rification de validit� des mails
	$r=$this->get_ml($id);
	$li=$this->listname($r);
	for($i=0;$i<count($mail);$i++) {
	  if ($mail[$i]) { // skip empty lines
	    // V�rification si pr�sence du mail dans user_table
	    $db->query("SELECT * FROM user_table WHERE email_user='$mail[$i]';");
	    if (!$db->num_rows()>0) {
	      $pass=$this-sympa_pwd();
	      $db->query("INSERT INTO user_table(email_user,gecos_user,password_user,cookie_delay_user,lang_user) 
                     VALUES('$mail[$i]','$nom','$pass',NULL,'$this->lang');");
	    }
	    // v�rification si pr�sence dans subscriber_table
	    $db->query("SELECT * FROM subscriber_table WHERE user_subscriber='$mail[$i]' AND list_subscriber='$li';");
	    if ($db->num_rows()>0) {
	      $err->raise("sympa",19);
	    } else {
	      // On rentre tout �a dans subscriber_table
	      $db->query("INSERT INTO subscriber_table (list_subscriber,user_subscriber,date_subscriber,
                      update_subscriber,visibility_subscriber,reception_subscriber,bounce_subscriber,comment_subscriber) 
                      VALUES('$li','$mail[$i]',NOW(),NOW(),'noconceal','mail',NULL,NULL);");
	    }
	  }
	} // fin for
	return true;
      } // fin if
    } else {
      $err->raise("sympa",17);
      return false;
    }
  } // fin add_user_multiple

  /* ----------------------------------------------------------------- */
  /** Modifie un membre de liste.
   * TODO : V�rifier l'utilisation et l'utilit� de cette fonction ?
   * @param integer $id Num�ro de la liste dont on souhaite modifier un membre
   * @param string $name Nom de la personne � modifier
   * @param string $mail Nouveau Mail de la personne � modifier
   * @param string $old_mail Ancien mail de la personne � modifier
   * @return boolean TRUE si la personne a bien �t� modifi�e, FALSE sinon.
   */
  function edit_user($id,$name,$mail,$old_mail) {
    // A voir si utile car probl�me pour une personne inscrite � plusieurs listes
    global $err,$db;
    $err->log("sympa","edit_user",$mail." - ".$old_mail." - ".$id);
    // V�rification du mail
    if (checkmail($mail)) {
      $err->raise("sympa",7);
      return false;
    }
    // on r�cup�re le nom de la liste
    $r=$this->get_ml($id);
    $li=$this->listname($r);
    // on v�rifie si le mail est utilis� dans une autre liste de diffusion
    $db->query("SELECT user_subscriber,list_subscriber FROM subscriber_table WHERE user_subscriber='$old_mail' AND list_subscriber!='$li';");
    if ($db->num_rows()>0 && $mail!=$old_mail) {
      // le mail est inscrit dans une autre liste de discussion
      // on change les param�tres dans subscriber_table
      $db->query("UPDATE subscriber_table SET user_subscriber='$mail' WHERE list_subscriber='$li' AND user_subscriber='$old_mail';");
      // on v�rifie si le nouveau mail de l'inscrit n'est pas d�j� pr�sent dans user_table
      $db->query("SELECT email_user FROM user_table WHERE email_user='$mail';");
      if ($db->num_rows()==0) {
	/* on r�cr�� un nouveau compte dans user_table avec les nouveaux param�tres avec tout d'abord une
	   r�cup�ration du mot de passe. */
	$db->query("SELECT password_user FROM user_table WHERE email_user='$old_mail';");
	$db->next_record();
	$pass=$db->f("password_user");
	$db->query("INSERT INTO user_table(email_user,gecos_user,password_user,cookie_delay_user,lang_user) VALUES('$mail','$nom','$pass',NULL,'$this->lang');");
      }
    } else {
      // le mail n'est pas inscrit ailleur
      // on change les param�tres dans user_table
      $db->query("UPDATE user_table SET email_user='$mail',gecos_user='$name' WHERE email_user='$old_mail';");
      // on change les param�tres dans subscriber_table
      $db->query("UPDATE subscriber_table SET user_subscriber='$mail' WHERE list_subscriber='$li' AND user_subscriber='$old_mail';");
    }
    return true;
  } // fin edit_user

  /* ----------------------------------------------------------------- */
  /** Efface (d�sinscrit) un membre d'une liste de discussion.
   * @param integer $id Num�ro de liste dont on souhaite d�sinscrire un membre
   * @param string $mail Mail du membre � d�sinscrire.
   * @return boolean TRUE si le membre a �t� d�sinscrit avec succ�s, FALSE sinon.
   */
  function del_user($id,$mail) {
    global $err,$db;
    $err->log("sympa","del_user",$mail." - ".$id);
    // on r�cup�re le nom de la liste
    $r=$this->get_ml($id);
    $li=$this->listname($r);
    // supression de la personne dans subscriber_table.
    $db->query("DELETE FROM subscriber_table WHERE list_subscriber='$li' AND user_subscriber='$mail';");
    // On v�rifie si la personne est inscrite ailleur
    $db->query("SELECT user_subscriber,list_subscriber FROM subscriber_table WHERE user_subscriber='$mail' AND list_subscriber!='$li';");
    if ($db->num_rows()==0) {
      // Si la personne n'est plus inscrite � aucunes listes, alors, on l'efface de user_table
      $db->query("DELETE FROM user_table WHERE email_user='$mail';");
    }
    return true;
  }

  /* ----------------------------------------------------------------- */
  /** Ajoute un propri�taire/mod�rateur � une liste.
   * @param string $mail Email de propri�taire
   * @param integer $id Num�ro de la liste � laquelle on ajoute un propri�taire
   * @return boolean TRUE si le propri�taire a �t� ajout� avec succ�s, FALSE sinon.
   */
  function add_owner($mail,$id) {
    global $err;
    $err->log("sympa","add_owner",$mail." - ".$id);
    // V�rification du mail
    if (checkmail($mail)) {
      $err->raise("sympa",7);
      return false;
    }
    $r=$this->get_ml($id);
    // comparaison si le mail est d�j� pr�sent dans la liste des "owner"
    for($i=0;$i<$r["owner"]["count"];$i++) {
      $c=$r["owner"][$i];
      if ($c==$mail) {
	$err->raise("sympa",12);
	return false;
      }
    }
    // r�cup�ration du nom de r�pertoire de la liste
    $RDIR=$this->path_ml($id);
    // lecture conf et $tmpfname (fichier temporaire pour la copie)
    unset($file);
    exec("/usr/lib/alternc/lst_getfile ".escapeshellarg($RDIR)." config",$file);
    $tmpfname = tempnam ("/tmp", "sympa");
    $newf = fopen($tmpfname,"wb");
    for($i=0;$i<count($file);$i++) {
      fwrite($newf, $file[$i]."\n");
    } // fin while
    fwrite($newf,"owner"."\n"."email ".$mail."\n\n"."editor"."\n"."email ".$mail."\n\n");
    fclose($newf);
    // Ecrasement de config
    chmod($tmpfname,0777);
    exec("/usr/lib/alternc/lst_putfile ".escapeshellarg($RDIR)." 'config' ".escapeshellarg($tmpfname));
    unlink($tmpfname);
    return true;
  } // fin add_owner

  /* ----------------------------------------------------------------- */
  /** Enlever un propri�taire d'une liste de discussion.
   * @param string $suppr Email � supprimer
   * @param integer $id Num�ro de liste concern�e.
   * @return boolen TRUE si le propri�taire a �t� enlev� avec succ�s, FALSE sinon.
   */
  function del_owner($suppr,$id) {
    global $err;
    $err->log("sympa","del_owner",$id);
    $r=$this->get_ml($id);
    if(count($suppr)==$r["owner"]["count"]) {
      $err->raise("sympa",13);
      return false;
    }
    // r�cup�ration du nom de r�pertoire de la liste
    $RDIR=$this->path_ml($id);
    // lecture conf et $tmpfname (fichier temporaire pour la copie)
    unset($file);
    exec("/usr/lib/alternc/lst_getfile ".escapeshellarg($RDIR)." config",$file);
    $tmpfname = tempnam ("/tmp", "sympa");
    $newf = fopen($tmpfname,"wb");
    for($i=0;$i<count($file);$i++) {
      // On enleve les owner voulus.
      if ($file[$i]=="owner" || $file[$i]=="editor") {
	if (!in_array(trim(substr($file[$i+1],6)),$suppr)) {
	  fwrite($newf, $file[$i]."\n");
	} else {
				// Supprimer => on passe 3 lignes (donc 2)
	  $i+=2;
	}
      } else {
	fwrite($newf, $file[$i]."\n");
      }
    } // fin while
    fclose($newf);
    // Ecrasement de config
    chmod($tmpfname,0777);
    exec("/usr/lib/alternc/lst_putfile ".escapeshellarg($RDIR)." config ".escapeshellarg($tmpfname));
    unlink($tmpfname);
    return true;
  }

  /* ----------------------------------------------------------------- */
  /**
   *
   */
  function edit_ml($id,$sub,$unsub,$send,$reply,$mail,$addsubject,$max_size,$review) {
    global $err;
    $err->log("sympa","edit_ml",$id);
    // v�rification s'il y a un mail si l'option reply_to est plac� sur other_email
    // et si l'email est valable
    if ($reply=="other_email") {
      if (checkmail($mail)) {
	$err->raise("sympa",14);
	return false;
      }
    }
    $r=$this->get_ml($id);
    $RDIR=$this->path_ml($id);
    // lecture conf et $tmpfname (fichier temporaire pour la copie)
    $tmpfname = tempnam ("/tmp", "sympa");
    $newf = fopen($tmpfname,"wb");
    // on recr�e le fichier config dans config.new avec les infos dont on a besoin
    fputs($newf,"host ".$r["host"]."\n\n");
    fputs($newf,"lang ".$r["lang"]."\n\n");
    // on fait une boucle pour remettre tous les propri�taires/mod�rateurs
    for($i=0;$i<$r["owner"]["count"];$i++) {
      fputs($newf,"owner"."\n"."email ".$r["owner"][$i]."\n\n"."editor"."\n"."email ".$r["owner"][$i]."\n\n");
    }
    if (trim($addsubject)) {
      fputs($newf,"custom_subject ".trim($addsubject)."\n\n");
    }
    fputs($newf,"max_size ".$max_size."\n\n");
    fputs($newf,"visibility ".$r["visibility"]."\n\n");
    fputs($newf,"subscribe ".$sub."\n\n");
    fputs($newf,"unsubscribe ".$unsub."\n\n");
    fputs($newf,"send ".$send."\n\n");
    fputs($newf,"review ".$review."\n\n");
    fputs($newf,"reply_to_header"."\n"."value ".$reply."\n");
    if ($reply=="other_email") // param�tre non obligatoire dans config
      fputs($newf,"other_email ".$mail."\n");
    fputs($newf,"\n");
    fclose($newf);
    // Ecrasement de config
    chmod($tmpfname,0777);
    exec("/usr/lib/alternc/lst_putfile ".escapeshellarg($RDIR)." config ".escapeshellarg($tmpfname));
    unlink($tmpfname);
    return true;
  }

  /* ----------------------------------------------------------------- */
  /**
   *
   */
  function get_ml($id) {
    global $err,$db;
    $err->log("sympa","get_ml",$id);
    $db->query("SELECT list,premier FROM ml WHERE id=$id AND uid=".$this->uid.";");
    if (!$db->num_rows()) {
      $err->raise("sympa",9);
      return false;
    }
    // the list exists
    $db->next_record();
    $ml=array("list"=>$db->Record["list"],"premier"=>$db->Record["premier"]);
    $o=0;
    // Read and parse a config file
    // r�cup�ration du nom de r�pertoire de la liste
    $RDIR=$this->path_ml($id);
    // lecture conf et $tmpfname (fichier temporaire pour la copie)
    unset($file);
    exec("/usr/lib/alternc/lst_getfile ".escapeshellarg($RDIR)." config",$file);
    for($i=0;$i<count($file);$i++) {
      $s=trim($file[$i]);
      if (substr($s,0,5)=="owner") {
	$i++;
	$s=trim($file[$i]);
	while ($s) {
	  if (substr($s,0,6)=="email ") {
	    $ml["owner"][$o]=substr($s,6,strlen($s)-6);
	    $o++;
	  }
	  $i++;
	  $s=trim($file[$i]);
	}
      } // owner
      if (substr($s,0,5)=="host ") {
	$ml["host"]=substr($s,5);
      }
      if (substr($s,0,9)=="max_size ") {
	$ml["max_size"]=doubleval(substr($s,9));
      }
      if (substr($s,0,5)=="lang ") {
	$ml["lang"]=substr($s,5);
      } //lang
      if (substr($s,0,15)=="custom_subject ") {
	$ml["addsubject"]=substr($s,15);
      } //custom_subject
      if (substr($s,0,11)=="visibility ") {
	$ml["visibility"]=substr($s,11);
      }
      if (substr($s,0,10)=="subscribe ") {
	$ml["subscribe"]=substr($s,10);
      } //subscribe
      if (substr($s,0,7)=="review ") {
	$ml["review"]=substr($s,7);
      } //review
      if (substr($s,0,12)=="unsubscribe ") {
	$ml["unsubscribe"]=substr($s,12);
      } //unsubscribe
      if (substr($s,0,5)=="send ") {
	$ml["send"]=substr($s,5);
      } //send
      if (substr($s,0,6)=="value ") {
	$ml["reply_to"]=substr($s,6);
      } //reply_to
      if (substr($s,0,12)=="other_email ") {
	$ml["other_email"]=substr($s,12);
      } //send
    } // fin for
    $ml["owner"]["count"]=$o;
    return $ml;
  }

  /* ----------------------------------------------------------------- */
  /**
   *
   */
  function prefix_list() {
    global $db,$err;
    $r=array();
    $db->query("SELECT domaine FROM domaines WHERE compte=".$this->uid." ORDER BY domaine;");
    while ($db->next_record()) {
      $r[]=$db->f("domaine");
    }
    return $r;
  }

  /* ----------------------------------------------------------------- */
  /**
   *
   */
  function select_prefix_list($current) {
    global $db,$err;
    $r=$this->prefix_list();
    reset($r);
    while (list($key,$val)=each($r)) {
      if ($current==$val) $c=" selected"; else $c="";
      echo "<option$c>$val</option>";
    }
    return true;
  }

  /* ----------------------------------------------------------------- */
  /**
   *
   */
  function add_lst($domain,$login,$owner) {
    global $db,$err,$quota,$mail;
    $err->log("sympa","add_lst",$login."@".$domain." - ".$owner);
    //connexion ldap
    if (!$this->_connectldap()) {
      $err->raise("sympa",1);
      return false;
    }
    if ($login=="") {
      $err->raise("sympa",5);
      return false;
    }
    if (!$owner) {
      $err->raise("sympa",8);
      return false;
    }
    if (checkmail($owner)) {
      $err->raise("sympa",7);
      return false;
    }
    $r=$this->prefix_list();
    if (!in_array($domain,$r) || $domain=="") {
      $err->raise("sympa",2);
      return false;
    }
    // Prefixe OK, on verifie la non-existence de login@prefixe :
    $t=$mail->get_mail_details($login."@".$domain);
    if (is_array($t)) {
      // This is a mail account already !!!
      $err->raise("sympa",3);
      return false;
    }
    $db->query("SELECT * FROM ml WHERE list='$login@$domain';");
    if ($db->num_rows()) {
      $err->raise("sympa",4);
      return false;
    }
    // Le compte n'existe pas, on v�rifie le quota et on le cr��.
    if ($quota->cancreate("sympa")) {
      $quota->inc("sympa"); // incr�mentation du quota
      // v�rification si le login de la liste est d�j� utilis�e
      if ($this->first_ml($login)) {
	$db->query("INSERT INTO ml (uid,list,premier) VALUES (".$this->uid.",'$login@$domain','1')");
	$premier=1;
	$aldom="";
      } else {
	$db->query("INSERT INTO ml (uid,list,premier) VALUES (".$this->uid.",'$login@$domain','0')");
	$premier=0;
	$aldom="_$domain";
      }
    } else {
      $err->raise("sympa",6);
      return false;
    }
    // Creation des entr�es LDAP qui vont bien ...
    $res=array("mail" => $login."@".$domain, "uid" => $this->uid, "type" => "ml", "objectclass" => "mail", "pop" => "0", "account"=>$login."_".$domain );
    if (!ldap_add($this->ds,"mail=".$res["mail"].",dc=domains,".$this->ldap["basedn"],$res)) {
      $err->raise("sympa",11,ldap_error($this->ds));
      return false;
    }
    $res["mail"]=$login."-request@".$domain;	$res["account"]=$login."-request_".$domain;
    if (!ldap_add($this->ds,"mail=".$res["mail"].",dc=domains,".$this->ldap["basedn"],$res)) {
      $err->raise("sympa",11,ldap_error($this->ds));
      return false;
    }
    $res["mail"]=$login."-editor@".$domain;	$res["account"]=$login."-editor_".$domain;
    if (!ldap_add($this->ds,"mail=".$res["mail"].",dc=domains,".$this->ldap["basedn"],$res)) {
      $err->raise("sympa",11,ldap_error($this->ds));
      return false;
    }
    $res["mail"]=$login."-owner@".$domain;	$res["account"]=$login."-owner_".$domain;
    if (!ldap_add($this->ds,"mail=".$res["mail"].",dc=domains,".$this->ldap["basedn"],$res)) {
      $err->raise("sympa",11,ldap_error($this->ds));
      return false;
    }
    $res["mail"]=$login."-subscribe@".$domain;	$res["account"]=$login."-subscribe_".$domain;
    if (!ldap_add($this->ds,"mail=".$res["mail"].",dc=domains,".$this->ldap["basedn"],$res)) {
      $err->raise("sympa",11,ldap_error($this->ds));
      return false;
    }
    $res["mail"]=$login."-unsubscribe@".$domain;	$res["account"]=$login."-unsubscribe_".$domain;
    if (!ldap_add($this->ds,"mail=".$res["mail"].",dc=domains,".$this->ldap["basedn"],$res)) {
      $err->raise("sympa",11,ldap_error($this->ds));
      return false;
    }
    // Ajout des entr�es LDAP dans postfix_aliases :
    $res=array("mail" => $login."_".$domain, "objectclass" => "alias", "alias"=>"\"|/usr/lib/sympa/bin/queue ".$login.$aldom."@".$domain."\"");
    if (!ldap_add($this->ds,"mail=".$res["mail"].",dc=aliases,".$this->ldap["basedn"],$res)) {
      $err->raise("sympa",11,ldap_error($this->ds));
      return false;
    }
    $res=array("mail" => $login."-request_".$domain, "objectclass" => "alias", "alias"=>"\"|/usr/lib/sympa/bin/queue ".$login.$aldom."-request@$domain\"");
    if (!ldap_add($this->ds,"mail=".$res["mail"].",dc=aliases,".$this->ldap["basedn"],$res)) {
      $err->raise("sympa",11,ldap_error($this->ds));
      return false;
    }
    $res=array("mail" => $login."-editor_".$domain, "objectclass" => "alias", "alias"=>"\"|/usr/lib/sympa/bin/queue ".$login.$aldom."-editor@$domain\"");
    if (!ldap_add($this->ds,"mail=".$res["mail"].",dc=aliases,".$this->ldap["basedn"],$res)) {
      $err->raise("sympa",11,ldap_error($this->ds));
      return false;
    }
    $res=array("mail" => $login."-unsubscribe_".$domain, "objectclass" => "alias", "alias"=>"\"|/usr/lib/sympa/bin/queue ".$login.$aldom."-unsubscribe@$domain\"");
    if (!ldap_add($this->ds,"mail=".$res["mail"].",dc=aliases,".$this->ldap["basedn"],$res)) {
      $err->raise("sympa",11,ldap_error($this->ds));
      return false;
    }
    $res=array("mail" => $login."-subscribe_".$domain, "objectclass" => "alias", "alias"=>"\"|/usr/lib/sympa/bin/queue ".$login.$aldom."-subscribe@$domain\"");
    if (!ldap_add($this->ds,"mail=".$res["mail"].",dc=aliases,".$this->ldap["basedn"],$res)) {
      $err->raise("sympa",11,ldap_error($this->ds));
      return false;
    }
    $res=array("mail" => $login."-owner_".$domain, "objectclass" => "alias", "alias"=>"\"|/usr/lib/sympa/bin/bouncequeue ".$login.$aldom."@".$domain."\"");
    if (!ldap_add($this->ds,"mail=".$res["mail"].",dc=aliases,".$this->ldap["basedn"],$res)) {
      $err->raise("sympa",11,ldap_error($this->ds));
      return false;
    }
    // Ok, la liste est cr��e, on cree le dossier qui va bien :
    // On test si le nom de la liste n'est pas d�j� utilis� ailleurs
    if ($premier==1) {
      $RDIR=$domain."/".$login;
    } else {
      $RDIR=$domain."/".$login."_".$domain;
    }
    exec("/usr/lib/alternc/lst_addlst ".escapeshellarg($RDIR));
    $tmpfname = tempnam ("/tmp", "sympa");
    $f = fopen($tmpfname,"wb");
    // CONFIG file
    fputs($f,"host $domain\n\n");
    fputs($f,"lang ".$this->lang."\n\n");
    fputs($f,"owner\nemail $owner\n\n");
    fputs($f,"editor\nemail $owner\n\n");
    fputs($f,"visibility secret\n\n");
    fputs($f,"subscribe closed\n\n");
    fputs($f,"unsubscribe open\n\n");
    fputs($f,"send private\n\n");
    fputs($f,"reply_to_header\n");
    fputs($f,"value sender\n\n");
    fputs($f,"max_size 102400\n\n");
    fputs($f,"review owner\n\n");
    fclose($f);
    chmod($tmpfname,0777);
    exec("/usr/lib/alternc/lst_putfile ".escapeshellarg($RDIR)." config ".escapeshellarg($tmpfname));
    unlink($tmpfname);
    return true;
  }

  /* ----------------------------------------------------------------- */
  /**
   *
   */
  function delete_lst($id) {
    global $db,$err,$quota;
    $err->log("sympa","delete_lst",$id);
    //connexion ldap
    if (!$this->_connectldap()) {
      $err->raise("sympa",1);
      return false;
    }
    $db->query("SELECT list,premier FROM ml WHERE id=$id and uid=".$this->uid.";");
    $db->next_record();
    $list=$db->f("list");
    if (!$list) {
      $err->raise("sympa",9);
      return false;
    }
    // on explose la liste : 
    $t=split("@",$list);
    $login=$t[0];
    $domain=$t[1];
    // DESTRUCTION des entr�es LDAP qui vont bien ...
    if (!ldap_delete($this->ds,"mail=".$login."@".$domain.",dc=domains,".$this->ldap["basedn"])) {
      $err->raise("sympa",11,ldap_error($this->ds));
    }
    if (!ldap_delete($this->ds,"mail=".$login."-request@".$domain.",dc=domains,".$this->ldap["basedn"])) {
      $err->raise("sympa",11,ldap_error($this->ds));
    }
    if (!ldap_delete($this->ds,"mail=".$login."-editor@".$domain.",dc=domains,".$this->ldap["basedn"])) {
      $err->raise("sympa",11,ldap_error($this->ds));
    }
    if (!ldap_delete($this->ds,"mail=".$login."-owner@".$domain.",dc=domains,".$this->ldap["basedn"])) {
      $err->raise("sympa",11,ldap_error($this->ds));
    }
    if (!ldap_delete($this->ds,"mail=".$login."-subscribe@".$domain.",dc=domains,".$this->ldap["basedn"])) {
      $err->raise("sympa",11,ldap_error($this->ds));
    }
    if (!ldap_delete($this->ds,"mail=".$login."-unsubscribe@".$domain.",dc=domains,".$this->ldap["basedn"])) {
      $err->raise("sympa",11,ldap_error($this->ds));
    }
    // DESTRUCTION des entr�es LDAP dans postfix_aliases :
    if (!ldap_delete($this->ds,"mail=".$login."_".$domain.",dc=aliases,".$this->ldap["basedn"])) {
      $err->raise("sympa",11,ldap_error($this->ds));
    }
    if (!ldap_delete($this->ds,"mail=".$login."-request_".$domain.",dc=aliases,".$this->ldap["basedn"])) {
      $err->raise("sympa",11,ldap_error($this->ds));
    }
    if (!ldap_delete($this->ds,"mail=".$login."-editor_".$domain.",dc=aliases,".$this->ldap["basedn"])) {
      $err->raise("sympa",11,ldap_error($this->ds));
    }
    if (!ldap_delete($this->ds,"mail=".$login."-owner_".$domain.",dc=aliases,".$this->ldap["basedn"])) {
      $err->raise("sympa",11,ldap_error($this->ds));
    }
    if (!ldap_delete($this->ds,"mail=".$login."-subscribe_".$domain.",dc=aliases,".$this->ldap["basedn"])) {
      $err->raise("sympa",11,ldap_error($this->ds));
    }
    if (!ldap_delete($this->ds,"mail=".$login."-unsubscribe_".$domain.",dc=aliases,".$this->ldap["basedn"])) {
      $err->raise("sympa",11,ldap_error($this->ds));
    }
    // suppression des subscribers
    $li=$this->listname($id);
    $db->query("SELECT user_subscriber FROM subscriber_table WHERE list_subscriber='$li';");
    $j=0;
    while ($db->next_record()) {
      $mail[$j]=$db->f("user_subscriber");
      $j++;
    }
    for ($i=0;$i<count($mail);$i++) {
      $this->del_user($id,$mail[$i]);
    }
    // si souci : on supprime toute trace de la liste dans subscriber_table
    $db->query("DELETE FROM subscriber_table WHERE list_subscriber='$li';");
    // Suppression du r�pertoire contenant la configuration de la liste (solution temporaire)
    $path=$this->path_ml($id);
    if ($path) {
      exec("/usr/lib/alternc/lst_dellst ".escapeshellarg($path));
    }
    // suppression de la liste de la table ml
    $db->query("DELETE FROM ml WHERE id=$id");
    $quota->dec("sympa");
    return $list;
  }

  /* ----------------------------------------------------------------- */
  /**
   *
   */
  function get_template($id,$txt) {
    global $db,$err;
    $err->log("sympa","get_template",$txt." - ".$id);
    if (!$this->textfiles[$txt]) {
      $err->raise("sympa",10);
      return false;
    }
    if ($path=$this->path_ml($id)) {
      if (!file_exists($this->LST_ROOT."/".$path."/".$txt))
	return "-";
      unset($file);
      exec("/usr/lib/alternc/lst_getfile ".escapeshellarg($path)." ".escapeshellarg($txt),$file);
      return join("\n",$file);
    } else {
      return false;
    }
  }

  /* ----------------------------------------------------------------- */
  /**
   *
   */
  function set_template($id,$txt,$message) {
    global $db,$err;
    $err->log("sympa","set_template",$txt." - ".$id);
    if (!$this->textfiles[$txt]) {
      $err->raise("sympa",10);
      return false;
    }
    if ($path=$this->path_ml($id)) {
      if ($message=="" || $message=="-") {
	exec("/usr/lib/alternc/lst_delfile ".escapeshellarg($path)." ".escapeshellarg($txt));
      } else {
	$tmpfname = tempnam ("/tmp", "sympa");
	$f = fopen($tmpfname,"wb");
	fputs($f,stripslashes($message));
	fclose($f);
	chmod($tmpfname,0777);
	exec("/usr/lib/alternc/lst_putfile ".escapeshellarg($path)." ".escapeshellarg($txt)." ".escapeshellarg($tmpfname));
	unlink($tmpfname);
      }
    } else {
      return false;
    }
    return true;
  }

  /* ----------------------------------------------------------------- */
  /**
   *
   */
  function select_subscribe_mode($current) {
    global $err;
    reset($this->subscribe_mode);
    while (list($key,$val)=each($this->subscribe_mode)) {
      if ($current==$key) $c=" selected"; else $c="";
      echo "<option$c value=\"$key\">"._($val)."</option>";
    }
    return true;
  }

  /* ----------------------------------------------------------------- */
  /**
   *
   */
  function select_review_mode($current) {
    global $err;
    reset($this->review_mode);
    while (list($key,$val)=each($this->review_mode)) {
      if ($current==$key) $c=" selected"; else $c="";
      echo "<option$c value=\"$key\">"._($val)."</option>";
    }
    return true;
  }

  /* ----------------------------------------------------------------- */
  /**
   *
   */
  function select_unsubscribe_mode($current) {
    global $err;
    reset($this->unsubscribe_mode);
    while (list($key,$val)=each($this->unsubscribe_mode)) {
      if ($current==$key) $c=" selected"; else $c="";
      echo "<option$c value=\"$key\">"._($val)."</option>";
    }
    return true;
  }

  /* ----------------------------------------------------------------- */
  /**
   *
   */
  function select_send_mode($current) {
    global $err;
    reset($this->send_mode);
    while (list($key,$val)=each($this->send_mode)) {
      if ($current==$key) $c=" selected"; else $c="";
      echo "<option$c value=\"$key\">"._($val)."</option>";
    }
    return true;
  }

  /* ----------------------------------------------------------------- */
  /**
   *
   */
  function select_reply_to($current) {
    global $err;
    reset($this->reply_to);
    while (list($key,$val)=each($this->reply_to)) {
      if ($current==$key) $c=" selected"; else $c="";
      echo "<option$c value=\"$key\">"._($val)."</option>";
    }
    return true;
  }

  /* ----------------------------------------------------------------- */
  /**
   *
   * @access private
   */
  function alternc_quota_check($id=-1) {
    global $db,$err,$quota;
    if (!$id==-1) $id=$this->uid;
    $db->query("SELECT COUNT(*) AS cnt FROM ml WHERE uid=$id");
    $db->next_record();
    $quota->setquota("sympa",$db->f("cnt"),1,$id);
    return true;
  }

  /* ----------------------------------------------------------------- */
  /**
   *
   * @access private
   */
  /* ****************************************************************************
     del_dom($dom) Supprime un domaine a l'utilisateur
     $dom est le domaine concerne
     fonction appel�e par m_domains
  *****************************************************************************/
  function alternc_del_domain($dom) {
    global $err;
    $err->log("sympa","del_dom",$dom);
    //connexion ldap
    if (!$this->_connectldap()) {
      $err->raise("sympa",1);
      return false;
    }
    // DESTRUCTION des entr�es LDAP qui vont bien ...
    if (!ldap_delete($this->ds,"mail=listmaster@".$dom.",dc=domains,".$this->ldap["basedn"])) {
      $err->raise("sympa",11,ldap_error($this->ds));
    }
    if (!ldap_delete($this->ds,"mail=sympa@".$dom.",dc=domains,".$this->ldap["basedn"])) {
      $err->raise("sympa",11,ldap_error($this->ds));
    }
    if (!ldap_delete($this->ds,"mail=sympa-request@".$dom.",dc=domains,".$this->ldap["basedn"])) {
      $err->raise("sympa",11,ldap_error($this->ds));
    }
    if (!ldap_delete($this->ds,"mail=bounce@".$dom.",dc=domains,".$this->ldap["basedn"])) {
      $err->raise("sympa",11,ldap_error($this->ds));
    }
    // DESTRUCTION des entr�es LDAP dans postfix_aliases :
    if (!ldap_delete($this->ds,"mail=listmaster_".$dom.",dc=aliases,".$this->ldap["basedn"])) {
      $err->raise("sympa",11,ldap_error($this->ds));
    }
    if (!ldap_delete($this->ds,"mail=sympa_".$dom.",dc=aliases,".$this->ldap["basedn"])) {
      $err->raise("sympa",11,ldap_error($this->ds));
    }
    if (!ldap_delete($this->ds,"mail=sympa-request_".$dom.",dc=aliases,".$this->ldap["basedn"])) {
      $err->raise("sympa",11,ldap_error($this->ds));
    }
    if (!ldap_delete($this->ds,"mail=bounce_".$dom.",dc=aliases,".$this->ldap["basedn"])) {
      $err->raise("sympa",11,ldap_error($this->ds));
    }
    // Suppression des listes du domaine
    $listes=$this->enum_ml();
    $domlen=-strlen($dom);
    while (list($key,$val)=each($listes)) {
      if (substr($val["list"],$domlen)==$dom) {
	echo "Deleting : ".$val["list"]."<br>";
	$this->delete_lst($val["id"]);
      }
    }
    exec("/usr/lib/alternc/lst_deldom ".escapeshellarg($dom));
    return true;
  }

  /* ----------------------------------------------------------------- */
  /**
   *
   * @access private
   */
  function alternc_add_domain($dom) {
    global $err;
    $err->log("sympa","add_dom",$dom);
    //connexion ldap
    if (!$this->_connectldap()) {
      $err->raise("sympa",1);
      return false;
    }
    // Creation des entr�es LDAP qui vont bien ...
    $res=array("mail" => "listmaster@".$dom, "uid" => $this->uid, "type" => "ml", "objectclass" => "mail", "pop" => "0", "account"=>"listmaster_".$dom );
    if (!ldap_add($this->ds,"mail=".$res["mail"].",dc=domains,".$this->ldap["basedn"],$res)) {
      $err->raise("sympa",11,ldap_error($this->ds));
      return false;
    }
    $res["mail"]="sympa@".$dom;	$res["account"]="sympa_".$dom;
    if (!ldap_add($this->ds,"mail=".$res["mail"].",dc=domains,".$this->ldap["basedn"],$res)) {
      $err->raise("sympa",11,ldap_error($this->ds));
      return false;
    }
    $res["mail"]="sympa-request@".$dom;	$res["account"]="sympa-request_".$dom;
    if (!ldap_add($this->ds,"mail=".$res["mail"].",dc=domains,".$this->ldap["basedn"],$res)) {
      $err->raise("sympa",11,ldap_error($this->ds));
      return false;
    }
    $res["mail"]="bounce@".$dom;	$res["account"]="bounce_".$dom;
    if (!ldap_add($this->ds,"mail=".$res["mail"].",dc=domains,".$this->ldap["basedn"],$res)) {
      $err->raise("sympa",11,ldap_error($this->ds));
      return false;
    }
    // Ajout des entr�es LDAP dans postfix_aliases : 
    $res=array("mail" => "listmaster_".$dom, "objectclass" => "alias", "alias"=>"\"|/usr/lib/sympa/bin/queue listmaster@".$dom."\"");
    if (!ldap_add($this->ds,"mail=".$res["mail"].",dc=aliases,".$this->ldap["basedn"],$res)) {
      $err->raise("sympa",11,ldap_error($this->ds));
      return false;
    }
    $res=array("mail" => "sympa_".$dom, "objectclass" => "alias", "alias"=>"\"|/usr/lib/sympa/bin/queue sympa@".$dom."\"");
    if (!ldap_add($this->ds,"mail=".$res["mail"].",dc=aliases,".$this->ldap["basedn"],$res)) {
      $err->raise("sympa",11,ldap_error($this->ds));
      return false;
    }
    $res=array("mail" => "sympa-request_".$dom, "objectclass" => "alias", "alias"=>"\"|/usr/lib/sympa/bin/queue listmaster@".$dom."\"");
    if (!ldap_add($this->ds,"mail=".$res["mail"].",dc=aliases,".$this->ldap["basedn"],$res)) {
      $err->raise("sympa",11,ldap_error($this->ds));
      return false;
    }
    $res=array("mail" => "bounce_".$dom, "objectclass" => "alias", "alias"=>"\"|/usr/lib/sympa/bin/bouncequeue sympa\"");
    if (!ldap_add($this->ds,"mail=".$res["mail"].",dc=aliases,".$this->ldap["basedn"],$res)) {
      $err->raise("sympa",11,ldap_error($this->ds));
      return false;
    }
    exec("/usr/lib/alternc/lst_adddom ".escapeshellarg($dom));
    return true;
  }


  /* ----------------------------------------------------------------- */
  /**
   *
   * @access private
   */
  function path_ml($id) {
    global $db;
    $db->query("SELECT list,premier FROM ml WHERE id=$id;");
    if (!$db->next_record()) {
      return false;
    }
    $s=explode("@",$db->f("list"));
    if ($db->f("premier")==1) {
      // /var/lib/sympa/expl/$domaine/$liste
      $path=$s["1"]."/".$s["0"];
    } else {
      // /var/lib/sympa/expl/$domaine/$liste_$domaine
      $path=$s["1"]."/".$s["0"]."_".$s["1"];
    }
    return $path;
  }

  /* ----------------------------------------------------------------- */
  /**
   *
   * @access private
   */
  function first_ml($login) {
    global $db,$err;
    $err->log("sympa","first_ml",$logi);
    $log=$login."@%";
    $db->query("SELECT list FROM ml WHERE premier='1' AND list LIKE '$log';");
    if ($db->num_rows()>0) {
      return false;
    } else {
      return true;
    }
  }

  /* ----------------------------------------------------------------- */
  /**
   *
   * @access private
   */
  function listname($r) {
    $s=explode("@",$r["list"]);
    if ($r["premier"])
      return $s[0];
    else
      return $s[0]."_".$s[1];
  }

  /* ----------------------------------------------------------------- */
  /**
   *
   * @access private
   */
  function _connectldap() {
    if ($this->ds==-1) {
      if (!($this->ds=ldap_connect($this->ldap["server"]))) {
	$this->ds=-1;
	return false;
      }
      if (!(ldap_bind($this->ds,$this->ldap["binddn"],$this->ldap["bindpwd"]))) {
	ldap_close($ds);
	$this->ds=-1;
	return false;
      }
      return true;
    } else return true;
  }

  function sympa_pwd() {
    $t=array(0,1,2,3,4,5,6,7,8,9,"a","b","c","d","e","f");
    $s="";
    for($i=0;$i<8;$i++)
      $s.=$t[mt_rand(0,15)];
    return "init".$s;
  }
  
} /* Class m_ml */

?>