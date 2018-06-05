<?php

namespace Protein\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraint;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;


/**
 * PDB file upload tracking
 *
 * @ORM\Table(name="uploads")
 * @ORM\Entity(repositoryClass="Protein\CoreBundle\Entity\UploadRepository")
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks
 */
class Upload
{
    /**
     * @var integer
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=250, unique=false)
     */
    private $filename;

    /**
     * @ORM\Column(type="string", length=250, unique=false)
     */
    private $UniProt;

    /**
     * @ORM\Column(type="float", nullable = true)
     */
    private $qmean;

    /**
     * @ORM\Column(type="integer")
     */
    private $attempts=1;

    /**
     * @ORM\ManyToOne(targetEntity="Index")
     * @ORM\JoinColumn(name="index_record", referencedColumnName="filename", onDelete="CASCADE")
     */
    protected $index_record;

    /**
     * @ORM\ManyToOne(targetEntity="Page", inversedBy="uploads")
     * @ORM\JoinColumn(name="page", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $page;


    /**
     * @var \DateTime
     * @ORM\Column(name="createdAt", type="datetime", nullable=false)
     */
    protected $createdAt;


    public function serializeArray()
    {
        return array(
            'UniProt' => $this->UniProt,
            'filename' => $this->filename,
            'record' => ($this->index_record)? $this->index_record->getFilename(): null,
            'qmean' => $this->qmean,
            'attempts' => $this->attempts,
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
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set filename.
     *
     * @param string $filename
     *
     * @return Upload
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;

        return $this;
    }

    /**
     * Get filename.
     *
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * Set createdAt.
     *
     * @param \DateTime $createdAt
     *
     * @return Upload
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
     * Set index.
     *
     * @param \Protein\CoreBundle\Entity\Index|null $index
     *
     * @return Upload
     */
    public function setIndex(\Protein\CoreBundle\Entity\Index $index = null)
    {
        $this->index = $index;

        return $this;
    }

    /**
     * Get index.
     *
     * @return \Protein\CoreBundle\Entity\Index|null
     */
    public function getIndex()
    {
        return $this->index;
    }

    /**
     * Set page.
     *
     * @param \Protein\CoreBundle\Entity\Page|null $page
     *
     * @return Upload
     */
    public function setPage(\Protein\CoreBundle\Entity\Page $page = null)
    {
        $this->page = $page;

        return $this;
    }

    /**
     * Get page.
     *
     * @return \Protein\CoreBundle\Entity\Page|null
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * Set uniProt.
     *
     * @param string $uniProt
     *
     * @return Upload
     */
    public function setUniProt($uniProt)
    {
        $this->UniProt = $uniProt;

        return $this;
    }

    /**
     * Get uniProt.
     *
     * @return string
     */
    public function getUniProt()
    {
        return $this->UniProt;
    }

    /**
     * Set qmean.
     *
     * @param float $qmean
     *
     * @return Upload
     */
    public function setQmean($qmean)
    {
        $this->qmean = $qmean;

        return $this;
    }

    /**
     * Get qmean.
     *
     * @return float
     */
    public function getQmean()
    {
        return $this->qmean;
    }

    /**
     * Set attempts.
     *
     * @param int $attempts
     *
     * @return Upload
     */
    public function setAttempts($attempts)
    {
        $this->attempts = $attempts;

        return $this;
    }

    /**
     * Get attempts.
     *
     * @return int
     */
    public function getAttempts()
    {
        return $this->attempts;
    }

    /**
     * Set indexRecord.
     *
     * @param \Protein\CoreBundle\Entity\Index|null $indexRecord
     *
     * @return Upload
     */
    public function setIndexRecord(\Protein\CoreBundle\Entity\Index $indexRecord = null)
    {
        $this->index_record = $indexRecord;

        return $this;
    }

    /**
     * Get indexRecord.
     *
     * @return \Protein\CoreBundle\Entity\Index|null
     */
    public function getIndexRecord()
    {
        return $this->index_record;
    }
}
