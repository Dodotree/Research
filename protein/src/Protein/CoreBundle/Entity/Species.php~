<?php

namespace Protein\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraint;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;


/**
 * Species
 *
 * @ORM\Table(name="species")
 * @ORM\Entity(repositoryClass="Protein\CoreBundle\Entity\SpeciesRepository")
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks
 * @UniqueEntity(fields="name", message="Name already taken")
 */
class Species
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
    private $name;

    /**
     * @ORM\Column(type="string", length=250, unique=false)
     */
    private $abbr;

    /**
     * @ORM\OneToMany(targetEntity="Protein", mappedBy="species", indexBy="id")
     * @ORM\OrderBy({"id" = "DESC"})
     */
    protected $proteins;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->proteins = new \Doctrine\Common\Collections\ArrayCollection();
    }


    public function serializeArray()
    {
        return array(
            'id' => $this->id,
            'name' => $this->slug,
            'abbr' => $this->getSerializedProteins(),
            'uploads'=> $this->uploads,
        );
    }

    public function getSerializedProteins()
    {
        $proteins = array();
        foreach($this->proteins as $protein){ $proteins[] = $protein->serializeArray(); }
    return $proteins;
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
     * Set name.
     *
     * @param string $name
     *
     * @return Species
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
     * Set abbr.
     *
     * @param string $abbr
     *
     * @return Species
     */
    public function setAbbr($abbr)
    {
        $this->abbr = $abbr;

        return $this;
    }

    /**
     * Get abbr.
     *
     * @return string
     */
    public function getAbbr()
    {
        return $this->abbr;
    }

    /**
     * Add protein.
     *
     * @param \Protein\CoreBundle\Entity\Protein $protein
     *
     * @return Species
     */
    public function addProtein(\Protein\CoreBundle\Entity\Protein $protein)
    {
        $this->proteins[] = $protein;

        return $this;
    }

    /**
     * Remove protein.
     *
     * @param \Protein\CoreBundle\Entity\Protein $protein
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeProtein(\Protein\CoreBundle\Entity\Protein $protein)
    {
        return $this->proteins->removeElement($protein);
    }

    /**
     * Get proteins.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getProteins()
    {
        return $this->proteins;
    }
}
