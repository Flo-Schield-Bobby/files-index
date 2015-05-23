<?php

namespace Fsb\Media\FilesIndexBundle\Entity;

use DateTime;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Doctrine\ORM\Mapping as ORM;

/**
 * User
 *
 * @ORM\Table(name="users")
 * @ORM\Entity(repositoryClass="Fsb\Media\FilesIndexBundle\Entity\UserRepository")
 * @UniqueEntity("username")
 * @ORM\HasLifecycleCallbacks()
 */
class User implements UserInterface
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime")
     */
    private $updatedAt;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="removed_at", type="datetime", nullable=true)
     */
    private $removedAt;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_active", type="boolean")
     */
    private $isActive;

    /**
     * @var string
     *
     * @ORM\Column(name="username", type="string", length=255)
     */
    private $username;

    /**
     * @var string
     *
     * @ORM\Column(name="root_folder", type="string", length=255)
     */
    private $rootFolder;

    /**
     * @var string
     *
     * @ORM\Column(name="password", type="string", length=255)
     */
    private $password;

    /**
     * @var string
     *
     * @ORM\Column(name="salt", type="string", length=40)
     */
    private $salt;

    public function __construct()
    {
        $this->createdAt = new DateTime();
        $this->updatedAt = new DateTime();
        $this->isActive = true;
        $this->salt = sha1(uniqid(null, true));

        return $this;
    }

    function __toString()
    {
        return $this->username;
    }

    function toString()
    {
        return $this->username;
    }


    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set createdAt
     *
     * @param DateTime $createdAt
     * @return User
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set updatedAt
     *
     * @param DateTime $updatedAt
     * @return User
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt
     *
     * @return DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Set removedAt
     *
     * @param DateTime $removedAt
     * @return User
     */
    public function setRemovedAt($removedAt)
    {
        $this->removedAt = $removedAt;

        return $this;
    }

    /**
     * Get removedAt
     *
     * @return DateTime
     */
    public function getRemovedAt()
    {
        return $this->removedAt;
    }

    /**
     * Set isActive
     *
     * @param boolean $isActive
     * @return User
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * Get isActive
     *
     * @return boolean
     */
    public function getIsActive()
    {
        return $this->isActive;
    }

    /**
     * Get isActive alias
     */
    public function isActive()
    {
        return $this->getIsActive();
    }

    /**
     * Set username
     *
     * @param string $username
     * @return User
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Get username
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Set rootFolder
     *
     * @param string $rootFolder
     * @return User
     */
    public function setRootFolder($rootFolder)
    {
        $this->rootFolder = $rootFolder;

        return $this;
    }

    /**
     * Get rootFolder
     *
     * @return string
     */
    public function getRootFolder()
    {
        return $this->rootFolder;
    }

    /**
     * Set password
     *
     * @param string $password
     * @return User
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get password
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Set salt
     *
     * @param string $salt
     * @return User
     */
    public function setSalt($salt)
    {
        $this->salt = $salt;

        return $this;
    }

    /**
     * Get salt
     *
     * @return string
     */
    public function getSalt()
    {
        return $this->salt;
    }

    public function eraseCredentials()
    {
    }

    public function getRoles()
    {
        return array('ROLE_USER');
    }

    /**
     * @ORM\PreUpdate
     */
    public function beforeUpdate()
    {
        $this->updatedAt = new DateTime();
    }
}
