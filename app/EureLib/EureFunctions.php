<?php

namespace App\EureLib;

use App\Models\Alumno;
use App\Models\AlumnoWeb;
use App\Models\Comunicacion;
use App\Models\ComunicacionDestinatario;
use App\Models\Responsable;
use App\Models\User;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use JeroenNoten\LaravelAdminLte\Events\BuildingMenu;


class EureFunctions
{
  public static function crearMenus()
  {
    Event::listen(BuildingMenu::class, function (BuildingMenu $event) {
      // Add some items to the menu...
      self::crearMenusInner($event);
    });
  }

  public static function crearMenusInner(BuildingMenu $event)
  {
    $responsable = self::getLoggedResponsableAttribute();

    foreach ($responsable->alumnos as $alumno)
    {

      $hmComunicacionesSinLeerResponsableAlumno = Comunicacion::NoLeidosPorAlumno(true, $responsable, $alumno)->count();

      $event->menu->addIn('AlumnosACargoRoot',
        [
          'text' => $alumno->Nombre,
          'url' => route('alumnos.show', $alumno),
          //'route' => 'dummy3',
          'icon' => 'fas fa-user',
          'key' => 'Alumno_' . $alumno->id,
          'classes' => 'ml-1',

        ]);

      // Comunicaciones del alumno
      $event->menu->addIn('AlumnosACargoRoot',
        [
          'text' => "Comunicaciones",
          'url' => route('comunicaciones.indexA', $alumno),
          'icon' => 'fas fa-paper-plane',
          'color' => 'red',
          'classes' => 'ml-2',
          //'route' => 'dummy3',
          //  'icon' => 'fas fa-user',
          //'key' => 'Alumno_' . $alumno->id
          'label' => ($hmComunicacionesSinLeerResponsableAlumno > 0) ? $hmComunicacionesSinLeerResponsableAlumno : '',
          'label_color' => self::devolverLabelStyleSegunCantidad($hmComunicacionesSinLeerResponsableAlumno)
        ]);

      // Comunicaciones del alumno
      $event->menu->addIn('AlumnosACargoRoot',
        [
          'text' => "Pagos",
          'url' => route('pagos.indexA', $alumno),
          'icon' => 'fas fa-money-bill-wave-alt',
          'color' => 'red',
          'classes' => 'ml-2',
          //'route' => 'dummy3',
          //  'icon' => 'fas fa-user',
          //'key' => 'Alumno_' . $alumno->id
        ]);

      $event->menu->addIn('AlumnosACargoRoot',
        [
          'text' => "Cuenta Corriente",
          'url' => route('cc.indexA', $alumno),
          'icon' => 'fas fa-money-check-alt',
          'color' => 'red',
          'classes' => 'ml-2',
          //'route' => 'dummy3',
          //  'icon' => 'fas fa-user',
          //'key' => 'Alumno_' . $alumno->id
        ]);

    }

  }

  public static function devolverLabelStyleSegunCantidad(int $hm)
  {
    if ($hm == 0)
      return '';

    if ($hm < 2)
      return 'info';

    if ($hm < 7)
      return 'warning';

    return 'danger';
  }

  public static function getLoggedUserAttribute()
  {
    return User::find(Auth::id());
  }

  public static function getLoggedResponsableAttribute()
  {
    return self::getLoggedUserAttribute()->responsable;
  }

  public static function esUsuarioLogueadoEsResponsableDeAlumno(Alumno $a)
  {
    return self::esResponsableDeAlumno(self::getLoggedResponsableAttribute(), $a);
  }

  public static function esResponsableDeAlumno(Responsable $r, Alumno $a)
  {
    return $a->Responsable1 == $r->id || $a->Responsable2 == $r->id;
  }


  public static function comunicacionPendienteDeLectura(Comunicacion $c, Responsable $r)
  {
    $cd = ComunicacionDestinatario::where('comunicacion_id', '=', $c->id)->where('Cod_Responsable', $r->id)->first();

    // Acá pueden pasar un par de cosas. No etiendo bien si es null. Como que es el
    // responsable de otro pibe. Le pongo "leido" porque no hay obligacion de leer
    if ($cd == null)
      return false;

    return $cd->leido == false;
  }

