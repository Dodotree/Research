<?php

namespace Protein\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use Protein\CoreBundle\Entity\Page;

class LandingController extends Controller
{
    public function landingAction($_subpage='', $pageslug='', Request $request){
        #exec("nohup php customProcesses/parsingWrapper.php uploads/whole_from_chunks/INDEX_10 >>uploads/out 2>>uploads/err &");

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


    public function speciesAction($_subpage='', Request $request){
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
 
    public function calculateAction(Request $request){
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
            }else{
                $page = new Page();
                $page->setId(uniqid());
                $em->persist($page);
                $em->flush();
            }
    return $page;
    }

}
