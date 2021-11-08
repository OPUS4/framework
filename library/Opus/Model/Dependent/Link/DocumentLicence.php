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
 *
 * @category    Framework
 * @package     Opus\Model
 * @author      Felix Ostrowski (ostrowski@hbz-nrw.de)
 */

namespace Opus\Model\Dependent\Link;

use Opus\Db\LinkDocumentsLicences;
use Opus\Licence;

/**
 * Abstract class for link licence model in the Opus framework.
 *
 * @category    Framework
 * @package     Opus\Model
 */
class DocumentLicence extends AbstractLinkModel
{
    /**
     * Specify then table gateway.
     *
     * @var string Classname of \Zend_DB_Table to use if not set in constructor.
     */
    protected static $tableGatewayClass = LinkDocumentsLicences::class;

    /**
     * Primary key of the parent model.
     *
     * @var mixed
     */
    protected $parentColumn = 'document_id';

    /**
     * The linked model's foreign key.
     *
     * @var mixed
     */
    protected $modelKey = 'licence_id';

    /**
     * The class of the model that is linked to.
     *
     * @var string
     */
    protected $modelClass = Licence::class;

    /**
     * The name of the field containing an identifying string.
     *
     * @var string
     */
    protected $displayAttributeName = 'NameLong';

    /**
     * Initialize model with the following values:
     * - Licence
     */
    protected function init()
    {
        $modelClass = $this->modelClass;
        if ($this->getId() !== null) {
            $this->setModel(new $modelClass($this->primaryTableRow->{$this->modelKey}));
        } else {
            $this->setModel(new $modelClass());
        }
    }

    /**
     * Persist foreign model & link.
     */
    public function store()
    {
        $this->primaryTableRow->licence_id = $this->model->store();
        // only store if something has changed
        // this avoids duplicate entries
        if ($this->getId() !== $this->primaryTableRow->licence_id) {
            parent::store();
        }
    }
}
