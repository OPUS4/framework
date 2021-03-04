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
 * @package     Opus\Document\Plugin
 * @author      Thoralf Klein <thoralf.klein@zib.de>
 * @copyright   Copyright (c) 2010-2020, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace OpusTest\Document\Plugin;

use Opus\Config;
use Opus\Document;
use Opus\Document\Plugin\SequenceNumber;
use Opus\Identifier;
use OpusTest\TestAsset\TestCase;

class SequenceNumberTest extends TestCase
{

    protected function setUp()
    {
        parent::setUp();
        Config::get()->merge(new \Zend_Config([
            'sequence' => [
                'identifier_type' => 'serial',
            ],
        ]));
    }

    public function testExceptionOnInvalidModel()
    {
        Config::set(new \Zend_Config([]));

        $model = new Identifier();
        $plugin = new SequenceNumber();

        $this->setExpectedException('Opus\Document\DocumentException');
        $plugin->postStoreInternal($model);
    }

    public function testDontGenerateIdIfConfigNotSet()
    {
        Config::set(new \Zend_Config([]));

        $model = new Document();
        $model->setServerState('published');

        $plugin = new SequenceNumber();
        $plugin->postStoreInternal($model);

        $identifiers = $model->getIdentifier();
        $this->assertEquals(
            0,
            count($identifiers),
            'List of identifiers should be empty.'
        );
    }

    public function testDontGenerateIdOnUnpublishedDocument()
    {
        $model = new Document();
        $model->setServerState('unpublished');

        $this->assertEquals(
            0,
            count($model->getIdentifier()),
            'List of identifiers should be empty *before* test.'
        );

        $plugin = new SequenceNumber();
        $plugin->postStoreInternal($model);

        $identifiers = $model->getIdentifier();
        $this->assertEquals(
            0,
            count($identifiers),
            'List of identifiers should be empty.'
        );
    }

    public function testGenerateIdOnPublishedDocument()
    {
        $model = new Document();
        $model->setServerState('published');

        $this->assertEquals(
            0,
            count($model->getIdentifier()),
            'List of identifiers should be empty *before* test.'
        );

        $plugin = new SequenceNumber();
        $plugin->postStoreInternal($model);

        $identifiers = $model->getIdentifier();
        $this->assertEquals(
            1,
            count($identifiers),
            'List of identifiers should contain new identifier.'
        );
        $this->assertEquals(
            'serial',
            $identifiers[0]->getType(),
            'The one-and-only identifiers should be of type "serial".'
        );
        $this->assertTrue(
            $identifiers[0]->getValue() > 0,
            'The one-and-only identifiers should be bigger zero.'
        );
    }

    public function testGenerateIdOnlyOnceOnPublishedDocument()
    {
        $model = new Document();
        $model->setServerState('published');

        $this->assertEquals(
            0,
            count($model->getIdentifier()),
            'List of identifiers should be empty *before* test.'
        );

        $plugin = new SequenceNumber();

        // create ID in first run
        $plugin->postStoreInternal($model);
        $identifiers = $model->getIdentifier();
        $id_first_run = $identifiers[0]->getValue();

        // check IDs after second run
        $plugin->postStoreInternal($model);

        $identifiers = $model->getIdentifier();
        $this->assertEquals(
            1,
            count($identifiers),
            'List of identifiers should contain only one new identifier.'
        );
        $this->assertEquals(
            $id_first_run,
            $identifiers[0]->getValue(),
            'The one-and-only identifiers should not change.'
        );
    }

    public function testGenerateIdOnPublishedDocumentWithExistingSequence()
    {
        $existing_model = new Document();
        $existing_model->setServerState('published');
        $existing_model->addIdentifier()
                ->setType('serial')
                ->setValue(10);
        $existing_model->store();

        $model = new Document();
        $model->setServerState('published');

        $this->assertEquals(
            0,
            count($model->getIdentifier()),
            'List of identifiers should be empty *before* test.'
        );

        $plugin = new SequenceNumber();
        $plugin->postStoreInternal($model);

        $identifiers = $model->getIdentifier();
        $this->assertEquals(
            1,
            count($identifiers),
            'List of identifiers should contain new identifier.'
        );
        $this->assertEquals(
            'serial',
            $identifiers[0]->getType(),
            'The one-and-only identifier should be of type "serial".'
        );
        $this->assertEquals(11, $identifiers[0]->getValue());

        $existing_model->delete();
    }
}
