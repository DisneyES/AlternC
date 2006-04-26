-- we don't store cleartext passwords anymore, we use saslauthd
ALTER TABLE `mail_users` DROP `sasl`;

use mysql;

-- on revient � l'ancienne routine: foo_bar au lieu de foo\_bar car
-- mysql a �t� arrang�
UPDATE IGNORE `db` set `Db` = REPLACE(`Db`,'\_','_') WHERE `Db` REGEXP '[^\\]\\\\_';
FLUSH PRIVILEGES;
