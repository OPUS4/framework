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
 * @copyright   Copyright (c) 2011-2020, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace Opus\Model2;

use Doctrine\ORM\Mapping as ORM;
use Opus\Enrichment\TypeInterface;
use Opus\Model\ModelException;
use Throwable;

/**
 * Domain model for enrichments in the Opus framework
 *
 * @uses \Opus\Model2\AbstractModel
 *
 * @ORM\Entity(repositoryClass="Opus\Db2\EnrichmentKeyRepository")
 * @ORM\Table(name="enrichmentkeys")
 */
class EnrichmentKey extends AbstractModel
{
    // TODO: EnrichmentKey uses "name" as identifer instead of "id".
    // TODO: Because we are still extending AbstractModel which expects the a property called "id",
    // TODO: "id" is mapped to the db column "name"
    /**
     * @ORM\Id
     * @ORM\Column(name="name", type="string", length=191, nullable=false)
     *
     * @var string
     */
    private $id;

    /**
     * @ORM\Column(name="type", type="string", length=255, nullable=true)
     *
     * @var string
     */
    private $type;

    /**
     * @ORM\Column(name="options", type="string", length=255, nullable=true)
     *
     * @var string
     */
    private $options;

    /** @var string */
    private $oldName;

    /**
     * @return int|string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        // TODO: To avoid big changes in AbstractModel the property id is used for the name
        return $this->id;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->oldName = $this->getName();

        // TODO: To avoid big changes in AbstractModel the property id is used for the name
        if (empty($name)) {
            // To avoid an empty string name, because it is the primary key (id).
            // TODO: Find a doctrine annotation or other better solution for this constraint.
            $this->id = null;
        } else {
            $this->id = $name;
        }
    }

    /**
     * Returns the previous name if the name has been set to a new value
     *
     * @return string | null
     */
    private function getOldName()
    {
        return $this->oldName;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param string $options
     */
    public function setOptions($options)
    {
        $this->options = $options;
    }

    /**
     * Returns the relevant properties of the class
     *
     * @return array
     */
    protected static function describe()
    {
        return [
            'Name',
            'Type',
            'Options',
        ];
    }

    /**
     * Retrieve all Opus\EnrichmentKeys instances from the database. If $reload
     * is set to false, we reuse the list of all enrichment keys if we previously
     * loaded it from the database.
     *
     * @return array Array of Opus\EnrichmentKeys objects.
     */
    public static function getAll()
    {
        return self::getRepository()->findAll();
    }

    /**
     * Factory function for retrieve EnrichmentKey instance by name.
     *
     * @param null|string $name Name of EnrichmentKey
     * @return self|null Returns null if $name is null *or* nothing is found
     */
    public static function fetchByName($name = null)
    {
        return self::getRepository()->fetchbyName($name);
    }

    /**
     * Returns name of an enrichmentkey.
     *
     * @return string Name of enrichment key
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
    public static function getAllReferenced()
    {
        return self::getRepository()->getAllReferenced();
    }

    /**
     * Returns a printable version of the current options if set, otherwise null.
     *
     * @return string|null
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
     * @return TypeInterface|null
     */
    public function getEnrichmentType()
    {
        if ($this->getType() === null || $this->getType() === '') {
            return null;
        }

        $typeClass = 'Opus\Enrichment\\' . $this->getType();
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
     * @param string      $newName neuer Name des EnrichmentKey
     * @param string|null $oldName ursprünglicher Name des EnrichmentKey, wenn null, dann
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

        self::getRepository()->rename($newName, $oldName);
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
        self::getRepository()->deleteFromDocuments($this->getName());
    }

    /**
     * Stores/updates EnrichmentKey in database.
     *
     * Beim Speichern eines bestehenden EnrichmentKeys wird im Falle einer Namensänderung
     * der Name des EnrichmentKeys in allen Enrichments, die den EnrichmentKey referenzieren,
     * aktualisiert (kaskadierende Namensänderung).
     *
     * @return mixed
     * @throws ModelException
     */
    public function store()
    {
        $this->rename($this->getName(), $this->getOldName());
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
     */
    public static function getKeys()
    {
        return self::getRepository()->getKeys();
    }

    /**
     * Gets the string representation.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getName();
    }
}