#
# $Id: mysql.sql,v 1.39 2006/02/17 15:15:54 olivier Exp $
# ----------------------------------------------------------------------
# AlternC - Web Hosting System
# Copyright (C) 2006 Le r�seau Koumbit Inc.
# http://koumbit.org/
# Copyright (C) 2002 by the AlternC Development Team.
# http://alternc.org/
# ----------------------------------------------------------------------
# Based on:
# Valentin Lacambre's web hosting softwares: http://altern.org/
# ----------------------------------------------------------------------
# LICENSE
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License (GPL)
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# To read the license please visit http://www.gnu.org/copyleft/gpl.html
# ----------------------------------------------------------------------
# Original Author of file: Benjamin Sonntag
# Purpose of file: Create the basic structure for the mysql system db
# ----------------------------------------------------------------------
#

######################################################################
# STRUCTURE DES TABLES D'ALTERNC
#
# IMPORTANT: lorsque la structure de ces tables est modifi�e, le
# fichier upgrades/<version>.sql doit �tre modifi� (ou cr��!) pour que
# les installations courantes soient mises � jour. <version> est ici
# le prochain num�ro de version d'AlternC. Voir upgrades/README pour
# plus de d�tails.
#########################################################################

CREATE TABLE IF NOT EXISTS `slaveip` (
`ip` VARCHAR( 15 ) NOT NULL ,
`class` TINYINT NOT NULL ,
PRIMARY KEY ( `ip` , `class` )
) COMMENT = 'Allowed ip for slave dns managment';

CREATE TABLE IF NOT EXISTS `slaveaccount` (
`login` VARCHAR( 64 ) NOT NULL ,
`pass`  VARCHAR( 64 ) NOT NULL ,
PRIMARY KEY ( `login` )
) COMMENT = 'Allowed account for slave dns managment';

# 
# Structure de la table mail_alias
# 

CREATE TABLE IF NOT EXISTS `mail_alias` (
  `mail` varchar(255) NOT NULL default '',	# Adresse email LOCALE
  `alias` varchar(255) NOT NULL default '',	# WRAPPER 
  PRIMARY KEY  (`mail`)
) TYPE=MyISAM COMMENT='Mail Alias pour postfix';


CREATE TABLE IF NOT EXISTS `mail_users` (
  `uid` int(10) unsigned NOT NULL default '0',	# UID AlternC de l'utilisateur du mail
  `alias` varchar(255) NOT NULL default '',	# Alias = Alias interm�diaire (voir domain)
  `path` varchar(255) NOT NULL default '',	# Chemin vers le mail de l'utilisateur
  `password` varchar(255) NOT NULL default '',	# Mot de passe crypt� 
  PRIMARY KEY  (`alias`),
  KEY `path` (`path`),
  KEY `uid` (`uid`)
) TYPE=MyISAM COMMENT='Comptes pop, wrappers, alias';


CREATE TABLE IF NOT EXISTS `mail_domain` (
  `mail` varchar(255) NOT NULL default '',	# Adresse email COMPLETE (login@domaine)
  `alias` text NOT NULL,			# Alias interm�diaire (login_domaine) pour r�f�rence dans users
  `uid` int(10) unsigned NOT NULL default '0',	# Num�ro de l'utilisateur (alternc)
  `pop` tinyint(4) NOT NULL default '0',	# Est-ce un compte pop ? 
  `type` tinyint(4) NOT NULL default '0',	# Je ne sais plus ...
  PRIMARY KEY  (`mail`),
  KEY `uid` (`uid`),
  KEY `pop` (`pop`)
) TYPE=MyISAM COMMENT='Alias en domaine pour Postfix';



#
# Structure de la table `browser`
#
# Cette table contient les pr�f�rences des utilisateurs dans le gestionnaire de fichiers


CREATE TABLE IF NOT EXISTS browser (
  uid int(10) unsigned NOT NULL default '0',		# Num�ro de l'utilisateur
  editsizex int(10) unsigned NOT NULL default '0',	# Largeur de la zone d'edition du brouteur
  editsizey int(10) unsigned NOT NULL default '0',	# Hauteur de la zone d'edition du brouteur
  listmode tinyint(3) unsigned NOT NULL default '0',	# Mode de listing (1 colonne, 2 colonne, 3 colonne)
  showicons tinyint(4) NOT NULL default '0',		# Faut-il afficher les icones (1/0)
  downfmt tinyint(4) NOT NULL default '0',		# Format de t�l�chargement (zip/bz2/tgz/tar.Z)
  createfile tinyint(4) NOT NULL default '0',		# Que fait-on apr�s cr�ation d'un fichier (1/0)
  showtype tinyint(4) NOT NULL default '0',		# Affiche-t-on le type mime ? 
  editor_font varchar(64) NOT NULL default '',		# Nom de la police dans l'�diteur de fichiers
  editor_size varchar(8) NOT NULL default '',		# Taille de la police dans l'�diteur de fichiers
  crff tinyint(4) NOT NULL default '0',			# m�morise le dernier fichier/dossier cr�� (pour le bouton radio)
  golastdir tinyint(4) NOT NULL default '0',		# Faut-il aller au dernier dossier ou au dossier racine dans le brouteur ?
  lastdir varchar(255) NOT NULL default '',		# Dernier dossier visit�.
  PRIMARY KEY  (uid)
) TYPE=MyISAM COMMENT='Pr�f�rences du gestionnaire de fichiers';


