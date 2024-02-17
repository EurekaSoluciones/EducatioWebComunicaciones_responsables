<?php

namespace App\Models;

use App\EureLib\StaticArrays;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;


class Alumno extends Model
{
  use HasFactory;

  protected $table = 'Alumnos';

  protected $primaryKey = 'Cod_Alumno';

  protected $casts = [
    'Ciclo' => 'integer',
  ];


  public function getapellidoAttribute($value)
  {
    return Str::title($value);
  }

  public function getIdAttribute()
  {
    return $this->attributes['Cod_Alumno'];
  }

  public function EResponsable1()
  {
    return $this->belongsTo(Responsable::class, 'Responsable1');
  }

  public function ETipoResponsable1()
  {
    return $this->belongsTo(ResponsableTipo::class, 'Tipo_Responsable1');
  }

  public function getETipoResponsable1DescripcionAttribute()
  {
    return $this->ETipoResponsable1 != null ? $this->ETipoResponsable1->Descripcion : '';
  }

  public function EResponsable2()
  {
    return $this->belongsTo(Responsable::class, 'Responsable2');
  }

  public function ETipoResponsable2()
  {
    return $this->belongsTo(ResponsableTipo::class, 'Tipo_Responsable2');
  }

  public function getETipoResponsable2DescripcionAttribute()
  {
    return $this->ETipoResponsable2 != null ? $this->ETipoResponsable2->Descripcion : '';
  }

  public function grupo()
  {
    return $this->belongsTo(Grupo::class, 'Cod_Grupo');
  }

  public function web()
  {
    // Pregunto y si no hay entrada en web_alumnos la creo
    $alumnoWeb = AlumnoWeb::find($this->id);

    if ($alumnoWeb == null)
    {
      // Lo creo
      $alumnoWeb = new AlumnoWeb();

      $alumnoWeb->Cod_Alumno = $this->id;
      $alumnoWeb->avatarImg = '';
      $alumnoWeb->bgImg = '';

      $alumnoWeb->save();
      $alumnoWeb = AlumnoWeb::find($this->id);
    }

    //Esto hace dos queries a la db y no me gusta
    return $this->belongsTo(AlumnoWeb::class, 'Cod_Alumno');
  }


//  public function comunicacionesDest()
//  {
//    return $this->hasMany(ComunicacionDestinatario::class, 'Cod_Alumno')->orderByDesc('id');
//  }

  public function comunicaciones()
  {
    return $this->obtenerComunicaciones(false);
  }

  public function comunicacionesSinLeer(Responsable $responsable)
  {
    return $this->obtenerComunicaciones(true, $responsable);
  }


  public function obtenerComunicaciones($soloSinLeer, Responsable $responsable = null)
  {
    // https://stackoverflow.com/questions/60411513/why-hasmanythrough-from-eloquent-documentation-not-work

    $ComRaw = $this->hasManyThrough(Comunicacion::class, ComunicacionDestinatario::class,
      'Cod_Alumno', 'id', 'id', 'comunicacion_id');

    //   'Cod_Responsable', 'id', 'Cod_Responsable', 'comunicacion_id');
    if ($soloSinLeer)
      $ComRaw = $ComRaw->whereNull('fhLeido');


    if ($responsable != null)
      $ComRaw = $ComRaw->where('Cod_Responsable', '=', $responsable->id);

    return $ComRaw->orderBy('web_comunicaciones.id', 'desc')->distinct();;
  }


  public function getnombreYApellidoAttribute()
  {
    return $this->Nombre . ' ' . $this->Apellido;
  }

  public function getinicialesAttribute()
  {
    return Str::substr($this->Nombre, 0, 1) . Str::substr($this->Apellido, 0, 1);
  }



  public function getCardAttribute()
  {
    return StaticArrays::$tiposCards[$this->id % count(StaticArrays::$tiposCards)];
  }

  public function gettextColorNWAttribute()
  {
    return StaticArrays::$textsColorsNoWhite[$this->id % count(StaticArrays::$textsColorsNoWhite)];
  }

  public function textColorNWConOffset($offset)
  {
    return StaticArrays::$textsColorsNoWhite[($this->id + $offset) % count(StaticArrays::$textsColorsNoWhite)];
  }


  public function getcolorNWAttribute()
  {
    return StaticArrays::$colorsNoWhite[($this->id) % count(StaticArrays::$colorsNoWhite)];
  }

  public function getbgAttribute()
  {
    return StaticArrays::$backgroundsNoWhite[($this->id) % count(StaticArrays::$backgroundsNoWhite)];
  }

  public function getbtnAttribute()
  {
    return StaticArrays::$clasesBtn[($this->id) % count(StaticArrays::$clasesBtn)];
  }

  public function gettextColorSegunBGAttribute()
  {
    return StaticArrays::$textSegunBackground[$this->bg];
  }

  public function TipoResponsableSegunResponsable(Responsable $responsable)
  {
    if ($responsable->id == $this->Responsable1)
      return $this->ETipoResponsable1;

    if ($responsable->id == $this->Responsable2)
      return $this->ETipoResponsable2;

    return null;
  }

  public function getsafeAvatarImgAttribute()
  {
    return $this->avatar_image_withDefaults();
  }

  public function avatar_image()
  {
    if (empty($this->web->avatarImg))
      return $this->web->avatarImg;

    return Storage::disk('public')->url($this->web->avatarImg);
  }

  public function avatar_image_withDefaults()
  {
    if (empty($this->avatar_image()))
      $AvatarIMG = $this->avatar_image_default();
    else
      $AvatarIMG = $this->avatar_image();

    return $AvatarIMG;
  }

  public function avatar_image_default()
  {
    $DVP = env('EURE_DEFAULT_AVATAR_PROVIDER_ALUMNOS');

    switch ($DVP)
    {
      case 'RobotoSet4':
        $AvatarIMG = 'https://robohash.org/' . $this->id . '.png?set=set4';
        break;

      case 'avataroxro_Iniciales':  // este no anda más
        $AvatarIMG = "https://avatar.oxro.io/avatar.svg?name={$this->Nombre}+{$this->Apellido}";
        break;

      case 'ui-avatars_Iniciales':
        $AvatarIMG = "https://ui-avatars.com/api/?background=random&size=512&bold=true&name=&name={$this->Nombre}+{$this->Apellido}";
        break;

      default:
        $AvatarIMG = "https://ui-avatars.com/api/?background=random&size=512&bold=true&name=&name={$this->Nombre}+{$this->Apellido}";
        break;
    }

    return $AvatarIMG;

  }
}
