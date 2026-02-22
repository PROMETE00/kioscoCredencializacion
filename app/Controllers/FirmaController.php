<?php

namespace App\Controllers;

class FirmaController extends BaseController
{
  public function index($turnoId = 0)
  {
    // Aquí luego lo conectas a tu BD real
    $data = [
      'title'      => 'Firma',
      'activeMenu' => 'Firma',
      'userName'   => 'Usuario',

      'turno'  => ['id' => (int)$turnoId, 'turno' => 'A-013'],
      'alumno' => ['id' => 1, 'nombre' => 'SOLANO RAMOS EDUARDO'],

      'firmaUrl'    => null,
      'miniUrl'     => null,
      'estadoTexto' => 'FIRMA EN CAPTURA',
      'estadoType'  => 'warn',

      'enCaptura' => ['nombre' => 'SOLANO RAMOS EDUARDO', 'turno' => 'A-013'],
      'fila'      => [
        ['nombre' => 'PÉREZ LÓPEZ ANA', 'turno' => 'A-014'],
        ['nombre' => 'GARCÍA JUÁREZ LUIS', 'turno' => 'A-015'],
      ],
    ];

    return view('captura/firma', $data);
  }
}
