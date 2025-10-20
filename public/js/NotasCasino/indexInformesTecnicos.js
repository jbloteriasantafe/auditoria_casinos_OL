//seteo nombre de la seccion y traigo notas
$(document).ready(function () {
  $("#barraMenu").attr("aria-expanded", "true");
  $(".tituloSeccionPantalla").text(" Informes Tecnicos");
  cargarNotas();
});

function colorBoton(boton) {
  $(boton).removeClass();
  $(boton).addClass("btn").addClass("btn-successAceptar");
  $(boton).css("cursor", "pointer");
  $(boton).text("Guardar Informe");
  $(boton).show();
  $(boton).val("nuevo");
}

//paginacion
//crear bien los links y ahora creo un controlador que se encargue de mostrar el pdf
function generarFilaTabla(nota) {
  let fila = $("#cuerpoTabla .filaTabla")
    .clone()
    .removeClass("filaTabla")
    .show();

  fila
    .find(".numero_nota")
    .text(nota.nronota_ev || "No hay información disponible")
    .attr("title", nota.nronota_ev || "No hay información disponible");
  fila
    .find(".nombre_evento")
    .text(nota.evento || "No hay información disponible")
    .attr("title", nota.evento || "No hay información disponible");
  //! SOLUCION SOLO PARA LA PARTE DE INFORMES TECNICOS
  fila
    .find(".adjunto_pautas")
    .html(
      `${
        !nota.adjunto_pautas
          ? "No hay información disponible"
          : `<a href='http://10.1.120.9/eventos_casinos/Eventos_Pautas/${nota.adjunto_pautas}'>${nota.adjunto_pautas}</a>`
      }`
    )
    .attr("title", nota.adjunto_pautas || "No hay información disponible");
  fila
    .find(".adjunto_disenio")
    .html(
      `${
        !nota.adjunto_diseño
          ? "No hay información disponible"
          : `<a href='http://10.1.120.9/eventos_casinos/Eventos_Diseño/${nota.adjunto_diseño}'>${nota.adjunto_diseño}</a>`
      }`
    )
    .attr("title", nota.adjunto_diseño || "No hay información disponible");
  fila
    .find(".adjunto_basesycond")
    .html(
      `${
        !nota.adjunto_basesycond
          ? "No hay información disponible"
          : `<a href='http://10.1.120.9/eventos_casinos/Eventos_byc/${nota.adjunto_basesycond}'>${nota.adjunto_basesycond}</a>`
      }`
    )
    .attr("title", nota.adjunto_basesycond || "No hay información disponible");
  fila
    .find(".adjunto_informe_tecnico")
    .html(
      `${
        !nota.adjunto_inf_tecnico
          ? "No hay información disponible"
          : `<a href='http://10.1.120.9/eventos_casinos/Eventos_inftec/${nota.adjunto_inf_tecnico}'>${nota.adjunto_inf_tecnico}</a>`
      }`
    )
    .attr("title", nota.adjunto_inf_tecnico || "No hay información disponible");
  fila
    .find(".estado")
    .text(nota.estado || "No hay información disponible")
    .attr("title", nota.estado || "No hay información disponible");
  fila
    .find(".notas_relacionadas")
    .text(nota.notas_relacionadas || "No hay información disponible")
    .attr("title", nota.notas_relacionadas || "No hay información disponible");

  fila.find(".acciones_nota").html(
    `   <a href="/informesTecnicos/generar">d</a>
        <button class="gestionarInformeTecnico btn btn-info" title="Generar informe técnico" data-id="${nota.idevento}"><i class="fa fa-file-alt"></i></button>
        <button class="cargarInformeTecnico btn btn-warning" title="Cargar informe técnico" data-id="${nota.idevento}"><i class="fa fa-upload"></i></button>
    `
  );

  return fila;
}

