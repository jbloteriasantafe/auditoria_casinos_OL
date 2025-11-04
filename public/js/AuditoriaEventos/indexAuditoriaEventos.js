$(document).ready(function () {
  $("#barraMenu").attr("aria-expanded", "true");
  $(".tituloSeccionPantalla").text(" Auditoría de Eventos ");
  const casinoSeleccionado = $("#filtroCasino").val();
  const fechaSeleccionada = $("#filtroFecha").val();
  cargarNotas(1, 5, casinoSeleccionado, fechaSeleccionada);
});

//! FUNCIONES AUXILIARES
function colorBoton(boton) {
  $(boton).removeClass();
  $(boton).addClass("btn").addClass("btn-successAceptar");
  $(boton).css("cursor", "pointer");
  $(boton).text("Importar Eventos");
  $(boton).show();
  $(boton).val("nuevo");
}

function actualizarFechasCarga() {
  let nuevaFecha = null;
  $.ajax({
    type: "GET",
    url: "/auditoriaEventos/fechasCarga",
    dataType: "json",
    headers: { "X-CSRF-TOKEN": $('meta[name="_token"]').attr("content") },
    processData: false,
    contentType: false,
    success: function (response) {
      console.log(response);
      const { fechas } = response;
      nuevaFecha = fechas[0].fecha;
      const filtroFecha = $("#filtroFecha");
      filtroFecha.empty();
      fechas.forEach((fecha) => {
        filtroFecha.append(
          `<option value="${fecha}">${fecha.formateada}</option>`
        );
      });
    },
  });
  console.log(nuevaFecha);
  return nuevaFecha;
}

//! MODAL IMPORTAR EVENTOS
$("#btn-importar-evento").on("click", function (e) {
  e.preventDefault();
  colorBoton("#btn-guardar-evento");
  $("#modalImporteEventos").modal("show");
});

//! BOTON GUARDAR EVENTO
const MAX_SIZE_MB = 150;
const MAX_SIZE_BYTES = MAX_SIZE_MB * 1024 * 1024;

$("#adjuntoEventosBtn").on("click", function (e) {
  $("#adjuntoEventos").click();
});

$("#adjuntoEventos").on("change", function (e) {
  if (!this.files[0]) {
    const fileName = "Ningún archivo seleccionado";
    $("#adjuntoEventosName").text(fileName);
    $("#eliminarAdjuntoEventos").hide();
    return;
  }
  const fileName = this.files[0].name;
  const archivo = this.files[0];
  if (archivo && archivo.size > MAX_SIZE_BYTES) {
    $("#mensajeErrorAdjuntoEventos").show();
    $("#eliminarAdjuntoEventos").hide();
    $("#adjuntoEventos").val(null);
    return;
  }
  $("#mensajeErrorAdjuntoEventos").hide();
  $("#mensajeErrorAdjuntoVacio").hide();
  $("#adjuntoEventosName").text(fileName);
  $("#eliminarAdjuntoEventos").show();
});

$("#eliminarAdjuntoEventos").on("click", function (e) {
  $("#adjuntoEventos").val(null);
  $("#adjuntoEventosName").text("Ningún archivo seleccionado");
  $(this).hide();
});

$("#modalImporteEventos").on("hidden.bs.modal", function () {
  $("#adjuntoEventos").val(null);
  $("#adjuntoEventosName").text("Ningún archivo seleccionado");
  $("#mensajeErrorAdjuntoEventos").hide();
  $("#eliminarAdjuntoEventos").hide();
  $("#mensajeErrorAdjuntoVacio").hide();
});

$("#btn-guardar-evento").on("click", function (e) {
  e.preventDefault();
  const archivo = $("#adjuntoEventos")[0].files[0];
  if (!archivo) {
    $("#mensajeErrorAdjuntoVacio").show();
    return;
  }

  $("#btn-guardar-evento").attr("disabled", true);
  $("#btn-guardar-evento").text("Importando...");

  const formData = new FormData();
  formData.append("adjuntoEventos", archivo);

  $.ajax({
    url: "/auditoriaEventos/importar",
    type: "POST",
    data: formData,
    dataType: "json",
    headers: { "X-CSRF-TOKEN": $('meta[name="_token"]').attr("content") },
    processData: false,
    contentType: false,
    success: function (response) {
      const { success } = response;
      if (success) {
        const nuevaFecha = actualizarFechasCarga();
        cargarNotas(1, 5, $("#filtroCasino").val(), nuevaFecha);
        $("#mensajeExito h3").text("ÉXITO DE IMPORTACIÓN");
        $("#mensajeExito p").text(
          "Los eventos se han importado correctamente."
        );
        $("#modalImporteEventos").modal("hide");

        $("#mensajeExito").hide();
        $("#mensajeExito").removeAttr("hidden");

        setTimeout(function () {
          $("#mensajeExito").fadeIn();
        }, 100);
        $("#btn-guardar-evento").attr("disabled", false);
        $("#btn-guardar-evento").text("Importar Eventos");
      } else {
        $("#btn-guardar-evento")
          .attr("disabled", false)
          .text("Importar Eventos");

        $("#mensajeError .textoMensaje").empty();
        $("#mensajeError .textoMensaje").append(
          $("<h3></h3>").text(
            "Ocurrio un error al cargar los eventos, por favor intenta nuevamente."
          )
        );
        $("#mensajeError").hide();
        setTimeout(function () {
          $("#mensajeError").show();
        }, 250);
      }
    },
    error: function (xhr, status, error) {
      console.log("Error al importar los eventos:", error);
      $("#btn-guardar-evento").attr("disabled", false).text("Importar Eventos");

      $("#mensajeError .textoMensaje").empty();
      $("#mensajeError .textoMensaje").append(
        $("<h3></h3>").text(
          "Ocurrio un error al cargar los eventos, por favor intenta nuevamente."
        )
      );
      $("#mensajeError").hide();
      setTimeout(function () {
        $("#mensajeError").show();
      }, 250);
    },
  });
});

