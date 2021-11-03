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
 * @copyright   Copyright (c) 2021, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 *
 * @category    Tests
 * @package     Opus\Db2
 * @author      Jens Schwidder <schwidder@zib.de>
 */

namespace OpusTest\Db2;

use Doctrine\ORM\Exception\MissingIdentifierField;
use Exception;
use Opus\Db2\Database;
use Opus\Model2\Language;
use OpusTest\TestAsset\TestCase;

use function get_class;

class Language2Test extends TestCase
{
    private $database;

    public function setUp()
    {
        parent::setUp();

        $this->clearTables(false, [
            'languages',
            'documents',
            'document_subjects',
            'document_files',
            'document_title_abstracts',
            'document_licences',
            'link_documents_licences',
        ]);
    }

    public function testStoreLanguage()
    {
        $lang = new Language();
        $lang->setPart2B('ger');
        $lang->setPart2T('deu');
        $lang->setPart1('de');
        $lang->setRefName('German');
        $lang->setComment('test comment');
        $lang->store();

        $entityManager = Database::getEntityManager();
        $lang2         = $entityManager->find(Language::class, $lang->getId());

        $this->assertNotNull($lang2);
        $this->assertEquals('ger', $lang2->getPart2B());
        $this->assertEquals('deu', $lang2->getPart2T());
        $this->assertEquals('de', $lang2->getPart1());
        $this->assertEquals('German', $lang2->getRefName());
        $this->assertEquals('test comment', $lang2->getComment());
        $this->assertNull($lang2->getScope());
        $this->assertNull($lang2->getType());
        $this->assertEquals('0', $lang2->getActive());
    }

    public function testDeleteLanguage()
    {
        $lang = new Language();
        $lang->setPart2B('ger');
        $lang->setPart2T('deu');
        $lang->setPart1('de');
        $lang->setRefName('German');
        $lang->setComment('test delete comment');
        $lang->store();
        $id = $lang->getId();
        $lang->delete();

        $entityManager = Database::getEntityManager();
        try {
            $exceptionClass = null;
            $entityManager->find(Language::class, $lang->getId());
        } catch (Exception $e) {
            $exceptionClass = get_class($e);
        }

        $this->assertEquals(MissingIdentifierField::class, $exceptionClass);
    }
}
