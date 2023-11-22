<?php

namespace App\EureLib;

class StaticArrays
{
  public static $tiposCards = [
    'card-primary',
    'card-secondary',
    'card-success',
    'card-danger',
    'card-warning',
    'card-info',
    'card-light',
    'card-dark'
  ];

  public static $backgrounds = [
    'bg-primary',
    'bg-secondary',
    'bg-success',
    'bg-info',
    'bg-warning',
    'bg-danger',
    'bg-light',
    'bg-dark',
    'bg-white',
    'bg-gradient-primary',
    'bg-gradient-secondary',
    'bg-gradient-success',
    'bg-gradient-info',
    'bg-gradient-warning',
    'bg-gradient-danger',
  ];

  public static $backgroundsNoWhite = [
    'bg-primary',
    'bg-secondary',
    'bg-success',
    'bg-info',
    'bg-warning',
    'bg-danger',
    'bg-light',
    'bg-dark',
  //  'bg-white',
    'bg-gradient-primary',
    'bg-gradient-secondary',
    'bg-gradient-success',
    'bg-gradient-info',
    'bg-gradient-warning',
    'bg-gradient-danger',
  ];

  public static $textSegunBackground = [
    'bg-primary' => 'text-light',
    'bg-secondary' => 'text-dark',
    'bg-success' => 'text-dark',
    'bg-info' => 'text-dark',
    'bg-warning' => 'text-dark',
    'bg-danger' => 'text-light',
    'bg-light' => 'text-dark',
    'bg-dark' => 'text-light',
    'bg-white' => 'text-dark',
    'bg-gradient-primary' => 'text-light',
    'bg-gradient-secondary' => 'text-light',
    'bg-gradient-success' => 'text-dark',
    'bg-gradient-info' => 'text-dark',
    'bg-gradient-warning' => 'text-dark',
    'bg-gradient-danger' => 'text-light',
    ];

  public static $clasesBtn = [
    "btn-default",
    "btn-primary",
    "btn-success",
    "btn-info",
    "btn-warning",
    "btn-danger"
  ];

  public static $imageMap = [
    'pdf' => 'PDF_file_icon.svg.png',
    'doc' => 'DOC_file_icon.svg.png',
    'docx' => 'DOC_file_icon.svg.png',
    'rtf' => 'DOC_file_icon.svg.png',
    'odf' => 'DOC_file_icon.svg.png',
    'xls' => 'XLS_file_icon.svg.png',
    'xlsx' => 'XLS_file_icon.svg.png',
  ];

}