#
# Structure de la table `chgmail`
#
# Cette table contient les demandes de changements de mail pour les membres

CREATE TABLE IF NOT EXISTS chgmail (
  uid int(10) unsigned NOT NULL default '0',		# Num�ro de l'utilisateur
  cookie varchar(20) NOT NULL default '',		# Cookie du mail
  ckey varchar(6) NOT NULL default '',			# Cl� de v�rif
  mail varchar(128) NOT NULL default '',		# Nouvel Email
  ts bigint(20) unsigned NOT NULL default '0',		# Timestamp de la demande 
  PRIMARY KEY  (uid)
) TYPE=MyISAM COMMENT='Demandes de changements de mail en cours';

#
# Structure de la table `db`
#
# Contient les bases mysql des membres, + login / pass en clair

CREATE TABLE IF NOT EXISTS db (
  uid int(10) unsigned NOT NULL default '0',		# Num�ro de l'utilisateur
  login varchar(16) NOT NULL default '',		# Nom d'utilisateur mysql
  pass varchar(16) NOT NULL default '',			# Mot de passe mysql
  db varchar(64) NOT NULL default '',			# Base de donn�es concern�e
  bck_mode tinyint(3) unsigned NOT NULL default '0',	# Mode de backup (0/non 1/Daily 2/Weekly)
  bck_history tinyint(3) unsigned NOT NULL default '0',	# Nombre de backup � conserver ?
  bck_gzip tinyint(3) unsigned NOT NULL default '0',	# Faut-il compresser les backups ?
  bck_dir varchar(255) NOT NULL default '',		# O� stocke-t-on les backups sql ?
  KEY uid (uid)
) TYPE=MyISAM COMMENT='Bases MySQL des membres';

#
# Structure de la table `domaines`
#
# Liste des domaines h�berg�s

CREATE TABLE IF NOT EXISTS domaines (
  compte int(10) unsigned NOT NULL default '0',
  domaine varchar(64) NOT NULL default '',
  mx varchar(64) default NULL,
  gesdns int(1) NOT NULL default '1',
  gesmx int(1) NOT NULL default '1',
  noerase tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (compte,domaine)
) TYPE=MyISAM;

#
# Structure de la table `domaines_standby`
#
# Liste temporaire utilis�e par le cron des domaines

CREATE TABLE IF NOT EXISTS domaines_standby (
  compte int(10) unsigned NOT NULL default '0',
  domaine varchar(64) NOT NULL default '',
  mx varchar(64) default NULL,
  gesdns int(1) NOT NULL default '1',
  gesmx int(1) NOT NULL default '1',
  action int(1) NOT NULL default '0',
  PRIMARY KEY  (compte,domaine,action)
) TYPE=MyISAM;

#
# Structure de la table `ftpusers`
#
# Comptes ftp des membres

CREATE TABLE IF NOT EXISTS ftpusers (
  id int(10) unsigned NOT NULL auto_increment,
  name varchar(64) NOT NULL default '',
  password varchar(32) NOT NULL default '',
  homedir varchar(128) NOT NULL default '',
  uid int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  UNIQUE KEY name (name),
  KEY homedir (homedir),
  KEY mid (uid)
) TYPE=MyISAM;

#
# Structure de la table `local`
#
# Champs utilisables par l'h�bergeur pour associer des donn�es locales aux membres.

CREATE TABLE IF NOT EXISTS local (
  uid int(10) unsigned NOT NULL default '0',
  nom varchar(128) NOT NULL default '',
  prenom varchar(128) NOT NULL default '',
  PRIMARY KEY  (uid)
) TYPE=MyISAM COMMENT='Parametres Locaux des membres';

#
# Structure de la table `membres`
#
# Liste des membres

