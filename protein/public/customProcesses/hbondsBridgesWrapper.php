<?php

$pid = getmypid();

$log_dir = "customProcesses/hbonds_bridges_log";

if( $argc <3 ){
    file_put_contents("$log_dir/err", "$pid Not enough arguments\n", FILE_APPEND);
    exit(1);
}
$slug = $argv[1];
$action = $argv[2];

exec( "ps -ax|grep 'app:hbonds $slug'|grep -v grep", $same_processes );

file_put_contents("$log_dir/err", "Here\n", FILE_APPEND);

if( count( $same_processes ) > 0 and $action=='start'){ 
    file_put_contents("$log_dir/err", "$pid Same process detected, try again later\n", FILE_APPEND);
    exit(1);
}elseif($action=='start'){
    cli_set_process_title( 'hbonds_bridges_wrapper' ); ## to check if anybody is still waiting
    exec("nohup php ../bin/console app:hbonds $slug  >>$log_dir/out 2>>$log_dir/err &");
    # it will end as soon as nohup starts
    file_put_contents("$log_dir/err", "Process initiated\n", FILE_APPEND);
}
if( count( $same_processes ) > 0 and $action=='stop'){ 
    $running_pid = preg_replace("/\s+.*/", "", trim($same_processes[0]));
    exec("kill -9 $running_pid");
    exec( "ps -ax|grep 'app:hbonds $slug'|grep -v grep", $process );
    if( count($process)>0 ){
        file_put_contents("$log_dir/err", "Failed to terminate the process '$running_pid' for page $slug |".$same_processes[0]."|".implode("\n", $same_processes)."\n", FILE_APPEND);
    }else{
        file_put_contents("$log_dir/err", "Process terminated\n", FILE_APPEND);
    }
    exit(1);
}elseif( $action=='stop' ){
    file_put_contents("$log_dir/err", "No process to stop\n", FILE_APPEND);
}

