import "/js/Components/modal.js";
import {AUX} from "/js/Components/AUX.js";

//Cuantos certificados distintos se mandan por request. PHP tiene max_file_uploads=20,
//y lo que pasa de ahi lo descarta SIN avisar, asi que se envia en tandas.
const CERTIFICADOS_POR_LOTE = 15;

$(function(){ $('[data-js-modal-rectificacion]').each(function(_,Mobj){
  const  M = $(Mobj);
  const $M = M.find.bind(M);

  M.on('mostrar',function(e){
    e.preventDefault();
    $M('[name="archivo"]').val('');
    $M('[data-js-cambio-repartir-pdfs]').val('');
    $M('[name="motivo"]').val('Rectificación ');
    $M('[data-tabla-rectificacion] tbody').empty();
    $M('[data-tabla-sin-asignar]').empty();
    $M('[data-panel-sin-asignar]').attr('hidden',true);
    $M('[data-resumen-pdfs]').empty();
    $M('[data-mensaje]').empty();
    M.attr('data-estado-visible','IMPORTAR');
    M.modal('show');
  });

  const crearFila = function(){
    return $M('[data-molde-tabla-rectificacion]').clone().removeAttr('data-molde-tabla-rectificacion');
  };

  //El cuerpo de la tabla scrollea y la cabecera no, asi que la cabecera queda mas ancha
  //y las columnas no coinciden. Se le reserva el ancho del scrollbar.
  const alinearCabecera = function(){
    const cuerpo = $M('[data-cuerpo-tabla]')[0];
    if(cuerpo === undefined) return;
    $M('[data-cabecera-tabla]').css('padding-right', (cuerpo.offsetWidth - cuerpo.clientWidth) + 'px');
  };

  const recalcularIndices = function(){
    $M('[data-tabla-rectificacion] tbody tr').each(function(fidx,trobj){
      $(trobj).find('[data-name]').each(function(){
        $(this).attr('name',`rectificacion[${fidx}][${$(this).attr('data-name')}]`);
      });
    });
  };

  //Varios juegos pueden compartir certificado (tipico: escritorio y movil del mismo
  //juego). El PDF y el laboratorio se cargan una sola vez, en la primera fila que lo use,
  //y las demas se muestran como parte del mismo bloque para que se entienda de donde sale.
  const actualizarCertificados = function(){
    const primera_fila_de = {};
    const cuantas_filas_usan = {};
    const orden_de_grupo = {};

    $M('[data-tabla-rectificacion] tbody tr').each(function(fidx,trobj){
      const nro_archivo = $(trobj).find('[data-name="nro_archivo"]').val();
      if(primera_fila_de[nro_archivo] === undefined){
        primera_fila_de[nro_archivo] = fidx;
        orden_de_grupo[nro_archivo] = Object.keys(orden_de_grupo).length;
      }
      cuantas_filas_usan[nro_archivo] = (cuantas_filas_usan[nro_archivo] ?? 0) + 1;
    });

    $M('[data-tabla-rectificacion] tbody tr').each(function(fidx,trobj){
      const tr = $(trobj);
      const nro_archivo = tr.find('[data-name="nro_archivo"]').val();
      const es_la_primera = primera_fila_de[nro_archivo] === fidx;
      const compartido = cuantas_filas_usan[nro_archivo] > 1;

      tr.find('[data-name="certificado"],[data-name="id_laboratorio"]')
        .prop('disabled',!es_la_primera);
      tr.find('[data-js-click-limpiar-pdf]').css('visibility', es_la_primera? 'visible' : 'hidden');

      //Bloque visual: fondo alternado por certificado y barra lateral si son varias filas
      tr.attr('data-grupo-alterno', (orden_de_grupo[nro_archivo] % 2) ? '1' : '0');
      tr.attr('data-grupo-tam', compartido ? 'varios' : 'una');

      if(es_la_primera){
        tr.removeAttr('data-comparte');
        return;
      }

      tr.attr('data-comparte','1');
      tr.find('[data-name="certificado"]').val('');
      tr.find('[data-name="id_laboratorio"]').val('');

      //Se avisa de donde sale el PDF de esta fila, en vez de dejar un campo vacio
      const fila_origen = $M('[data-tabla-rectificacion] tbody tr').eq(primera_fila_de[nro_archivo]);
      const pdf_origen = fila_origen.find('[data-name="certificado"]')[0].files?.[0] ?? null;
      tr.find('[data-pdf-heredado]').text(
        pdf_origen !== null ? '↑ mismo PDF: ' + pdf_origen.name : '↑ mismo certificado'
      );
    });
  };

  const nrosDeCertificado = function(){
    const nros = [];
    $M('[data-tabla-rectificacion] tbody tr').each(function(){
      const n = $(this).find('[data-name="nro_archivo"]').val().trim();
      if(n.length && nros.indexOf(n) < 0) nros.push(n);
    });
    return nros;
  };

  const asignarPDF = function(tr,archivo){
    //DataTransfer es la unica forma de setear FileList programaticamente
    const dt = new DataTransfer();
    if(archivo !== null) dt.items.add(archivo);
    tr.find('[data-name="certificado"]')[0].files = dt.files;
  };

  //Cada PDF trae el codigo del certificado en el nombre:
  //  PachinkoMysteryLink&Win_Veyron_e2522662KOVBAPM_Buenos Aires_Final Certification Report.pdf
  //Se busca el codigo DENTRO del nombre en vez de partirlo, asi tolera nombres mal armados.
  const repartirPDFs = function(archivos){
    const nros = nrosDeCertificado();
    const asignados = {};
    const sin_asignar = [];

    archivos.forEach(function(archivo){
      const nombre = archivo.name.toLowerCase();
      let candidatos = nros.filter(n => nombre.indexOf(n.toLowerCase()) >= 0);

      if(candidatos.length > 1){
        //Un codigo puede ser prefijo de otro: gana el mas especifico
        const mas_largo = Math.max(...candidatos.map(n => n.length));
        candidatos = candidatos.filter(n => n.length === mas_largo);
      }

      //Ambiguo o sin match: lo decide el auditor
      if(candidatos.length !== 1){ sin_asignar.push(archivo); return; }

      const nro = candidatos[0];
      //Si ya habia un PDF para ese certificado, el segundo sobra
      //(suelen mandar el Final Certification Report junto con el Math Report)
      if(asignados[nro] !== undefined){ sin_asignar.push(archivo); return; }
      asignados[nro] = archivo;
    });

    $M('[data-tabla-rectificacion] tbody tr').each(function(){
      const tr = $(this);
      const nro = tr.find('[data-name="nro_archivo"]').val().trim();
      if(asignados[nro] !== undefined && !tr.find('[data-name="certificado"]').prop('disabled')){
        asignarPDF(tr,asignados[nro]);
      }
    });

    mostrarSinAsignar(sin_asignar);
    actualizarCertificados();//Refresca el "↑ mismo PDF" de las filas que comparten certificado

    const cantidad = Object.keys(asignados).length;
    $M('[data-resumen-pdfs]').text(`${cantidad} de ${nros.length} certificados con PDF`);
  };

  const mostrarSinAsignar = function(archivos){
    const tbody = $M('[data-tabla-sin-asignar]').empty();
    $M('[data-panel-sin-asignar]').attr('hidden',archivos.length? null : true);

    archivos.forEach(function(archivo){
      const opciones = nrosDeCertificado()
        .map(n => `<option value="${n}">${n}</option>`).join('');
      const tr = $(`<tr class="pdf-sin-asignar">
        <td style="width: 60%;">${archivo.name}</td>
        <td><select class="form-control" data-js-asignar-a><option value="">- ASIGNAR A -</option>${opciones}</select></td>
      </tr>`);
      tr.find('[data-js-asignar-a]').data('archivo',archivo);
      tbody.append(tr);
    });
  };

  $M('[data-js-cambio-enviar-a-parsear]').on('change',function(e){
    const archivo = $M('[name="archivo"]')[0].files?.[0] ?? null;
    if(archivo === null) return;

    const formData = new FormData();
    formData.append('archivo',archivo);
    const tabla = $M('[data-tabla-rectificacion] tbody').empty();

    $.ajax({
      type: "POST",
      url: $(this).attr('data-js-cambio-enviar-a-parsear'),
      data: formData,
      dataType: "json",
      processData: false,
      contentType: false,
      cache: false,
      success: function (data) {
        $M('[data-mensaje]').empty().append(data?.mensaje ?? '');
        const filas = data?.filas ?? [];

        if(filas.length === 0){
          $M('[data-mensaje]').append('No se encontraron juegos en el ANEXO A del archivo');
          return;
        }

        filas.forEach(function(f){
          const tr = crearFila();

          tr.find('[data-name="cod_juego"]').val(f.cod_juego);
          tr.find('[data-name="nro_archivo"]').val(f.nro_archivo);
          tr.find('[data-name="id_laboratorio"]').val(f.id_laboratorio ?? '');

          tr.find('[data-celda="cod_juego"]').text(f.cod_juego);
          tr.find('[data-celda="nombre_juego"]').text(f.nombre_juego ?? f.nombre_anexo);

          const actuales = f.certificados_actuales ?? [];
          tr.find('[data-celda="certificado_actual"]')
            .text(actuales.length? actuales.map(c => c.nro_archivo).join(', ') : '- sin certificado -');

          if(f.id_juego === null){
            tr.attr('data-sin-juego','1');
            tr.find('[data-celda="nombre_juego"]')
              .text((f.nombre_anexo ?? '') + ' — NO ESTÁ EN EL SISTEMA');
          }
          if(actuales.length > 1) tr.attr('data-varios-certificados','1');

          tabla.append(tr);
        });

        recalcularIndices();
        actualizarCertificados();
        M.attr('data-estado-visible','FILAS');
        alinearCabecera();
      },
      error: function (data) {
        console.log(data,data?.responseJSON ?? {});
        AUX.mostrarErroresNames(M.find('form'),mapJSONtoNames(data?.responseJSON ?? {}),false);
      }
    });
  });

  M.on('change','[data-js-cambio-repartir-pdfs]',function(e){
    repartirPDFs(Array.from(e.currentTarget.files ?? []));
  });

  M.on('change','[data-js-asignar-a]',function(e){
    const select = $(e.currentTarget);
    const nro = select.val();
    if(!nro.length) return;

    const archivo = select.data('archivo');
    $M('[data-tabla-rectificacion] tbody tr').each(function(){
      const tr = $(this);
      if(tr.find('[data-name="nro_archivo"]').val().trim() !== nro) return;
      if(tr.find('[data-name="certificado"]').prop('disabled')) return;
      asignarPDF(tr,archivo);
      return false;//@BREAK: uno solo, el resto comparte certificado
    });

    select.closest('tr').remove();
    $M('[data-panel-sin-asignar]').attr('hidden',
      $M('[data-tabla-sin-asignar] tr').length? null : true);
    actualizarCertificados();
  });

  $M('[data-js-click-cambiar-estado]').click(function(e){
    $M('[data-mensaje]').empty();
    M.attr('data-estado-visible',$(e.currentTarget).attr('data-js-click-cambiar-estado'));
  });

  $M('[data-js-click-limpiar-archivo]').click(function(e){
    $M('[name="archivo"]').val('');
  });

  M.on('click','[data-js-click-limpiar-pdf]',function(e){
    asignarPDF($(this).closest('tr'),null);
    actualizarCertificados();
  });

  M.on('click','[data-tabla-rectificacion] tbody [data-js-click-eliminar-fila]',function(e){
    e.preventDefault();
    $(this).closest('tr').remove();
    recalcularIndices();
    actualizarCertificados();
    alinearCabecera();
  });

  M.on('change','[data-js-cambio-actualizar-certificados]',function(e){
    actualizarCertificados();
  });

  const mapJSONtoNames = function(json){
    const ret = {};
    Object.keys(json).forEach(function(k){
      const namearr = k.split('.');
      let name = namearr?.[0] ?? '';
      for(let nidx = 1;nidx < namearr.length;nidx++){
        name+='['+namearr[nidx]+']';
      }
      ret[name] = json[k];
    });
    return ret;
  };

  //Agrupa las filas en tandas, cuidando que las que comparten certificado viajen juntas
  //(la validacion de coherencia las compara entre si, no podrian quedar en lotes distintos)
  const armarLotes = function(filas){
    const por_certificado = {};
    filas.forEach(function(fila){
      const nro = $(fila).find('[data-name="nro_archivo"]').val().trim();
      (por_certificado[nro] = por_certificado[nro] ?? []).push(fila);
    });

    const lotes = [];
    let lote = [];
    let certificados_en_lote = 0;
    Object.keys(por_certificado).forEach(function(nro){
      if(certificados_en_lote >= CERTIFICADOS_POR_LOTE){
        lotes.push(lote);
        lote = [];
        certificados_en_lote = 0;
      }
      lote = lote.concat(por_certificado[nro]);
      certificados_en_lote++;
    });
    if(lote.length) lotes.push(lote);

    return lotes;
  };

  //Arma el FormData de un lote reindexando desde 0, porque el backend valida
  //rectificacion.N.campo y los indices tienen que ser correlativos
  const formDataDelLote = function(lote,motivo,con_archivos){
    const fd = new FormData();
    fd.append('motivo',motivo);

    lote.forEach(function(fila,idx){
      $(fila).find('[data-name]').each(function(){
        const campo = $(this).attr('data-name');
        const key = `rectificacion[${idx}][${campo}]`;

        if(campo === 'certificado'){
          const archivo = this.files?.[0] ?? null;
          if(con_archivos){
            if(archivo !== null) fd.append(key,archivo);
          }
          //Al validar no se suben los PDF, solo se avisa cuales van a venir despues
          else if(archivo !== null){
            fd.append(`rectificacion[${idx}][tiene_certificado]`,'1');
          }
          return;
        }

        if($(this).prop('disabled') && campo === 'id_laboratorio') return;
        fd.append(key,$(this).val());
      });
    });

    return fd;
  };

  $M('[data-js-click-cargar]').click(function(e){
    const tgt = $(e.currentTarget);
    const motivo = $M('[name="motivo"]').val();
    const filas = $M('[data-tabla-rectificacion] tbody tr').toArray();

    if(filas.length === 0){
      $M('[data-mensaje]').text('No hay nada para rectificar');
      return;
    }

    const marcar = function(lote,estado){
      lote.forEach(f => $(f).attr('data-estado-carga',estado));
    };

    const alFallar = function(lote,respuesta){
      const json = respuesta?.responseJSON ?? {};
      console.log(respuesta,json);
      //Los errores vienen indexados por el lote, hay que remapearlos a los names reales
      const names = {};
      Object.keys(json).forEach(function(k){
        const partes = k.split('.');
        if(partes[0] === 'rectificacion' && lote[partes[1]] !== undefined){
          const campo = partes[2];
          names[$(lote[partes[1]]).find(`[data-name="${campo}"]`).attr('name')] = json[k];
        } else {
          names[k] = json[k];
        }
      });
      AUX.mostrarErroresNames(M.find('form'),names,false);
      marcar(lote,'ERROR');
    };

    const lotes = armarLotes(filas);
    let lote_idx = 0;
    let rectificados = 0;

    const enviarSiguiente = function(){
      if(lote_idx >= lotes.length){
        AUX.mensajeExito('');
        M.modal('hide').trigger('completado',[rectificados]);
        return;
      }

      const lote = lotes[lote_idx++];
      marcar(lote,'CARGANDO');

      //Primero se valida sin los PDF: asi no se topa con max_file_uploads
      //y no se sube nada al pedo si hay errores
      $.ajax({
        type: "POST",
        url: tgt.attr('data-url-validar'),
        data: formDataDelLote(lote,motivo,false),
        dataType: "json", processData: false, contentType: false, cache: false,
        success: function(){
          $.ajax({
            type: "POST",
            url: tgt.attr('data-url-guardar'),
            data: formDataDelLote(lote,motivo,true),
            dataType: "json", processData: false, contentType: false, cache: false,
            success: function(data){
              const por_codigo = {};
              (data?.resultados ?? []).forEach(r => por_codigo[r.cod_juego] = r.estado);

              lote.forEach(function(f){
                const cod = $(f).find('[data-name="cod_juego"]').val();
                $(f).attr('data-estado-carga',por_codigo[cod] ?? 'COMPLETADO');
                if((por_codigo[cod] ?? '') === 'COMPLETADO') rectificados++;
              });

              enviarSiguiente();
            },
            error: function(respuesta){
              alFallar(lote,respuesta);
              $M('[data-mensaje]').text(
                `Se cortó en el lote ${lote_idx} de ${lotes.length}. Los anteriores ya se rectificaron.`);
            }
          });
        },
        error: function(respuesta){ alFallar(lote,respuesta); }
      });
    };

    $M('[data-mensaje]').empty();
    enviarSiguiente();
  });
}); });
