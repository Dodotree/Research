<?php

namespace Protein\CoreBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

use Protein\CoreBundle\Entity\Amino;

class AminoCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('app:amino')
            ->addArgument('path', InputArgument::REQUIRED, 'The path to file.')
            ->addArgument('page', InputArgument::REQUIRED, 'Page slug for amino acids collection')
            ->setDescription('Command to parse .fasta file for amino acids count')
            ->setHelp('Use as command for parsing and recording amino acids in .fasta files');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $em = $container->get('doctrine')->getManager();
        $path = $input->getArgument('path');
        $slug = $input->getArgument('page');
        $this->parseFASTA($em, $path, $slug);
        opcache_reset();
        $output->writeln("Done!");
    }

    public function parseFASTA($em, $filename, $slug){

        $amino_repo = $em->getRepository('Core:Amino');

        $page_repo = $em->getRepository('Core:Page');
        if($slug == '' or  !$page=$page_repo->find($slug)){
            $output->writeln("Page not found");
            return;
        }

        $logfile = "customProcesses/aminoacids_log/$slug/progress";
        if (!is_dir("customProcesses/aminoacids_log/$slug")) {
            mkdir("customProcesses/aminoacids_log/$slug", 0777, true);
        }

        $fh = fopen($filename, 'r');
        if(!$fh){
            return false;
        }

        file_put_contents($logfile, 0);
        $tot = (int)exec("wc -l '$filename' 2>/dev/null") -1;
        if($tot < 1){
            $output->writeln("File is too small");
            return;
        }

        $csv_file = "downloads/$slug";
        $csv_arr = array('UniProt') + str_split("ARNDCQEGHILKMFPOSUTWYVBZJX");
        $csv_keys = array_flip( $csv_arr );
        file_put_contents($csv_file, implode(',', $csv_arr)."\n");

        $i = 0;
        $i_ln = 0;
        while($line= fgets($fh)){
            $i_ln++;
            if( strpos($line, '>') === 0 ){
                if( isset($current['UniProt']) and $current['UniProt'] != ''){
                    $this->setProteinAminoIfNew($em, $amino_repo, $current, $page, $csv_file, $csv_keys);
                    $i++;
                    if( $i%100 < 2 ){
                        $em->flush();
                        $em->clear();
                        $page = $em->getRepository('Core:Page')->find($slug);
                        file_put_contents($logfile, round(100*$i_ln/$tot));
                        print round(100*$i_ln/$tot)."\n";
                    }
                }
                if( strlen($line) < 6 ){ continue; }

                list($arrow, $UniProt, $names) = explode('|', $line);

                $current = array(
                    'UniProt'=>$UniProt,
                    'line'=>'',
                );


            }elseif( isset($current['UniProt']) ){
                $current['line'] .= $line;
            }
        }

        if( isset($current['UniProt']) ){
            $this->setProteinAminoIfNew($em, $amino_repo, $current, $page, $csv_file, $csv_keys);
        }
        $em->flush();
        $em->clear();
        file_put_contents($logfile, 100);
        fclose($fh);
        unlink($filename);
    }

    public function setProteinAminoIfNew($em, $amino_repo, $current, $page, $csv_file, $csv_keys){
        if( $prot=$amino_repo->find($current['UniProt'])){
            $this->setProteinPage($em, $amino_repo, $prot, $page);

            $csv_arr = array();
            foreach( $csv_keys as $k=>$ind ){
                if( $k == 'UniProt' ){ $csv_arr[0] = $current['UniProt']; continue;}
                $getter = "get$k";
                $v = $prot->$getter();
                $csv_arr[$ind] = ($v)? $v: 0;
            }
            file_put_contents($csv_file, implode(',', $csv_arr)."\n", FILE_APPEND);

            return;
        }

        $prt = new Amino();
        $prt->setId($current['UniProt']);

        $line = trim($current['line']);
        $chars = count_chars($line, 1); # 1 -- is mode to return chars with counter > 1

        $csv_arr = array_fill(0, count($csv_keys), 0);
        $csv_arr[0] = $current['UniProt'];

        foreach ($chars as $code => $cnt) {
            $char = chr($code);
            if( $ind = strpos("ARNDCQEGHILKMFPOSUTWYVBZJX", $char) === false ){ continue; }

            $csv_arr[$ind+1] = $cnt; # +1 since UniProt takes first place

            $setter = "set$char";
#var_dump($char, $code, strpos("ARNDCQEGHILKMFPOSUTWYVBZJX", $char), '#######' );
            $prt->$setter($cnt);
        }

        file_put_contents($csv_file, implode(',', $csv_arr)."\n", FILE_APPEND);

        $prt->addPage($page);
        $page->addAminoacid($prt);
        $em->persist($prt);
        $em->persist($page);
    }


    public function setProteinPage($em, $amino_repo, $prot, $page){

        $qb = $amino_repo->createQueryBuilder("ent");
        $qb->select("count(ent.id) as total");
        $qb->innerJoin('ent.pages','pages')
          ->innerJoin('Core:Page', 'p', 'WITH','p.id = pages.id')
          ->where("p.id='" . $page->getId() . "'")
          ->andWhere("ent.id='" . $prot->getId() . "'");
        $res = $qb->getQuery()->getArrayResult();

        if( $res[0]['total'] == 0 ){
            $prot->addPage($page);
            $page->addAminoacid($prot);
            $em->persist($prot);
            $em->persist($page);
            $em->flush();
        }
    }

}


