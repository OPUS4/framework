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
 * @category    Framework
 * @package     Opus\Db2
 * @author      Jens Schwidder <schwidder@zib.de>
 */

namespace Opus\Model2;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\ORMException;

use function array_pop;
use function count;
use function is_array;
use function md5;
use function sha1;
use function strlen;

/**
 * @ORM\Entity(repositoryClass="Opus\Db2\AccountRepository")
 * @ORM\Table(name="accounts")
 */
class Account extends AbstractModel
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     *
     * @var int
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=45, unique=true)
     *
     * @var string
     */
    private $login;

    /**
     * @ORM\Column(type="string", length=45)
     *
     * @var string
     */
    private $password;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @var string
     */
    private $email;

    /**
     * @ORM\Column(type="string", name="first_name", length=255, nullable=true)
     *
     * @var string
     */
    private $firstName;

    /**
     * @ORM\Column(type="string", name="last_name", length=255, nullable=true)
     *
     * @var string
     */
    private $lastName;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getLogin()
    {
        return $this->login;
    }

    /**
     * @param string $login Login name.
     */
    public function setLogin($login)
    {
        $login = $this->convertToScalar($login);

        // TODO: validate $login
//        $loginField = $this->getField('Login');
//        if ($loginField->getValidator()->isValid($login) === false) {
//            Log::get()->debug('Login not valid: ' . $login);
//            throw new SecurityException('Login name is empty or contains invalid characters.');
//        }

        $this->login = $login;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Set a new password. The password goes through the PHP sha1 hash algorithm.
     *
     * @param string $password
     */
    public function setPassword($password)
    {
        $password       = $this->convertToScalar($password);
        $this->password = sha1($password);
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * @return string
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * @param string $firstName
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;
    }

    /**
     * @return string
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * @param string $lastName
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;
    }

    /**
     * Retrieve all Account instances from the database.
     *
     * @return self[]
     * @throws ORMException
     */
    public static function getAll()
    {
        return self::getRepository()->getAll();
    }

    /**
     * Convert array parameter into scalar.
     *
     * The FormBuilder provides an array. The setValue method can handle it, but
     * the validation and the sha1 function throw an exception.
     *
     * @param mixed $value
     * @return scalar
     */
    protected function convertToScalar($value)
    {
        if (true === is_array($value) && 1 === count($value)) {
            $value = array_pop($value);
        } elseif (true === is_array($value) && 0 === count($value)) {
            $value = null;
        }

        return $value;
    }

    /**
     * Check if a given string is the correct password for this account.
     *
     * @param string $password Password.
     * @return bool
     */
    public function isPasswordCorrect($password)
    {
        return $this->getPassword() === sha1($password);
    }

    /**
     * For migration of old password hashes to new ones: Check if a given
     * string is the correct password for this account, but to another
     * hashing algorithm.
     *
     * @param string $password Password.
     * @return bool
     */
    public function isPasswordCorrectOldHash($password)
    {
        return $this->getPassword() === md5($password);
    }

    /**
     * Returns long name.
     *
     * @see \Opus\Model\Abstract#getDisplayName()
     *
     * @return string
     */
    public function getDisplayName()
    {
        return $this->getLogin();
    }

    /**
     * @return string
     */
    public function getFullName()
    {
        $name     = $this->getFirstName();
        $lastName = $this->getLastName();

        if (strlen($name) > 0 && strlen($lastName) > 0) {
            $name .= ' ';
        }

        $name .= $lastName;

        return $name;
    }
}
