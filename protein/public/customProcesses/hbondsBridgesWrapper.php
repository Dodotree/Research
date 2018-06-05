<?php

$pid = getmypid();

$log_dir = "customProcesses/hbonds_bridges_log";

if( $argc <3 ){
    file_put_contents("$log_dir/err", "$pid Not enough arguments\n", FILE_APPEND);
    exit(1);
}
$slug = $argv[1];
$action = $argv[2];

exec( "ps -ax|grep 'app:hbonds-bridges $slug'|grep -v grep", $same_processes );

if( count( $same_processes ) > 0 and $action=='start'){ 
    file_put_contents("$log_dir/err", "$pid Same process detected, try again later\n", FILE_APPEND);
    exit(1);
}
if( count( $same_processes ) > 0 and $action=='stop'){ 
    exit(1);
}

cli_set_process_title( 'hbonds_bridges_wrapper' ); ## to check if anybody is still waiting
exec("nohup php ../bin/console app:hbonds-bridges $slug  > $log_dir/out 2>$log_dir/err &");
# it will end as soon as nohup starts
