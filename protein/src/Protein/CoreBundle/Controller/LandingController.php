<?php

namespace Protein\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use Protein\CoreBundle\Entity\Page;

class LandingController extends Controller
{
    public function landingAction($_subpage='', $pageslug='', Request $request){
        #exec("nohup php customProcesses/parsingWrapper.php uploads/whole_from_chunks/INDEX_10 >>uploads/out 2>>uploads/err &");
        file_put_contents('uploads/out', "landing\n", FILE_APPEND);

        $em = $this->getDoctrine()->getManager();

        $page_repo = $em->getRepository('Core:Page');
        if($pageslug == '' or !$page = $page_repo->find($pageslug)){
            $page = $this->getPage();
            return $this->redirect($this->generateUrl('protein_core_page', ['pageslug'=>$page->getId()]));
        }

        $subpage_ind = ($_subpage != '') ? $_subpage : 1;
        $per_page = 1000;
        list($proteins, $pagination) = $this->get('api_functions')->getEntityPagination(
            $page, 
            'Core:Protein', true, 
            $subpage_ind, 
            $per_page,
            'proteinPage');  

        list($uploads, $u_pagination) = $this->get('api_functions')->getEntityPagination(
            $page, 
            'Core:Upload', false,
            $subpage_ind, 
            $per_page,
            'uploadPage');  

        $route_name = $request->attributes->get('_route');

        exec( "ps -ax|grep 'app:parse-index'|grep -v grep", $parsing_processes );
        $parsing_on = ( count($parsing_processes)> 0 );
        $ind_repo = $em->getRepository('Core:Index');
        $qb = $ind_repo->createQueryBuilder("fi");
        $qb->select("count(fi)");
        $count = $qb->getQuery()->getSingleScalarResult();

        exec( "ps -ax|grep 'app:amino'|grep -v grep", $amino_parsing_processes );
        $amino_parsing_on = ( count($amino_parsing_processes)> 0 );
        $amino_repo = $em->getRepository('Core:Amino');
        $qb2 = $amino_repo->createQueryBuilder("fi");
        $qb2->select("count(fi)")
          ->innerJoin('fi.pages','pages')
          ->innerJoin('Core:Page', 'p', 'WITH','p.id = pages.id')
          ->where("p.id='$pageslug'");
        $amino_count = $qb2->getQuery()->getSingleScalarResult();

        exec( "ps -ax|grep 'app:hbonds-bridges $pageslug'|grep -v grep", $hbonds_processes );
        $hbonds_on = ( count($hbonds_processes)> 0 );
        $pagedir = "customProcesses/hbonds_bridges_log/$pageslug";
        $hbonds_progress = (file_exists("$pagedir/progress"))? file_get_contents("$pagedir/progress") : 0;

        $pagedir = "customProcesses/models_log/$pageslug";
        $models_on = false;
        $models_progress = (file_exists("$pagedir/progress"))? file_get_contents("$pagedir/progress") : 0;

        $pages = $page_repo->findAll();

        foreach( $pages as $page ){
            if( $page->getId() == $pageslug ){ continue; }
            if( count($page->getProteins()) < 1 and count($page->getAminoacids()) < 1 and count($page->getUploads()) < 1){ $em->remove($page); }
        }
        $em->flush();

        return $this->render('@ProteinCore/index.html.twig', [
            'pageslug'=>$pageslug,
            'proteins'=> $proteins, 
            'pagination'=>json_encode(array('pagination'=>$pagination)),
            'uploads'=> $uploads, 
            'index_count'=> $count,
            'parsing_on'=> $parsing_on,
            'amino_index_count'=> $amino_count,
            'amino_parsing_on'=> $amino_parsing_on,
            'hbonds_on'=> $hbonds_on,
            'hbonds_progress'=>$hbonds_progress,
            'models_on'=>$models_on,
            'models_progress'=>$models_progress,
            'pages'=> $pages,
            ]);
    }


    public function indexglobalAction($_subpage='', Request $request){
        file_put_contents('uploads/out', "indexglobal\n", FILE_APPEND);

        $em = $this->getDoctrine()->getManager();

        $subpage_ind = ($_subpage != '') ? $_subpage : 1;
        $per_page = 1000;

        list($proteins, $pagination) = $this->get('api_functions')->getEntityPagination(
            null,
            'Core:Index', true,
            $subpage_ind,
            $per_page,
            'indexPage');

        return $this->render('@ProteinCore/indexglobal.html.twig', [
            'proteins'=>$proteins,
            'pagination'=>json_encode(array('pagination'=>$pagination)),
        ]);
    }


