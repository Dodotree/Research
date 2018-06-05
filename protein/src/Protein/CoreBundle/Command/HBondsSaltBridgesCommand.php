<?php

namespace Protein\CoreBundle\Command;

#from public/
#php ../bin/console app:hbonds-bridges asdfaruiycvs

use Symfony\Component\Console\Command\Command;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

use Protein\CoreBundle\Entity\Index;

class HBondsSaltBridgesCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('app:hbonds-bridges')
            ->addArgument('page', InputArgument::REQUIRED, 'Page slug for proteins collection')
            ->setDescription('Command to parse Swiss INDEX file')
            ->setHelp('Use as command for parsing and recording');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $em = $container->get('doctrine')->getManager();
        $slug = $input->getArgument('page');
        $this->startProcess($em, $slug, $output);
        opcache_reset();
        $output->writeln("Done!");
    }

    public function startProcess($em, $slug, $output){

        $prot_repo = $em->getRepository('Core:Protein');
        $page_repo = $em->getRepository('Core:Page');
        if($slug == '' or  !$page=$page_repo->find($slug)){
            $output->writeln("Page not found");
            return;
        }

        $pagedir = "customProcesses/hbonds_bridges_log/$slug";
        if (!is_dir($pagedir)) {
            mkdir($pagedir, 0777, true);
        }
        file_put_contents("$pagedir/progress", 0);


        $qb = $prot_repo->createQueryBuilder("pr");
        $qb->select("pr.id, pr.filename, pr.bonds, pr.bridges")
          ->innerJoin('pr.pages','pages')
          ->innerJoin('Core:Page', 'p', 'WITH','p.id = pages.id')
          ->where("p.id='$slug'");
        $res = $qb->getQuery()->getArrayResult();

        $tot = count(array_keys($res));
        foreach($res as $i=>$prot){
            if(file_exists("uploads/pdb/{$prot['filename']}")){
                #$output->writeln($prot['filename']);
                $brgs = $this->curlBridges($prot['id'], $prot['filename'], $pagedir, $output);
                $hbonds = $this->curlHbonds($prot['filename'], $output);
                $this->setHbondsBridges($prot_repo, $prot['id'], $hbonds, $brgs);
                file_put_contents("$pagedir/progress", round(100*$i/$tot));
            }
        }        
        file_put_contents("$pagedir/progress", 100);
        $this->rrmdir($pagedir);
    }

    public function setHbondsBridges($prot_repo, $UniProt, $hbonds, $bridges){
        $qrb = $prot_repo->createQueryBuilder('pr');
        $qrb->update('Core:Protein', 'pr')
            ->set('pr.bonds', ':hbonds')
            ->set('pr.bridges', ':bridges')
            ->where('pr.id = :UniProt')
            ->setParameter('hbonds', $hbonds)
            ->setParameter('bridges', $bridges)
            ->setParameter('UniProt', $UniProt);
        $qrb->getQuery()->execute();
    }

    function rrmdir($dir) {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (filetype("$dir/$object") == "dir") {
                        $this->rrmdir("$dir/$object");
                    } else {
                        unlink("$dir/$object");
                    }
                }
            }
            reset($objects);
            rmdir($dir);
        }
    }

    public function curlHbonds($filename, $output){
        $target_url = "http://cib.cf.ocha.ac.jp/bitool/HBOND/HBOND.php";
        $path = realpath("uploads/pdb/$filename");
        $cFile = curl_file_create($path);
        $post = array(
            'OK'=>'start calculation',
            'sw_file'=>$cFile,
        );
 
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$target_url);
        curl_setopt($ch, CURLOPT_POST,1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300); 
        curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
         'Content-type: multipart/form-data;'
        ) );
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        $result=curl_exec ($ch);
        curl_close ($ch);

        $lines = explode("\n",$result);
        $hbonds = preg_grep('/^HBOND\s/', $lines);
        return count(array_keys($hbonds));
    }

    public function curlBridges($UniProt, $filename, $pagedir, $output){
        #http://bioinformatica.isa.cnr.it/ESBRI/input.html
        $target_url = "http://bioinformatica.isa.cnr.it/ESBRI/CGI/esegui.cgi";
        
        /*$post = array(
            'proteina'=>$UniProt,
            'input-text'=>file_get_contents("uploads/pdb/$filename"),
            'xxx'=>'xxx',
            'catena'=>'',
            'catena1bbb'=>'',
            'catena2bbb'=>'',
            'positivo'=>'Arg',
            'catena3'=>'',
            'numero3'=>'',
            'ngativo'=>'Asp',
            'catena4'=>'',
            'numero4'=>'',
            'dist'=>'4.0',
            'colore'=>'All%20black'
        );*/

        $post = "proteina=$UniProt&input-text=" . file_get_contents("uploads/pdb/$filename")
                ."&xxx=xxx&catena=&catena1bbb=&catena2bbb=&positivo=Arg&catena3=&numero3="
                ."&ngativo=Asp&catena4=&numero4=&dist=4.0&colore=All%20black";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$target_url);
        curl_setopt ($ch, CURLOPT_HTTPHEADER, Array("Content-Type: application/x-www-form-urlencoded"));
        curl_setopt($ch, CURLOPT_POST,1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300); 
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); 
        $result=curl_exec ($ch);
        curl_close ($ch);

        if( strpos($result, "The results are available at the following") === false ){
            $output->writeln("No link to results found");
            return null;
        }

        #plain text result will be available at http://bioinformatica.isa.cnr.it/ESBRI/CGI/tmp/INSERTED_proteina.txt
        #second curl needed to get that

        #A0A173FZD2___attempt
        #Residue 1   Residue 2   Distance
        #NH1 ARG A 21    OD1 ASP A 30    3.42
        #NH1 ARG A 21    OD2 ASP A 30    3.08
        #ND1 HIS A 277   OE1 GLU A 508   3.53
        #NE2 HIS A 277   OE1 GLU A 508   3.54

        $ch_plain = curl_init();
        curl_setopt($ch_plain, CURLOPT_URL, "http://bioinformatica.isa.cnr.it/ESBRI/CGI/tmp/$UniProt.txt");
        curl_setopt($ch_plain, CURLOPT_HEADER, false);
        curl_setopt($ch_plain, CURLOPT_RETURNTRANSFER, true);
        $text = curl_exec($ch_plain);
        curl_close($ch_plain);

        #file_put_contents("$pagedir/sample", $text);
        #$output->writeln($text);
        $lines = explode("\n", $text);
        return count($lines)-2;
    }

    public function countLines($pagedir){
        $fh = fopen($filename, 'r');
        if(!$fh){
            return false;
        }
        fgets($fh);
        $i = 0;
        while($line= fgets($fh)){
        }
        fclose($fh);
        unlink($filename);
    }

}
