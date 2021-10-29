<?php

/**
 * This file is part of OPUS. The software OPUS has been originally developed
 * at the University of Stuttgart with funding from the German Research Net,
 * the Federal Department of Higher Education and Research and the Ministry
 * of Science, Research and the Arts of the State of Baden-Wuerttemberg.
 *
 * OPUS 4 is a complete rewrite of the original OPUS software and was developed
 * by the Stuttgart University Library, the Library Service Center
 * Baden-Wuerttemberg, the Cooperative Library Network Berlin-Brandenburg,
 * the Saarland University and State Library, the Saxon State Library -
 * Dresden State and University Library, the Bielefeld University Library and
 * the University Library of Hamburg University of Technology with funding from
 * the German Research Foundation and the European Regional Development Fund.
 *
 * LICENCE
 * OPUS is free software; you can redistribute it and/or modify it under the
 * terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the Licence, or any later version.
 * OPUS is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU General Public License for more
 * details. You should have received a copy of the GNU General Public License
 * along with OPUS; if not, write to the Free Software Foundation, Inc., 51
 * Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 *
 * @copyright   Copyright (c) 2008-2010, OPUS 4 development team
 * @copyright   Copyright (c) 2010 Saechsische Landesbibliothek - Staats- und Universitaetsbibliothek Dresden (SLUB)
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 *
 * @category    Framework
 * @package     Opus\Model
 * @author      Thoralf Klein <thoralf.klein@zib.de>
 * @author      Henning Gerhardt <henning.gerhardt@slub-dresden.de>
 */

namespace Opus\Storage;

use Exception;
use finfo;
use Opus\Util\File as FileUtil;

use function class_exists;
use function copy;
use function count;
use function file_exists;
use function filesize;
use function function_exists;
use function getcwd;
use function glob;
use function is_dir;
use function is_executable;
use function is_file;
use function is_readable;
use function is_writable;
use function mime_content_type;
use function mkdir;
use function preg_match;
use function rename;
use function rmdir;
use function sprintf;
use function unlink;

use const FILEINFO_MIME_TYPE;

/**
 * phpcs:disable
 */
class File
{
    /**
     * Working directory.  All files will be modified relative to this path.
     *
     * @var string
     */
    private $filesDirectory;
    private $subDirectory;

    /**
     * Construct storage object.  The first parameter $directory states the
     * working directory, in which all file modifications will take place.
     *
     * @param null|string $directory
     * @throws StorageException
     */
    public function __construct($directory = null, $subdirectory = null)
    {
        if (! is_dir($directory)) {
            throw new StorageException("Storage directory '$directory' does not exist. (cwd: " . getcwd() . ")");
        }

        if (! is_executable($directory)) {
            throw new StorageException("Storage directory '$directory' is not executable. (cwd: " . getcwd() . ")");
        }

        $this->filesDirectory = FileUtil::addDirectorySeparator($directory);
        $this->subDirectory   = FileUtil::addDirectorySeparator($subdirectory);
    }

    public function getWorkingDirectory()
    {
        return $this->filesDirectory . $this->subDirectory;
    }

    /**
     * Creates subdirectory "$this->workingDirectory/$subdirectory".
     *
     * @param string $subdirectory Subdirectory of working dir to create.
     * @throws StorageException
     * @return bool
     */
    public function createSubdirectory()
    {
        $subFullPath = $this->getWorkingDirectory();

        if (is_dir($subFullPath)) {
            if (! is_readable($this->filesDirectory)) {
                throw new StorageException("Storage directory '$subFullPath' is not readable. (cwd: " . getcwd() . ")");
            }

            if (! is_writable($this->filesDirectory)) {
                throw new StorageException("Storage directory '$subFullPath' is not writable. (cwd: " . getcwd() . ")");
            }

            return true;
        }

        if (true === @mkdir($subFullPath, 0777, true)) {
            return true;
        }

        throw new StorageException('Could not create directory "' . $subFullPath . '"!');
    }

    /**
     * Move a file from source to destination.  The first parameter must be an
     * absolute path to a file outside the working directory.  The second
     * parameter is relative to the working directory.
     *
     * @param string $sourceFile Absolute path.
     * @param string $destintationFile Path relative to workingDirectory.
     * @throws StorageException
     * @return bool
     */
    public function copyExternalFile($sourceFile, $destinationFile)
    {
        $fullDestinationPath = $this->getWorkingDirectory() . $destinationFile;

        if (file_exists($fullDestinationPath)) {
            throw new StorageException('Destination file already exists: "' . $fullDestinationPath . '"!');
        }

        if (true !== @copy($sourceFile, $fullDestinationPath)) {
            throw new StorageException('Could not copy file from "' . $sourceFile . '" to "' . $fullDestinationPath . '"!');
        }

        return true;
    }

