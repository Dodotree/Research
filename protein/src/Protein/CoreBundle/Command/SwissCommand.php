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
            ->addArgument('page', InputArgument::REQUIRED, 'Page slug for list of UniProts that needs pdb files')
            ->setDescription('Command to request pdb files from Swiss site')
            ->setHelp('Use as command for creating requsts for models for each UniProt on the page that does not have pdb yet (or in case Swiss has pdb with better qmean already listed, retrive that)');
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $em = $container->get('doctrine')->getManager();
        $slug = $input->getArgument('page');

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

        $this->startProcess($em, $prot_repo, $mreq_repo, $pagedir, $slug, $output);

        opcache_reset();
        $output->writeln("Done!");
    }


    public function startProcess($em, $prot_repo, $mreq_repo, $pagedir, $slug, $output){

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
                $output->writeln("-----> request $UniProt");
                $this->requestModel($em, $prot_repo, $pagedir, $UniProt, $output);
            }

            file_put_contents("$pagedir/progress", round(100*$i/$tot));
        }        
        file_put_contents("$pagedir/progress", 100);
        #$this->rrmdir($pagedir);
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


    public function requestModel($em, $prot_repo, $pagedir, $UniProt, $output){

        $req_repo = $em->getRepository('Core:ModelRequest');
        $qr = $req_repo->createQueryBuilder("r");
        $date = new \DateTime();
        $date->modify('-24 hour');
        $requests_today = $qr->select('count(r.id)')
            ->andWhere('r.createdAt > :date')
            ->setParameter(':date', $date)
            ->getQuery()->getSingleScalarResult();

        if( $requests_today > 1995 ){
            file_put_contents("$pagedir/err", "Too many requests today, try again tomorrow");
            return false;
        }

        if( !$csrf_token = $this->setCookie_getCSRF( $pagedir, $UniProt ) ){
            file_put_contents("$pagedir/err", "No csrf for request $UniProt");
            return false;
        }

        #Accept  */*
        #Accept-Encoding gzip, deflate, br
        #Accept-Language en-US,en;q=0.5
        #Connection keep-alive
        #Content-Length 791
        #Content-Type application/x-www-form-urlencoded; charset=UTF-8
        #Cookie  csrftoken=yykpcqQQWcTnskln95xo…t48jybv24tzz8ku54mhovei5xs8u8 # one
        #Host swissmodel.expasy.org
        #Referer https://swissmodel.expasy.org/interactive
        #User-Agent Mozilla/5.0 (Windows NT 10.0; …) Gecko/20100101 Firefox/59.0
        #X-CSRFToken rcGlYKrez45RrRk8zRfOriyQg1SYuk…LsQk43XUskWxzvnix1zvlTOQJnrAR # two
        #X-Requested-With XMLHttpRequest

        $prot = $prot_repo->find($UniProt);
        $target = $prot->getSequence();

        if(!$target) {
            file_put_contents("$pagedir/err", "No sequence found for $UniProt");
            return false;
        }
        if(strpos($target,"X") !== false) {
            file_put_contents("$pagedir/err", "Sequence coutains X (not allowed) $UniProt");
            return false;
        }

        $name = ($prot->getName())? $prot->getName() : '';
        $gene = ($prot->getGene())? $prot->getGene() : '';
        $abbr = ($prot->getSpecies())? '_'. $prot->getSpecies()->getAbbr() : '';
        $title = "$gene$abbr $UniProt $name";

        $post = "csRadioGroup=secstruc"
                ."&csrfmiddlewaretoken=$csrf_token" # from text body
                ."&is_alignment=false"
                ."&target=$target" # sequence MPSPSRKSRSRSRSRSKSPKRSPAKKARKTPKKPRAAGGVKK...
                ."&aligned_template="
                ."&project_title=$title"
                ."&email="
                ."&automodel=true"
                ."&whatDoesThisDo=";

        $uri = "https://swissmodel.expasy.org/interactive";

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL,$uri);
        curl_setopt ($ch, CURLOPT_HTTPHEADER, Array(
            "Content-Type: application/x-www-form-urlencoded; charset=UTF-8",
            "Host: swissmodel.expasy.org",
            "X-CSRFToken: $csrf_token",
        ));
        curl_setopt($ch, CURLOPT_POST,1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_COOKIEJAR, "$pagedir/cookie");
        curl_setopt($ch, CURLOPT_COOKIEFILE, "$pagedir/cookie");

        $result=curl_exec ($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $last_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        curl_close ($ch);

        if ($httpCode != 200) {
            file_put_contents("$pagedir/err", "Failed to post swiss request form for $UniProt");
            return false;
        }
        if ($last_url == $uri) {
            file_put_contents("$pagedir/err_$UniProt", $result); 
            file_put_contents("$pagedir/err", "Redirected to same page $UniProt");
            return false;
        }

        # record redirect -- project url for later checks if ready
        
        $mreq = new ModelRequest();
        $mreq->setId($UniProt);
        $mreq->setUrl($last_url);
        $mreq->setCalledAt(new \DateTime);
        $em->persist($mreq);
        $em->flush();

        # $output->writeln($result);
        # $output->writeln("**************".$last_url);

        return true;
    }


    public function setCookie_getCSRF( $pagedir, $UniProt ){
        $uri = "https://swissmodel.expasy.org/interactive";

        $handle = curl_init();

        curl_setopt($handle, CURLOPT_URL, $uri);
        curl_setopt($handle, CURLOPT_POST, false);
        curl_setopt($handle, CURLOPT_BINARYTRANSFER, false);
        curl_setopt($handle, CURLOPT_HEADER, false);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($handle, CURLOPT_COOKIEJAR, "$pagedir/cookie");
        curl_setopt($handle, CURLOPT_COOKIEFILE, "$pagedir/cookie");

        $body = curl_exec($handle);
        $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);

        if ($httpCode != 200) {
            file_put_contents("$pagedir/err", "No reply for csrf request");
            return false;
        }

        # grep line  104: 
        #  xhr.setRequestHeader("X-CSRFToken", "SELrYWBuiaknKy4cmUQgUlHUgTKZXm9NvQeiAUyaKiH5rHt1HOHZHLcLm9yayjTX");
        $body_lines =  preg_split("/([\f\r\n]+)/", $body);
        $csrf_lines = preg_grep("/X-CSRFToken/", $body_lines);
        $csrf = preg_replace( "/.*X-CSRFToken\",\s+\"/", "", array_values($csrf_lines)[0]);
        $csrf = preg_replace( "/\"\);\s*/", "", $csrf); 

        #file_put_contents("$pagedir/$UniProt", file_get_contents("$pagedir/cookie"). $csrf);

    return $csrf;
    }


}
