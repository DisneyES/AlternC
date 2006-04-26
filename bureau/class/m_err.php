<?php
/*
 $Id: m_err.php,v 1.4 2004/05/19 14:23:06 benjamin Exp $
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
 Original Author of file: Benjamin Sonntag, Franck Missoum
 ----------------------------------------------------------------------
*/
/**
* Classe de gestion des erreurs apparaissant lors d'appels API.
*
* <p>Cette classe g�re les erreurs qui peuvent apparaitre lors d'appels
* � l'API d'AlternC. Ces erreurs sont stock�es sous la forme de 2 nombres
* (Classe ID et Num�ro d'erreur) ainsi qu'un texte facultatif associ�.
* Des textes d'erreurs localis�s sont aussi disponibles.</p>
* <p>Cette classe se charge aussi d'ins�rer les appels � l'API d'AlternC
* dans les logs du syst�me dans /var/log/alternc/bureau.log
* </p>
* Copyleft {@link http://alternc.net/ AlternC Team}
* 
* @copyright    AlternC-Team 2002-11-01 http://alternc.net/
*/
class m_err {

  /** Numero de classe d'erreur actuelle */
  var $clsid=0;

  /** Derni�re erreur enregistr�e par la classe */
  var $error=0;

  /** Param�tre chaine eventuellement associ� � la derni�re erreur */
  var $param="";

  /** Emplacement du fichier de logs d'AlternC */
  var $logfile="/var/log/alternc/bureau.log"; 

  /**
   * Leve une erreur, signale celle-ci dans les logs et stocke le code erreur
   * 
   * Cette fonction lance une erreur, l'ajoute dans les logs d'AlternC, 
   * et la met � disposition pour le bureau virtuel pour affichage ult�rieur.
   * 
   * @param integer $clsid Classe qui l�ve l'erreur
   * @param integer $error Num�ro de l'erreur
   * @param string $param Param�tre chaine associ� � l'erreur (facultatif)
   * @return boolean TRUE si l'erreur est connue, FALSE sinon.
   *
   */
  function raise($clsid,$error,$param="") {
    /* Leve une exception. Si elle existe, sinon, stocke un message d'erreur sur erreur ...*/
    if (_("err_".$clsid."_".$error)!="err_".$clsid."_".$error) {
      $this->clsid=$clsid;
      $this->error=$error;
      $this->param=$param;
      $this->logerr();
      return true;
    } else {
      $this->clsid="err";
      $this->error=1;
      $this->param="Num�ro $error, Classe $clsid, Valeur $param";
      $this->logerr();
      return false;
    }
  }

  /**
   * Retourne la chaine d'erreur correspondant � la derni�re erreur rencontr�e
   *
   * Si la derni�re erreur rencontr�e est connue, retourne l'erreur en toute lettre
   * dans la langue actuellement s�lectionn�e, ou en anglais par d�faut.
   * Si l'erreur n'est pas connue, retourne son num�ro de classe et d'ereur.
   *
   * @return string Chaine d'erreur.
   *
   */
  function errstr() {
    return sprintf(_("err_".$this->clsid."_".$this->error),$this->param);
  }

  /**
   * Envoi un log d'erreur dans /var/log/alternc/bureau.log
   *
   * Cette fonction Loggue la derni�re erreur dans /var/log sur la machine,
   * permettant ainsi aux admins de savoir ce qu'il se passe...
   * Elle est appel�e automatiquement par error
   * @access private
   */
  function logerr() {
    global $mem;
    $f=@fopen($this->logfile,"ab");
    if ($f) {
      fputs($f,date("d/m/Y H:i:s")." - ERROR - ");
      fputs($f,$mem->user["login"]." - ");
      fputs($f,sprintf(_("err_".$this->clsid."_".$this->error)."\n",$this->param));
      fclose($f);
    }
  }

  /**
   * Envoi un log d'appel d'API dans /var/log/alternc/bureau.log
   *
   * Cette fonction loggue dans /var/log l'appel � la fonction de l'API
   * d'AlternC.
   *
   * @param integer $clsid Num�ro de la classe dont on a appel� une fonction
   * @param string $function Nom de la fonction appel�e
   * @param string $param Param�tre (facultatif) pass�s � la fonction de l'API.
   * @return boolean TRUE si le log a �t� ajout�, FALSE sinon
   *
   */
  function log($clsid,$function,$param="") {
    global $mem,$cuid;
    $f=@fopen($this->logfile,"ab");
    if ($f) {
      fputs($f,date("d/m/Y H:i:s")." - CALL - ");
      fputs($f,$mem->user["login"]." - ");
      fputs($f,$clsid." - ".$function." - ".$param."\n");
      fclose($f);
      return true;
    } else {
      return false;
    }
  }

}; /* Classe m_err */

?>