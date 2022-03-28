function md5(input,file){
    let progress = 0;
    const loading = setInterval(function(){
        const message = ['―','/','|','\\'];
        input.val(message[progress]);
        progress = (progress + 1)%4;
    },100);

    if(typeof SparkMD5 !== 'undefined'){//Si esta sparkmd5 importado lo uso para hashear desde el cliente
        const file_reader = new FileReader();
        file_reader.onload = function(){
            input.val(SparkMD5.hash(file_reader.result)).change();
            clearInterval(loading);
        };
        file_reader.readAsText(file);
        return;
    }

    var formData = new FormData();
    formData.append('archivo',file);

    $.ajax({
        type: "POST",
        url: 'hashearArchivo/md5',
        data: formData,
        processData: false,
        contentType:false,
        cache:false,
        success: function(x){
            clearInterval(loading);
            input.val(x).change();
        },
        error: function(x){
            clearInterval(loading);
            input.val('ERROR').change();
            console.log(x);
        }
    });
}
  
function compararHash(div){
    const calculado = div.find('.hashCalculado');
    const recibido  = div.find('.hashRecibido');
    if(recibido.val() == ""){
        recibido.css('background-color','');
        return;
    }
    const dif = calculado.val() != recibido.val();
    recibido.css('background-color',dif? 'rgba(219, 68, 55, 0.59)' : 'rgba(15, 157, 88, 0.59)');
}

$('.hashCalculado').change(function(e){
    compararHash($(this).closest('.hashDiv'));
})

$('.hashRecibido').change(function(e){
    compararHash($(this).closest('.hashDiv'));
})

$('.hashRecibido').keyup(function(e){
    compararHash($(this).closest('.hashDiv'));
})

$(document).on('fileselect','#archivo',function(e){
    const modal = $(this).closest('.modal');
    md5(modal.find('.hashCalculado'),modal.find('#archivo')[0].files[0]);
    modal.find('.hashRecibido').val('').change();
});

$(document).on('hidden.bs.modal','.modal',function(e){
    $(this).find('.hashCalculado,.hashRecibido').val('').change();
})

$('.hashRecibidoFile').change(function(e){
    const file = $(this)[0].files[0];
    if(file != null){
        const reader = new FileReader();
        const input = $(this).closest('.hashDiv').find('.hashRecibido');
        reader.onload = function(f){
            input.val(f.target.result).change();
        };
        reader.readAsText(file);
    }
});

$('.hashRecibidoFileButton').click(function(e){
    $(this).closest('.hashDiv').find('.hashRecibidoFile').click();
})