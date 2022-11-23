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
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace OpusTest;

use Opus\Common\Document;
use Opus\Common\DocumentInterface;
use Opus\Common\Enrichment;
use Opus\Common\EnrichmentInterface;
use Opus\Common\EnrichmentKey;
use Opus\Common\EnrichmentKeyInterface;
use Opus\Common\Model\ModelException;
use OpusTest\TestAsset\TestCase;

use function array_diff;
use function array_intersect;
use function array_push;
use function count;

/**
 * Test cases for Opus\Enrichment.
 *
 * @group EnrichmentTests
 */
class EnrichmentTest extends TestCase
{
    /** @var DocumentInterface */
    private $doc;

     /** @var EnrichmentKeyInterface */
    private $enrichmentkey;

    /** @var EnrichmentKeyInterface */
    private $anotherenrichmentkey;

    public function setUp(): void
    {
        parent::setUp();

        $this->clearTables(false, ['documents', 'enrichmentkeys', 'document_enrichments']);

        $this->enrichmentkey = EnrichmentKey::new();
        $this->enrichmentkey->setName('valid');
        $this->enrichmentkey->store();

        $this->anotherenrichmentkey = EnrichmentKey::new();
        $this->anotherenrichmentkey->setName('anothervalid');
        $this->anotherenrichmentkey->store();

        $this->doc = Document::new();
        $this->doc->addEnrichment()->setKeyName('valid')->setValue('value');
        $this->doc->store();
    }

    /**
     * CREATE
     */
    public function testStoreEnrichment()
    {
        $this->doc->addEnrichment()->setKeyName('anothervalid')->setValue('anothervalue');
        $this->doc->store();

        $doc = Document::get($this->doc->getId());
        $this->assertEquals(2, count($doc->getEnrichment()));
        $this->assertKeysAndValues($doc->getEnrichment(), ['valid', 'anothervalid'], ['value', 'anothervalue']);
    }

    public function testStoreEqualKeyEnrichment()
    {
        $this->doc->addEnrichment()->setKeyName('valid')->setValue('value2');
        $this->doc->store();

        $doc = Document::get($this->doc->getId());
        $this->assertEquals(2, count($doc->getEnrichment()));
        $this->assertKeysAndValues($doc->getEnrichment(), ['valid', 'valid'], ['value', 'value2']);
    }

    public function testStoreEqualValueEnrichment()
    {
        $this->doc->addEnrichment()->setKeyName('anothervalid')->setValue('value');
        $this->doc->store();

        $doc = Document::get($this->doc->getId());
        $this->assertEquals(2, count($doc->getEnrichment()));
        $this->assertKeysAndValues($doc->getEnrichment(), ['valid', 'anothervalid'], ['value', 'value']);
    }

    public function testStoreDuplicateEnrichment()
    {
        $this->doc->addEnrichment()->setKeyName('valid')->setValue('value');
        $this->doc->store();

        $doc = Document::get($this->doc->getId());
        $this->assertEquals(2, count($doc->getEnrichment()));

        $expectedEnrichment = ['KeyName' => 'valid', 'Value' => 'value'];
        $this->assertEquals($doc->getEnrichment(0)->toArray(), $expectedEnrichment);
        $this->assertEquals($doc->getEnrichment(1)->toArray(), $expectedEnrichment);
    }

    public function testStoreEnrichmentWithUnknownKey()
    {
        $this->doc->addEnrichment()->setKeyName('unknown')->setValue('foo');
        $this->doc->store();

        $doc = Document::get($this->doc->getId());
        $this->assertEquals(2, count($doc->getEnrichment()));
        $this->assertKeysAndValues($doc->getEnrichment(), ['valid', 'unknown'], ['value', 'foo']);
    }

