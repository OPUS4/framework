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

# Define variables and their default values
root_pwd='root'
admin_name='opus4admin'
admin_pwd='opusadminpwd'
user_name='opus4'
user_pwd='opususerpwd'
database_name='opusdb'

# Print command line help to stderr
display_help() {
    echo "Usage: $0 [OPTIONS]" >&2
    echo
    echo "Options (default values given in parentheses):"
    echo
    echo "  --root_pwd        Root password ($root_pwd)"
    echo "  --admin_name      Admin name ($admin_name)"
    echo "  --admin_pwd       Admin password ($admin_pwd)"
    echo "  --user_name       User name ($user_name)"
    echo "  --user_pwd        User password ($user_pwd)"
    echo "  --database_name   Database name ($database_name)"
    echo
    echo "Examples:"
    echo
    echo "  $0"
    echo "  $0 --help"
    echo "  $0 --admin_pwd ADMINPWD --user_pwd USERPWD"
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

export MYSQL_PWD=$root_pwd && mysql --default-character-set=utf8 -h 'localhost' -P '3306' -u 'root' -v -e "CREATE DATABASE IF NOT EXISTS $database_name DEFAULT CHARACTER SET = UTF8 DEFAULT COLLATE = UTF8_GENERAL_CI; DROP USER IF EXISTS '$admin_name'@'localhost'; CREATE USER '$admin_name'@'localhost' IDENTIFIED WITH mysql_native_password BY '$admin_pwd'; GRANT ALL PRIVILEGES ON $database_name.* TO '$admin_name'@'localhost'; DROP USER IF EXISTS '$user_name'@'localhost'; CREATE USER '$user_name'@'localhost' IDENTIFIED WITH mysql_native_password BY '$user_pwd'; GRANT SELECT,INSERT,UPDATE,DELETE ON $database_name.* TO '$user_name'@'localhost'; FLUSH PRIVILEGES;"
