# OPUS 4 Framework Implementation

The project is using Composer for handling dependencies. The OPUS 4 Framework itself is a Composer package
that is used by the OPUS 4 Application. 

The OPUS 4 Framework is written in PHP. It also uses Ant for scripting common development actions. The Ant
script (`build.xml`) is used for automation by a continous integration system.

## Dependencies

The system must meet some basic requirements in order to set up framework and the tests:

- PHP < 7.2 (Because of Zend Framework 1)
- MySQL > 5.1

The dependencies are declared in composer.json and can be downloaded automatically using 

    composer install
    
Or 

    php composer.phar install
    
Now the necessary packages are automatically downloaded and installed in the vendor directory of the instance.

### Configuration

The config file (config.ini) for the testing configurations in the framework can be created using

    ant prepare-config

### Database

The database schema can be created using the `createdb.php` script.

    ant create-database

The database access parameters are stored in `tests/config.ini` and needs to be configured with the correct user and database name.

### Directory 

The workspace directory for testing can be created by using 

    ant prepare-workspace

## Testing

Tests can be run using the following ant command

    ant phpunit-fast

or

    vendor/bin/phpunit -c tests
    
Tests can also be run using the Composer

    composer test
    
## Coding Style

The coding style can be checked using `composer cs-check` and `composer cs-fix` to fix the style errors.