    public function proteinAction($_subpage='', Request $request){
        file_put_contents('uploads/out', "protein\n", FILE_APPEND);

        $em = $this->getDoctrine()->getManager();

        $subpage_ind = ($_subpage != '') ? $_subpage : 1;
        $per_page = 1000;

        list($proteins, $pagination) = $this->get('api_functions')->getEntityPagination(
            null,
            'Core:Protein', true,
            $subpage_ind,
            $per_page,
            'indexPage');

        return $this->render('@ProteinCore/protein.html.twig', [
            'proteins'=>$proteins,
            'pagination'=>json_encode(array('pagination'=>$pagination)),
        ]);
    }


    public function speciesAction($_subpage='', Request $request){
        file_put_contents('uploads/out', "species\n", FILE_APPEND);

        $em = $this->getDoctrine()->getManager();

        $subpage_ind = ($_subpage != '') ? $_subpage : 1;
        $per_page = 1000;

        list($proteins, $pagination) = $this->get('api_functions')->getEntityPagination(
            null,
            'Core:Species', true,
            $subpage_ind,
            $per_page,
            'indexPage');

        return $this->render('@ProteinCore/species.html.twig', [
            'proteins'=>$proteins,
            'pagination'=>json_encode(array('pagination'=>$pagination)),
        ]);
    }


    public function aminotableAction($_subpage='', $pageslug='', Request $request){
        file_put_contents('uploads/out', "amino table\n", FILE_APPEND);

        $em = $this->getDoctrine()->getManager();

        $page_repo = $em->getRepository('Core:Page');
        if($pageslug == '' or !$page = $page_repo->find($pageslug)){
            return $this->redirect($this->generateUrl('protein_core_page', ['pageslug'=>$pageslug]));
        }

        $subpage_ind = ($_subpage != '') ? $_subpage : 1;
        $per_page = 1000;

        list($proteins, $pagination) = $this->get('api_functions')->getEntityPagination(
            $page, 
            'Core:Amino', true,
            $subpage_ind, 
            $per_page,
            'aminoPage');  

        return $this->render('@ProteinCore/amino.html.twig', [
            'proteins'=>$proteins,
            'pagination'=>json_encode(array('pagination'=>$pagination)),
            'pageslug'=>$pageslug,
        ]);
    }

    public function aminoAction(Request $request){
        file_put_contents('uploads/out', "amino file\n", FILE_APPEND);

        $page = $this->getPage();
        $slug = $page->getId();

        $successes = array();
        $errors = array();
        $warnings = array();

        if ( $file_upload_res = $this->get('api_functions')->fileDrop('AMINO', $errors) ){
            if( $file_upload_res['final'] ){
                ### >/dev/null 2>/dev/null    id really important for initiating async process
                exec("nohup php customProcesses/aminoacidsWrapper.php {$file_upload_res['path']}  $slug >/dev/null 2>/dev/null &");
            }
            return $this->json( $file_upload_res );
        }
        $errors[] = 'fileDrop returned false';
        return $this->json(array( 'errors'=>$errors, 'warnings'=>$warnings ));
    }
 
    public function indexAction(Request $request){
        file_put_contents('uploads/out', "index file\n", FILE_APPEND);

        $successes = array();
        $errors = array();
        $warnings = array();

        if ( $file_upload_res = $this->get('api_functions')->fileDrop('INDEX', $errors) ){
            if( $file_upload_res['final'] ){
                ### >/dev/null 2>/dev/null    id really important for initiating async process
                #exec("nohup php ../bin/console app:parse-index {$file_upload_res['path']} >/dev/null 2>/dev/null &");
                exec("nohup php customProcesses/parsingWrapper.php {$file_upload_res['path']} >/dev/null 2>/dev/null &");
            }
            return $this->json( $file_upload_res );
        }
        $errors[] = 'fileDrop returned false';
        return $this->json(array( 'errors'=>$errors, 'warnings'=>$warnings ));
    }


    public function swissAction(Request $request){
        file_put_contents('uploads/out', "swiss command\n", FILE_APPEND);

        $page = $this->getPage();

        if(isset($_POST['start'])){
            ### >/dev/null 2>/dev/null    id really important for initiating async process
            exec("nohup php customProcesses/swissWrapper.php {$page->getId()} start >/dev/null 2>/dev/null &");
            return $this->json(array('successes'=>'Process initiated', 'page'=>$page->getId()));
        }
        if(isset($_POST['stop'])){
            exec("nohup php customProcesses/swissWrapper.php {$page->getId()} stop >/dev/null 2>/dev/null &");
            return $this->json(array('successes'=>'Process terminate', 'page'=>$page->getId()));
        }
        return $this->json(array('No start/stop action provided'));
    }

 
    public function calculateAction(Request $request){
        file_put_contents('uploads/out', "calculate command\n", FILE_APPEND);

        $page = $this->getPage();

        if(isset($_POST['start'])){
            ### >/dev/null 2>/dev/null    id really important for initiating async process
            exec("nohup php customProcesses/hbondsBridgesWrapper.php {$page->getId()} start >/dev/null 2>/dev/null &");
            return $this->json(array('successes'=>'Process initiated', 'page'=>$page->getId()));
        }
        if(isset($_POST['stop'])){
            exec("nohup php customProcesses/hbondsBridgesWrapper.php {$page->getId()} stop >/dev/null 2>/dev/null &");
            return $this->json(array('successes'=>'Process terminate', 'page'=>$page->getId()));
        }
        return $this->json(array('No start/stop action provided'));
    }

