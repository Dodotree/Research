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

        $ind_repo = $em->getRepository('Core:Index');
        $qb = $ind_repo->createQueryBuilder("fi");
        $qb->select("count(fi)");
        $count = $qb->getQuery()->getSingleScalarResult();

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

        exec( "ps -ax|grep 'app:hbonds-bridges $pageslug'|grep -v grep", $hbonds_processes );
        $hbonds_on = ( count($hbonds_processes)> 0 );
        $pagedir = "customProcesses/hbonds_bridges_log/$pageslug";
        $hbonds_progress = (file_exists("$pagedir/progress"))? file_put_contents("$pagedir/progress") : 0;


        $pagedir = "customProcesses/models_log/$pageslug";
        $models_on = false;
        $models_progress = (file_exists("$pagedir/progress"))? file_put_contents("$pagedir/progress") : 0;

        return $this->render('@ProteinCore/index.html.twig', [
            'pageslug'=>$pageslug,
            'proteins'=> $proteins, 
            'uploads'=> $uploads, 
            'index_count'=> $count,
            'parsing_on'=> $parsing_on,
            'hbonds_on'=> $hbonds_on,
            'hbonds_progress'=>$hbonds_progress,
            'models_on'=>$models_on,
            'models_progress'=>$models_progress,
            ]);
    }


    public function calculateAction(Request $request){
        $page = $this->getPage();

        if(isset($_POST['start'])){
            ### >/dev/null 2>/dev/null    id really important for initiating async process
            exec("nohup php customProcesses/hbondsBridgesWrapper.php {$page->getId()} start >/dev/null 2>/dev/null &");
            return $this->json(array('Process initiate'));
        }
        if(isset($_POST['stop'])){
            exec("nohup php customProcesses/hbondsBridgesWrapper.php {$page->getId()} stop >/dev/null 2>/dev/null &");
            return $this->json(array('Process terminate'));
        }
        return $this->json(array('No start/stop action provided'));
    }


    public function indexAction(Request $request){
        if ( $file_upload_res = $this->get('api_functions')->fileDrop('INDEX') ){
            if( $file_upload_res['final'] ){
                ### >/dev/null 2>/dev/null    id really important for initiating async process
                #exec("nohup php ../bin/console app:parse-index {$file_upload_res['path']} >/dev/null 2>/dev/null &");
                exec("nohup php customProcesses/parsingWrapper.php {$file_upload_res['path']} >/dev/null 2>/dev/null &");
            }
            return $this->json( $file_upload_res );
        }
        return $this->json(array('fileDrop returned false'));
    }
 

    public function fastaAction(Request $request){
        if ( $file_upload_res = $this->get('api_functions')->fileDrop('FASTA') ){
            $successes = array();
            $errors = array();
            $warnings = array();
            $filename = $file_upload_res['path'];
            $page = $this->getPage();

            if( !$file_upload_res['final'] ){ 
                return $this-json($file_upload_res);
            }

            $this->get('api_functions')->parseFASTA($page, $filename, $successes, $errors, $warnings);

            return $this->json(array(
                'page'=>$page->getId(), 
                'successes'=>$successes, 
                'errors'=>$errors, 
                'warnings'=>$warnings ));
        }
        return $this->json(array('fileDrop returned false'));
    }

    public function pdbfileAction(Request $request){
        if ( $file_upload_res = $this->get('api_functions')->fileDrop('PDB') ){
            $successes = array();
            $errors = array();
            $warnings = array();
        
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
        return $this->json(array('fileDrop returned false'));
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
