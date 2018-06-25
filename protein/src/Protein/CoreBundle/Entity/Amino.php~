<?php

namespace Protein\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraint;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;


/**
 * Protein .fasta based table with amino acids count
 *
 * @ORM\Table(name="aminoacids")
 * @ORM\Entity(repositoryClass="Protein\CoreBundle\Entity\AminoRepository")
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks
 * @UniqueEntity(fields="name", message="UniProt already taken")
 */
class Amino
{
    /**
     * @ORM\Id
     * @ORM\Column(type="string", length=250, unique=false)
     */
    private $id; # actually $UniProt

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $A; #Alanin
    
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $R; #Arginin

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $N; #Aspargine
    
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $D; #Aspartic Acid
    
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $C; #Cystein
    
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $Q; #Glutamine
    
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $E; #Glutamic acid
    
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $G; #Glycine
    
    /**
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $H; #Histidine
    
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $I; #Isoleucine
    
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $L; #Leucine
    
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $K; #Lysine
    
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $M; #Methionine
    
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $F; #Phenylalanine
    
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $P; #Proline
    
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $O; #Pyrrolysine
    
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $S; #Serine
    
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $U; #Selenocystein
    
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $T; #Threonine
    
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $W; #Tryptophan
    
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $Y; #Tyrosine
    
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $V; #Valine
    
    /**
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $B; #Aspartic acid or Asparagine
    
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $Z; #Glutamic acid or Glutamine
    
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $J; #Leucine or Isoleucine
    
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $X; #Any amino acid
    
    /**
     * @ORM\ManyToMany(targetEntity="Page", inversedBy="aminoacids", indexBy="id", orphanRemoval=true, cascade={"persist"})
     * @ORM\JoinTable(name="page_aminoacids",
     *      joinColumns={@ORM\JoinColumn(name="page_slug", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="UniProt", referencedColumnName="id")}
     *      )
     * @ORM\OrderBy({"id" = "ASC"})
     */
    protected $pages;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->pages = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function serializeArray()
    {
        $row =  array();
        foreach( $this as $key=>$val ){ 
            if( $key != 'pages' ){
                $row[$key] = $val; 
            }
        }
    return $row;
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
     * @return Amino
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
     * Set a.
     *
     * @param int|null $a
     *
     * @return Amino
     */
    public function setA($a = null)
    {
        $this->A = $a;

        return $this;
    }

    /**
     * Get a.
     *
     * @return int|null
     */
    public function getA()
    {
        return $this->A;
    }

    /**
     * Set r.
     *
     * @param int|null $r
     *
     * @return Amino
     */
    public function setR($r = null)
    {
        $this->R = $r;

        return $this;
    }

    /**
     * Get r.
     *
     * @return int|null
     */
    public function getR()
    {
        return $this->R;
    }

    /**
     * Set n.
     *
     * @param int|null $n
     *
     * @return Amino
     */
    public function setN($n = null)
    {
        $this->N = $n;

        return $this;
    }

    /**
     * Get n.
     *
     * @return int|null
     */
    public function getN()
    {
        return $this->N;
    }

    /**
     * Set d.
     *
     * @param int|null $d
     *
     * @return Amino
     */
    public function setD($d = null)
    {
        $this->D = $d;

        return $this;
    }

    /**
     * Get d.
     *
     * @return int|null
     */
    public function getD()
    {
        return $this->D;
    }

    /**
     * Set c.
     *
     * @param int|null $c
     *
     * @return Amino
     */
    public function setC($c = null)
    {
        $this->C = $c;

        return $this;
    }

    /**
     * Get c.
     *
     * @return int|null
     */
    public function getC()
    {
        return $this->C;
    }

    /**
     * Set q.
     *
     * @param int|null $q
     *
     * @return Amino
     */
    public function setQ($q = null)
    {
        $this->Q = $q;

        return $this;
    }

    /**
     * Get q.
     *
     * @return int|null
     */
    public function getQ()
    {
        return $this->Q;
    }

    /**
     * Set e.
     *
     * @param int|null $e
     *
     * @return Amino
     */
    public function setE($e = null)
    {
        $this->E = $e;

        return $this;
    }

    /**
     * Get e.
     *
     * @return int|null
     */
    public function getE()
    {
        return $this->E;
    }

    /**
     * Set h.
     *
     * @param int|null $h
     *
     * @return Amino
     */
    public function setH($h = null)
    {
        $this->H = $h;

        return $this;
    }

    /**
     * Get h.
     *
     * @return int|null
     */
    public function getH()
    {
        return $this->H;
    }

    /**
     * Set i.
     *
     * @param int|null $i
     *
     * @return Amino
     */
    public function setI($i = null)
    {
        $this->I = $i;

        return $this;
    }

    /**
     * Get i.
     *
     * @return int|null
     */
    public function getI()
    {
        return $this->I;
    }

    /**
     * Set l.
     *
     * @param int|null $l
     *
     * @return Amino
     */
    public function setL($l = null)
    {
        $this->L = $l;

        return $this;
    }

    /**
     * Get l.
     *
     * @return int|null
     */
    public function getL()
    {
        return $this->L;
    }

    /**
     * Set k.
     *
     * @param int|null $k
     *
     * @return Amino
     */
    public function setK($k = null)
    {
        $this->K = $k;

        return $this;
    }

    /**
     * Get k.
     *
     * @return int|null
     */
    public function getK()
    {
        return $this->K;
    }

    /**
     * Set m.
     *
     * @param int|null $m
     *
     * @return Amino
     */
    public function setM($m = null)
    {
        $this->M = $m;

        return $this;
    }

    /**
     * Get m.
     *
     * @return int|null
     */
    public function getM()
    {
        return $this->M;
    }

    /**
     * Set f.
     *
     * @param int|null $f
     *
     * @return Amino
     */
    public function setF($f = null)
    {
        $this->F = $f;

        return $this;
    }

    /**
     * Get f.
     *
     * @return int|null
     */
    public function getF()
    {
        return $this->F;
    }

    /**
     * Set p.
     *
     * @param int|null $p
     *
     * @return Amino
     */
    public function setP($p = null)
    {
        $this->P = $p;

        return $this;
    }

    /**
     * Get p.
     *
     * @return int|null
     */
    public function getP()
    {
        return $this->P;
    }

    /**
     * Set o.
     *
     * @param int|null $o
     *
     * @return Amino
     */
    public function setO($o = null)
    {
        $this->O = $o;

        return $this;
    }

    /**
     * Get o.
     *
     * @return int|null
     */
    public function getO()
    {
        return $this->O;
    }

    /**
     * Set s.
     *
     * @param int|null $s
     *
     * @return Amino
     */
    public function setS($s = null)
    {
        $this->S = $s;

        return $this;
    }

    /**
     * Get s.
     *
     * @return int|null
     */
    public function getS()
    {
        return $this->S;
    }

    /**
     * Set u.
     *
     * @param int|null $u
     *
     * @return Amino
     */
    public function setU($u = null)
    {
        $this->U = $u;

        return $this;
    }

    /**
     * Get u.
     *
     * @return int|null
     */
    public function getU()
    {
        return $this->U;
    }

    /**
     * Set t.
     *
     * @param int|null $t
     *
     * @return Amino
     */
    public function setT($t = null)
    {
        $this->T = $t;

        return $this;
    }

    /**
     * Get t.
     *
     * @return int|null
     */
    public function getT()
    {
        return $this->T;
    }

    /**
     * Set w.
     *
     * @param int|null $w
     *
     * @return Amino
     */
    public function setW($w = null)
    {
        $this->W = $w;

        return $this;
    }

    /**
     * Get w.
     *
     * @return int|null
     */
    public function getW()
    {
        return $this->W;
    }

    /**
     * Set y.
     *
     * @param int|null $y
     *
     * @return Amino
     */
    public function setY($y = null)
    {
        $this->Y = $y;

        return $this;
    }

    /**
     * Get y.
     *
     * @return int|null
     */
    public function getY()
    {
        return $this->Y;
    }

    /**
     * Set b.
     *
     * @param int|null $b
     *
     * @return Amino
     */
    public function setB($b = null)
    {
        $this->B = $b;

        return $this;
    }

    /**
     * Get b.
     *
     * @return int|null
     */
    public function getB()
    {
        return $this->B;
    }

    /**
     * Set z.
     *
     * @param int|null $z
     *
     * @return Amino
     */
    public function setZ($z = null)
    {
        $this->Z = $z;

        return $this;
    }

    /**
     * Get z.
     *
     * @return int|null
     */
    public function getZ()
    {
        return $this->Z;
    }

    /**
     * Set j.
     *
     * @param int|null $j
     *
     * @return Amino
     */
    public function setJ($j = null)
    {
        $this->J = $j;

        return $this;
    }

    /**
     * Get j.
     *
     * @return int|null
     */
    public function getJ()
    {
        return $this->J;
    }

    /**
     * Set x.
     *
     * @param int|null $x
     *
     * @return Amino
     */
    public function setX($x = null)
    {
        $this->X = $x;

        return $this;
    }

    /**
     * Get x.
     *
     * @return int|null
     */
    public function getX()
    {
        return $this->X;
    }

    /**
     * Add page.
     *
     * @param \Protein\CoreBundle\Entity\Page $page
     *
     * @return Amino
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
     * Set g.
     *
     * @param int|null $g
     *
     * @return Amino
     */
    public function setG($g = null)
    {
        $this->G = $g;

        return $this;
    }

    /**
     * Get g.
     *
     * @return int|null
     */
    public function getG()
    {
        return $this->G;
    }

    /**
     * Set v.
     *
     * @param int|null $v
     *
     * @return Amino
     */
    public function setV($v = null)
    {
        $this->V = $v;

        return $this;
    }

    /**
     * Get v.
     *
     * @return int|null
     */
    public function getV()
    {
        return $this->V;
    }
}
