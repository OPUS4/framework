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
 * @author      Thoralf Klein <thoralf.klein@zib.de>
 * @author      Jens Schwidder <schwidder@zib.de>
 * @copyright   Copyright (c) 2008-2019, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace Opus\Document\Plugin;

use Opus\Db\TableGateway;
use Opus\Document;
use Opus\Document\DocumentException;
use Opus\Model\ModelInterface;
use Opus\Model\Plugin\AbstractPlugin;
use Opus\Model\Plugin\PluginInterface;

/**
 * Plugin for generating sequence numbers on published documents.
 *
 * Generates an identifier with a number counting up from the highest existing
 * number in the database.
 *
 * Configuration:
 * - "sequence.identifier_type" : Defines the type of identifier
 *
 * The identifier is only generated for published documents.
 * If an identifier of that type already exists no new identifier is generated.
 *
 * If the identifier is deleted in the administration and the document stored a new
 * one will be generated.
 *
 * @category    Framework
 * @package     Opus\Document\Plugin
 * @uses        AbstractPlugin
 *
 * TODO The operation isn't atomic. What happens if number already exists?
 *      Probably nothing the same number will be stored twice.
 * TODO use function to get logger
 * TODO use function to get config object
 */
class SequenceNumber extends AbstractPlugin
{

    /**
     * @see PluginInterface::postStore
     */
    public function postStoreInternal(ModelInterface $model)
    {
        $log = Log::get();
        $log->debug('Opus\Document\Plugin\SequenceNumber::postStore() with id ' . $model->getId());

        if (! ($model instanceof Document)) {
            $message = 'Model is not an Opus\Document. Aborting...';
            $log->err($message);
            throw new DocumentException($message);
        }

        if ($model->getServerState() !== 'published') {
            $message = 'Skip documents not in ServerState *published* ...';
            $log->info($message);
            return;
        }

        $config = Config::get();
        if (! isset($config, $config->sequence->identifier_type)) {
            $log->debug('Sequence auto creation is not configured. skipping...');
            return;
        }
        $sequence_type = trim($config->sequence->identifier_type);

        $sequence_ids = [];

        foreach ($model->getIdentifier() as $id) {
            if ($id->getType() === $sequence_type) {
                $sequence_ids[] = trim($id->getValue());
            }
        }

        if (count($sequence_ids) > 0) {
            $message = "Sequence IDs for type '$sequence_type' already exists: "
                . implode(",", $sequence_ids);
            $log->debug($message);
            return;
        }

        // Create and initialize new sequence number...
        $next_sequence_number = $this->fetchNextSequenceNumber($sequence_type);

        $model->addIdentifier()
            ->setType($sequence_type)
            ->setValue($next_sequence_number);

        return;
    }

    /**
     * Small helper method to fetch next sequence number from database.
     */
    protected function fetchNextSequenceNumber($sequence_type)
    {
        $id_table = TableGateway::getInstance('Opus\Db\DocumentIdentifiers');
        $select = $id_table->select()->from($id_table, '')
                ->columns(new \Zend_Db_Expr('MAX(CAST(value AS SIGNED))'))
                ->where("type = ?", $sequence_type)
                ->where("value REGEXP '^[[:digit:]]+$'");
        $last_sequence_id = (int) $id_table->getAdapter()->fetchOne($select);

        if (is_null($last_sequence_id) or $last_sequence_id <= 0) {
            return 1;
        }

        return $last_sequence_id + 1;
    }
}
