$(document).ready(function () {
  $("#barraMenu").attr("aria-expanded", "true");
  $(".tituloSeccionPantalla").text(" Auditoría de Eventos ");
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
});

$("#btn-guardar-evento").on("click", function (e) {
  e.preventDefault();
  const archivo = $("#adjuntoEventos")[0].files[0];
  if (!archivo) {
    return;
  }
  const formData = new FormData();
  formData.append("archivo", archivo);
});
