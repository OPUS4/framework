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
 * @copyright   Copyright (c) 2008-2018, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 *
 * @category    Tests
 * @package     Opus
 * @author      Gunar Maiwald <maiwald@zib.de>
 * @author      Jens Schwidder <schwidder@zib.de>
 */

namespace OpusTest;

use Opus\Common\Model\ModelException;
use Opus\Document;
use Opus\EnrichmentKey;
use OpusTest\TestAsset\TestCase;

use function count;

/**
 * Test cases for class Opus\EnrichmentKeyTest .
 */
class EnrichmentKeyTest extends TestCase
{
    /** @var Document */
    private $doc;

     /** @var EnrichmentKey */
    private $unreferencedEnrichmentKey;

    /** @var EnrichmentKey */
    private $referencedEnrichmentKey;

    public function setUp()
    {
        parent::setUp();

        $this->clearTables(false, [
            'enrichmentkeys',
            'document_enrichments',
        ]);

        $this->unreferencedEnrichmentKey = new EnrichmentKey();
        $this->unreferencedEnrichmentKey->setName('foo');
        $this->unreferencedEnrichmentKey->store();

        $this->referencedEnrichmentKey = new EnrichmentKey();
        $this->referencedEnrichmentKey->setName('bar');
        $this->referencedEnrichmentKey->store();

        $this->doc = new Document();
        $this->doc->addEnrichment()->setKeyName('bar')->setValue('value');
        $this->doc->store();
    }

    /**
     * CREATE
     */
    public function testStoreEnrichmentKey()
    {
        $ek = new EnrichmentKey();
        $ek->setName('baz');
        $ek->setType('type');
        $ek->setOptions('options');
        $ek->store();

        $ek = new EnrichmentKey('baz');
        $this->assertNotNull($ek);
        $this->assertEquals('baz', $ek->getName());
        $this->assertEquals('type', $ek->getType());
        $this->assertEquals('options', $ek->getOptions());
        $this->assertEquals(3, count(EnrichmentKey::getAll()));
        $this->assertEquals(1, count(EnrichmentKey::getAllReferenced()));
    }

    public function testStoreEqualEnrichmentKey()
    {
        $ek = new EnrichmentKey();
        $ek->setName('foo');
        $this->setExpectedException(ModelException::class);
        $ek->store();
        $this->assertEquals(2, count(EnrichmentKey::getAll()));
        $this->assertEquals(1, count(EnrichmentKey::getAllReferenced()));
    }

    public function testStoreEmptyEnrichmentKey()
    {
        $ek = new EnrichmentKey();
        $ek->setName('');
        $this->setExpectedException(ModelException::class);
        $ek->store();
        $this->assertEquals(2, count(EnrichmentKey::getAll()));
        $this->assertEquals(1, count(EnrichmentKey::getAllReferenced()));
    }

    public function testStoryUnsetEnrichmentKey()
    {
        $ek = new EnrichmentKey();
        $this->setExpectedException(ModelException::class);
        $ek->store();
        $this->assertEquals(2, count(EnrichmentKey::getAll()));
        $this->assertEquals(1, count(EnrichmentKey::getAllReferenced()));
    }

    /**
     * DELETE
     */
    public function testDeleteUnreferencedEnrichmentKey()
    {
        $this->assertCount(2, EnrichmentKey::getAll());
        $this->assertCount(1, EnrichmentKey::getAllReferenced());

        $this->unreferencedEnrichmentKey->delete();

        $this->assertCount(1, EnrichmentKey::getAll());
        $this->assertCount(1, EnrichmentKey::getAllReferenced());
    }

    /**
     * Bereits in Verwendung befindliche EnrichmentKeys dürfen trotzdem gelöscht werden.
     * Die Löschoperation kaskadiert auf die Enrichments in den Dokumenten, die den zu
     * löschenden EnrichmentKey verwenden. Solche Enrichments werden entfernt.
     */
    public function testDeleteReferencedEnrichmentKey()
    {
        $this->assertCount(2, EnrichmentKey::getAll());
        $this->assertCount(1, EnrichmentKey::getAllReferenced());

        $this->referencedEnrichmentKey->delete(); // mit Kaskadierung

        $this->assertCount(1, EnrichmentKey::getAll());
        $this->assertCount(0, EnrichmentKey::getAllReferenced());
    }

    /**
     * Bereits in Verwendung befindliche EnrichmentKeys dürfen trotzdem gelöscht werden.
     * Beim Löschen des EnrichmentKeys soll die Operation *nicht* auf die Enrichments in
     * den Dokumenten, die den zu löschenden EnrichmentKey verwenden, kaskadieren.
     */
    public function testDeleteReferencedEnrichmentKeyWithoutCascading()
    {
        $this->assertCount(2, EnrichmentKey::getAll());
        $this->assertCount(1, EnrichmentKey::getAllReferenced());

        $this->referencedEnrichmentKey->delete(false);

        $this->assertCount(1, EnrichmentKey::getAll());

        // obwohl der EnrichmentKey gelöscht wurde, wird er noch in Dokumenten verwendet
        $referencedEnrichmentKeys = EnrichmentKey::getAllReferenced();
        $this->assertCount(1, $referencedEnrichmentKeys);
        $this->assertEquals($this->referencedEnrichmentKey->getName(), $referencedEnrichmentKeys[0]);
    }

