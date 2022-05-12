#!/usr/bin/env bash

root_pwd=${root_pwd:-root}
admin_name=${admin_name:-opus4admin}
admin_pwd=${admin_pwd:-opusadminpwd}
user_name=${user_name:-opus4}
user_pwd=${user_pwd:-opususerpwd}
database_name=${database_name:-opusdb}

while [ $# -gt 0 ]; do
    if [[ $1 == "--"* ]]; then
        v="${1/--/}"
        declare "$v"="$2"
        shift
    fi
    shift
done

if [[ -z $root_pwd ]]; then
    echo "Missing parameter --root_pwd"
    exit 1;
elif [[ -z $admin_name ]]; then
    echo "Missing parameter --admin_name"
    exit 1;
elif [[ -z $admin_pwd ]]; then
    echo "Missing parameter --admin_pwd"
    exit 1;
elif [[ -z $user_name ]]; then
    echo "Missing parameter --user_name"
    exit 1;
elif [[ -z $user_pwd ]]; then
    echo "Missing parameter --user_pwd"
    exit 1;
elif [[ -z $database_name ]]; then
    echo "Missing parameter --database_name"
    exit 1;
fi

export MYSQL_PWD=$root_pwd && mysql --default-character-set=utf8 -h 'localhost' -P '3306' -u 'root' -v -e "CREATE DATABASE IF NOT EXISTS $database_name DEFAULT CHARACTER SET = UTF8 DEFAULT COLLATE = UTF8_GENERAL_CI; CREATE USER IF NOT EXISTS '$admin_name'@'localhost' IDENTIFIED WITH mysql_native_password BY '$admin_pwd'; GRANT ALL PRIVILEGES ON $database_name.* TO '$admin_name'@'localhost'; CREATE USER IF NOT EXISTS '$user_name'@'localhost' IDENTIFIED WITH mysql_native_password BY '$user_pwd'; GRANT SELECT,INSERT,UPDATE,DELETE ON $database_name.* TO '$user_name'@'localhost'; FLUSH PRIVILEGES;"
