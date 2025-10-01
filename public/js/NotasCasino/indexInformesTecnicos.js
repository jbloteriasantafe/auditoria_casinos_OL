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
          : `<a href='informesTecnicos/notas/archivo/${nota.idevento_enc}/disenio'>${nota.adjunto_diseño}</a>`
      }`
    )
    .attr("title", nota.adjunto_diseño || "No hay información disponible");
  fila
    .find(".adjunto_basesycond")
    .html(
      `${
        !nota.adjunto_basesycond
          ? "No hay información disponible"
          : `<a href='informesTecnicos/notas/archivo/${nota.idevento_enc}/basesycond'>${nota.adjunto_basesycond}</a>`
      }`
    )
    .attr("title", nota.adjunto_basesycond || "No hay información disponible");
  fila
    .find(".adjunto_informe_tecnico")
    .html(
      `${
        !nota.adjunto_inf_tecnico
          ? "No hay información disponible"
          : `<a href='informesTecnicos/notas/archivo/${nota.idevento_enc}/inf_tecnico'>${nota.adjunto_inf_tecnico}</a>`
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
    `
        <button class="gestionarInformeTecnico btn btn-info" title="Gestionar informe técnico"><i class="fa fa-cog"></i></button>
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

//modal
$(document).on("click", ".gestionarInformeTecnico", function () {
  colorBoton($("#btn-guardar-informe"));
  $("#modalInformeTecnico").modal("show");
});

$("#select-juegos").on("click", function () {
  $(".lista-juegos").slideToggle(200);
});

$(".list-item").on("click", function () {
  const juegoSeleccionado = $(this).text();
  $(".juego-seleccionado").text(juegoSeleccionado);
});

$("#buscador-juegos").on("click", function (e) {
  e.stopPropagation();
});

function generarListaJuegos(juegos) {
  $(".resultados-busqueda").children().remove();
  juegos.forEach(function (juego) {
    $(".resultados-busqueda").append(
      `<p class="list-item">${juego.nombre}</p>`
    );
  });
}
