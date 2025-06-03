var datatablesConfig = {
  language: {
    "sEmptyTable":     "No hay datos disponibles en la tabla",
    "sInfo":           "Mostrando _START_ a _END_ de _TOTAL_ entradas",
    "sInfoEmpty":      "Mostrando 0 a 0 de 0 entradas",
    "sInfoFiltered":   "(filtrado de _MAX_ entradas totales)",
    "sInfoPostFix":    "",
    "sInfoThousands":  ",",
    "sLengthMenu":     "Mostrar _MENU_ entradas",
    "sLoadingRecords": "Cargando...",
    "sProcessing":     "Procesando...",
    "sSearch":         "Buscar:",
    "sZeroRecords":    "No se encontraron registros coincidentes",
    "oPaginate": {
      "sFirst":    "Primero",
      "sLast":     "Último",
      "sNext":     "Siguiente",
      "sPrevious": "Anterior"
    },
    "oAria": {
      "sSortAscending":  ": Activar para ordenar la columna de manera ascendente",
      "sSortDescending": ": Activar para ordenar la columna de manera descendente"
    },

  },
  // Otras opciones de configuración que desees establecer
};

var inputMoneyConfig = {
  numeric: {
    alias: 'numeric',
    groupSeparator: '.',
    radixPoint: ',',
    autoGroup: true,
    digits: 2,
    digitsOptional: false,
    placeholder: '0',
    rightAlign: false,
    removeMaskOnSubmit: true
  },
  currency: {
    alias: 'currency',
    groupSeparator: '.',
    radixPoint: ',',
    autoGroup: true,
    digits: 2,
    digitsOptional: false,
    prefix: '$ ',
    placeholder: '0',
    rightAlign: false,
    removeMaskOnSubmit: true
  }
}

function agruparCeldasIguales1erColumnaEnTabla(table)
{

  let headerCell = null;

  for (let row of table.rows) {
    const firstCell = row.cells[0];

    if (headerCell === null || firstCell.innerText !== headerCell.innerText) {
      headerCell = firstCell;
    } else {
      headerCell.rowSpan++;
      firstCell.remove();
    }
  }
}
