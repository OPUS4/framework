# OPUS 4 Framework Implementation

The project is starting to use Composer, but it also uses Ant the automate testing on
the continuous integration system. 

## Dependencies

The dependencies are declared in composer.json and can be downloaded automatically using 

composer install

### Database

The database schema can be created using the createdb.sh script

ant create-database

The database access parameters have to be put into config.sh.

```
user=
password=
host=
post=
dbname=
```

### Solr Server

## Testing

```
ant phpunit-fast
```

or

```
vender/bin/phpunit -c tests
```
