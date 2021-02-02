<?php
/**
 * This file is part of OPUS. The software OPUS has been originally developed
 * at the University of Stuttgart with funding from the German Research Net,
 * the Federal Department of Higher Education and Research and the Ministry
 * of Science, Research and the Arts of the State of Baden-Wuerttemberg.
 *
 * OPUS 4 is a complete rewrite of the original OPUS software and was developed
 * by the Stuttgart University Library, the Library Service Center
 * Baden-Wuerttemberg, the North Rhine-Westphalian Library Service Center,
 * the Cooperative Library Network Berlin-Brandenburg, the Saarland University
 * and State Library, the Saxon State Library - Dresden State and University
 * Library, the Bielefeld University Library and the University Library of
 * Hamburg University of Technology with funding from the German Research
 * Foundation and the European Regional Development Fund.
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
 * @category    Application
 * @author      Jens Schwidder <schwidder@zib.de>
 * @copyright   Copyright (c) 2018, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace Opus\Doi;

use Opus\Config;

/**
 * Provides array with recipients for DOI notifications from application configuration.
 *
 * This class is not really needed. The code was originally part of Opus\Doi\DoiMailNotification. However it made
 * sense to untie getting the recipients from that class. The old mechanism was transferred to this class, however
 * it is unlikely to be used again, because the configuration will move into the database more and more.
 *
 * TODO not really needed (however it is tested) :-)
 */
class ConfigRecipientProvider implements NotificationRecipientProvider
{

    public function getRecipients()
    {
        $config = Config::get(); // TODO use Trait or Application_Configuration

        $recipientAddresses = [];

        if (isset($config->doi->notificationEmail)) {
            $recipientAddresses = $config->doi->notificationEmail->toArray();
        }

        $recipients = [];

        foreach ($recipientAddresses as $recipient) {
            $entry = [];
            $entry['name'] = $recipient;
            $entry['address'] = $recipient;
            $recipients[] = $entry;
        }

        return $recipients;
    }
}