    /**
     * Copy a file from source to destination
     *
     * @param string $sourceFile Absolute path.
     * @param string $destintationFile Path relative to workingDirectory.
     * @throws StorageException
     * @throws FileNotFoundException if file does not exist
     * @throws FileAccessException if renaming of file failed
     * @return bool
     */
    public function renameFile($sourceFile, $destinationFile)
    {
        $fullSourcePath      = $this->getWorkingDirectory() . $sourceFile;
        $fullDestinationPath = $this->getWorkingDirectory() . $destinationFile;

        if (false === file_exists($fullSourcePath)) {
            throw new FileNotFoundException($fullSourcePath, 'File to rename "' . $fullSourcePath . '" does not exist!');
        }

        if (false === is_file($fullSourcePath)) {
            throw new StorageException('Tried to rename non-file "' . $fullSourcePath . '; abort"!');
        }

        if (true === @rename($fullSourcePath, $fullDestinationPath)) {
            return true;
        }

        throw new FileAccessException('Could not rename file from "' . $fullSourcePath . '" to "' . $fullDestinationPath . '"!');
    }

    /**
     * Deletes a given file inside the working directory.
     *
     * @param string $file
     * @throws StorageException
     * @throws FileNotFoundException if file does not exist
     * @throws FileAccessException if deleting file failed
     */
    public function deleteFile($file)
    {
        $fullFile = $this->getWorkingDirectory() . $file;
        if (false === file_exists($fullFile)) {
            throw new FileNotFoundException($fullFile, 'File to delete "' . $fullFile . '" does not exist!');
        }

        if (false === is_file($fullFile)) {
            throw new StorageException('Tried to delete non-file "' . $fullFile . '; abort"!');
        }

        if (false === @unlink($fullFile)) {
            throw new FileAccessException('File "' . $fullFile . '" could not be deleted!');
        }

        return;
    }

    /**
     * Deletes current working directory if empty.
     *
     * @throws StorageException If directory is empty but deleting failed.
     * @return bool true on success, false if not found or not empty
     */
    public function removeEmptyDirectory()
    {
        $directory = $this->getWorkingDirectory();

        if (! is_dir($directory)) {
            return false;
        }

        $is_empty = count(glob($directory . "/*")) === 0;
        if (! $is_empty) {
            return false;
        }

        if (false === @rmdir($directory)) {
            throw new StorageException('Empty directory "$directory" could not be deleted!');
        }

        return true;
    }

    /**
     * Determine mime encoding information for a given file.
     * If mime encoding could not be determinated 'application/octet-stream'
     * is returned.
     *
     * @param string $file
     * @return string
     */
    public function getFileMimeEncoding($file)
    {
        $fullFile = $this->getWorkingDirectory() . $file;

        // TODO basically this class should exist - why check? We don't for other classes.
        if (true === class_exists('finfo')) {
            // for PHP >= 5.3.0 or PECL fileinfo >= 0.1.0
            $finfo = new finfo(FILEINFO_MIME_TYPE); // return mime type ala mimetype extension
            if (false !== $finfo) {
                return $finfo->file($fullFile);
            }
        } elseif (function_exists('mime_content_type')) {
            // use mime_content_type for PHP < 5.3.0
            return @mime_content_type($fullFile);
        } else {
            $message = self::class . ": Neither PECL fileinfo, nor mime_content_type could be found.";
            $logger  = Log::get();
            $logger->err($message);

            return $this->getFileMimeTypeFromExtension($file);
        }
    }

    /**
     * Guesses mime type of file based on its file name.
     *
     * @param string $file Name of file, to guess mimetype for.
     * @return string
     *
     * TODO make mapping configurable in file?
     */
    public function getFileMimeTypeFromExtension($file)
    {
        $mimeEncoding = 'application/octet-stream';

        if (preg_match('/\.pdf$/', $file) > 0) {
            $mimeEncoding = "application/pdf";
        } elseif (preg_match('/\.ps$/', $file) > 0) {
            $mimeEncoding = "application/postscript";
        } elseif (preg_match('/\.txt$/', $file) > 0) {
            $mimeEncoding = "text/plain";
        }

        return $mimeEncoding;
    }

    /**
     * Determine size of a given file.
     *
     * @param string $file
     * return integer
     */
    public function getFileSize($file)
    {
        $fullFile = $this->getWorkingDirectory() . $file;
        if (false === file_exists($fullFile)) {
            throw new Exception("File does not exist.");
        }

        // Common workaround for php limitation (2 / 4 GB file size)
        // look at http://php.net/manual/en/function.filesize.php
        // more information
        return sprintf('%u', @filesize($fullFile));
    }
}
