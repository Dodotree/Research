//curl -sL https://deb.nodesource.com/setup_8.x | sudo -E bash -
//sudo apt-get install -y nodejs
//sudo npm install -g npm@latest
//sudo npm i ws

//monitoring should be in customProcesses 

var fs = require('fs'),
    http = require('http'),
    WebSocket = require('ws');

var WEBSOCKET_PORT = process.argv[2] || 8082;

var process_dir = '/var/www/protein/public/customProcesses/';
var logs = {
    amino: 'aminoacids_log',
    modeling: 'modeling_log',
    hbonds: 'hbonds_bridges_log',
    parsing: 'parsing_log',
    fasta: 'fasta_log',
}


var socketServer = new WebSocket.Server({port: WEBSOCKET_PORT, perMessageDeflate: false});
socketServer.connectionCount = 0;

//// for monitoring of server health
const server_ping_interval = setInterval(function ping() {
    fs.writeFile(process_dir + "/websocket_log/ping", new Date().toISOString(), function(err) {
        if(err) { return console.log(err); }
    });
}, 1000);


//// combination to get rid of lost clients  sockets 
function heartbeat() {
  this.isAlive = true;
}
const browser_ping_interval = setInterval(function ping() {
   socketServer.clients.forEach(function each(client) {
        if (client.isAlive === false) return client.terminate();

        client.isAlive = false;
        client.ping('', false, true);
  });
}, 30000);
////


socketServer.on('connection', function(socket) {
    socketServer.connectionCount++;
    socket.name =  Date.now();
    socket.isAlive = true;

    socket.on('pong', heartbeat);

    socket.on('close', function(code, message){
        socketServer.connectionCount--;
    });

    socket.on('message', function incoming(data) {

        var obj = JSON.parse(data);

        if( obj == 'closeme' ){ 
            console.log('terminate');
            socket.terminate();
        }

        if('undefined' != typeof( obj.setSocketType )){    
            socket.dataType = obj.setSocketType;
        }
        if('undefined' != typeof( obj.setPage )){    
            socket.page = obj.setPage;
        }

        if( 'undefined' != typeof(socket.dataType) && 'undefined' != typeof(socket.page) ){

            handleRequest(socket); /// sends replies when it's ready

        }else{
            //console.log('ask for type');
            socket.send(JSON.stringify({'type':'provideSocketType', 'echo': obj}));
        }

        /// needs to be at the bottom to let ask for type etc.
        if( obj == 'ping' ){ 
            //console.log('ping', socket.dataType, socket.page);
            socket.send(JSON.stringify('pong'));
        }

    });

})


function handleRequest(socket){
    if( socket.dataType != 'default' ){
        checkOutLogFile(socket, socket.dataType, true);
        return;
    }
    for( type in logs ){
        if('type'==''){ continue; }
        checkOutLogFile(socket, type, true);
    }
}


function checkOutLogFile(socket, type, ifdebug){
    if( type == 'parsing' ){
        var filepath = process_dir + logs[type] + '/progress';
    }else{
        var filepath = process_dir + logs[type] + '/' + socket.page + '/progress';
    }
    fs.stat( filepath, function(err, st){
        if (err) {
            if( ifdebug ){
                socket.send(JSON.stringify({'sys': 'file error ' + type, 'type': type}));
            }
            return;
        }

        var filedata = {};
        if( ifdebug ){ // if log file is current
            filedata = {'ts': st.mtime.getTime(), 'now': Date.now(), 'type': type};
        }

        fs.readFile( filepath, 'utf8', (err, data) => {
            if (err) {
                if( ifdebug ){
                    filedata['sys'] = 'file read error ' + type;
                    socket.send(JSON.stringify(filedata));
                }
                return;
            }

            //socket.send(JSON.stringify( JSON.parse(data) ));
            filedata['progress'] = JSON.parse(data);
            socket.send(JSON.stringify(filedata));

        }); // end of fs.readFile

    });
}

console.log('Awaiting WebSocket connections on ' + WEBSOCKET_PORT);
