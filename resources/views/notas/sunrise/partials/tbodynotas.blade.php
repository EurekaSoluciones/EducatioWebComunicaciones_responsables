
@foreach($notas as $n)
  @if ($n->FormatoTitulo)
    <tr class="tituloBoletinRow">
      <td class="tituloBoletin1erCelda" style="width:250px;">{{$n->Materia}}</td>
      <td class="text-center" style="width:50px;">&nbsp;</td>
      @if ($hmcols > 1)
      <td class="text-center" style="width:50px;">&nbsp;</td>
      @endif

      @if ($hmcols > 2)
        <td class="text-center" style="width:50px;">&nbsp;</td>
      @endif
    </tr>
  @else
    <tr class="notaBoletinRow">
      <td class="notaBoletin1erCelda" style="width:250px;">{{$n->Materia}}</td>
      <td class="text-center" style="width:50px;">{{$n->Nota}}</td>
      @if ($hmcols > 1)
      <td class="text-center" style="width:50px;">{{$n->Nota2}}</td>
      @endif
      @if ($hmcols > 2)
        <td class="text-center" style="width:50px;">{{$n->Nota3}}</td>
      @endif
    </tr>

  @endif


@endforeach