  public static function esDestinatarioDeComunicacion(Comunicacion $c, Responsable $r)
  {
    $cd = ComunicacionDestinatario::where('comunicacion_id', '=', $c->id)->where('Cod_Responsable', $r->id)->first();

    // Acá pueden pasar un par de cosas. No etiendo bien si es null. Como que es el
    // responsable de otro pibe. Le pongo "leido" porque no hay obligacion de leer
    if ($cd == null)
      return false;

    return true;
  }



  public static function crearUsuarioResponsable($login, $nombres, $apellidos, $desc, $cod_responsable, $password)
  {
    $userNew = new User();

    $userNew->login = $login;
    $userNew->nombres = $nombres;
    $userNew->apellidos = $apellidos;
    $userNew->desc = $desc;
    $userNew->tipo = 'Responsable';
    $userNew->Cod_Responsable = $cod_responsable;
    $userNew->password = Hash::make($password);

    $userNew->save();
  }

  public static function getIconByFileType($filename)
  {
    $filename = strtolower($filename);

    $extension = self::getFileExtension($filename);

    $extImagenes = ['png', 'jpg', 'jpeg', 'gif', 'bmp'];

    if (in_array($extension, $extImagenes))
      return "/storage/$filename";


    $iconFilename = StaticArrays::$imageMap[$extension] ?? 'GEN_file_icon.svg.png';

    return "/assets/images/$iconFilename";
  }

  public static function getFileExtension($fileName)
  {
    // Obtener la información de la ruta del archivo
    $pathInfo = pathinfo($fileName);

    // Obtener la extensión del archivo
    $extension = $pathInfo['extension'] ?? null;

    // Devolver la extensión en minúsculas (opcional)
    return strtolower($extension);
  }

  public static function hoy()
  {
    $f= Carbon::today();

    return $f;
  }

  public static function ultimoDiaMes()
  {
    $f = Carbon::now();

    // Obtiene el último día del mes actual
    $udm = $f->endOfMonth();

    return $udm;
  }


  public static function toCarbonDateFromYmd($ymd)
  {
    if ($ymd == null)
      return null;

    $ymd = substr($ymd, 0, 10);

    $_4D = Carbon::createFromFormat('Y-m-d', $ymd)->startOfDay();

//    dd($_4D->diffForHumans());

    return $_4D;
  }

  public static function toStringFromFloat($f)
  {
    return number_format($f, 2, ',', '.');
  }

  public static function toMoneyFromFloat($f)
  {
    return "$ " . self::toStringFromFloat($f);
  }

  public static function obtenerPDF($rptFN, $fnPrefix, $fnMiddleInsert, $rptParams)
  {
    $url = getenv('EURE_PDF_PROVIDER_URL');


    $clienteID = getenv('EURE_PDF_PROVIDER_CLIENTID');

    $post_data = [
      'clienteID' => $clienteID,
      'rptFN' => $rptFN,
      'fnPrefix' => $fnPrefix,
      'fnMiddleInsert' => $fnMiddleInsert,
      'rptParams' => $rptParams,
    ];

    $client = new Client();

//    dd($data);|

    $JPostData = json_encode($post_data);

    try
    {
      $r = $client->request('POST', $url, [
        'body' => $JPostData
      ]);


      // Procesar la respuesta aquí si es necesario
      $body = $r->getBody()->getContents();

      return json_decode($body);

      //return response()->json(['data' => $body]);

    } catch (\Exception $e)
    {
      // Hacer en el futuro una pantalla que maneje mejor esto
      abort(500, $e->getMessage());

      // Manejar cualquier excepción que ocurra durante la solicitud

    }
  }

  // Intenté si esto se mantenia estático pero no
  //  private static $initialized = false;
//  public static $loggedUser;
//  public static $loggedUserResponsable;
//
//  public static function initialize()
//  {
//    if (!self::$initialized) {
//      // Tareas de inicialización aquí
//      self::$loggedUser= User::find(Auth::id());
//      self::$loggedUserResponsable= self::$loggedUser->responsable;
//
//      self::$initialized = true;
//    }
//  }
//
//  public static function getMyStaticAttribute()
//  {
//    $A= self::$loggedUser;
//
//   // dd($A);
//
//    return $A;
//  }

}