    public function testStoreEnrichmentWithoutValue()
    {
        $this->doc->addEnrichment()->setKeyName('valid');
        $this->expectException(ModelException::class);
        $this->doc->store();

        $doc = Document::get($this->doc->getId());
        $this->assertEquals(1, count($doc->getEnrichment()));
        $this->assertKeysAndValues($doc->getEnrichment(), ['valid'], ['value']);
    }

    /**
     * READ
     */
    public function testLoadEnrichmentFromDocument()
    {
        $doc         = Document::get($this->doc->getId());
        $enrichments = $doc->getEnrichment();
        $enrichment  = $enrichments[0];
        $this->assertEquals('valid', $enrichment->getKeyName(), 'Loaded other key, then stored.');
        $this->assertEquals('value', $enrichment->getValue(), 'Loaded other value, then stored.');
    }

    public function testLoadEnrichmentById()
    {
        $doc         = Document::get($this->doc->getId());
        $enrichments = $doc->getEnrichment();
        $enrichment  = $enrichments[0];

        $enrichment = Enrichment::get($enrichment->getId());
        $this->assertEquals('valid', $enrichment->getKeyName(), 'Loaded other key, then stored.');
        $this->assertEquals('value', $enrichment->getValue(), 'Loaded other value, then stored.');
    }

    /**
     * UPDATE
     */
    public function testUpdateEnrichment()
    {
        $newkey = 'anothervalid';
        $newval = 'anothervalue';

        $doc         = Document::get($this->doc->getId());
        $enrichments = $doc->getEnrichment();
        $enrichment  = $enrichments[0];
        $enrichment->setKeyName($newkey);
        $enrichment->setValue($newval);
        $doc->store();

        $doc         = Document::get($this->doc->getId());
        $enrichments = $doc->getEnrichment();
        $enrichment  = $enrichments[0];
        $this->assertEquals($newkey, $enrichment->getKeyName(), 'Loaded other key, then stored.');
        $this->assertEquals($newval, $enrichment->getValue(), 'Loaded other value, then stored.');
    }

    public function testUpdateEnrichmentKeyOnly()
    {
        $newkey = 'anothervalid';

        $doc         = Document::get($this->doc->getId());
        $enrichments = $doc->getEnrichment();
        $enrichment  = $enrichments[0];
        $enrichment->setKeyName($newkey);
        $oldValue = $enrichment->getValue();
        $doc->store();

        $doc         = Document::get($this->doc->getId());
        $enrichments = $doc->getEnrichment();
        $enrichment  = $enrichments[0];
        $this->assertEquals($newkey, $enrichment->getKeyName(), 'Loaded other key, then stored.');
        $this->assertEquals($oldValue, $enrichment->getValue(), 'Loaded other value, then stored.');
    }

    public function testUpdateEnrichmentValueOnly()
    {
        $newval = 'newvalue';

        $doc         = Document::get($this->doc->getId());
        $enrichments = $doc->getEnrichment();
        $enrichment  = $enrichments[0];
        $enrichment->setValue($newval);
        $doc->store();

        $doc         = Document::get($this->doc->getId());
        $enrichments = $doc->getEnrichment();
        $enrichment  = $enrichments[0];
        $this->assertEquals('valid', $enrichment->getKeyName(), 'Loaded other key, then stored.');
        $this->assertEquals($newval, $enrichment->getValue(), 'Loaded other value, then stored.');
    }

    public function testUpdateEnrichmentUnknownKey()
    {
        $doc         = Document::get($this->doc->getId());
        $enrichments = $doc->getEnrichment();
        $enrichment  = $enrichments[0];
        $enrichment->setKeyName('unknown');
        $enrichment->setValue('bar');
        $doc->store();

        $doc        = Document::get($this->doc->getId());
        $enrichment = $doc->getEnrichment()[0];
        $this->assertEquals('unknown', $enrichment->getKeyName());
        $this->assertEquals('bar', $enrichment->getValue());
    }

