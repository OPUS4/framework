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
 * @copyright   Copyright (c) 2011, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace Opus;

use Opus\Common\EnrichmentKeyInterface;
use Opus\Common\EnrichmentKeyRepositoryInterface;
use Opus\Common\Model\AbstractFieldType;
use Opus\Common\Model\FieldTypeInterface;
use Opus\Common\Model\ModelException;
use Opus\Db\TableGateway;
use Opus\Model\AbstractDb;
use Opus\Model\Field;
use Throwable;
use Zend_Db_Select_Exception;
use Zend_Db_Table_Row_Exception;
use Zend_Validate_NotEmpty;

/**
 * Domain model for enrichments in the Opus framework
 *
 * @uses        \Opus\Model\AbstractModel
 *
 * @method void setName(string $string)
 * @method string getName()
 * @method void setType(string $type)
 * @method string getType()
 * @method void setOptions(string $options)
 * @method string getOptions()
 *
 * phpcs:disable
 */
class EnrichmentKey extends AbstractDb implements EnrichmentKeyInterface, EnrichmentKeyRepositoryInterface
{
    /**
     * Specify the table gateway.
     *
     * @var string Classname of \Zend_DB_Table to use if not set in constructor.
     */
    protected static $tableGatewayClass = Db\EnrichmentKeys::class;

    /**
     * Optional cache for database results.
     *
     * @var null
     */
    private static $allEnrichmentKeys;

    /**
     * Retrieve all Opus\EnrichmentKeys instances from the database. If $reload
     * is set to false, we reuse the list of all enrichment keys if we previously
     * loaded it from the database.
     *
     * @param bool $reload if true, reload enrichment keys from database
     * @return array Array of Opus\EnrichmentKeys objects.
     */
    public function getAll($reload = true)
    {
        if ($reload || self::$allEnrichmentKeys === null) {
            // cache database result to save database queries later
            self::$allEnrichmentKeys = self::getAllFrom(self::class, Db\EnrichmentKeys::class);
        }

        return self::$allEnrichmentKeys;
    }

    /**
     * Initialize model with the following fields:
     * - Name
     * - Type
     * - Options
     */
    protected function init()
    {
        $name = new Field('Name');
        $name->setMandatory(true)
                ->setValidator(new Zend_Validate_NotEmpty());
        $this->addField($name);

        $field = new Field('Type');
        $this->addField($field);

        $field = new Field('Options');
        $this->addField($field);
    }

    /**
     * ALTERNATE CONSTRUCTOR: Retrieve Opus\EnrichmentKey instance by name.  Returns
     * null if name is null *or* nothing found.
     *
     * @param  null|string $name
     * @return EnrichmentKey
     */
    public function fetchByName($name = null)
    {
        if (false === isset($name)) {
            return;
        }

        $table  = TableGateway::getInstance(self::$tableGatewayClass);
        $select = $table->select()->where('name = ?', $name);
        $row    = $table->fetchRow($select);

        if (isset($row)) {
            return new EnrichmentKey($row);
        }

        return;
    }

    /**
     * Returns name of an enrichmentkey.
     *
     * @see \Opus\Model\Abstract#getDisplayName()
     */
    public function getDisplayName()
    {
        return $this->getName();
    }

    /**
     * Retrieve all Opus\EnrichmentKeys which are referenced by at
     * least one document from the database.
     *
     * @return array Array of Opus\EnrichmentKeys objects.
     */
    public function getAllReferenced()
    {
        $table  = TableGateway::getInstance(Db\DocumentEnrichments::class);
        $db     = $table->getAdapter();
        $select = $db->select()->from('document_enrichments');
        $select->reset('columns');
        $select->columns("key_name")->distinct(true);
        return $db->fetchCol($select);
    }

    /**
     * Returns a printable version of the current options if set, otherwise null.
     *
     * @return
     */
    public function getOptionsPrintable()
    {
        $typeObj = $this->getEnrichmentType();

        if ($typeObj === null) {
            return null;
        }

        $typeObj->setOptions($this->getOptions());
        return $typeObj->getOptionsAsString();
    }