CREATE TABLE IF NOT EXISTS membres (
  uid int(10) unsigned NOT NULL auto_increment,		# Num�ro du membre (GID)
  login varchar(128) NOT NULL default '',		# Nom d'utilisateur
  pass varchar(64) NOT NULL default '',			# Mot de passe
  enabled tinyint(4) NOT NULL default '1',		# Le compte est-il actif ?
  su tinyint(4) NOT NULL default '0',			# Le compte est-il super-admin ?
  mail varchar(128) NOT NULL default '',		# Adresse email du possesseur
  lastaskpass bigint(20) unsigned default '0',		# Date de derni�re demande du pass par mail
  show_help tinyint(4) NOT NULL default '1',		# Faut-il afficher l'aide dans le bureau
  lastlogin datetime NOT NULL default '0000-00-00 00:00:00',	# Date du dernier login
  lastfail tinyint(4) NOT NULL default '0',		# Nombre d'�checs depuis le dernier login
  lastip varchar(255) NOT NULL default '',		# Nom DNS du client au dernier login
  creator int(10) unsigned default '0',			# Qui a cr�� le compte (quel uid admin)
  canpass tinyint(4) default '1',			# L'utilisateur peut-il changer son pass.
  warnlogin tinyint(4) default '0',			# TODO L'utilisateur veut-il recevoir un mail quand on se loggue sur son compte ?
  warnfailed tinyint(4) default '0',			# TODO L'utilisateur veut-il recevoir un mail quand on tente de se logguer sur son compte ?
  admlist tinyint(4) default '0',			# Mode d'affichage de la liste des membres pour les super admins
  type varchar(128) default 'default',
  PRIMARY KEY  (uid),
  UNIQUE KEY k_login (login)
) TYPE=MyISAM COMMENT='Liste des membres du serveur';

#
# Structure de la table `quotas`
#
# Listes des quotas des membres

CREATE TABLE IF NOT EXISTS quotas (
  uid int(10) unsigned NOT NULL default '0',		# Num�ro GID du membre concern�
  name varchar(64) NOT NULL default '',			# Nom du quota
  total int(11) NOT NULL default '0',			# Quota total (maximum autoris�)
  PRIMARY KEY  (uid,name)
) TYPE=MyISAM COMMENT='Quotas des Membres';

#
# Structure de la table `sessions`
#
# Sessions actives sur le bureau

CREATE TABLE IF NOT EXISTS sessions (
  sid varchar(32) NOT NULL default '',			# Cookie de session (md5)
  uid int(10) unsigned NOT NULL default '0',		# UID du membre concern�
  ip int(10) unsigned NOT NULL default '0',		# Adresse IP de la connexion
  ts timestamp(14) NOT NULL
) TYPE=MyISAM COMMENT='Session actives sur le bureau';

#
# Structure de la table `sub_domaines`
#
# Sous-domaines des membres

CREATE TABLE IF NOT EXISTS sub_domaines (
  compte int(10) unsigned NOT NULL default '0',
  domaine varchar(64) NOT NULL default '',
  sub varchar(100) NOT NULL default '',
  valeur varchar(255) default NULL,
  type int(1) NOT NULL default '0',
  PRIMARY KEY  (compte,domaine,sub)
) TYPE=MyISAM;

#
# Structure de la table `sub_domaines_standby`
#
# Table temporaire des sous-domaines des membres utilis�e par le cron

CREATE TABLE IF NOT EXISTS sub_domaines_standby (
  compte int(10) unsigned NOT NULL default '0',
  domaine varchar(64) NOT NULL default '',
  sub varchar(100) NOT NULL default '',
  valeur varchar(255) default NULL,
  type int(1) NOT NULL default '0',
  action int(1) NOT NULL default '0',
  PRIMARY KEY  (compte,domaine,sub,action)
) TYPE=MyISAM;

#
# Structure de la table `stats2`
#
# Liste des jeux de stat brutes demand�es sur le serveur

CREATE TABLE IF NOT EXISTS stats2 (
  id int(10) unsigned NOT NULL auto_increment,	# Num�ro du jeu de stat brut
  mid int(10) unsigned NOT NULL default '0',	# Num�ro de l'utilisateur
  hostname varchar(255) NOT NULL default '',	# Domaine concern�
  folder varchar(255) NOT NULL default '',	# Dossier de stockage des logs
  PRIMARY KEY  (id),
  KEY mid (mid)
) TYPE=MyISAM COMMENT='Statistiques apaches brutes';


#
# Structure de la table `defquotas`
#
# Quotas par d�faut pour les services

CREATE TABLE IF NOT EXISTS defquotas (
  quota varchar(128),				# Nom du quota
  value bigint(20) unsigned default '0',	# Valeur du quota
  type  varchar(128) default 'default',		# Type de compte associ�e � ce quota
  PRIMARY KEY (quota,type)
) TYPE=MyISAM;

#
# Quotas par defaut pour les nouveaux membres
#
# Ces quotas par defaut sont redefinissables dans l'interface web

INSERT IGNORE INTO defquotas (quota,value) VALUES ('dom',1);
INSERT IGNORE INTO defquotas (quota,value) VALUES ('mail',10);
INSERT IGNORE INTO defquotas (quota,value) VALUES ('ftp',2);
INSERT IGNORE INTO defquotas (quota,value) VALUES ('stats',1);
INSERT IGNORE INTO defquotas (quota,value) VALUES ('mysql',1);