    /**
     * READ
     */
    public function testReadEnrichmentKey()
    {
        foreach (['foo', 'bar'] as $name) {
            $ek = new EnrichmentKey($name);
            $ek->setType('type');
            $ek->setOptions('options');

            $this->assertEquals($name, $ek->getName());
            $this->assertEquals('type', $ek->getType());
            $this->assertEquals('options', $ek->getOptions());
        }
    }

    /**
     * UPDATE
     */
    public function testUpdateUnreferencedEnrichmentKey()
    {
        $newName = 'baz';
        $this->assertNotEquals($newName, $this->unreferencedEnrichmentKey->getName());
        $this->unreferencedEnrichmentKey->setName($newName);
        $this->unreferencedEnrichmentKey->store();
        $this->assertEquals($newName, $this->unreferencedEnrichmentKey->getName());
    }

    /**
     * Der Name eines bereits in Verwendung befindlichen EnrichmentKeys darf geändert werden.
     * Es wird dann der Name des EnrichmentKeys in allen Dokumenten angepasst, die den
     * EnrichmentKey verwenden (kaskadierende Operation).
     */
    public function testUpdateReferencedEnrichmentKey()
    {
        $newName = 'baz';
        $this->assertNotEquals($newName, $this->referencedEnrichmentKey->getName());
        $this->referencedEnrichmentKey->setName($newName);
        $this->referencedEnrichmentKey->store();

        $doc        = new Document($this->doc->getId());
        $enrichment = $doc->getEnrichment()[0];
        $this->assertEquals($newName, $enrichment->getKeyName());

        $this->assertCount(1, EnrichmentKey::getAllReferenced());
    }

    /**
     * METHODS
     */
    public function testFetchByName()
    {
        $enrichmentkey = EnrichmentKey::fetchByName('foo');
        $this->assertNotNull($enrichmentkey);
    }

    public function testFetchWithoutName()
    {
        $enrichmentkey = EnrichmentKey::fetchByName();
        $this->assertNull($enrichmentkey);
    }

    public function testFetchByInvalidName()
    {
        $enrichmentkey = EnrichmentKey::fetchByName('invalid');
        $this->assertNull($enrichmentkey);
    }

    public function testGetDisplayName()
    {
        $name        = $this->unreferencedEnrichmentKey->getName();
        $displayName = $this->unreferencedEnrichmentKey->getDisplayName();
        $this->assertEquals($name, $displayName);
    }

    public function testGetAll()
    {
        foreach (EnrichmentKey::getAll() as $name) {
             $this->assertNotContains(EnrichmentKey::class, (string) $name);
        }
    }

    public function testToArray()
    {
        $key = new EnrichmentKey();

        $key->setName('mykey');
        $key->setType('mytype');
        $key->setOptions('myoptions');

        $data = $key->toArray();

        $this->assertEquals([
            'Name'    => 'mykey',
            'Type'    => 'mytype',
            'Options' => 'myoptions',
        ], $data);
    }

    public function testFromArray()
    {
        $key = EnrichmentKey::fromArray([
            'Name'    => 'mykey',
            'Type'    => 'mytype',
            'Options' => 'myoptions',
        ]);

        $this->assertNotNull($key);
        $this->assertInstanceOf(EnrichmentKey::class, $key);

        $this->assertEquals('mykey', $key->getName());
        $this->assertEquals('mytype', $key->getType());
        $this->assertEquals('myoptions', $key->getOptions());
    }

    public function testUpdateFromArray()
    {
        $key = new EnrichmentKey();

        $key->updateFromArray([
            'Name'    => 'mykey',
            'Type'    => 'mytype',
            'Options' => 'myoptions',
        ]);

        $this->assertEquals('mykey', $key->getName());
        $this->assertEquals('mytype', $key->getType());
        $this->assertEquals('myoptions', $key->getOptions());
    }

    public function testRenameEnrichmentKey()
    {
        $this->referencedEnrichmentKey->rename('baz');

        $doc         = new Document($this->doc->getId());
        $enrichments = $doc->getEnrichment();
        $this->assertCount(1, $enrichments);
        $this->assertEquals('baz', $enrichments[0]->getKeyName());

        $this->assertNull($doc->getEnrichmentValue('bar'));
        $this->assertEquals('value', $doc->getEnrichmentValue('baz'));
    }

    public function testDeleteFromDocuments()
    {
        $this->referencedEnrichmentKey->deleteFromDocuments();

        $doc = new Document($this->doc->getId());
        $this->assertEmpty($doc->getEnrichment());

        // prüfe, dass der EnrichmentKey selbst immer noch vorhanden ist
        $this->assertCount(2, EnrichmentKey::getAll());
        $this->assertCount(0, EnrichmentKey::getAllReferenced());
    }

    public function testGetKeys()
    {
        $keys = EnrichmentKey::getKeys();

        $this->assertCount(2, $keys);
        $this->assertContains('bar', $keys);
        $this->assertContains('foo', $keys);
    }
}
