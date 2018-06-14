<?php

$pid = getmypid();

if( $argc <2 ){
    file_put_contents('customProcesses/parsing_log/err', "$pid NO filename\n", FILE_APPEND);
    exit(1);
}
$filename = $argv[1];

exec( "ps -ax|grep 'app:parse-index'|grep -v grep", $same_processes );

$attempts = 0;
file_put_contents('customProcesses/parsing_log/waitlist', "$pid $filename\n", FILE_APPEND);

while( count( $same_processes ) > 0 and $attempts++ < 100){ 
    sleep(60);
    exec( "ps -ax|grep 'app:parse-index'|grep -v grep", $same_processes );
}
if( count( $same_processes ) > 0 ){
    file_put_contents('customProcesses/parsing_log/waitlist', "$pid $filename giving up\n", FILE_APPEND);
    exit(1);
}

cli_set_process_title( 'parsing_wrapper' ); ## to check if anybody is still waiting

exec("nohup php ../bin/console app:parse-index $filename > customProcesses/parsing_log/out 2>customProcesses/parsing_log/err &");

file_put_contents('customProcesses/parsing_log/waitlist', "$pid $filename started\n", FILE_APPEND);
# it will end as soon as nohup starts