    /**
     * Gibt ein Objekt des zugehörigen Enrichment-Types zurück, oder null, wenn
     * für den Enrichment-Key kein Typ festgelegt wurde (bei Altdaten) oder der
     * Typ aus einem anderen Grund nicht geladen werden konnte.
     *
     * @return FieldTypeInterface
     */
    public function getEnrichmentType()
    {
        if ($this->getType() === null || $this->getType() === '') {
            return null;
        }

        $typeClass = AbstractFieldType::TYPES_NAMESPACE . '\\' . $this->getType();
        try {
            $typeObj = new $typeClass();
        } catch (Throwable $ex) {
            $this->getLogger()->err('could not find enrichment type class ' . $typeClass);
            return null;
        }
        $typeObj->setOptions($this->getOptions());
        return $typeObj;
    }

    /**
     * Ändert den Namen des vorliegenden EnrichmentKey in allen Dokumenten, die
     * Enrichments mit diesem Namen verwenden.
     *
     * Achtung: diese Methode ändert *nicht* den Namen des EnrichmentKeys in der
     * Tabelle enrichmentkeys.
     *
     * @param string        $newName neuer Name des EnrichmentKey
     * @param string | null $oldName ursprünglicher Name des EnrichmentKey, wenn null, dann
     *                      wird der aktuelle Name des EnrichmentKey verwendet
     */
    public function rename($newName, $oldName = null)
    {
        if ($oldName === null) {
            $oldName = $this->getName();
        }
        if ($oldName === $newName) {
            // keine Umbenennung erforderlich
            return;
        }
        $table                    = TableGateway::getInstance(Enrichment::getTableGatewayClass());
        $db                       = $table->getAdapter();
        $renameEnrichmentKeyQuery = ' UPDATE document_enrichments '
            . ' SET key_name = ?'
            . ' WHERE key_name = ?;';
        $db->query($renameEnrichmentKeyQuery, [$newName, $oldName]);
    }

    /**
     * Löscht alle Enrichments aus den Dokumenten, die den Namen des vorliegenden
     * EnrichmentKey verwenden.
     *
     * Achtung: diese Methode löscht *nicht* den EnrichmentKey aus der Tabelle
     * enrichmentkeys.
     */
    public function deleteFromDocuments()
    {
        $table                    = TableGateway::getInstance(Enrichment::getTableGatewayClass());
        $db                       = $table->getAdapter();
        $deleteEnrichmentKeyQuery = ' DELETE FROM document_enrichments WHERE key_name = ?;';
        $db->query($deleteEnrichmentKeyQuery, $this->getName());
    }

    /**
     * Beim Speichern eines bestehenden EnrichmentKeys wird im Falle einer Namensänderung
     * der Name des EnrichmentKeys in allen Enrichments, die den EnrichmentKey referenzieren,
     * aktualisiert (kaskadierende Namensänderung).
     *
     * @return mixed|void
     * @throws ModelException
     * @throws Zend_Db_Table_Row_Exception
     */
    public function store()
    {
        $oldName = $this->getTableRow()->__get('name');
        $this->rename($this->getName(), $oldName);
        return parent::store();
    }

    /**
     * Das Löschen eines EnrichmentKeys kaskadiert standardmäßig auf alle Enrichments, die
     * den EnrichmentKey referenzieren, d.h. die entsprechenden Enrichments werden aus den
     * Dokumenten gelöscht.
     *
     * Ist der Parameter $cascade auf false gesetzt, so erfolgt keine kaskadierende Löschoperation.
     *
     * @param bool $cascade wenn false, dann kaskadiert die Löschoperation nicht
     * @throws ModelException
     */
    public function delete($cascade = true)
    {
        if ($cascade) {
            $this->deleteFromDocuments();
        }
        parent::delete();
    }

    /**
     * Returns names of all enrichment keys.
     *
     * @return array
     * @throws Zend_Db_Select_Exception
     */
    public function getKeys()
    {
        $table  = TableGateway::getInstance(Db\EnrichmentKeys::class);
        $db     = $table->getAdapter();
        $select = $db->select()->from('enrichmentkeys');
        $select->reset('columns');
        $select->columns('name');
        return $db->fetchCol($select);
    }
}
