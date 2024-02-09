<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\EureLib\StaticArrays;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
  protected $table = 'web_users';

  protected $dateFormat = 'Y-m-d H:i:s';

  use HasApiTokens, HasFactory, Notifiable;

  /**
   * The attributes that are mass assignable.
   *
   * @var array<int, string>
   */
  protected $fillable = [
    'name',
    'email',
    'password',
  ];

  /**
   * The attributes that should be hidden for serialization.
   *
   * @var array<int, string>
   */
  protected $hidden = [
    'password',
    'remember_token',
  ];

  /**
   * The attributes that should be cast.
   *
   * @var array<string, string>
   */
  protected $casts = [
    'email_verified_at' => 'datetime',
  ];

  public function responsable()
  {
    return $this->belongsTo(Responsable::class, 'Cod_Responsable');
  }

  public function profe()
  {
    return $this->belongsTo(Profe::class, 'Cod_Profesor');
  }


  public function getNameAttribute()
  {
    return $this->nombres;
  }

  public function getnombreYApellidoAttribute()
  {
    return  $this->nombres . ' ' . $this->apellidos;
  }

  public function getapellidoComaNombresAttribute()
  {
    return  $this->apellidos . ', ' . $this->nombres;
  }

  public function getNombreCompletoAttribute()
  {
    return  $this->nombres . ' ' . $this->apellidos;
  }

  public function getSafeAvatarImgAttribute()
  {
    return $this->avatar_image_withDefaults();
  }

  public function getSafeBgImgAttribute()
  {
    return $this->background_image();
  }

  public function getCardAttribute()
  {
    return StaticArrays::$tiposCards[$this->id % count(StaticArrays::$tiposCards)];
  }


  /********************************************************
   * Nav bar de adminlte
   */

  public function adminlte_image()
  {
    return $this->avatar_image_withDefaults();
  }

  public function adminlte_profile_url()
  {
    return route('responsables.showLogged');
  }

  public function adminlte_desc()
  {
    return $this->desc;
  }

  public function avatar_image()
  {
    if (empty($this->avatarImg))
      return $this->avatarImg;

    return Storage::disk('public')->url($this->avatarImg);
  }

  public function avatar_image_withDefaults()
  {
    // Complicadito el diseño eh.
    switch($this->tipo)
    {
      case 'Responsable':
        return $this->avatar_image_withDefaults_Responsable();
        break;

      case 'Profe':
        return $this->avatar_image_withDefaults_Profe();
        break;

      case 'Secretaria':  // Secretaria, académica? decidite
        return $this->avatar_image_withDefaults_Secretaria();
        break;
    }
  }

  public function avatar_image_withDefaults_Responsable()
  {
    // https://robohash.org/{identificador}.png
    //https://avatars.dicebear.com/api/avataaars/{identificador}.svg.

    if (empty($this->avatar_image()))
    {
      $DVP= env('EURE_DEFAULT_AVATAR_PROVIDER_RESPONSABLES');

      switch($DVP)
      {
        case 'RobotoSet4':
          $AvatarIMG=  'https://robohash.org/' . $this->id . '.png?set=set4';
          break;

        case 'avataroxro_Iniciales':  // este no anda más
          $AvatarIMG= "https://avatar.oxro.io/avatar.svg?name={$this->nombres}+{$this->apellidos}";
          break;

        case 'ui-avatars_Iniciales':
          $AvatarIMG= "https://ui-avatars.com/api/?background=random&size=512&bold=true&name=&name={$this->nombres}+{$this->apellidos}";
        break;

        default:
          $AvatarIMG= "https://avatar.oxro.io/avatar.svg?name={$this->nombres}+{$this->apellidos}";
          break;
      }

    }
    else
      $AvatarIMG= $this->avatar_image();


    return $AvatarIMG;
  }

  public function avatar_image_withDefaults_Profe()
  {
    // https://robohash.org/{identificador}.png
    //https://avatars.dicebear.com/api/avataaars/{identificador}.svg.

    if (empty($this->avatar_image()))
    {
      $DVP= env('EURE_DEFAULT_AVATAR_PROVIDER_PROFES');

      switch($DVP)
      {
        case 'RobotoSet4':
          $AvatarIMG=  'https://robohash.org/' . $this->id . '.png?set=set4';
          break;

        case 'avataroxro_Iniciales':
          $AvatarIMG= "https://avatar.oxro.io/avatar.svg?name={$this->nombres}+{$this->apellidos}";
          break;

        default:
          $AvatarIMG= "https://avatar.oxro.io/avatar.svg?name={$this->nombres}+{$this->apellidos}";
          break;
      }

    }
    else
      $AvatarIMG= $this->avatar_image();


    return $AvatarIMG;
  }

  public function avatar_image_withDefaults_Secretaria()
  {
    // https://robohash.org/{identificador}.png
    //https://avatars.dicebear.com/api/avataaars/{identificador}.svg.

    if (empty($this->avatar_image()))
    {
      $DVP= env('EURE_DEFAULT_AVATAR_PROVIDER_SECRETARIA');

      switch($DVP)
      {
        case 'RobotoDefault':
          $AvatarIMG=  'https://robohash.org/' . $this->id . '.png';
          break;

        case 'RobotoSet4':
          $AvatarIMG=  'https://robohash.org/' . $this->id . '.png?set=set4';
          break;

        case 'avataroxro_Iniciales':
          $AvatarIMG= "https://avatar.oxro.io/avatar.svg?name={$this->nombres}+{$this->apellidos}";
          break;

        default:
          $AvatarIMG= "https://avatar.oxro.io/avatar.svg?name={$this->nombres}+{$this->apellidos}";
          break;
      }

    }
    else
      $AvatarIMG= $this->avatar_image();


    return $AvatarIMG;
  }

  public function background_image()
  {
    if (empty($this->bgImg))
      return '/assets/images/usuarios/bgs/' . ($this->id % 8) . '.jpg';


      return 'https://upload.wikimedia.org/wikipedia/commons/thumb/4/43/Red_flag.svg/1280px-Red_flag.svg.png';
  }

  public function scopeEsRemitenteDeComunicacionA($query, $alumno)
  {
    if ($alumno != "")
      $query
        ->whereIn('id', function ($query) use ($alumno) {
          $query->select('C.usuario_id')
            ->from('web_comunicaciones AS C')
            ->whereIn('C.id', function ($query) use ($alumno) {
              $query->select('CD.comunicacion_id')
                ->from('web_comunicaciones_destinatarios AS CD')
                ->where('Cod_Alumno', $alumno->id);
            });
        });
  }

  public function scopeRemitentesDe($query, $comunicaciones)
  {
    if ($comunicaciones != null) {
      $ids= $comunicaciones->pluck('usuario_id');

      $query->whereIn('id', $ids);
    }
  }

}
