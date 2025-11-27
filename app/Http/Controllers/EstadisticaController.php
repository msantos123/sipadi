<?php

namespace App\Http\Controllers;

use App\Models\Establecimiento;
use App\Models\Estancia;
use App\Models\Nacionalidad;
use App\Models\TipoCuarto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Carbon\Carbon;

class EstadisticaController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $userRoles = $user->roles->pluck('id')->toArray();
        
        // Determinar alcance según rol
        // Admin (1) y Nacional (2) - Acceso total
        if (in_array(1, $userRoles) || in_array(2, $userRoles)) {
            $nacionalidades = Nacionalidad::orderBy('gentilicio')->get();
            $departamentos = \App\Models\Departamento::all();
            $establecimientos = Establecimiento::where('id_prestador', 5)->orderBy('razon_social')->get();
            $sucursales = \App\Models\Sucursal::all();
            $alcance = 'nacional';
            
        // Departamental (4) - Solo su departamento
        } elseif (in_array(4, $userRoles)) {
            $nacionalidades = Nacionalidad::orderBy('gentilicio')->get();
            $departamentos = \App\Models\Departamento::where('id', $user->departamento_id)->get();
            $establecimientos = Establecimiento::where('id_departamento', $user->departamento_id)->where('id_prestador', 5)->orderBy('razon_social')->get();
            $sucursales = \App\Models\Sucursal::where('id_departamento', $user->departamento_id)->get();
            $alcance = 'departamental';
            
        // Prestador (6) - Solo su establecimiento y sucursales
        } elseif (in_array(6, $userRoles)) {
            $nacionalidades = Nacionalidad::orderBy('gentilicio')->get();
            $departamentos = collect(); // Vacío
            $establecimientos = Establecimiento::where('id_establecimiento', $user->establecimiento_id)->get();
            $sucursales = \App\Models\Sucursal::where('id_casa_matriz', $user->establecimiento_id)->get();
            $alcance = 'establecimiento';
            
        } else {
            // Sin acceso
            abort(403, 'No tienes permiso para acceder a estadísticas');
        }

        return Inertia::render('Estadisticas/Index', [
            'nacionalidades' => $nacionalidades,
            'departamentos' => $departamentos,
            'establecimientos' => $establecimientos,
            'sucursales' => $sucursales,
            'alcance' => $alcance,
            'estadisticas' => null,
            'filters' => [],
        ]);
    }

    public function generar(Request $request)
    {
        Log::info('--- Inicio de generación de estadísticas ---');
        Log::info('Filtros recibidos:', $request->all());

        $user = auth()->user();
        $userRoles = $user->roles->pluck('id')->toArray();

        $request->validate([
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'departamento_ids' => 'nullable|array',
            'departamento_ids.*' => 'exists:departamentos,id',
            'establecimiento_ids' => 'nullable|array',
            'establecimiento_ids.*' => 'exists:mysql_sidetur.establecimiento,id_establecimiento',
            'sucursal_ids' => 'nullable|array',
            'sucursal_ids.*' => 'exists:mysql_sidetur.sucursal,id_sucursal',
        ]);

        // Aplicar restricciones según rol ANTES de filtros del usuario
        // Departamental (4) - Forzar solo su departamento
        if (in_array(4, $userRoles)) {
            $request->merge(['departamento_ids' => [$user->departamento_id]]);
        }
        
        // Prestador (6) - Forzar solo su establecimiento
        if (in_array(6, $userRoles)) {
            $request->merge(['establecimiento_ids' => [$user->establecimiento_id]]);
        }

        $fechaInicio = $request->fecha_inicio ? Carbon::parse($request->fecha_inicio)->startOfDay() : Carbon::now()->startOfDay();
        $fechaFin = $request->fecha_fin ? Carbon::parse($request->fecha_fin)->endOfDay() : Carbon::now()->endOfDay();

        // Base query
        $estanciasQuery = Estancia::with('persona', 'tipoCuarto')
            ->whereBetween('fecha_hora_ingreso', [$fechaInicio, $fechaFin]);

        // Filtrar por múltiples departamentos (solo si hay departamentos seleccionados)
        if ($request->filled('departamento_ids') && is_array($request->departamento_ids) && count($request->departamento_ids) > 0) {
            
            $departamentoIds = $request->departamento_ids;

            $establecimientoIds = Establecimiento::whereIn('id_departamento', $departamentoIds)->pluck('id_establecimiento');
            $sucursalIds = \App\Models\Sucursal::whereIn('id_departamento', $departamentoIds)->pluck('id_sucursal');

            if ($establecimientoIds->isNotEmpty() || $sucursalIds->isNotEmpty()) {
                $estanciasQuery->whereHas('tipoCuarto', function ($q) use ($establecimientoIds, $sucursalIds) {
                    $q->where(function ($subQuery) use ($establecimientoIds, $sucursalIds) {
                        if ($establecimientoIds->isNotEmpty()) {
                            $subQuery->whereIn('establecimiento_id', $establecimientoIds);
                        }
                        if ($sucursalIds->isNotEmpty()) {
                            $subQuery->orWhereIn('sucursal_id', $sucursalIds);
                        }
                    });
                });
            }
        }

        // Filtrar por múltiples establecimientos (solo si hay establecimientos seleccionados)
        if ($request->filled('establecimiento_ids') && is_array($request->establecimiento_ids) && count($request->establecimiento_ids) > 0) {
            $establecimientoIds = $request->establecimiento_ids;
                
            $estanciasQuery->whereHas('tipoCuarto', function ($q) use ($establecimientoIds, $request) {
                $q->whereIn('establecimiento_id', $establecimientoIds);
                
                // Si también hay sucursales, filtrar por ellas
                if ($request->filled('sucursal_ids') && is_array($request->sucursal_ids) && count($request->sucursal_ids) > 0) {
                    $sucursalIds = $request->sucursal_ids;
                    $q->whereIn('sucursal_id', $sucursalIds);
                }
            });
        } elseif ($request->filled('sucursal_ids') && is_array($request->sucursal_ids) && count($request->sucursal_ids) > 0) {
            // Solo filtrar por sucursales si no hay establecimientos
            $sucursalIds = $request->sucursal_ids;
                
            $estanciasQuery->whereHas('tipoCuarto', function ($q) use ($sucursalIds) {
                $q->whereIn('sucursal_id', $sucursalIds);
            });
        }

        $estancias = $estanciasQuery->get();
        
        // 1. Total de Llegadas
        $totalLlegadas = $estancias->unique('persona_id')->count();

        // Llegadas por nacionalidad (usando las estancias ya filtradas)
        $llegadasPorNacionalidad = $estancias
            ->unique('persona_id')
            ->groupBy(function($estancia) {
                return $estancia->persona?->nacionalidad?->gentilicio ?? 'Desconocida';
            })
            ->map->count();


        // 2. Total Pernoctaciones
        $totalPernoctaciones = $estancias->sum(function ($estancia) {
            if ($estancia->fecha_hora_salida_efectiva) {
                $ingreso = Carbon::parse($estancia->fecha_hora_ingreso);
                $salida = Carbon::parse($estancia->fecha_hora_salida_efectiva);
                $noches = $ingreso->startOfDay()->diffInDays($salida->startOfDay());
                return $noches > 0 ? $noches : 0;
            }
            return 0;
        });

        // 3. Estadia Media
        $estadiaMedia = $totalLlegadas > 0 ? $totalPernoctaciones / $totalLlegadas : 0;

        // 4. Tasa de Ocupacion
        $habitacionesDisponiblesQuery = TipoCuarto::query();
        
        if ($request->filled('establecimiento_ids')) {
            $establecimientoIds = is_array($request->establecimiento_ids) 
                ? $request->establecimiento_ids 
                : [$request->establecimiento_ids];
            $habitacionesDisponiblesQuery->whereIn('establecimiento_id', $establecimientoIds);
        }
        
        if ($request->filled('sucursal_ids')) {
            $sucursalIds = is_array($request->sucursal_ids) 
                ? $request->sucursal_ids 
                : [$request->sucursal_ids];
            $habitacionesDisponiblesQuery->whereIn('sucursal_id', $sucursalIds);
        }
        
        $totalHabitacionesDisponibles = $habitacionesDisponiblesQuery->sum('nro_habitaciones');

        $habitacionesOcupadas = $estancias->pluck('nro_cuarto')->unique()->count();

        $tasaOcupacion = $totalHabitacionesDisponibles > 0 
            ? ($habitacionesOcupadas / $totalHabitacionesDisponibles) * 100 
            : 0;

        // 5. Distribución por Rangos de Edad
        $personasUnicas = $estancias->unique('persona_id')->pluck('persona');
        
        $distribucionEdad = [
            '18-25' => 0,
            '26-35' => 0,
            '36-45' => 0,
            '46-60' => 0,
            '60+' => 0,
        ];

        foreach ($personasUnicas as $persona) {
            if ($persona && $persona->fecha_nacimiento) {
                $edad = \Carbon\Carbon::parse($persona->fecha_nacimiento)->age;
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

        // 6. Distribución por Sexo
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

        // 7. Top 6 Procedencias (Países)
        $procedencias = $personasUnicas
            ->filter(function($persona) {
                return $persona && $persona->nacionalidad;
            })
            ->groupBy(function($persona) {
                return $persona->nacionalidad->pais ?? 'Desconocido';
            })
            ->map->count()
            ->sortDesc()
            ->take(6);

        // 8. Coordenadas de países para mapa mundial
        $coordenadasPaises = [
            'Afganistán' => [33.9391, 67.7100],
            'Albania' => [41.1533, 20.1683],
            'Alemania' => [51.1657, 10.4515],
            'Andorra' => [42.5063, 1.5218],
            'Angola' => [-11.2027, 17.8739],
            'AntiguayBarbuda' => [17.0608, -61.7964],
            'ArabiaSaudita' => [23.8859, 45.0792],
            'Argelia' => [28.0339, 1.6596],
            'Argentina' => [-38.4161, -63.6167],
            'Armenia' => [40.0691, 45.0382],
            'Aruba' => [12.5211, -69.9683],
            'Australia' => [-25.2744, 133.7751],
            'Austria' => [47.5162, 14.5501],
            'Azerbaiyán' => [40.1431, 47.5769],
            'Bahamas' => [25.0343, -77.3963],
            'Bangladés' => [23.6850, 90.3563],
            'Barbados' => [13.1939, -59.5432],
            'Baréin' => [26.0667, 50.5577],
            'Bélgica' => [50.5039, 4.4699],
            'Belice' => [17.1899, -88.4976],
            'Benín' => [9.3077, 2.3158],
            'Bielorrusia' => [53.7098, 27.9534],
            'Birmania' => [21.9162, 95.9560],
            'Bolivia' => [-16.5000, -68.1500],
            'BosniayHerzegovina' => [43.9159, 17.6791],
            'Botsuana' => [-22.3285, 24.6849],
            'Brasil' => [-14.2350, -51.9253],
            'Brunéi' => [4.5353, 114.7277],
            'Bulgaria' => [42.7339, 25.4858],
            'BurkinaFaso' => [12.2383, -1.5616],
            'Burundi' => [-3.3731, 29.9189],
            'Bután' => [27.5142, 90.4336],
            'CaboVerde' => [16.5388, -23.0418],
            'Camboya' => [12.5657, 104.9910],
            'Camerún' => [7.3697, 12.3547],
            'Canadá' => [56.1304, -106.3468],
            'Catar' => [25.3548, 51.1839],
            'Chad' => [15.4542, 18.7322],
            'Chile' => [-35.6751, -71.5430],
            'China' => [35.8617, 104.1954],
            'Chipre' => [35.1264, 33.4299],
            'CiudaddelVaticano' => [41.9029, 12.4534],
            'Colombia' => [4.5709, -74.2973],
            'Comoras' => [-11.6455, 43.3333],
            'CoreadelNorte' => [40.3399, 127.5101],
            'CoreadelSur' => [35.9078, 127.7669],
            'CostadeMarfil' => [7.5400, -5.5471],
            'CostaRica' => [9.7489, -83.7534],
            'Croacia' => [45.1000, 15.2000],
            'Cuba' => [21.5218, -77.7812],
            'Dinamarca' => [56.2639, 9.5018],
            'Dominica' => [15.4150, -61.3710],
            'Ecuador' => [-1.8312, -78.1834],
            'Egipto' => [26.8206, 30.8025],
            'ElSalvador' => [13.7942, -88.8965],
            'EmiratosÁrabesUnidos' => [23.4241, 53.8478],
            'Eritrea' => [15.1794, 39.7823],
            'Eslovaquia' => [48.6690, 19.6990],
            'Eslovenia' => [46.1512, 14.9955],
            'España' => [40.4637, -3.7492],
            'EstadosUnidos' => [37.0902, -95.7129],
            'Estados Unidos' => [37.0902, -95.7129],
            'Estonia' => [58.5953, 25.0136],
            'Etiopía' => [9.1450, 40.4897],
            'Filipinas' => [12.8797, 121.7740],
            'Finlandia' => [61.9241, 25.7482],
            'Fiyi' => [-17.7134, 178.0650],
            'Francia' => [46.2276, 2.2137],
            'Gabón' => [-0.8037, 11.6094],
            'Gambia' => [13.4432, -15.3101],
            'Georgia' => [42.3154, 43.3569],
            'Gibraltar' => [36.1408, -5.3536],
            'Ghana' => [7.9465, -1.0232],
            'Granada' => [12.1165, -61.6790],
            'Grecia' => [39.0742, 21.8243],
            'Groenlandia' => [71.7069, -42.6043],
            'Guatemala' => [15.7835, -90.2308],
            'Guineaecuatorial' => [1.6508, 10.2679],
            'Guinea' => [9.9456, -9.6966],
            'Guinea-Bisáu' => [11.8037, -15.1804],
            'Guyana' => [4.8604, -58.9302],
            'Haití' => [18.9712, -72.2852],
            'Honduras' => [15.2000, -86.2419],
            'Hungría' => [47.1625, 19.5033],
            'India' => [20.5937, 78.9629],
            'Indonesia' => [-0.7893, 113.9213],
            'Irak' => [33.2232, 43.6793],
            'Irán' => [32.4279, 53.6880],
            'Irlanda' => [53.4129, -8.2439],
            'Islandia' => [64.9631, -19.0208],
            'IslasCook' => [-21.2367, -159.7777],
            'IslasMarshall' => [7.1315, 171.1845],
            'IslasSalomón' => [-9.6457, 160.1562],
            'Israel' => [31.0461, 34.8516],
            'Italia' => [41.8719, 12.5674],
            'Jamaica' => [18.1096, -77.2975],
            'Japón' => [36.2048, 138.2529],
            'Jordania' => [30.5852, 36.2384],
            'Kazajistán' => [48.0196, 66.9237],
            'Kenia' => [-0.0236, 37.9062],
            'Kirguistán' => [41.2044, 74.7661],
            'Kiribati' => [-3.3704, -168.7340],
            'Kuwait' => [29.3117, 47.4818],
            'Laos' => [19.8563, 102.4955],
            'Lesoto' => [-29.6100, 28.2336],
            'Letonia' => [56.8796, 24.6032],
            'Líbano' => [33.8547, 35.8623],
            'Liberia' => [6.4281, -9.4295],
            'Libia' => [26.3351, 17.2283],
            'Liechtenstein' => [47.1660, 9.5554],
            'Lituania' => [55.1694, 23.8813],
            'Luxemburgo' => [49.8153, 6.1296],
            'Madagascar' => [-18.7669, 46.8691],
            'Malasia' => [4.2105, 101.9758],
            'Malaui' => [-13.2543, 34.3015],
            'Maldivas' => [3.2028, 73.2207],
            'Malí' => [17.5707, -3.9962],
            'Malta' => [35.9375, 14.3754],
            'Marruecos' => [31.7917, -7.0926],
            'Martinica' => [14.6415, -61.0242],
            'Mauricio' => [-20.3484, 57.5522],
            'Mauritania' => [21.0079, -10.9408],
            'México' => [23.6345, -102.5528],
            'Micronesia' => [7.4256, 150.5508],
            'Moldavia' => [47.4116, 28.3699],
            'Mónaco' => [43.7384, 7.4246],
            'Mongolia' => [46.8625, 103.8467],
            'Montenegro' => [42.7087, 19.3744],
            'Mozambique' => [-18.6657, 35.5296],
            'Namibia' => [-22.9576, 18.4904],
            'Nauru' => [-0.5228, 166.9315],
            'Nepal' => [28.3949, 84.1240],
            'Nicaragua' => [12.8654, -85.2072],
            'Níger' => [17.6078, 8.0817],
            'Nigeria' => [9.0820, 8.6753],
            'Noruega' => [60.4720, 8.4689],
            'NuevaZelanda' => [-40.9006, 174.8860],
            'Omán' => [21.4735, 55.9754],
            'PaísesBajos' => [52.1326, 5.2913],
            'Pakistán' => [30.3753, 69.3451],
            'Palaos' => [7.5150, 134.5825],
            'Palestina' => [31.9522, 35.2332],
            'Panamá' => [8.5380, -80.7821],
            'PapúaNuevaGuinea' => [-6.3150, 143.9555],
            'Paraguay' => [-23.4425, -58.4438],
            'Perú' => [-9.1900, -75.0152],
            'Polonia' => [51.9194, 19.1451],
            'Portugal' => [39.3999, -8.2245],
            'PuertoRico' => [18.2208, -66.5901],
            'ReinoUnido' => [55.3781, -3.4360],
            'Reino Unido' => [55.3781, -3.4360],
            'RepúblicaCentroafricana' => [6.6111, 20.9394],
            'RepúblicaCheca' => [49.8175, 15.4730],
            'RepúblicadeMacedonia' => [41.6086, 21.7453],
            'RepúblicadelCongo' => [-0.2280, 15.8277],
            'RepúblicaDemocráticadelCongo' => [-4.0383, 21.7587],
            'RepúblicaDominicana' => [18.7357, -70.1627],
            'RepúblicaSudafricana' => [-30.5595, 22.9375],
            'Ruanda' => [-1.9403, 29.8739],
            'Rumanía' => [45.9432, 24.9668],
            'Rusia' => [61.5240, 105.3188],
            'Samoa' => [-13.7590, -172.1046],
            'SanCristóbalyNieves' => [17.3578, -62.7830],
            'SanMarino' => [43.9424, 12.4578],
            'SanVicenteylasGranadinas' => [12.9843, -61.2872],
            'SantaLucía' => [13.9094, -60.9789],
            'SantoToméyPríncipe' => [0.1864, 6.6131],
            'Senegal' => [14.4974, -14.4524],
            'Serbia' => [44.0165, 21.0059],
            'Seychelles' => [-4.6796, 55.4920],
            'SierraLeona' => [8.4606, -11.7799],
            'Singapur' => [1.3521, 103.8198],
            'Siria' => [34.8021, 38.9968],
            'Somalia' => [5.1521, 46.1996],
            'SriLanka' => [7.8731, 80.7718],
            'Suazilandia' => [-26.5225, 31.4659],
            'SudándelSur' => [6.8770, 31.3070],
            'Sudán' => [12.8628, 30.2176],
            'Suecia' => [60.1282, 18.6435],
            'Suiza' => [46.8182, 8.2275],
            'Surinam' => [3.9193, -56.0278],
            'Tailandia' => [15.8700, 100.9925],
            'Tanzania' => [-6.3690, 34.8888],
            'Tayikistán' => [38.8610, 71.2761],
            'TimorOriental' => [-8.8742, 125.7275],
            'Togo' => [8.6195, 0.8248],
            'Tonga' => [-21.1789, -175.1982],
            'TrinidadyTobago' => [10.6918, -61.2225],
            'Túnez' => [33.8869, 9.5375],
            'Turkmenistán' => [38.9697, 59.5563],
            'Turquía' => [38.9637, 35.2433],
            'Tuvalu' => [-7.1095, 177.6493],
            'Ucrania' => [48.3794, 31.1656],
            'Uganda' => [1.3733, 32.2903],
            'Uruguay' => [-32.5228, -55.7658],
            'Uzbekistán' => [41.3775, 64.5853],
            'Vanuatu' => [-15.3767, 166.9592],
            'Venezuela' => [6.4238, -66.5897],
            'Vietnam' => [14.0583, 108.2772],
            'Yemen' => [15.5527, 48.5164],
            'Yibuti' => [11.8251, 42.5903],
            'Zambia' => [-13.1339, 27.8493],
            'Zimbabue' => [-19.0154, 29.1549],
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

        $estadisticas = [
            'totalLlegadas' => $totalLlegadas,
            'llegadasPorNacionalidad' => $llegadasPorNacionalidad->toArray(),
            'totalPernoctaciones' => $totalPernoctaciones,
            'estadiaMedia' => round($estadiaMedia, 2),
            'tasaOcupacion' => round($tasaOcupacion, 2),
            'habitacionesOcupadas' => $habitacionesOcupadas,
            'totalHabitacionesDisponibles' => $totalHabitacionesDisponibles,
            'distribucionEdad' => $distribucionEdad,
            'distribucionSexo' => $distribucionSexo,
            'topProcedencias' => $procedencias->toArray(),
            'puntosMapa' => array_values($puntosMapa),
        ];
        Log::info('Array de estadísticas calculado:', $estadisticas);
        Log::info('--- Fin de generación de estadísticas ---');

        // Recargar datos según alcance del usuario
        if (in_array(1, $userRoles) || in_array(2, $userRoles)) {
            $nacionalidades = Nacionalidad::orderBy('gentilicio')->get();
            $departamentos = \App\Models\Departamento::all();
            $establecimientos = Establecimiento::where('id_prestador', 5)->orderBy('razon_social')->get();
            $sucursales = \App\Models\Sucursal::all();
            $alcance = 'nacional';
        } elseif (in_array(4, $userRoles)) {
            $nacionalidades = Nacionalidad::orderBy('gentilicio')->get();
            $departamentos = \App\Models\Departamento::where('id', $user->departamento_id)->get();
            $establecimientos = Establecimiento::where('id_departamento', $user->departamento_id)->where('id_prestador', 5)->orderBy('razon_social')->get();
            $sucursales = \App\Models\Sucursal::where('id_departamento', $user->departamento_id)->get();
            $alcance = 'departamental';
        } else {
            $nacionalidades = Nacionalidad::orderBy('gentilicio')->get();
            $departamentos = collect();
            $establecimientos = Establecimiento::where('id_establecimiento', $user->establecimiento_id)->get();
            $sucursales = \App\Models\Sucursal::where('id_casa_matriz', $user->establecimiento_id)->get();
            $alcance = 'establecimiento';
        }

        return Inertia::render('Estadisticas/Index', [
            'nacionalidades' => $nacionalidades,
            'departamentos' => $departamentos,
            'establecimientos' => $establecimientos,
            'sucursales' => $sucursales,
            'alcance' => $alcance,
            'estadisticas' => $estadisticas,
            'filters' => $request->only(['fecha_inicio', 'fecha_fin', 'departamento_ids', 'establecimiento_ids', 'sucursal_ids'])
        ]);
    }
}
