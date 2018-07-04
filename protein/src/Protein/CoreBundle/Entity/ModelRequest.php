<?php

namespace Protein\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraint;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;


/**
 * Protein .fasta based table with bonds and salt bridges
 *
 * @ORM\Table(name="modelrequests")
 * @ORM\Entity(repositoryClass="Protein\CoreBundle\Entity\ModelRequestRepository")
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks
 * @UniqueEntity(fields="name", message="UniProt already taken")
 */
class ModelRequest
{
    /**
     * @ORM\Id
     * @ORM\Column(type="string", length=250, unique=false)
     */
    private $id; # actually $UniProt


    /**
     * @ORM\Column(type="integer")
     */
    private $status=0;


    /**
     * @ORM\Column(type="string", length=250, unique=false, nullable = true)
     */
    private $url;

    /**
     * @ORM\Column(type="string", length=250, unique=false, nullable = true)
     */
    private $last_error;


    /**
     * @var \DateTime
     * @ORM\Column(name="createdAt", type="datetime", nullable=false)
     */
    protected $createdAt; # should not create more then 2K requests per day, results expire in 2 weeks


    /**
     * @var \DateTime
     * @ORM\Column(name="calledAt", type="datetime", nullable=false)
     */
    protected $calledAt; # last time there was an attempt to check results


    /**
     * @ORM\Column(type="integer")
     */
    private $callcount=0;


    /**
     * Constructor
     */
    public function __construct()
    {
        #$this->pages = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function serializeArray()
    {
        return array(
            'id' => $this->id,
            'status' => $this->status,
            'url' => $this->url,
            'calls' => $this->callcount,
            'last err' => $this->last_error,
        );
    }


    /**
     * Hook on pre-persist operations
     * @ORM\PrePersist()
     */
    public function prePersist()
    {
        $this->createdAt = new \DateTime;
    }



    /**
     * Set id.
     *
     * @param string $id
     *
     * @return ModelRequest
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get id.
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set status.
     *
     * @param int $status
     *
     * @return ModelRequest
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set url.
     *
     * @param string|null $url
     *
     * @return ModelRequest
     */
    public function setUrl($url = null)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url.
     *
     * @return string|null
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set lastError.
     *
     * @param string|null $lastError
     *
     * @return ModelRequest
     */
    public function setLastError($lastError = null)
    {
        $this->last_error = $lastError;

        return $this;
    }

    /**
     * Get lastError.
     *
     * @return string|null
     */
    public function getLastError()
    {
        return $this->last_error;
    }

    /**
     * Set createdAt.
     *
     * @param \DateTime $createdAt
     *
     * @return ModelRequest
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt.
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set calledAt.
     *
     * @param \DateTime $calledAt
     *
     * @return ModelRequest
     */
    public function setCalledAt($calledAt)
    {
        $this->calledAt = $calledAt;

        return $this;
    }

    /**
     * Get calledAt.
     *
     * @return \DateTime
     */
    public function getCalledAt()
    {
        return $this->calledAt;
    }

    /**
     * Set callcount.
     *
     * @param int $callcount
     *
     * @return ModelRequest
     */
    public function setCallcount($callcount)
    {
        $this->callcount = $callcount;

        return $this;
    }

    /**
     * Get callcount.
     *
     * @return int
     */
    public function getCallcount()
    {
        return $this->callcount;
    }
}
