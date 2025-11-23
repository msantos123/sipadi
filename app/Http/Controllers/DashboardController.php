<?php

namespace App\Http\Controllers;

use App\Models\Estancia;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // Obtener todas las estancias con personas
        $estancias = Estancia::with('persona.nacionalidad')->get();
        $personasUnicas = $estancias->unique('persona_id')->pluck('persona');

        // 1. Distribución por Rangos de Edad
        $distribucionEdad = [
            '18-25' => 0,
            '26-35' => 0,
            '36-45' => 0,
            '46-60' => 0,
            '60+' => 0,
        ];

        foreach ($personasUnicas as $persona) {
            if ($persona && $persona->fecha_nacimiento) {
                $edad = Carbon::parse($persona->fecha_nacimiento)->age;
                if ($edad >= 18 && $edad <= 25) {
                    $distribucionEdad['18-25']++;
                } elseif ($edad >= 26 && $edad <= 35) {
                    $distribucionEdad['26-35']++;
                } elseif ($edad >= 36 && $edad <= 45) {
                    $distribucionEdad['36-45']++;
                } elseif ($edad >= 46 && $edad <= 60) {
                    $distribucionEdad['46-60']++;
                } elseif ($edad > 60) {
                    $distribucionEdad['60+']++;
                }
            }
        }

        // 2. Distribución por Sexo
        $distribucionSexo = [
            'Masculino' => 0,
            'Femenino' => 0,
            'Otro' => 0,
        ];

        foreach ($personasUnicas as $persona) {
            if ($persona && $persona->sexo) {
                if (strtoupper($persona->sexo) === 'M' || strtoupper($persona->sexo) === 'MASCULINO') {
                    $distribucionSexo['Masculino']++;
                } elseif (strtoupper($persona->sexo) === 'F' || strtoupper($persona->sexo) === 'FEMENINO') {
                    $distribucionSexo['Femenino']++;
                } else {
                    $distribucionSexo['Otro']++;
                }
            }
        }

        // 3. Top 20 Procedencias (Países)
        $procedencias = $personasUnicas
            ->filter(function($persona) {
                return $persona && $persona->nacionalidad;
            })
            ->groupBy(function($persona) {
                return $persona->nacionalidad->pais ?? 'Desconocido';
            })
            ->map->count()
            ->sortDesc()
            ->take(20)
            ->toArray();

        // 4. Coordenadas de países para mapa mundial
        $coordenadasPaises = [
            'Bolivia' => [-16.5, -68.15],
            'Argentina' => [-38.4161, -63.6167],
            'Brasil' => [-14.235, -51.9253],
            'Chile' => [-35.6751, -71.543],
            'Perú' => [-9.19, -75.0152],
            'Colombia' => [4.5709, -74.2973],
            'Ecuador' => [-1.8312, -78.1834],
            'Venezuela' => [6.4238, -66.5897],
            'Paraguay' => [-23.4425, -58.4438],
            'Uruguay' => [-32.5228, -55.7658],
            'México' => [23.6345, -102.5528],
            'Estados Unidos' => [37.0902, -95.7129],
            'Canadá' => [56.1304, -106.3468],
            'España' => [40.4637, -3.7492],
            'Francia' => [46.2276, 2.2137],
            'Alemania' => [51.1657, 10.4515],
            'Italia' => [41.8719, 12.5674],
            'Reino Unido' => [55.3781, -3.436],
            'China' => [35.8617, 104.1954],
            'Japón' => [36.2048, 138.2529],
            'Corea del Sur' => [35.9078, 127.7669],
            'India' => [20.5937, 78.9629],
            'Rusia' => [61.524, 105.3188],
            'Australia' => [-25.2744, 133.7751],
            'Sudáfrica' => [-30.5595, 22.9375],
            'Egipto' => [26.8206, 30.8025],
            'Marruecos' => [31.7917, -7.0926],
            'Tayikistán' => [38.861, 71.2761],
            'El Salvador' => [13.7942, -88.8965],
            'Togo' => [8.6195, 0.8248],
        ];

        $puntosMapa = [];
        foreach ($personasUnicas as $persona) {
            if ($persona && $persona->nacionalidad && $persona->nacionalidad->pais) {
                $pais = $persona->nacionalidad->pais;
                if (isset($coordenadasPaises[$pais])) {
                    if (!isset($puntosMapa[$pais])) {
                        $puntosMapa[$pais] = [
                            'pais' => $pais,
                            'lat' => $coordenadasPaises[$pais][0],
                            'lng' => $coordenadasPaises[$pais][1],
                            'count' => 0,
                        ];
                    }
                    $puntosMapa[$pais]['count']++;
                }
            }
        }

        // 5. Llegadas por Mes (últimos 12 meses)
        $llegadasPorMes = [];
        for ($i = 11; $i >= 0; $i--) {
            $mes = Carbon::now()->subMonths($i);
            $mesKey = $mes->format('M Y');
            $count = Estancia::whereYear('fecha_hora_ingreso', $mes->year)
                ->whereMonth('fecha_hora_ingreso', $mes->month)
                ->distinct('persona_id')
                ->count('persona_id');
            $llegadasPorMes[$mesKey] = $count;
        }

        // 6. Top 10 Establecimientos con más estancias
        $topEstablecimientos = Estancia::with('tipoCuarto.establecimiento')
            ->get()
            ->filter(function($estancia) {
                return $estancia->tipoCuarto && $estancia->tipoCuarto->establecimiento;
            })
            ->groupBy(function($estancia) {
                return $estancia->tipoCuarto->establecimiento->nombre_establecimiento ?? 'Desconocido';
            })
            ->map->count()
            ->sortDesc()
            ->take(10)
            ->toArray();

        return Inertia::render('Dashboard', [
            'estadisticas' => [
                'distribucionEdad' => $distribucionEdad,
                'distribucionSexo' => $distribucionSexo,
                'topProcedencias' => $procedencias,
                'puntosMapa' => array_values($puntosMapa),
                'llegadasPorMes' => $llegadasPorMes,
                'topEstablecimientos' => $topEstablecimientos,
                'totalPersonas' => $personasUnicas->count(),
                'totalEstancias' => $estancias->count(),
            ],
        ]);
    }
}