function cargarNotas(page = 1, perPage = 5, nroNota, nombreEvento, idCasino) {
  let formData = new FormData();
  formData.append("page", page);
  formData.append("perPage", perPage);

  if (nroNota) {
    formData.append("nroNota", nroNota);
  }
  if (nombreEvento) {
    formData.append("nombreEvento", nombreEvento);
  }

  if (idCasino) {
    formData.append("idCasino", idCasino);
  }

  $.ajax({
    type: "POST",
    url: "/informesTecnicos/paginar",
    data: formData,
    dataType: "json",
    processData: false,
    contentType: false,
    headers: { "X-CSRF-TOKEN": $('meta[name="_token"]').attr("content") },
    success: function (response) {
      // Limpiar tabla
      $("#cuerpoTabla tr").not(".filaTabla").remove();

      // Llenar tabla
      response.data.forEach(function (nota) {
        $("#tablaNotas tbody").append(generarFilaTabla(nota));
      });

      // Actualizar paginación
      $("#herramientasPaginacion").generarTitulo(
        response.current_page,
        response.per_page,
        response.total,
        clickIndice
      );
      $("#herramientasPaginacion").generarIndices(
        response.current_page,
        response.per_page,
        response.total,
        clickIndice
      );
    },
    error: function (xhr, status, error) {
      // Manejar el error
      console.error("Error al cargar notas:", err);
    },
  });
}

// Función para manejar cambio de página
function clickIndice(e, pageNumber, page_size) {
  e && e.preventDefault();
  var page_size = $("#size").val() || 5;

  const nroNota = $("#buscarNroNota").val();
  const nombreEvento = $("#buscarNombreEvento").val();
  const idCasino = $("#buscarNombreCasino").val();

  cargarNotas(pageNumber, page_size, nroNota, nombreEvento, idCasino);
}

$("#btn-buscar").on("click", function (e) {
  e.preventDefault();

  $("#btn-buscar").prop("disabled", true).text("BUSCANDO...");

  const nroNota = $("#buscarNroNota").val();
  const nombreEvento = $("#buscarNombreEvento").val();
  const idCasino = $("#buscarNombreCasino").val();

  cargarNotas(1, 5, nroNota, nombreEvento, idCasino);

  $("#btn-buscar").prop("disabled", false).text("BUSCAR");
});

function clearInputInfTec() {
  $("#adjuntoInformeTecnico").val(null);
  $("#adjuntoInformeTecnicoName").text("Ningún archivo seleccionado");
  $("#eliminarAdjuntoInformeTecnico").hide();
}
function clearErrorsInfTec() {
  $("#mensajeErrorAdjuntoInformeTecnico").hide();
}

let ID_NOTA_ACTUAL = null;
$("#cuerpoTabla").on("click", ".cargarInformeTecnico", function (e) {
  e.preventDefault();
  ID_NOTA_ACTUAL = $(this).data("id");
  clearInputInfTec();
  clearErrorsInfTec();
  colorBoton("#btn-guardar-informeTecnico");
  $("#modalCargaInfTecnico").modal("show");
});

$("#modalCargaInfTecnico").on("hidden.bs.modal", function () {
  clearInputInfTec();
  clearErrorsInfTec();
  ID_NOTA_ACTUAL = null;
});

const MAX_SIZE_MB = 150;
const MAX_SIZE_BYTES = MAX_SIZE_MB * 1024 * 1024;

function validarInforme(archivo) {
  if (!archivo) {
    return false;
  }
  if (archivo.size > MAX_SIZE_BYTES) {
    return false;
  }
  return true;
}

$("#adjuntoInformeTecnicoBtn").on("click", function (e) {
  $("#adjuntoInformeTecnico").click();
});

$("#adjuntoInformeTecnico").on("change", function (e) {
  if (!this.files[0]) {
    const fileName = "Ningún archivo seleccionado";
    $("#adjuntoInformeTecnicoName").text(fileName);
    $("#eliminarAdjuntoInformeTecnico").hide();
    return;
  }
  const fileName = this.files[0].name;
  const archivo = this.files[0];
  if (archivo && archivo.size > MAX_SIZE_BYTES) {
    $("#mensajeErrorAdjuntoInformeTecnico").show();
    $("#eliminarAdjuntoInformeTecnico").hide();
    $("#adjuntoInformeTecnico").val(null);
    return;
  }
  $("#mensajeErrorAdjuntoInformeTecnico").hide();
  $("#adjuntoInformeTecnicoName").text(fileName);
  $("#eliminarAdjuntoInformeTecnico").show();
});

