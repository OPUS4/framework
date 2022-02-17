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
 * @copyright   Copyright (c) 2011-2018, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 *
 * @category    Framework
 * @author      Thoralf Klein <thoralf.klein@zib.de>
 */

namespace Opus\File\Plugin;

use Opus\Config;
use Opus\File;
use Opus\LoggingTrait;
use Opus\Model\ModelInterface;
use Opus\Model\Plugin\AbstractPlugin;
use Opus\UserRole;

use function strlen;
use function trim;

/**
 * Plugin for adding "default" privileges to a file.
 *
 * @uses        \Opus\Model\AbstractModel
 *
 * TODO NAMESPACE rename class
 *
 * @category    Framework
 * @package     Opus
 */
class DefaultAccess extends AbstractPlugin
{
    use LoggingTrait;

    /**
     * Post-store hook will be called right after the document has been stored
     * to the database.
     *
     * @see {Opus\Model\Plugin\PluginInterface::postStore}
     */
    public function postStore(ModelInterface $model)
    {
        // only index Opus\File instances
        if (false === $model instanceof File) {
            $this->getLogger()->err(__METHOD__ . '#1 argument must be instance of Opus\File');
            return;
        }

        // only new Opus\File instances
        if (true !== $model->isNewRecord()) {
            return;
        }

        $config = Config::get();

        if ($config !== null && isset($config->securityPolicy->files->defaultAccessRole)) {
            $roleName = $config->securityPolicy->files->defaultAccessRole;

            // Empty name -> don't set any role for access
            if (strlen(trim($roleName)) > 0) {
                $accessRole = UserRole::fetchByName($roleName);

                if ($accessRole === null) {
                    $this->getLogger()->err(
                        __METHOD__ . ": Failed to add role '$roleName' to file "
                        . $model->getId() . "; '$roleName' role does not exist!"
                    );
                    return;
                }

                $accessRole->appendAccessFile($model->getId());
                $accessRole->store();
            }
        }
    }
}