#
# Structure de la table `forbidden_domains`
#
# Liste des domaines explicitement interdits sur le serveur :

CREATE TABLE IF NOT EXISTS forbidden_domains (
  domain varchar(255) NOT NULL default '',
  PRIMARY KEY  (domain)
) TYPE=MyISAM COMMENT='forbidden domains to install';

#
# Contenu de la table `forbidden_domains`
#

# Registries : 
INSERT IGNORE INTO forbidden_domains VALUES ('afilias.net');
INSERT IGNORE INTO forbidden_domains VALUES ('afnic.fr');
INSERT IGNORE INTO forbidden_domains VALUES ('dns.be');
INSERT IGNORE INTO forbidden_domains VALUES ('internic.net');
INSERT IGNORE INTO forbidden_domains VALUES ('netsol.com');
INSERT IGNORE INTO forbidden_domains VALUES ('nic.biz');
INSERT IGNORE INTO forbidden_domains VALUES ('nic.cx');
INSERT IGNORE INTO forbidden_domains VALUES ('nic.fr');
INSERT IGNORE INTO forbidden_domains VALUES ('verisign.com');
INSERT IGNORE INTO forbidden_domains VALUES ('octopuce.com');
INSERT IGNORE INTO forbidden_domains VALUES ('pir.org');
INSERT IGNORE INTO forbidden_domains VALUES ('cira.ca');
# big isp :
INSERT IGNORE INTO forbidden_domains VALUES ('aol.com');
INSERT IGNORE INTO forbidden_domains VALUES ('hotmail.com');
INSERT IGNORE INTO forbidden_domains VALUES ('microsoft.com');
INSERT IGNORE INTO forbidden_domains VALUES ('sympatico.ca');
INSERT IGNORE INTO forbidden_domains VALUES ('tiscali.fr');
INSERT IGNORE INTO forbidden_domains VALUES ('voila.fr');
INSERT IGNORE INTO forbidden_domains VALUES ('wanadoo.fr');
INSERT IGNORE INTO forbidden_domains VALUES ('yahoo.com');
INSERT IGNORE INTO forbidden_domains VALUES ('yahoo.fr');

#
# Structure de la table `tld`
#
# Liste des tld autoris�s sur ce serveur : 

CREATE TABLE IF NOT EXISTS tld (
  tld varchar(128) NOT NULL default '',		# lettres du tld (sans le .)
  mode tinyint(4) NOT NULL default '0',		# Comment est-il autoris� ?
  PRIMARY KEY  (tld),
  KEY mode (mode)
) TYPE=MyISAM COMMENT='TLD autoris�s et comment sont-ils autoris�s ? ';

#
# Contenu de la table `tld`
#

INSERT IGNORE INTO tld VALUES ('fr', 4);
INSERT IGNORE INTO tld VALUES ('com', 1);
INSERT IGNORE INTO tld VALUES ('org', 1);
INSERT IGNORE INTO tld VALUES ('net', 1);
INSERT IGNORE INTO tld VALUES ('biz', 1);
INSERT IGNORE INTO tld VALUES ('info', 1);
INSERT IGNORE INTO tld VALUES ('name', 1);
INSERT IGNORE INTO tld VALUES ('ca', 1);
INSERT IGNORE INTO tld VALUES ('it', 1);
INSERT IGNORE INTO tld VALUES ('ws', 1);
INSERT IGNORE INTO tld VALUES ('be', 1);
INSERT IGNORE INTO tld VALUES ('eu.org', 4);
INSERT IGNORE INTO tld VALUES ('cjb.net', 4);
INSERT IGNORE INTO tld VALUES ('asso.fr', 4);

--
-- Table structure for table 'variable'
--
-- Taken from http://cvs.drupal.org/viewcvs/drupal/drupal/database/database.mysql?rev=1.164&view=auto
--
-- if comment is null, then the variable is internal and will not show
-- up in the generic configuration panel
CREATE TABLE IF NOT EXISTS variable (
  name varchar(48) NOT NULL default '',
  value longtext NOT NULL,
  comment mediumtext NULL,
  PRIMARY KEY  (name),
  KEY name (name)
) TYPE=MyISAM;

-- hosting_tld: only used, for now, in bureau/admin/adm_*add.php
INSERT IGNORE INTO `variable` (name, value, comment) VALUES ('hosting_tld', 0,
'This is a FQDN that designates the main hostname of the service.

For example, hosting_tld determines in what TLD the "free" user domain
is created. If this is set to "example.com", a checkbox will appear in
the user creation dialog requesting the creator if he wants to create
the domain "username.example.com".

If this is set to 0 or a "false" string, it will be ignored.');
