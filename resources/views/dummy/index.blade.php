@extends('adminlte::page')

{{--@section('title', 'Educatio')--}}

@section('content_header')
  <h1>Dashboard</h1>



@stop

@section('content')
  <p>Welcome to this beautiful admin panel.</p>

  {{--  <i class="far fa-chalkboard-teacher"></i>--}}

  {{--  <i class="fa fa-plane"></i>--}}

  {{--  <img src="/assets/images/grupo.svg" width="100">--}}
  {{--  <i class="fas fa-users-class"></i>--}}



{{--{{ dd($a) }}--}}

  @php
    $config = [
        "placeholder" => "Select multiple options...",
        "allowClear" => true,
    ];
  @endphp
  <x-adminlte-select2 id="sel2Category" name="sel2Category[]" label="Categories"
                      label-class="text-danger" igroup-size="sm" :config="$config" multiple>
    <x-slot name="prependSlot">
      <div class="input-group-text bg-gradient-red">
        <i class="fas fa-tag"></i>
      </div>
    </x-slot>
    <x-slot name="appendSlot">
      <x-adminlte-button theme="outline-dark" label="Clear" icon="fas fa-lg fa-ban text-danger"/>
    </x-slot>
    <option>Sports</option>
    <option>News</option>
    <option>Games</option>
    <option>Science</option>
    <option>Maths</option>
  </x-adminlte-select2>


  <x-adminlte-select2 name="sel2Vehicle" label="Vehicle" label-class="text-lightblue"
                      igroup-size="lg" data-placeholder="Select an option...">
    <x-slot name="prependSlot">
      <div class="input-group-text bg-gradient-info">
        <i class="fas fa-car-side"></i>
      </div>
    </x-slot>
    <option/>
    <option>Vehicle 1</option>
    <option>Vehicle 2</option>
  </x-adminlte-select2>





@stop

@section('css')
  <link rel="stylesheet" href="/css/admin_custom.css">
@stop

@section('js')
  <script>
    $(function () {

      //Initialize Select2 Elements
      //    $('.select2').select2()


      // $('.select2').select2({
      //   'theme': 'bootstrap4'
      // })


    })
  </script>
@stop
