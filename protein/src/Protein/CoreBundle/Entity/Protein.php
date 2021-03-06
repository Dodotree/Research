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
 * @ORM\Table(name="proteins")
 * @ORM\Entity(repositoryClass="Protein\CoreBundle\Entity\ProteinRepository")
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks
 * @UniqueEntity(fields="name", message="UniProt already taken")
 */
class Protein
{
    /**
     * @ORM\Id
     * @ORM\Column(type="string", length=250, unique=false)
     */
    private $id; # actually $UniProt

    /**
     * @ORM\Column(type="string", length=250, unique=false, nullable = true)
     */
    private $name;

    /**
     * @ORM\ManyToOne(targetEntity="Species", inversedBy="proteins")
     * @ORM\JoinColumn(name="species_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $species;

    /**
     * @ORM\Column(type="string", length=250, unique=false, nullable = true)
     */
    private $gene;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $len;

    /**
     * @ORM\Column(type="string", length=100000, unique=false, nullable = true)
     */
    private $sequence;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $qmean;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $qmean_norm;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $bonds;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $bridges;

    /**
     * @ORM\ManyToMany(targetEntity="Page", inversedBy="proteins", indexBy="id", orphanRemoval=true, cascade={"persist"})
     * @ORM\JoinTable(name="page_proteins",
     *      joinColumns={@ORM\JoinColumn(name="page_slug", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="UniProt", referencedColumnName="id")}
     *      )
     * @ORM\OrderBy({"id" = "ASC"})
     */
    protected $pages;

    /**
     * @ORM\ManyToOne(targetEntity="Index")
     * @ORM\JoinColumn(name="index_record", referencedColumnName="filename", onDelete="SET NULL")
     */
    protected $index_record;

    /**
     * @ORM\Column(type="string", length=250, unique=false, nullable = true)
     */
    private $filename;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->pages = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function serializeArray()
    {
        return array(
            'id' => $this->id,
            'name' => $this->name,
            'gene' => $this->gene,
            'len' => $this->len,
            'qmean' => $this->qmean,
            'qmean_norm' => $this->qmean_norm,
            'bonds' => $this->bonds,
            'bridges' => $this->bridges,
            'species' => ($this->species) ? $this->species->getName() : 'unknown',
            'filename'=>$this->filename,
            'record'=>($this->getIndexRecord())? $this->getIndexRecord()->getFilename(): null,
        );
    }

    public function getSerializedPages()
    {
        $pages = array();
        #foreach($this->pages as $pages){ $pages[] = $pages->serializeArray(); }
    return $pages;
    }




    /**
     * Set id.
     *
     * @param string $id
     *
     * @return Protein
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
     * Set name.
     *
     * @param string $name
     *
     * @return Protein
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set gene.
     *
     * @param string $gene
     *
     * @return Protein
     */
    public function setGene($gene)
    {
        $this->gene = $gene;

        return $this;
    }

    /**
     * Get gene.
     *
     * @return string
     */
    public function getGene()
    {
        return $this->gene;
    }

    /**
     * Set len.
     *
     * @param int|null $len
     *
     * @return Protein
     */
    public function setLen($len = null)
    {
        $this->len = $len;

        return $this;
    }

    /**
     * Get len.
     *
     * @return int|null
     */
    public function getLen()
    {
        return $this->len;
    }

    /**
     * Set qmean.
     *
     * @param float|null $qmean
     *
     * @return Protein
     */
    public function setQmean($qmean = null)
    {
        $this->qmean = $qmean;

        return $this;
    }

    /**
     * Get qmean.
     *
     * @return float|null
     */
    public function getQmean()
    {
        return $this->qmean;
    }

    /**
     * Set qmeanNorm.
     *
     * @param float|null $qmeanNorm
     *
     * @return Protein
     */
    public function setQmeanNorm($qmeanNorm = null)
    {
        $this->qmean_norm = $qmeanNorm;

        return $this;
    }

    /**
     * Get qmeanNorm.
     *
     * @return float|null
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
     * @return Protein
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
     * @return Protein
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

    /**
     * Set filename.
     *
     * @param string|null $filename
     *
     * @return Protein
     */
    public function setFilename($filename = null)
    {
        $this->filename = $filename;

        return $this;
    }

    /**
     * Get filename.
     *
     * @return string|null
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * Set species.
     *
     * @param \Protein\CoreBundle\Entity\Species|null $species
     *
     * @return Protein
     */
    public function setSpecies(\Protein\CoreBundle\Entity\Species $species = null)
    {
        $this->species = $species;

        return $this;
    }

    /**
     * Get species.
     *
     * @return \Protein\CoreBundle\Entity\Species|null
     */
    public function getSpecies()
    {
        return $this->species;
    }

    /**
     * Add page.
     *
     * @param \Protein\CoreBundle\Entity\Page $page
     *
     * @return Protein
     */
    public function addPage(\Protein\CoreBundle\Entity\Page $page)
    {
        $this->pages[] = $page;

        return $this;
    }

    /**
     * Remove page.
     *
     * @param \Protein\CoreBundle\Entity\Page $page
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removePage(\Protein\CoreBundle\Entity\Page $page)
    {
        return $this->pages->removeElement($page);
    }

    /**
     * Get pages.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPages()
    {
        return $this->pages;
    }

    /**
     * Set indexRecord.
     *
     * @param \Protein\CoreBundle\Entity\Index|null $indexRecord
     *
     * @return Protein
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

    /**
     * Set sequence.
     *
     * @param string|null $sequence
     *
     * @return Protein
     */
    public function setSequence($sequence = null)
    {
        $this->sequence = $sequence;

        return $this;
    }

    /**
     * Get sequence.
     *
     * @return string|null
     */
    public function getSequence()
    {
        return $this->sequence;
    }
}
