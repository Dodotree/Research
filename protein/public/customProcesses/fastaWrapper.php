<?php

$pid = getmypid();

$log_dir = "customProcesses/fasta_log";

if( $argc <3 ){
    file_put_contents("$log_dir/err", "$pid Not enough arguments\n", FILE_APPEND);
    exit(1);
}
$filename = $argv[1];
$slug = $argv[2];
$action = $argv[3];

exec( "ps -ax|grep 'app:fasta $filename $slug'|grep -v grep", $same_processes );

if( count( $same_processes ) > 0 and $action=='start'){ 
    file_put_contents("$log_dir/err", "$pid Same process detected, try again later\n", FILE_APPEND);
    exit(1);
}
if( count( $same_processes ) > 0 and $action=='stop'){ 
    exit(1);
}

cli_set_process_title( 'fasta_wrapper' );
exec("nohup php ../bin/console app:fasta $filename $slug > $log_dir/out 2>$log_dir/err &");
# it will end as soon as nohup starts
