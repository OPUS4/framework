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
 * @category    Tests
 * @package     Opus
 * @author      Gunar Maiwald <maiwald@zib.de>
 * @author      Jens Schwidder <schwidder@zib.de>
 * @copyright   Copyright (c) 2008-2018, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

/**
 * Test cases for class Opus_EnrichmentKeyTest .
 *
 */
class Opus_EnrichmentKeyTest extends TestCase
{

    /**
     * @var Opus_Document
    */
    private $_doc;

     /**
     * @var Opus_EnrichmentKey
     */
    private $unreferencedEnrichmentKey;


    /**
     * @var Opus_EnrichmentKey
     */
    private $referencedEnrichmentKey;

    public function setUp()
    {
        parent::setUp();

        $this->unreferencedEnrichmentKey = new Opus_EnrichmentKey();
        $this->unreferencedEnrichmentKey->setName('foo');
        $this->unreferencedEnrichmentKey->store();

        $this->referencedEnrichmentKey = new Opus_EnrichmentKey();
        $this->referencedEnrichmentKey->setName('bar');
        $this->referencedEnrichmentKey->store();

        $this->_doc = new Opus_Document();
        $this->_doc->addEnrichment()->setKeyName('bar')->setValue('value');
        $this->_doc->store();
    }

    /* CREATE */
    public function testStoreEnrichmentKey()
    {
        $ek = new Opus_EnrichmentKey();
        $ek->setName('baz');
        $ek->setType('type');
        $ek->setOptions('options');
        $ek->store();

        $ek = new Opus_EnrichmentKey('baz');
        $this->assertNotNull($ek);
        $this->assertEquals('baz', $ek->getName());
        $this->assertEquals('type', $ek->getType());
        $this->assertEquals('options', $ek->getOptions());
        $this->assertEquals(3, count(Opus_EnrichmentKey::getAll()));
        $this->assertEquals(1, count(Opus_EnrichmentKey::getAllReferenced()));
    }

    public function testStoreEqualEnrichmentKey()
    {
        $ek = new Opus_EnrichmentKey();
        $ek->setName('foo');
        $this->setExpectedException('Opus_Model_Exception');
        $ek->store();
        $this->assertEquals(2, count(Opus_EnrichmentKey::getAll()));
        $this->assertEquals(1, count(Opus_EnrichmentKey::getAllReferenced()));
    }

    public function testStoreEmptyEnrichmentKey()
    {
        $ek = new Opus_EnrichmentKey();
        $ek->setName('');
        $this->setExpectedException('Opus_Model_Exception');
        $ek->store();
        $this->assertEquals(2, count(Opus_EnrichmentKey::getAll()));
        $this->assertEquals(1, count(Opus_EnrichmentKey::getAllReferenced()));
    }

    public function testStoryUnsetEnrichmentKey()
    {
        $ek = new Opus_EnrichmentKey();
        $this->setExpectedException('Opus_Model_Exception');
        $ek->store();
        $this->assertEquals(2, count(Opus_EnrichmentKey::getAll()));
        $this->assertEquals(1, count(Opus_EnrichmentKey::getAllReferenced()));
    }

    /* DELETE */
    public function testDeleteUnreferencedEnrichmentKey()
    {
        $this->assertCount(2, Opus_EnrichmentKey::getAll());
        $this->assertCount(1, Opus_EnrichmentKey::getAllReferenced());

        $this->unreferencedEnrichmentKey->delete();

        $this->assertCount(1, Opus_EnrichmentKey::getAll());
        $this->assertCount(1, Opus_EnrichmentKey::getAllReferenced());
    }

    /**
     * Bereits in Verwendung befindliche EnrichmentKeys dürfen trotzdem gelöscht werden.
     * Die Löschoperation kaskadiert auf die Enrichments in den Dokumenten, die den zu
     * löschenden EnrichmentKey verwenden. Solche Enrichments werden entfernt.
     */
    public function testDeleteReferencedEnrichmentKey()
    {
        $this->assertCount(2, Opus_EnrichmentKey::getAll());
        $this->assertCount(1, Opus_EnrichmentKey::getAllReferenced());

        $this->referencedEnrichmentKey->delete(); // mit Kaskadierung

        $this->assertCount(1, Opus_EnrichmentKey::getAll());
        $this->assertCount(0, Opus_EnrichmentKey::getAllReferenced());
    }

