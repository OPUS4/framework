# OPUS 4 Framework Implementation

The project is using Composer for handling dependencies. The OPUS 4 Framework itself is a Composer package
that is used by the OPUS 4 Application. 

The OPUS 4 Framework is written in PHP. It also uses Ant for scripting common development actions. The Ant
script (`build.xml`) is used for automation by a continous integration system.

## Dependencies

The dependencies are declared in composer.json and can be downloaded automatically using 

    composer install

### Database

The database schema can be created using the `createdb.php` script.

    ant create-database

The database access parameters are stored in `tests/config.ini`.

### Solr Server

## Testing

    ant phpunit-fast

or

    vendor/bin/phpunit -c tests

