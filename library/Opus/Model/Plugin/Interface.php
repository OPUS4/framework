<?php
/**
 * LICENCE
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This code is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * @category    Framework
 * @package     Opus
 * @subpackage  Model
 * @author      Ralf Claußnitzer (ralf.claussnitzer@slub-dresden.de)
 * @copyright   Copyright (c) 2009-2010
 *              Saechsische Landesbibliothek - Staats- und Universitaetsbibliothek Dresden (SLUB)
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Interface for plugin mechanism of Opus_Model_AbstractDb. Defines hook
 * methods called after varios store and fetch operations.
 *
 * @category    Framework
 * @package     Opus
 * @subpackage  Model
 */
interface Opus_Model_Plugin_Interface {

    /**
     * Gets called just before a store() is performed.
     *
     * @param Opus_Model_AbstractDb $model The database model that triggered the event.
     * @return void
     */
    public function preStore(Opus_Model_AbstractDb $model);

    /**
     * Gets called just before a fetchValues() is performed.
     *
     * @param Opus_Model_AbstractDb $model The database model that triggered the event.
     * @return void
     */
    public function preFetch(Opus_Model_AbstractDb $model);

    /**
     * Gets called just after a store() is performed.
     *
     * @param Opus_Model_AbstractDb $model The database model that triggered the event.
     * @return void
     */
    public function postStore(Opus_Model_AbstractDb $model);
    
    /**
     * Gets called just after a _storeInternalFields() is performed.
     *
     * @param Opus_Model_AbstractDb $model The database model that triggered the event.
     * @return void
     */
    public function postStoreInternal(Opus_Model_AbstractDb $model);

    /**
     * Gets called just after a _storeExternalFields() is performed.
     *
     * @param Opus_Model_AbstractDb $model The database model that triggered the event.
     * @return void
     */
    public function postStoreExternal(Opus_Model_AbstractDb $model);

    /**
     * Gets called just before a delete() is performed.
     *
     * @param Opus_Model_AbstractDb $model The database model that triggered the event.
     * @return void
     */
    public function preDelete(Opus_Model_AbstractDb $model);

    /**
     * Gets called just after a delete() was performed.
     *
     * @param mixed $modelId The database model id.
     * @return void
     */
    public function postDelete($modelId);

}