    public function testUpdateEnrichmentSetDuplicateValue()
    {
        $doc = Document::get($this->doc->getId());

        // add another enrichment to document
        $doc->addEnrichment()->setKeyName('valid')->setValue('newvalue');
        $doc->store();

        // set duplicate value
        $doc         = Document::get($this->doc->getId());
        $enrichments = $doc->getEnrichment();
        $this->assertTrue(count($enrichments) === 2);

        $enrichment = $enrichments[1];
        $this->assertTrue($enrichment->getValue() === 'newvalue');
        $enrichment->setValue('value');
        $doc->store();

        $doc = Document::get($doc->getId());
        $this->assertEquals(2, count($doc->getEnrichment()));
    }

    public function testUpdateEnrichmentSetDuplicateKeyName()
    {
        $doc = Document::get($this->doc->getId());

        // add another enrichment to document
        $doc->addEnrichment()->setKeyName('anothervalid')->setValue('value');
        $doc->store();

        // set duplicate key
        $doc         = Document::get($this->doc->getId());
        $enrichments = $doc->getEnrichment();
        $this->assertTrue(count($enrichments) === 2);

        $enrichment = $enrichments[1];
        $this->assertTrue($enrichment->getKeyName() === 'anothervalid');
        $enrichment->setKeyName('valid');
        $doc->store();

        $doc = Document::get($doc->getId());
        $this->assertEquals(2, count($doc->getEnrichment()));
    }

    /**
     * DELETE
     */
    public function testDeleteEnrichment()
    {
        $this->doc->addEnrichment()->setKeyName('valid')->setValue('anothervalue');
        $this->doc->store();

        $doc         = Document::get($this->doc->getId());
        $enrichments = $doc->getEnrichment();
        $this->assertEquals(2, count($doc->getEnrichment()));

        unset($enrichments[0]);
        $doc->setEnrichment($enrichments);
        $doc->store();

        $doc = Document::get($this->doc->getId());
        $this->assertEquals(1, count($doc->getEnrichment()));
    }

    /**
     * @param array    $enrichments
     * @param string[] $expectedKeys
     * @param string[] $expectedValues
     */
    private function assertKeysAndValues($enrichments, $expectedKeys, $expectedValues)
    {
        $keys   = [];
        $values = [];
        foreach ($enrichments as $enrichment) {
            array_push($keys, $enrichment->getKeyName());
            array_push($values, $enrichment->getValue());
        }
        // check if arrays contain the same elements
        $this->assertTrue(count($expectedKeys) === count(array_intersect($keys, $expectedKeys)));
        $this->assertTrue(count($expectedValues) === count(array_intersect($values, $expectedValues)));
    }

    public function testToArray()
    {
        $enrichment = Enrichment::new();
        $enrichment->setKeyName('MyKey');
        $enrichment->setValue('test');

        $data = $enrichment->toArray();

        $this->assertEquals([
            'KeyName' => 'MyKey',
            'Value'   => 'test',
        ], $data);
    }

    public function testFromArray()
    {
        $enrichment = Enrichment::fromArray([
            'KeyName' => 'MyKey',
            'Value'   => 'test',
        ]);

        $this->assertNotNull($enrichment);
        $this->assertInstanceOf(EnrichmentInterface::class, $enrichment);

        $this->assertEquals('MyKey', $enrichment->getKeyName());
        $this->assertEquals('test', $enrichment->getValue());
    }

    public function testUpdateFromArray()
    {
        $enrichment = Enrichment::new();

        $enrichment->updateFromArray([
            'KeyName' => 'MyKey',
            'Value'   => 'test',
        ]);

        $this->assertNotNull($enrichment);
        $this->assertInstanceOf(EnrichmentInterface::class, $enrichment);

        $this->assertEquals('MyKey', $enrichment->getKeyName());
        $this->assertEquals('test', $enrichment->getValue());
    }

