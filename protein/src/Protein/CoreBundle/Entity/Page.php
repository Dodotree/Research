<?php

namespace Protein\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraint;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;


/**
 * Pages are collections identified by unique slug
 *
 * @ORM\Table(name="pages")
 * @ORM\Entity(repositoryClass="Protein\CoreBundle\Entity\PageRepository")
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks
 * @UniqueEntity(fields="slug", message="Slug already exists")
 */
class Page
{
    /**
     * @var string
     * @ORM\Id
     * @ORM\Column(name="id", type="string", length=250, unique=false)
     */
    private $id; ## it is slug actually


    /**
     * @ORM\OneToMany(targetEntity="Upload", mappedBy="page", indexBy="id")
     * @ORM\OrderBy({"filename" = "DESC"})
     */
    protected $uploads;

    /**
     * @ORM\ManyToMany(targetEntity="Protein", mappedBy="pages", orphanRemoval=true, cascade={"persist"}, indexBy="id")
     * @ORM\OrderBy({"id" = "ASC"})
     */
    protected $proteins;


    /**
     * Constructor
     */
    public function __construct()
    {
        $this->proteins = new \Doctrine\Common\Collections\ArrayCollection();
        $this->uploads = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function serializeArray()
    {
        return array(
            'id' => $this->id,
            'slug' => $this->slug,
            'proteins' => $this->getSerializedProteins(),
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
     * Set id.
     *
     * @param string $id
     *
     * @return Page
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
     * Add upload.
     *
     * @param \Protein\CoreBundle\Entity\Upload $upload
     *
     * @return Page
     */
    public function addUpload(\Protein\CoreBundle\Entity\Upload $upload)
    {
        $this->uploads[] = $upload;

        return $this;
    }

    /**
     * Remove upload.
     *
     * @param \Protein\CoreBundle\Entity\Upload $upload
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeUpload(\Protein\CoreBundle\Entity\Upload $upload)
    {
        return $this->uploads->removeElement($upload);
    }

    /**
     * Get uploads.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUploads()
    {
        return $this->uploads;
    }

    /**
     * Add protein.
     *
     * @param \Protein\CoreBundle\Entity\Protein $protein
     *
     * @return Page
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
