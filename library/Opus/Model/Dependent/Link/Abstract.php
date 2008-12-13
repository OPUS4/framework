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
 * @category    Framework
 * @package     Opus_Model
 * @author      Felix Ostrowski (ostrowski@hbz-nrw.de)
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Abstract class for all links to independent models in the Opus framework.
 *
 * @category    Framework
 * @package     Opus_Model
 */

abstract class Opus_Model_Dependent_Link_Abstract extends Opus_Model_DependentAbstract
{
    /**
     * The model to link to.
     *
     * @var mixed
     */
    protected $_model;

    /**
     * Set the model that is linked to.
     *
     * @param  Opus_Model_Abstract  $model
     * @return void
     */
    protected function _setModel(Opus_Model_Abstract $model) {
        $this->_model = $model;
        $model->setTransactional(false);
    }

    /**
     * Tunnel get/set/add methods to the linked model.
     *
     * @param  mixed  $name      The name of the called method.
     * @param  array  $arguments The arguments passed in the method call.
     * @return mixed
     */
    public function __call($name, array $arguments) {
        if (array_key_exists(0, $arguments) === true) {
            return $this->_model->$name($arguments[0]);
        } else {
            return $this->_model->$name();
        }
    }
}
