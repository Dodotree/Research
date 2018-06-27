<?php

namespace Protein\CoreBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

use Protein\CoreBundle\Entity\ModelRequest;

class CollectCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('app:collect')
            ->setDescription('Command to request pdb files from Swiss site')
            ->setHelp('Use as command for creating requsts for models for each UniProt on the page that does not have pdb yet (or in case Swiss has pdb with better qmean already listed, retrive that)');
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $em = $container->get('doctrine')->getManager();

        $prot_repo = $em->getRepository('Core:Protein');
        $mreq_repo = $em->getRepository('Core:ModelRequest');

        $pagedir = "customProcesses/collect_log";

        $this->collectModels($em, $prot_repo, $mreq_repo, $pagedir, $output);

        opcache_reset();
        $output->writeln("Done!");
    }


    public function collectModels($em, $prot_repo, $mreq_repo, $pagedir, $output){

        # take page proteins vs page proteins without files
        file_put_contents("$pagedir/progress", 0);

        $reqs = $mreq_repo->findBy(array('status'=>0)); # for all pages!
        $today = new \DateTime;
        foreach($reqs as $req){
            # if 2 weeks, remove
            $diff = $req->getCreatedAt()->diff($today);
            
            $callcount = $req->getCallcount();
            if( $diff->d > 14 ){
                $em->remove($req);
                continue;
            }
            if( $diff->i > 0*pow($callcount*2, 3) ){
                if( !$filename = $this->checkModelPage($em, $req, $pagedir, $output) ){

                    $req->setCalledAt(new \DateTime);
                    $req->setCallcount( $callcount+1 );
                    $em->persist($req);
                    $em->flush();
                    continue;
                }

                $em->remove($req);
                #$output->writeln($prot['filename']);
                #$brgs = $this->curlBridges($prot['id'], $prot['filename'], $pagedir, $output);
                #$hbonds = $this->curlHbonds($prot['filename'], $output);
                #$this->setHbondsBridges($prot_repo, $prot['id'], $hbonds, $brgs);
            }
        }
        return false;
    }


    public function checkModelPage($em, $req, $pagedir, $output){

        # https://swissmodel.expasy.org/interactive/G96rEu/models/
        # remove /models/ to get to the summary
        # then load "interactive/G96rEu/models/01.pdb" by best model id 

        $UniProt = $req->getId();
        $uri = $req->getUrl();
        $uri = str_replace('/models/', '/', $uri);

        $output->writeln($uri);

        $handle = curl_init();

        curl_setopt($handle, CURLOPT_URL, $uri);
        curl_setopt($handle, CURLOPT_POST, false);
        curl_setopt($handle, CURLOPT_BINARYTRANSFER, false);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 10);

        $response = curl_exec($handle);
        $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);

        if ($httpCode != 200) {
            file_put_contents("$pagedir/err", "No summary page for $UniProt");
            return false;
        }

        # check if there are models (if not -> model impossible to UniProt table, continue)
        # find the best

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($response, LIBXML_NOWARNING);
        libxml_use_internal_errors(false);
        $ths = $dom->getElementsByTagName('th');
        foreach( $ths as $th ){
            if( $th->hasAttribute('title') and $th->getAttribute('title')=="Global Model Quality Estimate"){
                $table = $th->parentNode->parentNode->parentNode->getElementsByTagName('tbody')->item(0);
                break;
            }
        }
        $best_qmean = -1000000000;
        $best_id = "01";
        $best_template = "";
        foreach( $table->childNodes as $tr ){
            $id = $tr->childNodes->item(1)->textContent;
            $template = $tr->childNodes->item(2)->textContent;
            $qmean = (float)$tr->childNodes->item(4)->textContent;
            if($qmean > $best_qmean){
                $best_qmean = $qmean;
                $best_id = $id;
                $best_template = $template;
            }
        }

        $output->writeln("$best_id $best_qmean");

        $url = "{$uri}models/$best_id.pdb";

        if( !$filename = $this->loadBestRepositoryPDB($url, $UniProt, $best_template, $pagedir, $output) ){ 
            return false; 
        }

    return $this->registerPDB($em, $filename, $best_qmean, $UniProt);
    }


    public function loadBestRepositoryPDB($best_uri, $UniProt, $template, $pagedir, $output){
        # Returns the best homology model or experimental structure in PDB format.
        # without any information inside, just atoms
        # $uri = "https://swissmodel.expasy.org/repository/uniprot/$UniProt.pdb";

        $handle = curl_init();

        curl_setopt($handle, CURLOPT_URL, $best_uri);
        curl_setopt($handle, CURLOPT_POST, false);
        curl_setopt($handle, CURLOPT_BINARYTRANSFER, false);
        curl_setopt($handle, CURLOPT_HEADER, false);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 10);

        $response = curl_exec($handle);
        $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);

        if ($httpCode != 200) {
            file_put_contents("$pagedir/err", "No best pdb file for $UniProt");
            return false;
        }

        $filename = "{$UniProt}_$template.pdb";
        file_put_contents("uploads/pdb/$filename", $response);

        return $filename;
    }


    public function registerPDB($em, $filename, $qmean, $UniProt){ # valid for all pages
        if(!$prot = $em->getRepository('Core:Protein')->find($UniProt)){
            return false;
        }
        $prot_qmean = $prot->getQmean();
        if( is_null($prot_qmean) or $qmean > $prot_qmean ){
            $prot->setQmean($qmean);
            $prot->setFilename($filename);
            $em->persist($prot);
            $em->flush();
            return true;
        }
        return false;
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

}
