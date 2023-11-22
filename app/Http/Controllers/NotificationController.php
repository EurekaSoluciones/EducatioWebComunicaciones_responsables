<?php

namespace App\Http\Controllers;

use App\EureLib\EureFunctions;
use App\Models\Comunicacion;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
  public function show()
  {

  }

  public  function get()
  {
    // For the sake of simplicity, assume we have a variable called
    // $notifications with the unread notifications. Each notification
    // have the next properties:
    // icon: An icon for the notification.
    // text: A text for the notification.
    // time: The time since notification was created on the server.
    // At next, we define a hardcoded variable with the explained format,
    // but you can assume this data comes from a database query.

    $responsable= EureFunctions::getLoggedResponsableAttribute();

    $hmComunicacionesSinLeer= Comunicacion::NoLeidos(true, $responsable)->count();

    $notifications = [
      [
        'icon' => 'fas fa-fw fa-envelope',
        'text' => $hmComunicacionesSinLeer . ' Comunicaciones sin leer',
        'link' => '#'
     //   'time' => rand(0, 10) . ' minutes',
      ],
//      [
//        'icon' => 'fas fa-fw fa-users text-primary',
//        'text' => rand(0, 10) . ' friend requests',
//        'time' => rand(0, 60) . ' minutes',
//      ],
//      [
//        'icon' => 'fas fa-fw fa-file text-danger',
//        'text' => rand(0, 10) . ' new reports',
//        'time' => rand(0, 60) . ' minutes',
//      ],
    ];

    // Now, we create the notification dropdown main content.

    $dropdownHtml = '';

    foreach ($notifications as $key => $not) {
      $icon = "<i class='mr-2 {$not['icon']}'></i>";

//      $time = "<span class='float-right text-muted text-sm'>
//                   {$not['time']}
//                 </span>";

      $time= '';

      if ($not['link'] != '')
            $dropdownHtml .= "<a href='" . $not['link'] ."' class='dropdown-item'>
                            {$icon}{$not['text']}{$time}
                          </a>";
      else
        $dropdownHtml .= "{$icon}{$not['text']}{$time}";


      if ($key < count($notifications) - 1) {
        $dropdownHtml .= "<div class='dropdown-divider'></div>";
      }
    }

    //
    $hmNotificaciones=
      $hmComunicacionesSinLeer +
      0;    // seguir metiendo cosas para la campanita

    // Return the new notification data.

    return [
      'label'       => $hmNotificaciones,
      'label_color' => 'danger',
      'icon_color'  => 'dark',
      'dropdown'    => $dropdownHtml,
    ];
  }
}
