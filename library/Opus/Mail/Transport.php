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
 * @package     Opus\Mail
 * @author      Thoralf Klein <thoralf.klein@zib.de>
 * @author      Jens Schwidder <schwidder@zib.de>
 * @copyright   Copyright (c) 2011-2018, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace Opus\Mail;

use Opus\Log;

/**
 * Override
 *
 * @category    Framework
 * @package     Opus\Mail\Transport
 *
 */
class Transport extends \Zend_Mail_Transport_Smtp
{

    /**
     * Create a new \Zend_Mail_Transport instance.
     */
    public function __construct($config = null)
    {
        $smtp = null;

        if (isset($config->smtp)) {
            $smtp = $config->smtp;
        }

        if (is_null($smtp) || $smtp === 'localhost') {
            $smtp = '127.0.0.1';
        }

        $port = 25;

        if (isset($config->port)) {
            $port = $config->port;
        }

        Log::get()->info(__CLASS__ . " Using mail server {$smtp}:{$port}");

        parent::__construct($smtp, ['port' => $port]);
    }
}
