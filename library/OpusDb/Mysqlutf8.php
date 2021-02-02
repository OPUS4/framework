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
 * @category    Framework
 * @package     Opus\Db\Adapter\Pdo
 * @author      Ralf Claussnitzer (ralf.claussnitzer@slub-dresden.de)
 * @author      Henning Gerhardt (henning.gerhardt@slub-dresden.de)
 * @author      Thoralf Klein <thoralf.klein@zib.de>
 * @copyright   Copyright (c) 2008-2018, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

use Opus\Config;
use Opus\Database;
use Opus\Log;

/**
 * Extend standard PDO MySQL adapter to use UTF-8 strings by passing
 * 'SET NAMES uft8' via query. This adapter can be found by \Zend_Db::factory()
 * as 'PDO_MYSQLUTF8' adapter.
 *
 * @category    Framework
 * @package     Opus\Db
 *
 */
class OpusDb_Mysqlutf8 extends \Zend_Db_Adapter_Pdo_Mysql
{
    /**
     * Number of transaction start attempts.
     *
     * @var int
     */
    protected $_runningTransactions = 0;

    /**
     * Modifies standard connection behavior to use UTF-8.
     *
     * @return void
     */
    protected function _connect()
    {
        // if we already have a PDO object, no need to re-connect.
        if (is_null($this->_connection) === false) {
            return;
        }

        $config = Config::get();
        if (isset($config->db->debug) && filter_var($config->db->debug, FILTER_VALIDATE_BOOLEAN)) {
            $logger = Log::get();
            $logger->debug("Mysqlutf8: created new adapter");

            $backtrace = debug_backtrace(false);
            foreach ($backtrace as $row) {
                $file     = array_key_exists('file', $row) ? $row['file'] : '_no_file_)';
                $line     = array_key_exists('line', $row) ? $row['line'] : '0';
                $function = array_key_exists('function', $row) ? $row['function'] : '_no_function_';

                $optional = '';
                if ($row['function'] == 'query' && ! is_null($row['args'][0])) {
                    $optional = "(" . $row['args'][0] . ")";
                }

                $logger->debug("Mysqlutf8: $file:$line at $function $optional");
            }
        }

        parent::_connect();

        // set connection to default character set ('utf8mb4')
        $this->query('SET NAMES ' . Database::DEFAULT_CHARACTER_SET);

        // Enable "strict" mode on all transactional tables to avoid silent
        // truncation of inserted/updated data.  See ticket [OPUSVIER-2111].
        $this->query("SET sql_mode = 'STRICT_TRANS_TABLES'");
    }

    /**
     * Override to implement transaction start counting.
     *
     * If a transaction is already running, no new one will be started.
     *
     * @return bool True
     */
    protected function _beginTransaction()
    {
        if ($this->_runningTransactions < 1) {
            $query = $this->getProfiler()->queryStart('real_BEGIN', \Zend_Db_Profiler::TRANSACTION);
            parent::_beginTransaction();
            $this->getProfiler()->queryEnd($query);
        }
        $this->_runningTransactions++;
        return true;
    }

    /**
     * Decrease transaction counter and issue commit.
     *
     * @return bool True
     */
    protected function _commit()
    {
        if ($this->_runningTransactions < 2) {
            // Check for values < 2 to not mask errors on misuse of commit()
            $query = $this->getProfiler()->queryStart('real_COMMIT', \Zend_Db_Profiler::TRANSACTION);
            parent::_commit();
            $this->getProfiler()->queryEnd($query);
        }
        $this->_runningTransactions--;
        return true;
    }

    /**
     * Decrease transaction counter and issue rollback.
     *
     * @return bool True
     */
    protected function _rollback()
    {
        if ($this->_runningTransactions < 2) {
            // Check for values < 2 to not mask errors on misuse of rollback()
            parent::_rollback();
        }
        $this->_runningTransactions--;
        return true;
    }
}
