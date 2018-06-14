<?php

namespace Protein\CoreBundle\Functions;

use Protein\CoreBundle\Entity\Upload;
use Protein\CoreBundle\Entity\Protein;
use Protein\CoreBundle\Entity\Species;

class ApiFunctions
{
    public function __construct($em, $container)
    {
        $this->em = $em;
        $this->container = $container;
        $this->user = $container->get('security.token_storage')->getToken()->getUser();
    }

    public function getEntityPagination($page, $entity, $isJoin, $page_ind, $per_page, $pageParam) {
        $ent_repo = $this->em->getRepository($entity);
        $qb = $ent_repo->createQueryBuilder("ent");
        $qb->select("ent");
        if($page and $isJoin){
           $qb->innerJoin('ent.pages','pages')
              ->innerJoin('Core:Page', 'p', 'WITH','p.id = pages.id')
              ->where("p.id='" . $page->getId() . "'");
        }
        if($page and !$isJoin){
             $qb->where("ent.page='" . $page->getId() . "'");
        }

        $paginator  = $this->container->get('knp_paginator');
        $q = $qb->getQuery(); //->getArrayResult();
        $entity_collection = $paginator->paginate($q,  $page_ind, $per_page, array('pageParameterName'=>$pageParam));
        $pagination = $entity_collection->getPaginationData();
        $collection = array();
        $ids = array();
        foreach($entity_collection as $e){
            $collection[] = $e->serializeArray(); // Entity should implement serializeArray()
            $ids[] = $e->getId();
        }
        $pagination['ids'] = $ids;
        $pagination['pageParameterName'] = $pageParam;
    return array($collection, $pagination);
    }

