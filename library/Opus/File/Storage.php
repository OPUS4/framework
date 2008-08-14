<?php
/**
 *
 */


/**
 * Provides methods for storing and retrieving files and their meta data.
 *
 * @category Framework
 * @package  Opus_File
 */
class Opus_File_Storage {

    /**
     * Array mapping repository paths to storage components. Whenever a
     * Opus_File_Storage class gets initialised, a corresponding mapping
     * is added to this array.
     *
     * @var array
     */
    protected static $repositories = null;


    /**
     * Retrieve an instance of Opus_File_Storage for a specific repository path.
     *
     * @param string $repositoryPath Full qualified path to repository directory.
     * @throws Opus_File_Exception Thrown if the repository path is invalid or not writable.
     * @return Opus_File_Storage Storage component responsible for the given repository path.
     */
    static function getInstance($repositoryPath) {
        if (empty(self::$repositories) === true) {
            self::$repositories = array();
        }

        if ( array_key_exists($repositoryPath, self::$repositories) === true ) {
            return self::$repositories[$repositoryPath];
        } else {
            $storage = new Opus_File_Storage($repositoryPath);
            self::$repositories[$repositoryPath] = $storage;
            return $storage;
        }
    }

    /**
     * Initialise the storage component with a valid target repository path.
     *
     * @param string $repositoryPath Full qualified path to repository directory.
     * @throws Opus_File_Exception Thrown if the repository path is invalid or not writable.
     * @return void
     */
    private function __construct($repositoryPath) {

    }

    /**
     * Take a concrete file description to transfer a file into the repository and
     * augment it with a meta data record.
     *
     * The associative array has to contain the following keys:
     * - sourcePath     Path of file to import.
     * - documentId     Identifier of associated document.
     * - sortOrder      Number that determines a sort order between files of a particular document.
     * - label          Short file description.
     * - type           Dublin Core file type.
     * - language       Locale code representing the content language.
     * - mimeType       MIME type of file contents.
     *
     * @param array $fileInformation File path and meta information.
     * @throws InvalidArgumentException Thrown on input parameter errors.
     * @return integer Identifier of file in the storage.
     */
    public function store(array $fileInformation) {
        return 4711;
    }

    /**
     * Remove a file specified by the given identifier.
     *
     * @param integer $fileId Identifier of file record.
     * @throws Opus_File_Exception If removing failed for any reason.
     * @return void
     */
    public function remove($fileId) {

    }

    /**
     * Get path to file in repository.
     *
     * @param integer $fileId Identifier of file record.
     * @return string Full qualified path to repository file. Empty if file is not existent.
     */
    public function getPath($fileId) {
        return '/rep/files';
    }

    /**
     * Retrieve all identifiers of associated files by passing a document identifier.
     *
     * @param integer $documentId Document identifier.
     * @return array Set of file identifiers for a specified documen.
     */
    public function getAllFileIds($documentId) {
        return array();
    }


}