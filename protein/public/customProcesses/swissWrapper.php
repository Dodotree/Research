<?php

$pid = getmypid();

$log_dir = "customProcesses/swiss_log";

if( $argc <3 ){
    file_put_contents("$log_dir/err", "$pid Not enough arguments\n", FILE_APPEND);
    exit(1);
}
$slug = $argv[1];
$action = $argv[2];

exec( "ps -ax|grep 'app:swiss $slug'|grep -v grep", $same_processes );

if( count( $same_processes ) > 0 and $action=='start'){ 
    file_put_contents("$log_dir/err", "$pid Same process detected, try again later\n", FILE_APPEND);
    exit(1);
}elseif( $action=='start'){
    cli_set_process_title( 'fasta_wrapper' );
    exec("nohup php ../bin/console app:swiss $slug >>$log_dir/out 2>>$log_dir/err &");
    # it will end as soon as nohup starts
}
if( count( $same_processes ) > 0 and $action=='stop'){ 
    $running_pid = preg_replace("/\s+.*/", "", trim($same_processes[0]));
    exec("kill -9 $running_pid");
    exec( "ps -ax|grep 'app:swiss $slug'|grep -v grep", $process );
    if( count($process)>0 ){
        file_put_contents("$log_dir/err", "Failed to terminate the process $running_pid\n", FILE_APPEND);
    }else{
        file_put_contents("$log_dir/err", "Process terminated\n", FILE_APPEND);
    }
    exit(1);
}