    public function setProteinPage($prot_repo, $prot, $page){

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
            $this->em->persist($prot);
            $this->em->persist($page);
            $this->em->flush();
        }
    }

    public function setProteinIfNew($prot_repo, $current, $page){
        if( $prot=$prot_repo->find($current['UniProt'])){ 
            $this->setProteinPage($prot_repo, $prot, $page);
            return; 
        }

        $prt = new Protein();
        $prt->setId($current['UniProt']);
        $prt->setName($current['name']);
        $prt->setGene($current['gene']);
        $prt->setLen($current['len']);
        if( isset($current['species']) or isset($current['species_abbr']) ){
            $spec_repo = $this->em->getRepository('Core:Species');
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
                $this->em->persist($spec);
            }
            $prt->setSpecies($spec);
        }
        $prt->setQmean($current['qmean']);
        $prt->setQmeanNorm($current['qmean_norm']);
        $prt->setIndexRecord($current['record']);
        $prt->setFilename($current['filename']);
        $prt->addPage($page);
        $page->addProtein($prt);
        $this->em->persist($prt);
        $this->em->persist($page);
    }

    public function parseFASTA($page, $filename, &$successes, &$errors, &$warnings){
        $prot_repo = $this->em->getRepository('Core:Protein');

        $fh = fopen($filename, 'r');
        if(!$fh){
            return false;
        }

        fgets($fh); 

        $i = 0;
        while($line= fgets($fh)){
            if( strpos($line, '>') === 0 ){
                if( isset($current['UniProt']) and $current['UniProt'] != ''){
                    $current['len'] = strlen($current['line']);
                    $this->setProteinIfNew($prot_repo, $current, $page);
                    $i++;
                    if( $i%100 < 2 ){
                        $this->em->flush();
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
            $this->setProteinIfNew($prot_repo, $current, $page);
        }
        $this->em->flush();
        fclose($fh);
        unlink($filename);
        $successes[] = "Inserted $i records";
    }

    
    public function collectPDB($page, $path, $filename, &$successes, &$errors, &$warnings){
        $index_repo = $this->em->getRepository('Core:Index');
        $prot_repo = $this->em->getRepository('Core:Protein');
        $upload_repo = $this->em->getRepository('Core:Upload');

        $fnm = preg_replace('/.*\//','', $filename);
        $filename_base = str_replace('.pdb', '', $fnm);
        $filename_base = str_replace('.PDB', '', $filename_base);

        $UniProt = $gene = $species = $abbr = $protein_name = $len = $qmean = $qmean_norm = null;

        $protein = $prot_repo->find($filename_base); #ca not be both
        $record = $index_repo->find($filename_base);

        if( $record ){
            $UniProt = $record->getUniProt();
            $len = $record->getLen();
            $qmean = $record->getQmean();
            $qmean_norm = $record->getQmeanNorm();
            $protein = $prot_repo->find($UniProt); #can not be both
        }else{
            #TITLE    2 A0A173FZD2_MYTTR A0A173FZD2 Cytochrome c oxidase subunit 1 # second line
            #REMARK   3  GMQE    0.75
            #REMARK   3  QMN4    -5.68
            $file = file($path);
            $title_line = trim($file[1]);
            $arr = preg_split('/\s+/',$title_line);
            if( count($arr) > 5){
                array_shift($arr);
                array_shift($arr);
                $gene_abbr = array_shift($arr);
                if( strpos($gene_abbr, '_') !== false ){
                    list($gene, $abbr) = explode('_', $gene_abbr);
                    $UniProt = array_shift($arr);
                }else{
                    $UniProt = $gene_abbr;
                }
                $protein_name = implode(' ', $arr);
            }
            $qmean_lines = preg_grep('/REMARK\s+3\s+QMN4/', $file);
            if(count($qmean_lines)>0){
                $qmean = preg_replace('/.*\s+/', '', trim(current(array_filter($qmean_lines))));
            }
        }

        if( !($protein or $UniProt) ){
            $errors[] = "Not able to Identify UniProt";
            return false;
        }
        if( $protein2 = $prot_repo->find($UniProt) and $protein ){
            $protein = $protein2;
        }

        $savefile = true;
        $savename = $fnm;
        $same_upload = $upload_repo->findOneBy(array('page'=>$page, 'UniProt'=>$UniProt, 'qmean'=>$qmean));
        $errors[] = array($fnm, is_null($same_upload) );
        if( $same_upload ){
            $savefile = false;
            $same_upload->setAttempts( $same_upload->getAttempts() + 1 );
            $this->em->persist($same_upload);
        }

        if( $savefile and file_exists("uploads/pdb/$fnm") ){
            if($record){ #in this case file exists for the record
                $savefile = false;
            }else{
                $savename = $this->getNextAvailableFilename( "uploads/pdb/", $filename_base, '.pdb', $errors );
                $savename .= ".pdb";
            }
        }

        if( !$protein ){
            # create new protein record
            $current = array(
                'UniProt'=>$UniProt,
                'gene'=>$gene,
                'name'=>$protein_name,
                'species'=>$species,
                'species_abbr'=>$abbr,
                'len'=>$len,
                'qmean'=>$qmean,
                'qmean_norm'=>$qmean_norm, 
                'filename'=>$savename,
                'record'=>$record,
            );
            $this->setProteinIfNew($prot_repo, $current, $page);
            $savefile = true;
        }elseif( $qmean < $protein->getQmean() ){
            $errors[] = "Current PDB has better Qmean";
            $savefile = false;
        }else{
            # replace qmean and filename in protein
            $protein->setQmean($qmean);
            $protein->setIndexRecord($record);
            $protein->setFilename($savename);
            $this->em->persist($protein);

            $this->setProteinPage($prot_repo, $protein, $page);
        }

        if( $savefile and !rename($path, "uploads/pdb/$savename") ){
            $errors[] = "Not able to move file to uploads/pdb folder";
        }

        if( !$same_upload ){
            $upload = new Upload();
            $upload->setUniProt($UniProt);
            $upload->setQmean($qmean);
            $upload->setFilename($savename);
            $upload->setIndexRecord($record);
            $upload->setPage($page);
            $this->em->persist($upload);
        }

        $this->em->flush();
    }


    public function fileDrop($file_type){
       if (!empty($_FILES)){
            foreach ($_FILES as $file) {
                if ($file['error'] != 0) {
                    $errors[] = array( 'text'=>'File error', 'error'=>$file['error'], 'name'=>$file['name']);
                    continue;
                }
                if(!$file['tmp_name']){
                    $errors[] = array( 'text'=>'Tmp file not found', 'name'=>$file['name']);
                    continue;
                }
                $tmp_file_path = $file['tmp_name'];
                $filename =  (isset($file['filename']) )? $file['filename'] : $file['name'];
                if( isset($_POST['dzuuid'])){
                    $chunks_res = $this->resumableUpload($tmp_file_path, $filename);
                    $chunks_res['name'] = $filename;
                    return $chunks_res;
                }
                return array('final'=>true, 'path'=>$file['tmp_name'], 'name'=>$filename);
             }
        }
    return false;
    }


    public function resumableUpload($tmp_file_path, $filename){
        $successes = array();
        $errors = array();
        $warnings = array();
        $dir = "uploads/tmp/";
            $identifier = ( isset($_POST['dzuuid']) )?  trim($_POST['dzuuid']) : '';
            $file_chunks_folder = "$dir$identifier";
            if (!is_dir($file_chunks_folder)) {
                mkdir($file_chunks_folder, 0777, true);
            }
            $filename = str_replace( array(' ','(', ')' ), '_', $filename ); # remove problematic symbols
            $info = pathinfo($filename);
            $extension = isset($info['extension'])? '.'.strtolower($info['extension']) : '';
            $filename = $info['filename'];
            $totalSize =   (isset($_POST['dztotalfilesize']) )?    (int)$_POST['dztotalfilesize'] : 0;
            $totalChunks = (isset($_POST['dztotalchunkcount']) )?  (int)$_POST['dztotalchunkcount'] : 0;
            $chunkInd =  (isset($_POST['dzchunkindex']) )?         (int)$_POST['dzchunkindex'] : 0;
            $chunkSize = (isset($_POST['dzchunksize']) )?          (int)$_POST['dzchunksize'] : 0;
            $startByte = (isset($_POST['dzchunkbyteoffset']) )?    (int)$_POST['dzchunkbyteoffset'] : 0;
            $chunk_file = "$file_chunks_folder/{$filename}.part{$chunkInd}";
            if (!move_uploaded_file($tmp_file_path, $chunk_file)) {
                $errors[] = array( 'text'=>'Move error', 'name'=>$filename, 'index'=>$chunkInd );
            }
            if( count($errors) == 0 and $new_path = $this->checkAllParts(  $file_chunks_folder,
                                                                    $filename,
                                                                    $extension,
                                                                    $totalSize,
                                                                    $totalChunks,
                                                                    $successes, $errors, $warnings) and count($errors) == 0){
                return array('final'=>true, 'path'=>$new_path, 'successes'=>$successes, 'errors'=>$errors, 'warnings' =>$warnings);
            }
    return array('final'=>false, 'successes'=>$successes, 'errors'=>$errors, 'warnings' =>$warnings);
    }

    public function checkAllParts( $file_chunks_folder,
                            $filename,
                            $extension,
                            $totalSize,
                            $totalChunks,
                            &$successes, &$errors, &$warnings){
        // reality: count all the parts of this file
        $parts = glob("$file_chunks_folder/*");
        $successes[] = count($parts)." of $totalChunks parts done so far in $file_chunks_folder";
        // check if all the parts present, and create the final destination file
        if( count($parts) == $totalChunks ){
            $loaded_size = 0;
            foreach($parts as $file) {
                $loaded_size += filesize($file);
            }
            if ($loaded_size >= $totalSize and $new_path = $this->createFileFromChunks(
                                                            $file_chunks_folder,
                                                            $filename,
                                                            $extension,
                                                            $totalSize,
                                                            $totalChunks,
                                                            $successes, $errors, $warnings) and count($errors) == 0){
                $this->cleanUp($file_chunks_folder);
                return $new_path;
            }
        }
    return false;
    }

    /**
     * Check if all the parts exist, and
     * gather all the parts of the file together
     * @param string $file_chunks_folder - the temporary directory holding all the parts of the file
     * @param string $fileName - the original file name
     * @param string $totalSize - original file size (in bytes)
     */
    public function createFileFromChunks($file_chunks_folder, $fileName, $extension, $total_size, $total_chunks,
                                            &$successes, &$errors, &$warnings) {
        $rel_path = "uploads/whole_from_chunks/";
        $saveName = $this->getNextAvailableFilename( $rel_path, $fileName, $extension, $errors );
        if( !$saveName ){
            return false;
        }
        $fp = fopen("$rel_path$saveName$extension", 'w');
        if ($fp === false) {
            $errors[] = 'cannot create the destination file';
            return false;
        }
        for ($i=0; $i<$total_chunks; $i++) {
            fwrite($fp, file_get_contents($file_chunks_folder.'/'.$fileName.'.part'.$i));
        }
        fclose($fp);
        return "$rel_path$saveName$extension";
    }

    public function getNextAvailableFilename( $rel_path, $orig_file_name, $extension, &$errors ){
        if( file_exists("$rel_path$orig_file_name$extension") ){
            $i=0;
            while(file_exists("$rel_path{$orig_file_name}_".(++$i).$extension) and $i<10000){}
            if( $i >= 10000 ){
                $errors[] = "Can not create unique name for saving file $orig_file_name$extension";
                return false;
            }
        return $orig_file_name."_".$i;
        }
    return $orig_file_name;
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

    public function cleanUp($file_chunks_folder){
        // rename the temporary directory (to avoid access from other concurrent chunks uploads) and than delete it
        if (rename($file_chunks_folder, $file_chunks_folder.'_UNUSED')) {
            $this->rrmdir($file_chunks_folder.'_UNUSED');
        } else {
            $this->rrmdir($file_chunks_folder);
        }
    }

}
