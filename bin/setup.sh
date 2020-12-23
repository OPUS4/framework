#!/usr/bin/env bash
#
# LICENCE
# This code is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This code is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# @author      Kaustabh Barman <barman@zib.de>
# @copyright   Copyright (c) 2020, OPUS 4 development team
# @license     http://www.gnu.org/licenses/gpl.html General Public License
#

#
# Script for creating OPUS 4 database and setting up user and admin.
#
# As part of the installation this script creates a database and configures config.ini with the
# database credentials provided during the execution of the script.
#
# Parameters can be used to specify a different database name and other information.

set -e

MYSQL_CLIENT='/usr/bin/mysql'

SCRIPT_NAME="`basename "$0"`"
SCRIPT_NAME_FULL="`readlink -f "$0"`"
SCRIPT_PATH="`dirname "$SCRIPT_NAME_FULL"`"
BASEDIR="$(dirname "$SCRIPT_PATH")"

# Parse command line options
#
# User may chose to create a custom configuration file by passing the argument '-c filename'

while getopts ":c:" opt; do
  case $opt in
    c) OPUS_CONF="$OPTARG"
    ;;
  esac
done

# Default configuration file
OPUS_CONF="${OPUS_CONF:-config.ini}"

#
# Prompt for database parameters
#

echo
echo "Database configuration"
echo

[[ -z $DBNAME ]] && read -p "New OPUS Database Name [opusdbfw]: "           DBNAME
[[ -z $DB_ADMIN ]] && read -p "New OPUS Database Admin Name [opus4adminfw]: " DB_ADMIN

while [[ -z $DB_ADMIN_PASSWORD || "$DB_ADMIN_PASSWORD" != "$DB_ADMIN_PASSWORD_VERIFY" ]] ;
do
  read -p "New OPUS Database Admin Password: " -s       DB_ADMIN_PASSWORD
  echo
  read -p "New OPUS Database Admin Password again: " -s DB_ADMIN_PASSWORD_VERIFY
  echo
  if [[ $DB_ADMIN_PASSWORD != $DB_ADMIN_PASSWORD_VERIFY ]] ;
  then
    echo "Passwords do not match. Please try again."
  fi
done

[[ -z $DB_USER ]] && read -p "New OPUS Database User Name [opus4fw]: "       DB_USER

while [[ -z $DB_USER_PASSWORD || "$DB_USER_PASSWORD" != "$DB_USER_PASSWORD_VERIFY" ]] ;
do
  read -p "New OPUS Database User Password: " -s        DB_USER_PASSWORD
  echo
  read -p "New OPUS Database User Password again: " -s  DB_USER_PASSWORD_VERIFY
  echo
  if [[ $DB_USER_PASSWORD != $DB_USER_PASSWORD_VERIFY ]] ;
  then
    echo "Passwords do not match. Please try again."
  fi
done

# set defaults if values are not given
DBNAME="${DBNAME:-opusdbfw}"
DB_ADMIN="${DB_ADMIN:-opus4adminfw}"
DB_USER="${DB_USER:-opus4fw}"

# escape ! (for later use in sed substitute)
DBNAME_ESC="${DBNAME//\!/\\\!}"
DB_ADMIN_ESC="${DB_ADMIN//\!/\\\!}"
DB_ADMIN_PASSWORD_ESC="${DB_ADMIN_PASSWORD//\!/\\\!}"
DB_USER_ESC="${DB_USER//\!/\\\!}"
DB_USER_PASSWORD_ESC="${DB_USER_PASSWORD//\!/\\\!}"

#
# Create database and users.
#
# By default the database and the users are created requiring the MySQL root password,
# however that can be suppressed in order to just generate the configuration files for
# an existing database.
#

echo
echo "Please provide parameters for the database connection:"
[[ -z $MYSQLHOST ]] && read -p "MySQL DBMS Host [localhost]: " MYSQLHOST
[[ -z $MYSQLPORT ]] && read -p "MySQL DBMS Port [3306]: "      MYSQLPORT

# set defaults if value is not given
MYSQLHOST="${MYSQLHOST:-localhost}"
MYSQLPORT="${MYSQLPORT:-3306}"

# escape ! (for later use in sed substitute)
MYSQLHOST_ESC="${MYSQLHOST//\!/\\\!}"
MYSQLPORT_ESC="${MYSQLPORT//\!/\\\!}"

#
# Create config.ini and set database related parameters.
#

cd "$BASEDIR/tests"

