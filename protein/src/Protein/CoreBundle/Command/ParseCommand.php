<?php

namespace Protein\CoreBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

use Protein\CoreBundle\Entity\Index;

class ParseCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('app:parse-index')
            ->addArgument('path', InputArgument::REQUIRED, 'The path to file.')
            ->setDescription('Command to parse Swiss INDEX file')
            ->setHelp('Use as command for parsing and recording');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $em = $container->get('doctrine')->getManager();
        $path = $input->getArgument('path');
        $this->parseINDEX($em, $path);
        opcache_reset();
        $output->writeln("Done!");
    }

    public function parseINDEX($em, $filename){

        $prot_repo = $em->getRepository('Core:Index');

        $fh = fopen($filename, 'r');
        if(!$fh){
            return false;
        }

        file_put_contents('customProcesses/parsing_log/progress', 0);
        $tot = (int)exec("wc -l '$filename' 2>/dev/null");

        fgets($fh);

        $organism_id = fgets($fh);
        $organism_id = explode(':', $organism_id);
        $organism_id = (int)$organism_id[1];

        fgets($fh); fgets($fh); fgets($fh); fgets($fh); fgets($fh); # 7 top lines

        $i = 0;
        while($line= fgets($fh)){
            list($UniProtKB_ac, $iso_id, $uniprot_seq_length, $coordinate_id,
                 $provider, $from, $to, $coverage, $template, $qmean, $qmean_norm, $url) = preg_split('/\t/', $line);

            $filename = "{$from}_{$to}_{$template}_$coordinate_id";  # combined from_to_template_coordinateId

            if( $prot_repo->find($filename) ){ continue; }
            
            $index = new Index();
            $index->setFilename($filename);
            $index->setOrganismId($organism_id);
            $index->setUniProt($UniProtKB_ac);
            $index->setLen($uniprot_seq_length);
            $index->setQmean($qmean);
            $index->setQmeanNorm($qmean_norm);
            $em->persist($index);

            $i++;
            if( $i%100 < 2 ){
                $em->flush();
                file_put_contents('customProcesses/parsing_log/progress', round(100*$i/$tot));
            }
        }
        $em->flush();
        file_put_contents('customProcesses/parsing_log/progress', 100);
        fclose($fh);
        unlink($filename);
    }

}
