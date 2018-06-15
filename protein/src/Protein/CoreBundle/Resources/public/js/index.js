
Dropzone.autoDiscover = false;
$('.mydropzone').addClass('dropzone');

var webSocket = window.WebSocket || window.MozWebSocket;

var Page = {
    init: function(){
        Page.slug = window.__PAGESLUG__;
        Page.initDropzone("#index-dropzone", 'index', "Drop INDEX file here", "INDEX", {});
        Page.initDropzone("#pdb-dropzone", 'pdbfile', "Drop PDB file here", "PDB", {});
        Page.initDropzone("#fasta-dropzone", 'fasta', "Drop .fasta file here", "FASTA", {});
        Page.initDropzone("#amino-dropzone", 'amino', "Drop .fasta for amino acid count", "AMINO", {});

        TableExport(document.getElementById("proteins-table"), {
            headers: false, // (Boolean), display table headers (th or td elements) in the <thead>, (default: true)
            footers: false, // (Boolean), display table footers (th or td elements) in the <tfoot>, (default: false)
            formats: ['xlsx', 'csv', 'txt'], // (String[]), filetype(s) for the export, (default: ['xlsx', 'csv', 'txt'])
            filename: 'proteins', // (id, String), filename for the downloaded file, (default: 'id')
            bootstrap: false, // (Boolean), style buttons using bootstrap, (default: true)
            exportButtons: true, // (Boolean), automatically generate the built-in export buttons for each of the specified formats (default: true)
            position: 'top', // (top, bottom), position of the caption element relative to table, (default: 'bottom')
            ignoreRows: null,  // (Number, Number[]), row indices to exclude from the exported file(s) (default: null)
            ignoreCols: null, // (Number, Number[]), column indices to exclude from the exported file(s) (default: null)
            trimWhitespace: true // (Boolean), remove all leading/trailing newlines, spaces, and tabs from cell text in the exported file(s) (default: false)
        });

        $('#proteins-table .top.tableexport-caption').append(' <a class="btn btn-sm btn-outline-secondary" href="/">Clear document</a>');

        $('#hbonds-bridges-start').click(function(e){
            e.preventDefault();
            $.post('calculate', 'pageslug=' + Page.slug + '&start=1')
                .done(function(reply){ flashCard.add('success', JSON.stringify(reply)); });
        });
        $('#hbonds-bridges-stop').click(function(e){
            e.preventDefault();
            $.post('calculate', 'pageslug=' + Page.slug + '&stop=1')
                .done(function(reply){ flashCard.add('success', JSON.stringify(reply)); });
        });
    },

    initDropzone: function(id, url, message, paramName, params){
        console.log('initiating dropzone');
        Page[id] = new Dropzone(id,{
                    url: url,
                    chunking: true,
                    chunkSize: 1*1024*1024,
                    forceChunking: false,
                    parallelChunkUploads: false,
                    retryChunks: true,
                    retryChunksLimit: 3,
                    chunksUploaded: function(big_file, done_func){
                        done_func();
                    },
                    sending: function(a,b,formdata){ // in case you want to add data and not override chunk info
                        $.each(params, function(nm,vl){ 
                            formdata.append(nm,vl);
                        });
                        formdata.append('pageslug', Page.slug); // subject to changes
                    },
                    dictDefaultMessage: message,
                    paramName: paramName
        });
        Page[id].on("complete", function(file) { // even chunked file gives it only once
            var data = file.xhr.response;
            if( typeof( data ) == 'string' ){ data = JSON.parse(data); }
            if('undefined' != typeof(data.page)){
                Page.slug = data.page;
            }
            Page[id].removeFile(file);
        });
    }
}

var ProgressBar = {
    set: function(id, percentComplete){
        id = "#" + id + "-progress";
        $(id + ".progress").addClass("show in");
        $(id + ' .progress-bar')
            .css('width', percentComplete +'%')
            .attr('aria-valuenow', percentComplete)
            .html(percentComplete +'%');
        if( percentComplete >= 100 ){
            $(id + ".progress").removeClass("show in");
            $(id + ' .progress-bar').css('width', '0%').attr('aria-valuenow', 0).html('0%');
        }
    }
}