    public function fastaAction(Request $request){
        file_put_contents('uploads/out', "fasta file\n", FILE_APPEND);

        $successes = array();
        $errors = array();
        $warnings = array();

        if ( $file_upload_res = $this->get('api_functions')->fileDrop('FASTA', $errors) ){

            if( !$file_upload_res['final'] ){ 
                return $this->json($file_upload_res);
            }

            $page = $this->getPage();
            $slug = $page->getId();
            $filename = $file_upload_res['path'];

            $tot = (int)exec("wc -l '$filename' 2>/dev/null") -1;
            if($tot < 3000){
                $this->get('api_functions')->parseFASTA($page, $filename, $successes, $errors, $warnings);
            }else{
                exec("nohup php customProcesses/fastaWrapper.php $filename $slug >/dev/null 2>/dev/null &");
            }

            return $this->json(array(
                'reload'=>($tot < 3000), 
                'page'=>$slug, 
                'successes'=>$successes, 
                'errors'=>$errors, 
                'warnings'=>$warnings ));
        }

        $errors[] = 'fileDrop returned false';
        return $this->json(array( 'errors'=>$errors, 'warnings'=>$warnings ));
    }


    public function pdbfileAction(Request $request){
        file_put_contents('uploads/out', "pdb file\n", FILE_APPEND);

        $successes = array();
        $errors = array();
        $warnings = array();

        if ( $file_upload_res = $this->get('api_functions')->fileDrop('PDB', $errors) ){
        
            if( !isset($file_upload_res['path']) or !$file_upload_res['final'] ){ 
                return $this->json($file_upload_res);
            }

            $path = $file_upload_res['path'];
            $filename = $file_upload_res['name'];
            $page = $this->getPage();

            $this->get('api_functions')->collectPDB($page, $path, $filename, $successes, $errors, $warnings);
            return $this->json(array(
                'page'=>$page->getId(), 
                'successes'=>$successes, 
                'errors'=>$errors, 
                'warnings'=>$warnings ));
        }
        $errors[] = 'fileDrop returned false';
        return $this->json(array( 'errors'=>$errors, 'warnings'=>$warnings ));
    }


    public function getPage(){
            $em = $this->getDoctrine()->getManager();
            $page_repo = $em->getRepository('Core:Page');

            if(isset($_POST['pageslug']) and  $_POST['pageslug'] != '' and $page=$page_repo->find($_POST['pageslug'])){
                file_put_contents('uploads/out', "page slug is valid\n", FILE_APPEND);
            }else{
                file_put_contents('uploads/out', "NEW PAGE CREATED\n" . $this->getReport() . "\n", FILE_APPEND);
                $page = new Page();
                $page->setId(uniqid());
                $em->persist($page);
                $em->flush();
            }
    return $page;
    }

    public function getReport(){

        $ts = mktime();
        /////////////// detecting ip ////////////////////////////
        $client  = @$_SERVER['HTTP_CLIENT_IP'];
        $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
        $remote  = @$_SERVER['REMOTE_ADDR'];

        if(filter_var(     $client,  FILTER_VALIDATE_IP)){
            $ip = $client;
        }elseif(filter_var($forward, FILTER_VALIDATE_IP)){
            $ip = $forward;
        }elseif(filter_var($remote,  FILTER_VALIDATE_IP)){
            $ip = $remote;
        }else{
            $ip = 'no_ip';
        }
        /////////////// detecting pageURL ////////////////////////////
        $pageURL = ( isset($_SERVER["HTTPS"]) and $_SERVER["HTTPS"] != "off" )? "https://" : "http://";
        $port = ( !isset($_SERVER["SERVER_PORT"] ) )? '' : ':'.$_SERVER["SERVER_PORT"];
        if ( ($pageURL == "https://" and $port == ":443" ) or
             ($pageURL == "http://" and $port == ":80" ) ){
                $port = '';
        }
        $pageURL .= $_SERVER["SERVER_NAME"].$port.$_SERVER["REQUEST_URI"];
        ////////////// detecting referer /////////////////////////////
        $referer = ( isset($_SERVER['HTTP_REFERER']) and $pageURL != $_SERVER['HTTP_REFERER'] )?
                        htmlspecialchars( $_SERVER['HTTP_REFERER'] ) : 'no_referer';
        ////////////// detecting client agent ////////////////////////
        $agent = ( isset($_SERVER["HTTP_USER_AGENT"]))? $_SERVER["HTTP_USER_AGENT"]:'unknown';

    return implode( ', ', array( $ip, $ts, $pageURL, $referer, $agent));
    }

}
