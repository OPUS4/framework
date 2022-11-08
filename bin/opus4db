#!/usr/bin/env bash

# This file is part of OPUS. The software OPUS has been originally developed
# at the University of Stuttgart with funding from the German Research Net,
# the Federal Department of Higher Education and Research and the Ministry
# of Science, Research and the Arts of the State of Baden-Wuerttemberg.
#
# OPUS 4 is a complete rewrite of the original OPUS software and was developed
# by the Stuttgart University Library, the Library Service Center
# Baden-Wuerttemberg, the Cooperative Library Network Berlin-Brandenburg,
# the Saarland University and State Library, the Saxon State Library -
# Dresden State and University Library, the Bielefeld University Library and
# the University Library of Hamburg University of Technology with funding from
# the German Research Foundation and the European Regional Development Fund.
#
# LICENCE
# OPUS is free software; you can redistribute it and/or modify it under the
# terms of the GNU General Public License as published by the Free Software
# Foundation; either version 2 of the Licence, or any later version.
# OPUS is distributed in the hope that it will be useful, but WITHOUT ANY
# WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
# FOR A PARTICULAR PURPOSE. See the GNU General Public License for more
# details. You should have received a copy of the GNU General Public License
# along with OPUS; if not, write to the Free Software Foundation, Inc., 51
# Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
#
# @copyright   Copyright (c) 2022, OPUS 4 development team
# @license     http://www.gnu.org/licenses/gpl.html General Public License

#
# Script to set up an OPUS 4 database and database users with optional
# parameters to provide custom database user names & passwords and/or a
# custom database name. If no optional parameters are given, default values
# will be used.
#
# IMPORTANT: This script is also used by other OPUS 4 packages that require
#            a database for testing.
#
# TODO merge with setup.sh (get rid of setup.sh or remove redundancy)
# TODO creating database.ini file with config ? (replacing/removing config.ini.template)
#      config.ini OR database.ini should just be database options - Rest goes into test.ini and application.ini
# TODO creating schema
#
# TODO make this script available in other projects in vendor/bin (setup in composer.json)
#

# Define variables and their default values
sqluser='root'
admin='opus4admin'
adminpwd='opusadminpwd'
user='opus4'
userpwd='opususerpwd'
dbname='opusdb'
host='localhost'
port='3306'
mysql='mysql'

# Print command line help to stderr
display_help() {
    echo "Usage: $0 [OPTIONS]" >&2
    echo
    echo "The script will ask for the SQL root password interactively, unless it is specified as option."
    echo
    echo "Options (default values given in parentheses):"
    echo
    echo "  --help        (-h)    Print out help"
    echo
    echo "  --sqluser             SQL root user      ($sqluser)"
    echo "  --sqlpwd              SQL root password"
    echo "  --admin               Admin name         ($admin)"
    echo "  --adminpwd            Admin password     ($adminpwd)"
    echo "  --user                User name          ($user)"
    echo "  --userpwd             User password      ($userpwd)"
    echo "  --dbname              Database name      ($dbname)"
    echo "  --host                MySQL host         ($host)"
    echo "  --port                MySQL port         ($port)"
    echo "  --mysql               MySQL client       ($mysql)"
    echo
    echo "Examples:"
    echo
    echo "  $0"
    echo "  $0 --help"
    echo "  $0 --adminpwd ADMINPWD --userpwd USERPWD"
    echo "  $0 -i --dbname opusdbtest"
    echo
    exit 1
}

# Display command line help if '-h' or '--help' is given as first option
if [ $# -gt 0 ]; then
    if [[ $1 == "-h" || $1 == "--help" ]]; then
        display_help
        exit 0
    fi
fi

# Parse any other command line options
while [ $# -gt 0 ]; do
    if [[ $1 == "--"* ]]; then # only deal with long options
        if [[ -n "$2" && $2 != "--"* ]]; then # ignore options without a value
            # Create variable name from option name
            v="${1/--/}" # uses parameter expansion removing '--'

            # Read option value into variable
            declare "$v"="$2"

            # Process next option
            shift
        fi
    fi
    shift
done

# Querying MySQL root password
[[ -z $sqlpwd ]] && read -p "MySQL root user password: " -s sqlpwd

sql=$(cat <<-ENDSTRING
    CREATE DATABASE IF NOT EXISTS $dbname DEFAULT CHARACTER SET = UTF8 DEFAULT COLLATE = UTF8_GENERAL_CI;
    CREATE USER IF NOT EXISTS '$admin'@'localhost' IDENTIFIED WITH mysql_native_password BY '${adminpwd}';
    GRANT ALL PRIVILEGES ON $dbname.* TO '$admin'@'localhost';
    CREATE USER IF NOT EXISTS '$user'@'localhost' IDENTIFIED WITH mysql_native_password BY '$userpwd';
    GRANT SELECT,INSERT,UPDATE,DELETE ON $dbname.* TO '$user'@'localhost';
    FLUSH PRIVILEGES;
ENDSTRING
)

export MYSQL_PWD=$sqlpwd && mysql --default-character-set=utf8 -h $host -P $port -u $sqluser -v -e "$sql"
