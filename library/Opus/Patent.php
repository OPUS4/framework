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

namespace Opus;

use Opus\Common\Date;
use Opus\Common\PatentInterface;
use Opus\Common\Validate\Year;
use Opus\Model\Dependent\AbstractDependentModel;
use Opus\Model\Field;
use Zend_Validate_NotEmpty;

use function func_get_args;

/**
 * Domain model for patents in the Opus framework
 */
class Patent extends AbstractDependentModel implements PatentInterface
{
    /**
     * Primary key of the parent model.
     *
     * @var mixed
     */
    protected $parentColumn = 'document_id';

    /**
     * Specify then table gateway.
     *
     * @var string Classname of \Zend_DB_Table to use if not set in constructor.
     */
    protected static $tableGatewayClass = Db\DocumentPatents::class;

    /**
     * Initialize model with the following fields:
     * - Language
     * - Title
     */
    protected function init()
    {
        $countries = new Field('Countries');

        $dateGranted = new Field('DateGranted');
        $dateGranted->setValueModelClass(Date::class);

        $number = new Field('Number');
        $number->setMandatory(true)
            ->setValidator(new Zend_Validate_NotEmpty());

        $yearApplied = new Field('YearApplied');
        $yearApplied->setValidator(new Year());

        $application = new Field('Application');

        $this->addField($countries)
            ->addField($dateGranted)
            ->addField($number)
            ->addField($yearApplied)
            ->addField($application);
    }

    /**
     * @return string|null
     */
    public function getCountries()
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @param string|null $countries
     * @return $this
     */
    public function setCountries($countries)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @return Date|null
     */
    public function getDateGranted()
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @param Date|null $date
     * @return $this
     */
    public function setDateGranted($date)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @return string|null
     */
    public function getNumber()
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @param string|null $number
     * @return $this
     */
    public function setNumber($number)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @return int|null
     */
    public function getYearApplied()
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @param int $year
     * @return $this
     */
    public function setYearApplied($year)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @return string|null
     */
    public function getApplication()
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @param string|null $application
     * @return $this
     */
    public function setApplication($application)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }
}
