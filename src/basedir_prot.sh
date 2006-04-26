#!/bin/sh

set -e

# Ceci cr�� un hack php pour chacun des domaines h�berg�s par alternc
# ce hack consiste � restreindre chaque usager � son propre r�pertoire
# dans alternc/html/u/user avec open_base_dir

# ce script a les d�pendances suivantes:
# (mysql, /etc/alternc/local.sh) OR /usr/bin/get_account_by_domain dans
# alternc-admintools
# cut, awk, sort

override_d=/var/alternc/apacheconf
override_f=${override_d}/override_php.conf
extra_paths="/var/alternc/dns/redir:/usr/share/php/:/var/alternc/tmp/:/tmp/"

. /etc/alternc/local.sh
if [ -z "$MYSQL_HOST" ]
then
    MYSQL_HOST="localhost"
fi

# imprime le nom d'usager associ� au domaine
get_account_by_domain() {
	# les admintools ne sont peut-�tre pas l�
	if [ -x "/usr/bin/get_account_by_domain" ]
	then
		# only first field, only first line
		/usr/bin/get_account_by_domain "$1" | cut -d\  -f1 | cut -d'
' -f 1
	else
		# implantons localement ce que nous avons besoin, puisque admintools
		# n'est pas l�
  		mysql -h$MYSQL_HOST -u$MYSQL_USER -p$MYSQL_PASS -D$MYSQL_DATABASE -B -N -e \
  		'SELECT a.login FROM membres a, sub_domaines b WHERE a.uid = b.compte AND \
  		CONCAT(IF(sub="", "", CONCAT(sub, ".")), domaine) = "'"$1"'" LIMIT 1;'
	fi
}

# add the standard input to a given file, only if not already present
append_no_dupe() {
	realfile="$1"
	tmpfile=`mktemp`
	trap "rm -f $tmpfile; exit 1" 1 2 15
	cat > $tmpfile
	if [ -r "$realfile" ] &&
		(diff -q "$tmpfile" "$realfile" > /dev/null || \
			diff -u "$tmpfile" "$realfile"  | grep '^ ' | sed 's/^ //' | diff -q - "$tmpfile" > /dev/null)
	then
		ret=0
	else
		ret=1
		cat "$tmpfile" >> "$realfile"
	fi
	rm -f "$tmpfile"
	return "$ret"
}

add_dom_entry() {
	# protect ourselves from interrupts
	trap "rm -f ${override_f}.new; exit 1" 1 2 15
	# ajouter une entr�e, seulement s'il n'y en pas d�j�, pour ce domaine
	(echo "$1"; [ -r $override_f ] && cat $override_f) | \
	sort -u > ${override_f}.new && \
	cp ${override_f}.new ${override_f} && \
	rm ${override_f}.new
}

# la premi�re lettre de l'avant-derni�re partie du domaine (e.g.
# www.alternc.org -> a)
#
# argument: le domaine
# imprime: la lettre
init_dom_letter() {
    echo "$1" | awk '{z=split($NF, a, ".") ; print substr(a[z-1], 1, 1)}'
}

echo -n "adding open_base_dir protection for:"
# boucle sur tous les domaines h�berg�s, ou sur les arguments de la
# ligne de commande
if [ $# -gt 0 ]; then
	for i in "$*"
        do
                if echo "$i" | grep -q '^\*\.'
                then
                    echo skipping wildcard "$i" >&2
                    continue
                fi
		if echo "$i" | grep -q /var/alternc/dns > /dev/null; then
			dom="$i"
		else
		    initial_domain=`init_dom_letter "$i"`
		    dom="/var/alternc/dns/$initial_domain/$i"
		fi
		if [ -e "$dom" ]; then
			doms="$doms $dom"
		else
			echo skipping non-existent domain "$dom" >&2
		fi
	done
else
	doms=`find /var/alternc/dns -type l`
fi

for i in $doms
do
	# don't "protect" squirrelmail, it legitimatly needs to consult
	# files out of its own directory
	if readlink "$i" | grep -q '^/var/alternc/bureau/admin/webmail/*$' || \
	   readlink "$i" | grep -q '^/var/alternc/bureau/*$'
	then
		continue
	fi
	domain=`basename "$i"`
	account=`get_account_by_domain $domain`
	if [ -z "$account" ]; then
		continue
	fi
	# la premi�re lettre de l'avant-derni�re partie du domaine (e.g.
	# www.alternc.org -> a)
	initial_domain=`init_dom_letter "$domain"`
	# la premi�re lettre du username
	initial_account=`echo "$account" | cut -c1`
	path1="/var/alternc/dns/$initial_domain/$domain"
	path2="/var/alternc/html/$initial_account/$account"

	mkdir -p "$override_d/$initial_domain"
	if append_no_dupe "$override_d/$initial_domain/$domain" <<EOF
<Directory ${path1}>
  php_admin_value open_basedir ${path2}/:${extra_paths}
</Directory>
EOF
	then
		true
	else
		echo -n " $domain"
		add_dom_entry "Include $override_d/$initial_domain/$domain"
	fi
done

echo .