    public function testGetEnrichmentKey()
    {
        $enrichments = $this->doc->getEnrichment();
        $this->assertEquals(1, count($enrichments));
        $enrichment    = $enrichments[0];
        $enrichmentKey = $enrichment->getEnrichmentKey();
        $this->assertInstanceOf(EnrichmentKeyInterface::class, $enrichmentKey);
        $this->assertEquals('valid', $enrichmentKey->getName());
        $this->assertEquals('valid', $enrichment->getKeyName());
        $this->assertEquals('value', $enrichment->getValue());
    }

    public function testGetEnrichmentKeyWithUnregisteredKey()
    {
        $enrichment = Enrichment::new();
        $enrichment->setKeyName('unregisteredKey');
        $enrichment->setValue('unregisteredKeyValue');
        $this->doc->addEnrichment($enrichment);
        $docId = $this->doc->store();

        $this->doc   = Document::get($docId);
        $enrichments = $this->doc->getEnrichment();

        $this->assertEquals(2, count($enrichments));

        $enrichment    = $enrichments[0];
        $enrichmentKey = $enrichment->getEnrichmentKey();
        $this->assertInstanceOf(EnrichmentKeyInterface::class, $enrichmentKey);
        $this->assertEquals('valid', $enrichmentKey->getName());
        $this->assertEquals('valid', $enrichment->getKeyName());
        $this->assertEquals('value', $enrichment->getValue());

        $enrichment    = $enrichments[1];
        $enrichmentKey = $enrichment->getEnrichmentKey();
        $this->assertNull($enrichmentKey);
        $this->assertEquals('unregisteredKey', $enrichment->getKeyName());
        $this->assertEquals('unregisteredKeyValue', $enrichment->getValue());
    }

    public function testGetAllUsedEnrichmentKeyNames()
    {
        $enrichmentKeyNames = Enrichment::getAllUsedEnrichmentKeyNames();
        $this->assertCount(1, $enrichmentKeyNames);
        $this->assertEquals('valid', $enrichmentKeyNames[0]);
    }

    public function testGetAllUsedEnrichmentKeyNamesWithUnregisteredKey()
    {
        $enrichment = Enrichment::new();
        $enrichment->setKeyName('unregisteredKey');
        $enrichment->setValue('unregisteredKeyValue');
        $this->doc->addEnrichment($enrichment);
        $this->doc->store();

        $enrichmentKeyNames = Enrichment::getAllUsedEnrichmentKeyNames();
        $this->assertCount(2, $enrichmentKeyNames);

        $expectedValues = ['valid', 'unregisteredKey'];
        // check that both arrays have the same values (order is irrelevant)
        $this->assertEmpty(array_diff($enrichmentKeyNames, $expectedValues), 'array values are not the same');
        $this->assertEmpty(array_diff($expectedValues, $enrichmentKeyNames), 'array values are not the same');
    }

    public function testGetAllUsedEnrichmentKeyNamesWithDuplicateKeyName()
    {
        $enrichment = Enrichment::new();
        $enrichment->setKeyName('unregisteredKey');
        $enrichment->setValue('unregisteredKeyValue1');
        $this->doc->addEnrichment($enrichment);

        $enrichment = Enrichment::new();
        $enrichment->setKeyName('unregisteredKey');
        $enrichment->setValue('unregisteredKeyValue2');
        $this->doc->addEnrichment($enrichment);

        $this->doc->store();

        $enrichmentKeyNames = Enrichment::getAllUsedEnrichmentKeyNames();
        // duplicate key names are filtered out
        $this->assertCount(2, $enrichmentKeyNames);

        $expectedValues = ['valid', 'unregisteredKey'];
        // check that both arrays have the same values (order is irrelevant)
        $this->assertEmpty(array_diff($enrichmentKeyNames, $expectedValues), 'array values are not the same');
        $this->assertEmpty(array_diff($expectedValues, $enrichmentKeyNames), 'array values are not the same');
    }
}
