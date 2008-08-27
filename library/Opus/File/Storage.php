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
     * Hold repository path value.
     *
     * @var string
     */
    protected $repositoryPath = null;

    /**
     * Retrieve an instance of Opus_File_Storage for a specific repository path.
     *
     * @param string $repositoryPath Full qualified path to repository directory.
     * @throws Opus_File_Exception Thrown if the repository path is invalid or not writable.
     * @return Opus_File_Storage Storage component responsible for the given repository path.
     */
    public static function getInstance($repositoryPath) {
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
     * @throws InvalidArgumentException Thrown if path value is empty.
     * @throws Opus_File_Exception Thrown if the repository path is invalid or not writable.
     */
    private function __construct($repositoryPath) {
        if (empty($repositoryPath) === true) {
            throw new InvalidArgumentException('Path value is empty.');
        }
        // Check for a directory
        if (is_dir($repositoryPath) === false) {
            throw new Opus_File_Exception('Committed value is not a valid directory name.');
        }
        if (is_writable($repositoryPath) === false) {
            throw new Opus_File_Exception('Repository directory is not writable.');
        }
        $this->repositoryPath = $repositoryPath;
    }

    /**
     * Checks if array fileInformation contain neccessary keys and values
     *
     * @param array $fileInformation Contains all necessary informations
     * @throws InvalidArgumentException Exception if key not found or value are empty
     * @return void
     */
    private function checkFileInformation(array $fileInformation) {
        $valid_values = array('sourcePath', 'documentId', 'fileName', 'sortOrder', 'publishYear', 'label', 'type', 'language', 'mimeType');
        foreach ($valid_values as $value) {
            if (array_key_exists($value, $fileInformation) === false) {
                throw new InvalidArgumentException($value . ' does not exists.');
            }
            if (empty($fileInformation[$value]) === true) {
                throw new InvalidArgumentException($value . ' is empty.');
            }
        }
    }

    /**
     * Take a concrete file description to transfer a file into the repository and
     * augment it with a meta data record.
     *
     * The associative array has to contain the following keys:
     * - sourcePath     Path of file to import.
     * - documentId     Identifier of associated document.
     * - fileName       Name of file.
     * - sortOrder      Number that determines a sort order between files of a particular document.
     * - publishYear    Year of publishing.
     * - label          Short file description.
     * - type           Dublin Core file type.
     * - language       Locale code representing the content language.
     * - mimeType       MIME type of file contents.
     *
     * @param array $fileInformation File path and meta information.
     * @throws InvalidArgumentException Thrown on input parameter errors.
     * @throws Opus_File_Exception Thrown on missing informations.
     * @return integer Identifier of file in the storage.
     */
    public function store(array $fileInformation) {
        if (empty($fileInformation) === true) {
            throw new InvalidArgumentException('Array with file information is empty.');
        }
        // Check general array structure in a special function
        $this->checkFileInformation($fileInformation);
        if (file_exists($fileInformation['sourcePath']) === false) {
            throw new Opus_File_Exception('Import file does not exists.');
        }
        if (is_writable($fileInformation['sourcePath']) === false) {
            throw new Opus_File_Exception('Import file is not writeable.');
        }
        if (is_int($fileInformation['documentId']) === false ) {
            throw new InvalidArgumentException('documentId is not an integer value.');
        }
        if (is_int($fileInformation['sortOrder']) === false) {
            throw new InvalidArgumentException('sortOrder is not an integer value.');
        }
        if (is_int($fileInformation['publishYear']) === false) {
            throw new InvalidArgumentException('publishYear is not an integer value.');
        }
        $filedb = new Opus_File_DocumentFilesModel();
        $filedb->getAdapter()->beginTransaction();
        try {
            // First: Move uploaded file to destination directory
            $destdir = $this->repositoryPath
                     . DIRECTORY_SEPARATOR
                     . $fileInformation['publishYear']
                     . DIRECTORY_SEPARATOR
                     . $fileInformation['documentId'];
            // Create destination directory if not exists
            if (is_dir($destdir) === false) {
                if (@mkdir($destdir, 0755, true) === false) {
                    throw new Opus_File_Exception('Could not create destination directory.');
                }
            }
            // should be move_upload_file
            if (rename($fileInformation['sourcePath'], $destdir . DIRECTORY_SEPARATOR .  $fileInformation['fileName']) === false) {
                // Throw an exception
                throw new Opus_File_Exception('Could not move file to destination directory.');
            }
            // Second: Try to put file meta data in database
            $file_data = array(
                'documents_id'   => $fileInformation['documentId'],
                'file_path_name' => $fileInformation['publishYear']
                                  . DIRECTORY_SEPARATOR
                                  . $fileInformation['documentId']
                                  . DIRECTORY_SEPARATOR
                                  .  $fileInformation['fileName'],
                'file_label'     => $fileInformation['label'],
                'file_type'      => $fileInformation['type'],
                'file_language'  => $fileInformation['language'],
                'mime_type'      => $fileInformation['mimeType']
                );
            $id = (int) $filedb->insert($file_data);
            $filedb->getAdapter()->commit();
        } catch (Exception $e) {
            // Something is going wrong, restore old data
            $filedb->getAdapter()->rollBack();
            throw new Opus_File_Exception('Error during inserting meta data or file movement: ' . $e->getMessage());
        }
        return $id;
    }

    /**
     * Remove a file specified by the given identifier.
     *
     * @param integer $fileId Identifier of file record.
     * @throws InvalidArgumentException Thrown on invalid identifier argument.
     * @throws Opus_File_Exception If removing failed for any reason.
     * @return void
     */
    public function remove($fileId) {
        if (is_int($fileId) === false) {
            throw new InvalidArgumentException('Identifier is not an integer value.');
        }
        $filedb = new Opus_File_DocumentFilesModel();
        $rows = $filedb->find($fileId)->current();
        if (empty($rows) === true) {
            throw new Opus_File_Exception('Informations about specific entry not found.');
        }
        $filedb->getAdapter()->beginTransaction();
        try {
            $where = $filedb->getAdapter()->quoteInto('document_files_id = ?', $fileId);
            $filedb->delete($where);
            $filedb->getAdapter()->commit();
            // Try to delete the file
            $destfile = $this->repositoryPath . DIRECTORY_SEPARATOR . $rows->file_path_name;
            if (unlink($destfile) === false) {
                throw new Opus_File_Exception('Error occurs during file deleting.');
            }
        } catch (Exception $e) {
            // Something is going wrong, restore old data
            $filedb->getAdapter()->rollBack();
            throw new Opus_File_Exception('Error during deleting meta data or file: ' . $e->getMessage());
        }

    }

    /**
     * Get path to file in repository.
     *
     * @param integer $fileId Identifier of file record.
     * @throws InvalidArgumentException Thrown on invalid identifier argument.
     * @throws Opus_File_Exception Thrown on error.
     * @return string Full qualified path to repository file. Empty if file is not existent.
     */
    public function getPath($fileId) {
        if (is_int($fileId) === false) {
            throw new InvalidArgumentException('Identifier is not an integer value.');
        }
        $filedb = new Opus_File_DocumentFilesModel();
        $rows = $filedb->find($fileId)->current();
        if (empty($rows) === true) {
            throw new Opus_File_Exception('Could not found any data to specific entry.');
        }
        $result = $rows->file_path_name;
        return $result;
    }

    /**
     * Retrieve all identifiers of associated files by passing a document identifier.
     *
     * @param integer $documentId Document identifier.
     * @throws InvalidArgumentException Thrown on invalid identifier argument.
     * @return array Set of file identifiers for a specified documen.
     */
    public function getAllFileIds($documentId) {
        if (is_int($documentId) === false) {
            throw new InvalidArgumentException('Identifier is not an integer value.');
        }
        $result = array();
        $filedb = new Opus_File_DocumentFilesModel();
        $select = $filedb->select()->where('documents_id = ?', $documentId);
        $results = $filedb->fetchAll($select);
        foreach ($results as $key => $value) {
            $result[] = $value->document_files_id;
        }
        return $result;
    }


}