$("#eliminarAdjuntoInformeTecnico").on("click", function (e) {
  $("#adjuntoInformeTecnico").val(null);
  $("#adjuntoInformeTecnicoName").text("Ningún archivo seleccionado");
  $(this).hide();
});

$("#btn-guardar-informeTecnico").on("click", function (e) {
  e.preventDefault();
  const archivo = $("#adjuntoInformeTecnico")[0].files[0];
  const esValido = validarInforme(archivo);
  if (!esValido) {
    $("#mensajeErrorAdjuntoInformeTecnico").show();
    $("#eliminarAdjuntoInformeTecnico").hide();
    $("#adjuntoInformeTecnico").val(null);
    return;
  }

  const formData = new FormData();
  formData.append("id", ID_NOTA_ACTUAL);
  formData.append(
    "adjuntoInformeTecnico",
    $("#adjuntoInformeTecnico")[0].files[0]
  );

  $.ajax({
    type: "POST",
    url: "/informesTecnicos/guardar",
    data: formData,
    dataType: "json",
    processData: false,
    contentType: false,
    headers: { "X-CSRF-TOKEN": $('meta[name="_token"]').attr("content") },
    success: function (response) {
      const { success, message } = response;
      if (success) {
        $("#mensajeExito h3").text("LA CARGA DEL INFORME SE REALIZO CON EXITO");
        $("#mensajeExito p").text("El informe se guardó correctamente");
        $("#modalCargaInfTecnico").modal("hide");
        ID_NOTA_ACTUAL = null;
        $("#mensajeExito").hide();
        $("#mensajeExito").removeAttr("hidden");

        setTimeout(function () {
          $("#mensajeExito").fadeIn();
        }, 100);

        $("#btn-guardar-informeTecnico")
          .prop("disabled", false)
          .text("Guardar Informe");
        clearInputInfTec();
        clearErrorsInfTec();
        cargarNotas();
      } else {
        $("#btn-guardar-informeTecnico")
          .prop("disabled", false)
          .text("Guardar Informe");

        $("#mensajeError .textoMensaje").empty();
        $("#mensajeError .textoMensaje").append(
          $("<h3></h3>").text(
            "Ocurrio un error al guardar el informe, por favor intenta nuevamente."
          )
        );
        $("#mensajeError").hide();
        setTimeout(function () {
          $("#mensajeError").show();
        }, 250);
        console.error("Error al guardar informe técnico:", message);
      }
    },
    error: function (xhr, status, error) {
      $("#btn-guardar-informeTecnico")
        .prop("disabled", false)
        .text("Guardar Informe");

      $("#mensajeError .textoMensaje").empty();
      $("#mensajeError .textoMensaje").append(
        $("<h3></h3>").text(
          "Ocurrio un error al guardar el informe, por favor intenta nuevamente."
        )
      );
      $("#mensajeError").hide();
      setTimeout(function () {
        $("#mensajeError").show();
      }, 250);
      console.error("Error al guardar informe técnico:", error);
    },
  });
});

function colorBotonGenerar(boton) {
  $(boton).removeClass();
  $(boton).addClass("btn").addClass("btn-successAceptar");
  $(boton).css("cursor", "pointer");
  $(boton).text("Generar Informe");
  $(boton).show();
  $(boton).val("nuevo");
}

let idNotaGenerarInformeTecnico = null;
//! SECCION DE GENERACION DE INFORMES TECNICOS
$("#cuerpoTabla").on("click", ".gestionarInformeTecnico", function (e) {
  e.preventDefault();
  idNotaGenerarInformeTecnico = $(this).data("id");
  $("#modalGeneracionInfTecnico").modal("show");
  colorBotonGenerar($("#btn-guardar-informeTecnico-generado"));
  $.ajax({
    type: "GET",
    url: "/informesTecnicos/preview/" + idNotaGenerarInformeTecnico,
    success: function (response) {
      $("#informeTecnicoEmbed").show();
      $("#informeTecnicoEmbed").attr("src", response.pdfUrl);
    },
    error: function (xhr, status, error) {
      console.error("Error al generar informe técnico:", error);
      $("#informeTecnicoEmbed").hide();
      $("#mensajeErrorInformeTecnico").show();
    },
  });
});