if ! [ -f "$OPUS_CONF" ]; then
  cp config.ini.template "$OPUS_CONF"

  if [ localhost != "$MYSQLHOST" ]; then
    sed -i -e "s!^; db.params.host = localhost!db.params.host = '$MYSQLHOST_ESC'!" "$OPUS_CONF"
  fi

  if [ 3306 != "$MYSQLPORT" ]; then
    sed -i -e "s!^; db.params.port = 3306!db.params.port = '$MYSQLPORT_ESC'!" "$OPUS_CONF"
  fi

  sed -i -e "s!@db.admin.name@!'$DB_USER_ESC'!" "$OPUS_CONF" \
         -e "s!@db.admin.password@!'$DB_USER_PASSWORD_ESC'!" "$OPUS_CONF" \
         -e "s!@db.name@!'$DBNAME_ESC'!" "$OPUS_CONF" \
         -e "s!@db.admin.name@!'$DB_ADMIN_ESC'!" "$OPUS_CONF" \
         -e "s!@db.admin.password@!'$DB_ADMIN_PASSWORD_ESC'!" "$OPUS_CONF"

else
  [[ -z $OVERWRITE ]] && read -p "A configuration file already exists. Do you really want to overwrite [Y]?"   OVERWRITE
  if [[ -z $OVERWRITE || "$OVERWRITE" == Y || "$OVERWRITE" == y ]];
  then
    cp config.ini config.ini.backup
    echo "config.ini.backup created"

    markers=()
    while read marker; do
	    found=false
	    for n in "${markers[@]}"; do
		    [ "$n" == "$marker" ] && { found=true; break; }
	    done

	    $found || markers+=("$marker")
    done < <(awk <"$BASEDIR/tests/config.ini.template" -F '[ =]+' '$2 ~ /^@.+@$/ {print substr($2,2,length($2)-2)}')

    PARAMS=($DB_ADMIN_ESC $DB_ADMIN_PASSWORD_ESC $DBNAME_ESC)
    count=0
    map=
    for marker in "${markers[@]}"; do
	    map="${map}M[\"@$marker@\"]=\"${PARAMS[$count]}\";"
	    (( count++ ))
    done

    awk <"$BASEDIR/tests/config.ini.template" >"$BASEDIR/tests/config.ini" -F '[ =]+' \
	    'BEGIN{'$map'}{if($2 ~ /^@.+@$/)print $1 " = " M[$2];else print $0}'

	fi
fi


#
# Optionally initialize database.
#

[[ -z $CREATE_DATABASE ]] && read -p "Create database and users [Y]? " CREATE_DATABASE

if [[ -z "$CREATE_DATABASE" || "$CREATE_DATABASE" == Y || "$CREATE_DATABASE" == y ]] ;
then

    echo
    [[ -z $MYSQLROOT ]] && read -p "MySQL Root User [root]: "                    MYSQLROOT
    read -p "MySQL Root User Password: " -s MYSQLROOT_PASSWORD
    echo

    # set defaults if value is not given
    MYSQLROOT="${MYSQLROOT:-root}"

    # prepare to access MySQL service
    MYSQL_OPTS=""
    [ "localhost" != "$MYSQLHOST" ] && MYSQL_OPTS="-h $MYSQLHOST"
    [ "3306" != "$MYSQLPORT" ] && MYSQL_OPTS="$MYSQL_OPTS -P $MYSQLPORT"

    #
    # Create database and users in MySQL.
    #
    # Users do not have to be created first before granting privileges.
    #

mysqlRoot() {
  "$MYSQL_CLIENT" --defaults-file=<(echo -e "[client]\npassword=${MYSQLROOT_PASSWORD}") --default-character-set=utf8mb4 ${MYSQL_OPTS} -u "$MYSQLROOT" -v
}

mysqlRoot <<LimitString
CREATE DATABASE IF NOT EXISTS $DBNAME DEFAULT CHARACTER SET = UTF8MB4 DEFAULT COLLATE = UTF8MB4_UNICODE_CI;
GRANT ALL PRIVILEGES ON $DBNAME.* TO '$DB_ADMIN'@'$MYSQLHOST' IDENTIFIED BY '$DB_ADMIN_PASSWORD';
GRANT SELECT,INSERT,UPDATE,DELETE ON $DBNAME.* TO '$DB_USER'@'$MYSQLHOST' IDENTIFIED BY '$DB_USER_PASSWORD';
FLUSH PRIVILEGES;
LimitString

    #
    # Create database schema
    #

    php "$BASEDIR/db/createdb.php"

fi
