<?php

namespace Protein\CoreBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

use Protein\CoreBundle\Entity\Protein;
use Protein\CoreBundle\Entity\Species;

class FastaCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('app:fasta')
            ->addArgument('path', InputArgument::REQUIRED, 'The path to file.')
            ->addArgument('page', InputArgument::REQUIRED, 'Page slug')
            ->setDescription('Command to parse .fasta file')
            ->setHelp('Use as command for parsing and recording protein info in .fasta files');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $em = $container->get('doctrine')->getManager();
        $path = $input->getArgument('path');
        $slug = $input->getArgument('page');
        $this->parseFASTA($em, $output, $path, $slug);
        opcache_reset();
        $output->writeln("Done!");
    }

    public function parseFASTA($em, $output, $filename, $slug){

        $prot_repo = $em->getRepository('Core:Protein');

        $page_repo = $em->getRepository('Core:Page');
        if($slug == '' or  !$page=$page_repo->find($slug)){
            $output->writeln("Page not found");
            return;
        }

        $logfile = "customProcesses/fasta_log/$slug/progress";
        if (!is_dir("customProcesses/fasta_log/$slug")) {
            mkdir("customProcesses/fasta_log/$slug", 0777, true);
        }

        $fh = fopen($filename, 'r');
        if(!$fh){
            $output->writeln("Can not open file");
            return false;
        }

        file_put_contents($logfile, 0);
        $tot = (int)exec("wc -l '$filename' 2>/dev/null") -1;
        if($tot < 1){
            $output->writeln("File is too small");
            return;
        }

        $i = 0;
        $i_ln = 0;
        while($line= fgets($fh)){
            $i_ln++;
            if( strpos($line, '>') === 0 ){

                if( isset($current['UniProt']) and $current['UniProt'] != ''){
                    $current['len'] = strlen($current['line']);
                    $this->setProteinIfNew($em, $prot_repo, $current, $page);
                    $i++;
                    if( $i%100 == 0 and $i != 0 ){
                        $em->flush();
                        $em->clear();
                        $page = $em->getRepository('Core:Page')->find($slug);
                        file_put_contents($logfile, round(100*$i_ln/$tot));
                        print round(100*$i_ln/$tot)."\n";
                    }
                }
                if( strlen($line) < 6 ){ continue; }

                list($arrow, $UniProt, $names) = explode('|', $line);
                list($gene_abbr_name, $species_therest) = explode('OS=', $names);
                $protein_name = preg_replace("/^\w+\s+/",'', $gene_abbr_name);
                $gene_abbr = str_replace($protein_name, '', $gene_abbr_name);
                list($gene, $species_abbr) = explode('_', $gene_abbr);
                list($species, $therest) = explode('OX=', $species_therest);

                $current = array(
                    'UniProt'=>$UniProt,
                    'gene'=>$gene,
                    'name'=>$protein_name,
                    'species'=>$species,
                    'species_abbr'=>$species_abbr,
                    'line'=>'',
                    'len'=>null,
                    'qmean'=>null,
                    'qmean_norm'=>null, # we might insert those from pdb parsing
                    'filename'=>null,
                    'record'=>null,
                );

            }elseif( isset($current['UniProt']) ){
                $current['line'] .= $line;
            }
        }

        if( isset($current['UniProt']) ){
            $this->setProteinIfNew($em, $prot_repo, $current, $page);
        }
        $em->flush();
        $em->clear();
        file_put_contents($logfile, 100);
        fclose($fh);
        #unlink($filename);
    }


    public function setProteinPage($em, $prot_repo, $prot, $page){

        $qb = $prot_repo->createQueryBuilder("ent");
        $qb->select("count(ent.id) as total");
        $qb->innerJoin('ent.pages','pages')
          ->innerJoin('Core:Page', 'p', 'WITH','p.id = pages.id')
          ->where("p.id='" . $page->getId() . "'")
          ->andWhere("ent.id='" . $prot->getId() . "'");
        $res = $qb->getQuery()->getArrayResult();

        if( $res[0]['total'] == 0 ){
            $prot->addPage($page);
            $page->addProtein($prot);
            $em->persist($prot);
            $em->persist($page);
        }
    }

    public function setProteinIfNew($em, $prot_repo, $current, $page){
        if( $prot=$prot_repo->find($current['UniProt'])){

            $this->setProteinPage($em, $prot_repo, $prot, $page);

            $prot->setSequence($current['line']);

            if($current['gene'] and !$prot->getGene()){ $prot->setGene($current['gene']); }
            if($current['name'] and !$prot->getName()){ $prot->setName($current['name']); }
            if($current['species'] and !$prot->getSpecies()){
                $species = $em->getRepository('Core:Species')->findOneBy(array('name'=>$current['species']));
                $prot->setSpecies($species);
            }
            $em->persist($prot);

            return $prot;
        }

        $prt = new Protein();
        $prt->setId($current['UniProt']);
        $prt->setName($current['name']);
        $prt->setGene($current['gene']);
        $prt->setLen($current['len']);
        $prt->setSequence($current['line']);
        if( isset($current['species']) or isset($current['species_abbr']) ){
            $spec_repo = $em->getRepository('Core:Species');
            $spec = null;
            if(isset($current['species']) and !$spec = $spec_repo->findOneBy(array('name'=>$current['species']))){
            }
            if( !$spec and isset($current['species_abbr']) ){
                $spec = $spec_repo->findOneBy(array('abbr'=>$current['species_abbr']));
            }
            if(!$spec and isset($current['species'])){
                $spec = new Species();
                $spec->setName($current['species']);
                $spec->setAbbr($current['species_abbr']);
                $em->persist($spec);
            }
            $prt->setSpecies($spec);
        }
        $prt->setQmean($current['qmean']);
        $prt->setQmeanNorm($current['qmean_norm']);
        $prt->setIndexRecord($current['record']);
        $prt->setFilename($current['filename']);
        $prt->addPage($page);
        $page->addProtein($prt);
        $em->persist($prt);
        $em->persist($page);
    return $prt;
    }


}


