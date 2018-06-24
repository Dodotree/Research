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
 * @ORM\Table(name="swissindex")
 * @ORM\Entity(repositoryClass="Protein\CoreBundle\Entity\IndexRepository")
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks
 * @UniqueEntity(fields="filename", message="Filename already taken")
 */
class Index
{

    #Created:   2018-05-18
    #Organism:  7244
    #Taxid: 7244
    #Models:    30315
    #Structures:    255
    #UniProt Version:   2018_04
    #UniProtKB_ac    iso_id  uniprot_seq_length  coordinate_id   provider    from    to  coverage    template    qmean   qmean_norm  url

    /**
     * @ORM\Id
     * @ORM\Column(type="string", length=70, unique=false)
     */
    private $filename; # combined from_to_template_coordinateId


    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $organism_id;

    /**
     * @ORM\Column(type="string", length=250, unique=false)
     */
    private $UniProt;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $len;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    protected $qmean;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    protected $qmean_norm;

    ### bonds and bridges to keep from recalculating over same files

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $bonds;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $bridges;

    

    public function serializeArray()
    {
        return array(
            'filename' => $this->filename,
            'organism_id' => $this->organism_id,
            'UniProt'=> $this->UniProt,
            'len'=> $this->len,
            'qmean'=> $this->qmean,
            'qmean_norm'=> $this->qmean_norm,
            'bonds'=> $this->bonds,
            'bridges'=> $this->bridges,
        );
    }



    /**
     * Set filename.
     *
     * @param string $filename
     *
     * @return Index
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
     * Set organismId.
     *
     * @param int $organismId
     *
     * @return Index
     */
    public function setOrganismId($organismId)
    {
        $this->organism_id = $organismId;

        return $this;
    }

    /**
     * Get organismId.
     *
     * @return int
     */
    public function getOrganismId()
    {
        return $this->organism_id;
    }

    /**
     * Set uniProt.
     *
     * @param string $uniProt
     *
     * @return Index
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
     * Set len.
     *
     * @param int $len
     *
     * @return Index
     */
    public function setLen($len)
    {
        $this->len = $len;

        return $this;
    }

    /**
     * Get len.
     *
     * @return int
     */
    public function getLen()
    {
        return $this->len;
    }

    /**
     * Set qmean.
     *
     * @param float $qmean
     *
     * @return Index
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
     * Set qmeanNorm.
     *
     * @param float $qmeanNorm
     *
     * @return Index
     */
    public function setQmeanNorm($qmeanNorm)
    {
        $this->qmean_norm = $qmeanNorm;

        return $this;
    }

    /**
     * Get qmeanNorm.
     *
     * @return float
     */
    public function getQmeanNorm()
    {
        return $this->qmean_norm;
    }

    /**
     * Set bonds.
     *
     * @param int|null $bonds
     *
     * @return Index
     */
    public function setBonds($bonds = null)
    {
        $this->bonds = $bonds;

        return $this;
    }

    /**
     * Get bonds.
     *
     * @return int|null
     */
    public function getBonds()
    {
        return $this->bonds;
    }

    /**
     * Set bridges.
     *
     * @param int|null $bridges
     *
     * @return Index
     */
    public function setBridges($bridges = null)
    {
        $this->bridges = $bridges;

        return $this;
    }

    /**
     * Get bridges.
     *
     * @return int|null
     */
    public function getBridges()
    {
        return $this->bridges;
    }
}
