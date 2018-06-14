<?php

### */10 * * * *  cd /var/www/protein/public && php customProcesses/websocketControl.php >> /tmp/cron_websocketControl.log 2>&1
### && runs next command only if first was successful 


$pid = getmypid();

exec( "ps -ax|grep 'websocket-control'|grep -v grep", $same_processes );

if( count( $same_processes ) > 0 ){
    exit(1);
}

cli_set_process_title( 'websocket-control' );

while(1){
    clearstatcache();
    exec( "ps -ax|grep 'server.js 13005'|grep -v grep", $same_processes2 );

    if( count( $same_processes2 ) == 0 ){
        initiateWebsocket();
    }elseif( !file_exists('customProcesses/websocket_log/ping') or (time() - filemtime('customProcesses/websocket_log/ping') > 60) ){
        print "Node websocket ping file doesn't exist or it's too old\n";
        killWebsocket($same_processes2);
        initiateWebsocket();
    }

    flush();
    sleep(60);
}


function initiateWebsocket(){
    exec("nohup node socket/server.js 13005 > customProcesses/websocket_log/out 2>customProcesses/websocket_log/err &");

    exec( "ps -ax|grep 'server.js 13005'|grep -v grep", $same_processes3 );
    if( count( $same_processes3 ) == 0 ){
        print "attempt to start websocket server failed\n";
    }else{
        print "websocket server is active\n";
    }
}

function killWebsocket($pss){
    foreach( $pss as $ps ){
        $pid = explode(" ",trim($ps))[0];
        print $pid." old process to remove\n";
        posix_kill($pid, 9);
        if( $err=posix_get_last_error()){
            print posix_strerror($err);
        }else{
            print "success\n\n";
        }
    }
}
