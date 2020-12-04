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
# Script for creating OPUS 4 database.
#
# As part of the installation this script creates a database using the information
# provided in the OPUS 4 configuration files.
#
# Parameters can be used to specify a different database name and other information.
#

set -e

MYSQL_CLIENT='/usr/bin/mysql'

SCRIPT_NAME="`basename "$0"`"
SCRIPT_NAME_FULL="`readlink -f "$0"`"
SCRIPT_PATH="`dirname "$SCRIPT_NAME_FULL"`"
BASEDIR="$(dirname "$SCRIPT_PATH")"

# Parse command line options

while getopts ":c:" opt; do
  case $opt in
    c) OPUS_CONF="$OPTARG"
    ;;
  esac
done

OPUS_CONF="${OPUS_CONF:-config.ini}"
OPUS_CONSOLE_CONF="${OPUS_CONSOLE_CONF:-console.ini}"

#
# Prompt for database parameters
#

echo
echo "Database configuration"
echo

[[ -z $DBNAME ]] && read -p "New OPUS Database Name [opusdb]: "           DBNAME
[[ -z $DB_ADMIN ]] && read -p "New OPUS Database Admin Name [opus4admin]: " DB_ADMIN

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

[[ -z $DB_USER ]] && read -p "New OPUS Database User Name [opus4]: "       DB_USER

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
DBNAME="${DBNAME:-opusdb}"
DB_ADMIN="${DB_ADMIN:-opus4admin}"
DB_USER="${DB_USER:-opus4}"

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
