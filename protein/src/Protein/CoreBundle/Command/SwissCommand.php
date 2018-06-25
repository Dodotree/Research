<?php

namespace Protein\CoreBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

use Protein\CoreBundle\Entity\ModelRequest;

class SwissCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('app:swiss')
            ->addArgument('cmd', InputArgument::REQUIRED, 'Commands "request" and "collect"')
            ->addArgument('page', InputArgument::REQUIRED, 'Page slug for list of UniProts that needs pdb files')
            ->setDescription('Command to request pdb files from Swiss site')
            ->setHelp('Use as command for creating requsts for models for each UniProt on the page that does not have pdb yet (or in case Swiss has pdb with better qmean already listed, retrive that)');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $em = $container->get('doctrine')->getManager();
        $slug = $input->getArgument('page');
        $command = $input->getArgument('cmd');

        $output->writeln("$$$$$$$$$$ $command");

        if($command == 'request'){
            $this->startProcess($em, $slug, $output);
        }
        if($command == 'collect'){
            $this->collectModels($em, $UniProt, $page, $output);
        }
        opcache_reset();
        $output->writeln("Done!");
    }


    public function startProcess($em, $slug, $output){

        $prot_repo = $em->getRepository('Core:Protein');
        $mreq_repo = $em->getRepository('Core:ModelRequest');
        $page_repo = $em->getRepository('Core:Page');
        if($slug == '' or  !$page=$page_repo->find($slug)){
            $output->writeln("Page not found");
            return;
        }

        $pagedir = "customProcesses/swiss_log/$slug";
        if (!is_dir($pagedir)) {
            mkdir($pagedir, 0777, true);
        }
        file_put_contents("$pagedir/progress", 0);


        $qb = $prot_repo->createQueryBuilder("pr");
        $qb->select("pr.id, pr.filename, pr.qmean")
          ->innerJoin('pr.pages','pages')
          ->innerJoin('Core:Page', 'p', 'WITH','p.id = pages.id')
          ->where("p.id='$slug'");
        $res = $qb->getQuery()->getArrayResult();

        $tot = count(array_keys($res));
        $output->writeln("$$$$$$$$$$ $tot");

        foreach($res as $i=>$prot){
            # technically pdb file could be not the best
            if( ($prot['filename'] != '' and file_exists("uploads/pdb/{$prot['filename']}")) 
                or $mreq_repo->find($prot['id'])){
                continue;
            }

            $UniProt = $prot['id'];
            $output->writeln("$$$$$$$$$ $UniProt");
            if( !$this->checkAPIifModelExists($em, $UniProt, $pagedir, $output) ) {
                $this->requestModel($em, $UniProt);
            }

            file_put_contents("$pagedir/progress", round(100*$i/$tot));
        }        
        file_put_contents("$pagedir/progress", 100);
        #$this->rrmdir($pagedir);
    }


    public function collectModels($em, $UniProt, $page, $output){

        $prot_repo = $em->getRepository('Core:Protein');
        $mreq_repo = $em->getRepository('Core:ModelRequest');
        $page_repo = $em->getRepository('Core:Page');
        if($slug == '' or  !$page=$page_repo->find($slug)){
            $output->writeln("Page not found");
            return;
        }

        $pagedir = "customProcesses/swiss_log/$slug";
        if (!is_dir($pagedir)) {
            mkdir($pagedir, 0777, true);
        }

        # take page proteins vs page proteins without files
        file_put_contents("$pagedir/collect_progress", 0);

        $reqs = $mreq_repo->findBy(array('status'=>0)); # for all pages!
        foreach($reqs as $req){
            # if 2 weeks, remove
            # if time() + (callcount*2)^3 > createdAt
                # check if url ready
                # check if there are models (if not -> model impossible to UniProt table, continue)
                # find the best
                # if( $this->loadModelPDB($UniProt, $url) ){ $em->remove($model_request); }
                # else err. loading model to $model_request, $last_error

            #$output->writeln($prot['filename']);
            #$brgs = $this->curlBridges($prot['id'], $prot['filename'], $pagedir, $output);
            #$hbonds = $this->curlHbonds($prot['filename'], $output);
            #$this->setHbondsBridges($prot_repo, $prot['id'], $hbonds, $brgs);

        }

        return false;
    }


    public function checkAPIifModelExists($em, $UniProt, $pagedir, $output){
        $uri = "https://swissmodel.expasy.org/repository/uniprot/$UniProt.json";
        $handle = curl_init();

        curl_setopt($handle, CURLOPT_URL, $uri);
        curl_setopt($handle, CURLOPT_POST, false);
        curl_setopt($handle, CURLOPT_BINARYTRANSFER, false);
        curl_setopt($handle, CURLOPT_HEADER, true);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 10);

        $response = curl_exec($handle);
        $hlength  = curl_getinfo($handle, CURLINFO_HEADER_SIZE);
        $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        $body = substr($response, $hlength);

        if ($httpCode != 200) {
            file_put_contents("$pagedir/err", "No info file for $UniProt");
            return false;
        }

        if (!$info = json_decode($body, true)) {
            file_put_contents("$pagedir/err", "Bad JSON in info file for $UniProt");
            return false;
        }

        if (!isset($info['result']) or !isset($info['result']['structures']) or count($info['result']['structures']) == 0) {
            file_put_contents("$pagedir/err", "No structures in info file for $UniProt");
            return false;
        }

        $best_structure = 'notset';
        foreach( $info['result']['structures'] as $structure ) {
            if( !isset($structure['qmean']) ) { continue; }
            if( $best_structure == 'notset' or $structure['qmean'] > $best_structure['qmean'] ){
                $best_structure = $structure;
            }
        }

        if( $best_structure == 'notset' ) {
            return false;
        }

        if( !$filename = $this->loadBestRepositoryPDB($best_structure['coordinates'], $UniProt, 
                                                         $best_structure['template'], $pagedir, $output) ){
            return false;
        }

        $output->writeln($filename);

    return $this->registerPDB($em, $filename, $structure, $UniProt);
    }


    public function loadBestRepositoryPDB($best_uri, $UniProt, $template, $pagedir, $output){
        # Returns the best homology model or experimental structure in PDB format.
        # without any information inside, just atoms
        # $uri = "https://swissmodel.expasy.org/repository/uniprot/$UniProt.pdb";

        $handle = curl_init();

        curl_setopt($handle, CURLOPT_URL, $best_uri);
        curl_setopt($handle, CURLOPT_POST, false);
        curl_setopt($handle, CURLOPT_BINARYTRANSFER, false);
        curl_setopt($handle, CURLOPT_HEADER, true);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 10);

        $response = curl_exec($handle);
        $hlength  = curl_getinfo($handle, CURLINFO_HEADER_SIZE);
        $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        $header = substr($response, 0, $hlength);
        $body = substr($response, $hlength);

        if ($httpCode != 200) {
            file_put_contents("$pagedir/err", "No best pdb file for $UniProt");
            return false;
        }

        $headers = $this->getHeaders($header);
        $filename = isset($headers['filename']) ? $headers['filename'] : $UniProt."_".$template;
        file_put_contents("uploads/pdb/$filename", $body);

        return $filename;
    }


    public function getHeaders($header){
        $lines =  preg_split("/([\f\r\n]+)/", $header);
        $headers = array();
        foreach($lines as $line){
            $arr = explode(':', $line, 2);
            $headers[ trim($arr[0]) ] = isset($arr[1])? trim($arr[1]) : '';
        }
        if(isset($headers["Content-Disposition"]) and preg_match('/.*filename=([^ ]+)/', $headers["Content-Disposition"], $matches)) {
            $headers['filename'] = $matches[1];
        }
        return $headers;
    }


    public function loadModelPDB($UniProt, $page, $url){
        # $this->registerPDB($em, $filename, $structure, $UniProt);
        return false;
    }


    public function registerPDB($em, $filename, $structure, $UniProt){ # valid for all pages
        if(!$prot = $em->getRepository('Core:Protein')->find($UniProt)){
            return false;
        }
        $prot_qmean = $prot->getQmean();
        if( is_null($prot_qmean) or $structure['qmean'] > $prot_qmean ){
            $prot->setQmean($structure['qmean']);
            $prot->setQmeanNorm($structure['qmean_norm']);
            $prot->setFilename($filename);
            $em->persist($prot);
            $em->flush();
            return true;
        }
        return false;
    }


    public function requestModel($em, $UniProt){

        #csRadioGroup=secstruc
        #&csrfmiddlewaretoken=$csrf_token
        #&is_alignment=false
        #&target=
        #&aligned_template=
        #&project_title=$title
        #&email=
        #&automodel=true
        #&whatDoesThisDo=

        # record redirect -- project url for later checks if ready

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
