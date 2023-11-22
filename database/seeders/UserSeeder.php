<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    //\App\Models\User::factory(10)->create();
    $valores = [
      '28461501|Wenceslao|Canziani Aguilar|Responsable|Desc|2330',
      '30401484|Rosario|Mendez Paz|Responsable|Desc|2331',
      '37401152|Julieta|Gianelli|Responsable|Desc|2336',
      '29540966|Natalia|NAVARRO|Responsable|Desc|2329',
      '28718716|Diego|CRESCIMONE|Responsable|Desc|2328',
      '94049721|Guillaume|PIGNOL|Responsable|Desc|2334',
    ];


    foreach ($valores as $valor) {
      $datos = explode('|', $valor);

      \App\Models\User::create([
        'login' => $datos[0],
        'nombres' => $datos[1],
        'apellidos' => $datos[2],
        'tipo' => $datos[3],
        'desc' => $datos[4],
        'Cod_Responsable' => $datos[5],
        'password' => '$2y$10$Mahfbajk9WwkzG9ga9HA2uQA6Wu/owbHkV94AFj.Yv01gU2MTZWS6', // q
      ]);


//
//       \App\Models\User::factory()->create([
//           'name' => 'Test User',
//           'email' => 'test@example.com',
//       ]);
    }
  }
}