var Socket = {
    ws: null,
    sockets: [],
    url: 'ws://'+document.location.hostname+':13005/',
    last_pong:  Date.now(),  /// important, or there will be loop
    checkInterval: null,
    socketType: 'default',

    ping: function(){
        if( null == Socket.ws ){
            Socket.init();
        }
        if( Date.now() - Socket.last_pong > 30*1000 ){ 
            Socket.reload();
            return;
        }

        /// overprotection for non closing sockets
        var count = 0;
        $.each(Socket.sockets, function(i, sk){ 
            count += Number(sk.readyState == 1);
        });
        if( count > 0 ){
            var gotOne = false;
            $.each(Socket.sockets, function(i, sk){
                if( !gotOne && sk.readyState == 1 ){
                    Socket.ws = sk;
                    gotOne = true;
                }else if(sk.readyState == 1){
                    Socket.ws.send(JSON.stringify('closeme'));
                }
            });
        } /// not sure if it's ever needed


        // state 0 - opening, 1 - ready, 2 - closing, 3 - closed

        if( Socket.ws.readyState == 1 && Date.now() - Socket.last_pong > 1000 ){ 
            Socket.ws.send(JSON.stringify('ping'));
        }
    },
    
    init: function(){
        console.log( 'node init');
        var new_ws = new webSocket(this.url);
        Socket.sockets.push( new_ws );
        new_ws.ID = Socket.sockets.length;
        Socket.ws = new_ws; 
        this.bind_socket();
        if( !this.checkInterval ){  /// hm, might need to be restatrted sometimes
            this.checkInterval = setInterval(Socket.ping, 1000);
        }
    },
    destroy: function() {
        clearInterval(this.checkInterval);
        this.checkInterval = null;
        this.shouldAttemptReconnect = false;
        Socket.ws.close();
        Socket.last_pong = Date.now(); /// important
    },
    reload: function(){
            Socket.destroy();
            Socket.init();
    },
    bind_socket: function(){

        Socket.ws.onopen = function(e){
            console.log( 'node open');
            Socket.ws.last_ping = Date.now(); 
        };

        Socket.ws.onclose = function(e){
            if( Socket.ws && Socket.ws.ID == e.target.ID ){
                if(Socket.ws.readyState == 1){ Socket.ws.send(JSON.stringify('closeme')); }
                Socket.ws.onclose = function () {};
                Socket.ws.close();
                Socket.ws = null;
            }
        };

        // same as onclose
        Socket.ws.onerror = function(e){
            if( Socket.ws && Socket.ws.ID == e.target.ID ){
                if(Socket.ws.readyState == 1){ Socket.ws.send(JSON.stringify('closeme')); }
                Socket.ws.onclose = function () {};
                Socket.ws.close();
                Socket.ws = null;
            }
        };

        Socket.ws.onmessage = function(e){
            Socket.last_pong = Date.now(); 
            if('object' == typeof(e.data)){ 
                var dataObject = e.data;
            }else if( 'string' == typeof(e.data)){ 
                var dataObject = JSON.parse(e.data);
            } else {
                return;
            }


            if( 'object' == typeof(dataObject) ){

                if( 'provideSocketType' == dataObject.type ){
                    Socket.ws.send(JSON.stringify({ 
                        'setSocketType' : Socket.socketType, 
                        'setPage': Page.slug
                    }));
                }else{
                    //console.log(dataObject);
                    Socket.handleUpdate(dataObject);
                }

            }else if(dataObject != 'pong'){

                console.log('not object:', dataObject, typeof(dataObject));

            }else{
                // console.log('pong',  dataObject );
            }
        };
    }, // end of bind socket

    handleUpdate: function(data){
        if('undefined'==typeof(data.sys) && 'undefined'!=typeof(data.progress)){
            console.log(data.type, data.progress);
            ProgressBar.set(data.type, data.progress);
        }
    }
};


var flashCard = {
    dismiss: function(){
        var this_card = $(this);
        this_card.addClass( 'way-to-the-right' );
        setTimeout(function(){ this_card.remove(); }, 500 );
    },

    add: function( type, note ){
       var box = $('.flash-box');
       var card  = $(
         '<div class="flash-card way-below ' + type + '">\
            <div class="flash-card-icon"><i class="icon3-flash"></i></div>\
            <div class="flash-card-text">' + note + '</div>\
         </div>').appendTo( box );
       setTimeout(function(){ card.removeClass('way-below'); }, 100 );
       card.bind('click', flashCard.dismiss);
       setTimeout(function(){ card.fadeOut(600, function(){ card.remove(); }) }, 45000 );
    }
};


Page.init();
Socket.init();
