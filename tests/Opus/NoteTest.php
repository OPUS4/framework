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
 * @author      Jens Schwidder <schwidder@zib.de>
 * @copyright   Copyright (c) 2017-2018, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

class Opus_NoteTest extends TestCase
{

    public function testSetVisibility()
    {
        $doc = new Opus_Document();

        $note = $doc->addNote();
        $note->setMessage('test note');
        $note->setVisibility('public');

        $docId = $doc->store();


        $doc = new Opus_Document($docId);

        $notes = $doc->getNote();

        $this->assertNotNull($notes);
        $this->assertCount(1, $notes);

        $note = $notes[0];

        $this->assertInstanceOf('Opus_Note', $note);

        $this->assertEquals('public', $note->getVisibility());
    }

    public function testVisibilityDefault()
    {
        $doc = new Opus_Document();

        $note = $doc->addNote();
        $note->setMessage('test note');

        $docId = $doc->store();


        $doc = new Opus_Document($docId);

        $notes = $doc->getNote();

        $this->assertNotNull($notes);
        $this->assertCount(1, $notes);

        $note = $notes[0];

        $this->assertInstanceOf('Opus_Note', $note);

        $this->assertEquals('private', $note->getVisibility());
    }

    public function testToArray()
    {
        $note = new Opus_Note();
        $note->setVisibility(Opus_Note::ACCESS_PUBLIC);
        $note->setMessage('a public message');

        $data = $note->toArray();

        $this->assertEquals([
            'Message' => 'a public message',
            'Visibility' => 'public'
        ], $data);
    }

    public function testFromArray()
    {
        $note = Opus_Note::fromArray([
            'Visibility' => 'private',
            'Message' => 'a private message'
        ]);

        $this->assertNotNull($note);
        $this->assertInstanceOf('Opus_Note', $note);

        $this->assertEquals('private', $note->getVisibility());
        $this->assertEquals('a private message', $note->getMessage());
    }

    public function testUpdateFromArray()
    {
        $note = new Opus_Note();

        $note->updateFromArray([
            'Visibility' => 'private',
            'Message' => 'a private message'
        ]);

        $this->assertNotNull($note);
        $this->assertInstanceOf('Opus_Note', $note);

        $this->assertEquals('private', $note->getVisibility());
        $this->assertEquals('a private message', $note->getMessage());
    }
}