    /**
     * Bereits in Verwendung befindliche EnrichmentKeys dürfen trotzdem gelöscht werden.
     * Beim Löschen des EnrichmentKeys soll die Operation *nicht* auf die Enrichments in
     * den Dokumenten, die den zu löschenden EnrichmentKey verwenden, kaskadieren.
     */
    public function testDeleteReferencedEnrichmentKeyWithoutCascading()
    {
        $this->assertCount(2, Opus_EnrichmentKey::getAll());
        $this->assertCount(1, Opus_EnrichmentKey::getAllReferenced());

        $this->referencedEnrichmentKey->delete(false);

        $this->assertCount(1, Opus_EnrichmentKey::getAll());

        // obwohl der EnrichmentKey gelöscht wurde, wird er noch in Dokumenten verwendet
        $referencedEnrichmentKeys = Opus_EnrichmentKey::getAllReferenced();
        $this->assertCount(1, $referencedEnrichmentKeys);
        $this->assertEquals($this->referencedEnrichmentKey->getName(), $referencedEnrichmentKeys[0]);
    }

    /* READ */
    public function testReadEnrichmentKey()
    {
        foreach (['foo', 'bar'] as $name) {
            $ek = new Opus_EnrichmentKey($name);
            $ek->setType('type');
            $ek->setOptions('options');

            $this->assertEquals($name, $ek->getName());
            $this->assertEquals('type', $ek->getType());
            $this->assertEquals('options', $ek->getOptions());
        }
    }

    /* UPDATE */
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

        $doc = new Opus_Document($this->_doc->getId());
        $enrichment = $doc->getEnrichment()[0];
        $this->assertEquals($newName, $enrichment->getKeyName());

        $this->assertCount(1, Opus_EnrichmentKey::getAllReferenced());
    }

    /* METHODS */
    public function testFetchByName()
    {
        $enrichmentkey = Opus_EnrichmentKey::fetchByName('foo');
        $this->assertNotNull($enrichmentkey);
    }

    public function testFetchWithoutName()
    {
        $enrichmentkey = Opus_EnrichmentKey::fetchByName();
        $this->assertNull($enrichmentkey);
    }

    public function testFetchByInvalidName()
    {
        $enrichmentkey = Opus_EnrichmentKey::fetchByName('invalid');
        $this->assertNull($enrichmentkey);
    }

    public function testGetDisplayName()
    {
        $name = $this->unreferencedEnrichmentKey->getName();
        $displayName = $this->unreferencedEnrichmentKey->getDisplayName();
        $this->assertEquals($name, $displayName);
    }

    public function testGetAll()
    {
        foreach (Opus_EnrichmentKey::getAll() as $name) {
             $this->assertNotContains('Opus_EnrichmentKey', (string) $name);
        }
    }

    public function testToArray()
    {
        $key = new Opus_EnrichmentKey();

        $key->setName('mykey');
        $key->setType('mytype');
        $key->setOptions('myoptions');

        $data = $key->toArray();

        $this->assertEquals([
            'Name' => 'mykey',
            'Type' => 'mytype',
            'Options' => 'myoptions',
        ], $data);
    }

    public function testFromArray()
    {
        $key = Opus_EnrichmentKey::fromArray([
            'Name' => 'mykey',
            'Type' => 'mytype',
            'Options' => 'myoptions'
        ]);

        $this->assertNotNull($key);
        $this->assertInstanceOf('Opus_EnrichmentKey', $key);

        $this->assertEquals('mykey', $key->getName());
        $this->assertEquals('mytype', $key->getType());
        $this->assertEquals('myoptions', $key->getOptions());
    }

    public function testUpdateFromArray()
    {
        $key = new Opus_EnrichmentKey();

        $key->updateFromArray([
            'Name' => 'mykey',
            'Type' => 'mytype',
            'Options' => 'myoptions'
        ]);

        $this->assertEquals('mykey', $key->getName());
        $this->assertEquals('mytype', $key->getType());
        $this->assertEquals('myoptions', $key->getOptions());
    }

    public function testRenameEnrichmentKey()
    {
        $this->referencedEnrichmentKey->rename('baz');

        $doc = new Opus_Document($this->_doc->getId());
        $enrichments = $doc->getEnrichment();
        $this->assertCount(1, $enrichments);
        $this->assertEquals('baz', $enrichments[0]->getKeyName());

        $this->assertNull($doc->getEnrichmentValue('bar'));
        $this->assertEquals('value', $doc->getEnrichmentValue('baz'));
    }

    public function testDeleteFromDocuments()
    {
        $this->referencedEnrichmentKey->deleteFromDocuments();

        $doc = new Opus_Document($this->_doc->getId());
        $this->assertEmpty($doc->getEnrichment());

        // prüfe, dass der EnrichmentKey selbst immer noch vorhanden ist
        $this->assertCount(2, Opus_EnrichmentKey::getAll());
        $this->assertCount(0, Opus_EnrichmentKey::getAllReferenced());
    }
}
