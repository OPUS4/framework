# OPUS 4 Framework Implementation

The project is using Composer for handling dependencies. The OPUS 4 Framework itself is a Composer package
that is used by the OPUS 4 Application. 

The OPUS 4 Framework is written in PHP. It also uses Ant for scripting common development actions. The Ant
script (`build.xml`) is used for automation by a continous integration system.

## Requirements

The system must meet the following basic requirements in order to run the unit tests:

- PHP < 7.2 (because of Zend Framework 1)
- MySQL > 5.1

## Dependencies

The dependencies are declared in `composer.json` and can be downloaded automatically using 

    composer install
    
or 

    php composer.phar install
    
Now the necessary packages are automatically downloaded and installed in the `vendor` directory.

For more information about Composer: https://getcomposer.org

The script `bin/install-composer.sh` can be used to automatically download `composer.phar`, so 
the most recent version can be used. Composer is also available in most Linux distributions. 

## Running the Unit Tests

In order to run the unit tests you need to create database and a configuration for the framework.

### Creating the database

The database schema can be created using the `createdb.php` script.

    ant create-database

The database access parameters are stored in `tests/config.ini` and needs to be configured with the correct user and database name.

### Configuring the Framework

The configuration file (`tests/config.ini`) can be created using the following command. 

    cd tests
    ./configure.sh
    
The script will ask you for values for the placeholders in the configuration template file,
`tests/config.ini.template`.

| Placeholder        | Description                                           |
| ------------------ | ----------------------------------------------------- |
| db.admin.name      | Name of MySQL user with full access to OPUS database. |
| db.params.password | Password for MySQL user.                              |
| db.params.dbname   | Name of MySQL database for OPUS.                      |

### Creating the workspace folders

The workspace directory for testing can be created using 

    ant prepare-workspace

### Testing

Tests can be run using the Composer

    composer test

or executing phpunit directly

    vendor/bin/phpunit -c tests    
    
## Coding Style

The basic formatting of the code can be checked automatically using

    composer cs-check

Most basic styling errors can be fixed automatically using 

    composer cs-fix
