#!/bin/bash

# Migrate a svn repository to WHEEZY

# DON'T COMMIT ANYTHING AFTER launching this
# reset your subversion repos back to the serverside one!

cd `dirname $0` 

pushd ../debian 
patch <../jessie/control.diff
patch <../jessie/changelog.diff
popd

cp vhost.conf ../etc/alternc/templates/apache2/