//! FUNCIONES PAGINACION
function generarFilaTabla(evento) {
  let fila = $("#cuerpoTabla .filaTabla")
    .clone()
    .removeClass("filaTabla")
    .show();

  fila
    .find(".numero_nota")
    .text(evento.nro_nota || "No hay información disponible")
    .attr("title", evento.nro_nota || "No hay información disponible");
  fila
    .find(".casino_origen")
    .text(
      ` ${
        evento.casino_origen === 4
          ? "CCOL"
          : evento.casino_origen === 5
          ? "BPLAY"
          : "No hay información disponible"
      } `
    )
    .attr("title", evento.casino_origen || "No hay información disponible");
  fila
    .find(".nombre_evento")
    .text(evento.nombre_evento || "No hay información disponible")
    .attr("title", evento.nombre_evento || "No hay información disponible");
  fila
    .find(".fecha_inicio_evento")
    .text(evento.fecha_inicio_evento || "No hay información disponible")
    .attr(
      "title",
      evento.fecha_inicio_evento || "No hay información disponible"
    );
  fila
    .find(".fecha_finalizacion_evento")
    .text(evento.fecha_finalizacion_evento || "No hay información disponible")
    .attr(
      "title",
      evento.fecha_finalizacion_evento || "No hay información disponible"
    );
  fila
    .find(".fecha_carga")
    .text(evento.fecha_carga || "No hay información disponible")
    .attr("title", evento.fecha_carga || "No hay información disponible");
  fila
    .find(".estado")
    .text(evento.estado || "No hay información disponible")
    .attr("title", evento.estado || "No hay información disponible");
  fila
    .find(".url_promo")
    .html(
      `
      ${
        evento.url_promo
          ? `<a href="${evento.url_promo}" target="_blank">${evento.url_promo}</a>`
          : "No hay información disponible"
      }
    `
    )
    .attr("title", evento.url_promo || "No hay información disponible");
  fila
    .find(".valido")
    .html(
      `
      ${
        evento.valido
          ? `<i class="fas fa-check" style="color: green;"></i>`
          : '<i class="fas fa-times" style="color: red;"></i>'
      }
    `
    )
    .attr("title", evento.valido ? "Sí" : "No");

  return fila;
}

function cargarNotas(page = 1, perPage = 5, casino, fechaCarga) {
  let formData = new FormData();
  formData.append("page", page);
  formData.append("perPage", perPage);

  if (casino) {
    formData.append("casino", casino);
  }
  if (fechaCarga) {
    formData.append("fechaCarga", fechaCarga);
  }
  console.log(fechaCarga);
  $.ajax({
    type: "POST",
    url: "/auditoriaEventos/paginar",
    data: formData,
    dataType: "json",
    processData: false,
    contentType: false,
    headers: { "X-CSRF-TOKEN": $('meta[name="_token"]').attr("content") },
    success: function (response) {
      // Limpiar tabla
      $("#cuerpoTabla tr").not(".filaTabla").remove();

      // Llenar tabla
      response.data.data.forEach(function (evento) {
        $("#tablaNotas tbody").append(generarFilaTabla(evento));
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

function clickIndice(e, pageNumber, page_size) {
  e && e.preventDefault();
  var page_size = $("#size").val() || 5;

  var casino = $("#filtroCasino").val();
  var fechaCarga = $("#filtroFecha").val();

  cargarNotas(pageNumber, page_size, casino, fechaCarga);
}

//TODOS:AL IMPORTAR EVENTOS RECARGAR LAS FECHAS DE CARGA Y LA TABLA
$("#filtroCasino, #filtroFecha").on("change", function () {
  const casino = $("#filtroCasino").val();
  const fecha = $("#filtroFecha").val();

  cargarNotas(1, 5, casino, fecha);
});
