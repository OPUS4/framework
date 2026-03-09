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
 * @copyright   Copyright (c) 2026, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace Opus\Db\Console;

use Opus\Db\Util\Maintenance;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function count;
use function sprintf;

class DateCommand extends Command
{
    const OPTION_FIX = 'fix';

    protected function configure()
    {
        parent::configure();

        $help = <<<EOT
Checks format of document date values. In some cases dates might habe been stored as timestamps.

Checked document fields:
  - completed_date
  - published_date
  - thesis_date_accepted
  - embargo_date
EOT;

        $this->setName('database:date')
            ->setDescription('Checks and fixes date values')
            ->setHelp($help)
            ->addOption(
                self::OPTION_FIX,
                null,
                InputOption::VALUE_NONE,
                'Shorten timestamps into date values'
            );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $maintenance = new Maintenance();

        $output->writeln('Checking date fields for timestamps.');

        $dates = $maintenance->checkDateValues();

        if (count($dates) > 0) {
            if ($input->getOption(self::OPTION_FIX)) {
                $output->writeln('Fixing date values...');
                $maintenance->fixDateValues();

                $dates = $maintenance->checkDateValues();

                if (count($dates) > 0) {
                    $output->writeln('Not all date values could be fixed.');
                } else {
                    $output->writeln('Finished');
                }
            } else {
                $output->writeln('Timestamps in date values found:');
                foreach ($dates as $field => $count) {
                    $output->writeln(sprintf('%d %s', $count, $field));
                }
            }
        } else {
            $output->writeln('No timestamps in date fields found.');
        }

        return Command::SUCCESS;
    }
}
