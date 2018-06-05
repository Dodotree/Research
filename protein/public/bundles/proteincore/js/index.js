
Dropzone.autoDiscover = false;
$('.mydropzone').addClass('dropzone');

var Page = {
    init: function(){
        Page.slug = window.__PAGESLUG__;
        Page.initDropzone("#index-dropzone", 'index', "Drop INDEX file here", "INDEX", {});
        Page.initDropzone("#pdb-dropzone", 'pdbfile', "Drop PDB file here", "PDB", {});
        Page.initDropzone("#fasta-dropzone", 'fasta', "Drop .fasta file here", "FASTA", {});

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
            $.post('calculate', 'page=' + Page.slug + '&start=1');
        });
        $('#hbonds-bridges-stop').click(function(e){
            e.preventDefault();
            $.post('calculate', 'page=' + Page.slug + '&stop=1');
        });
    },

    initDropzone: function(id, url, message, paramName, params){
        console.log('initiating dropzone');
        Page[id] = new Dropzone(id,{
                    url: url,
                    chunking: true,
                    chunkSize: 1*1024*1024,
                    forceChunking: false,
                    parallelChunkUploads: true,
                    parallelChunkUploads: true,
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

Page.init();
