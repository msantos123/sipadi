<?php

namespace App\Http\Controllers;

use App\Models\DetallesOrdenJudicial;
use App\Models\DetallesOrdenOficial;
use App\Models\DetallesRequerimientoFiscal;
use App\Models\Solicitud;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Rap2hpoutre\FastExcel\FastExcel;

class SolicitudController extends Controller
{
    public function index()
    {
        $solicitudes = Solicitud::with([
            'detallesOrdenJudicial',
            'detallesOrdenOficial',
            'detallesRequerimientoFiscal',
            'usuarioCreador'
        ])->get();

        return Inertia::render('Solicitudes/Index', [
            'solicitudes' => $solicitudes,
        ]);
    }

    public function create()
    {
        return Inertia::render('Solicitudes/Create');
    }

        public function store(Request $request)

        {
            $request->validate([
                'detalleType' => 'required|string|in:orden_judicial,orden_oficial,requerimiento_fiscal',
                'pdfFile' => 'required|file|mimes:pdf',
                'persona_buscada_nombre' => 'required|string|max:255',
                'persona_buscada_identificacion' => 'required|string|max:255',
                'fecha_solicitud' => 'required|date',
            ]);

            try {
                $solicitud = null;
                DB::transaction(function () use ($request, &$solicitud) {
                    $solicitud = new Solicitud();
                    $solicitud->usuario_creador_id = Auth::id();
                    $solicitud->persona_buscada_nombre = $request->input('persona_buscada_nombre');
                    $solicitud->persona_buscada_identificacion = $request->input('persona_buscada_identificacion');
                    $solicitud->fecha_solicitud = $request->input('fecha_solicitud');

                    if ($request->hasFile('pdfFile')) {
                        $path = $request->file('pdfFile')->store('solicitudes_pdf', 'public');
                        $solicitud->documento_adjunto_path = $path;
                    }
                    $persona = \App\Models\Persona::where('nombres', 'like', '%' . $request->input('persona_buscada_nombre') . '%')
                        ->orWhere('nro_documento', $request->input('persona_buscada_identificacion'))
                        ->first();

                    if ($persona) {
                        $estancias = \App\Models\Estancia::with([
                            'reserva.establecimiento.sucursales', // Load up to sucursales
                            'persona.nacionalidad',
                            'lote',
                            'tipoCuarto'
                        ])->where('persona_id', $persona->id)->get();
                        //dd($estancias->toArray());
                        // Manually load Departamento to avoid cross-database relationship issue
                        $departamentoIds = $estancias->pluck('reserva.establecimiento.sucursales.*.id_departamento')->flatten()->unique()->filter();
                        $departamentos = \App\Models\Departamento::whereIn('id', $departamentoIds)->get()->keyBy('id');

                        $estancias->each(function ($estancia) use ($departamentos) {
                            if ($estancia->reserva && $estancia->reserva->establecimiento) {
                                $estancia->reserva->establecimiento->sucursales->each(function ($sucursal) use ($departamentos) {
                                    if ($sucursal->id_departamento && isset($departamentos[$sucursal->id_departamento])) {
                                        $sucursal->setRelation('departamento', $departamentos[$sucursal->id_departamento]);
                                    }
                                });
                            }
                        });

                        $solicitud->resultado_busqueda = $estancias->toJson();
                    }

                    $solicitud->save();

                                    switch ($request->input('detalleType')) {
                                        case 'orden_judicial':
                                            $request->validate([
                                                'nombre_juzgado_tribunal' => 'required|string|max:255',
                                                'numero_orden_judicial' => 'required|string|max:255',
                                            ]);

                                            DetallesOrdenJudicial::create([
                                                'solicitud_id' => $solicitud->id,
                                                'nombre_juzgado_tribunal' => $request->input('nombre_juzgado_tribunal'),
                                                'numero_orden_judicial' => $request->input('numero_orden_judicial'),

                                            ]);
                                            break;

                                        case 'orden_oficial':

                                            $request->validate([

                                                'institucion' => 'required|string|max:255',
                                            ]);

                                            DetallesOrdenOficial::create([
                                                'solicitud_id' => $solicitud->id,
                                                'institucion' => $request->input('institucion'),
                                            ]);
                                            break;

                                        case 'requerimiento_fiscal':
                                            $request->validate([
                                                'fiscal_apellidos_nombres' => 'required|string|max:255',
                                                'fiscal_de_materia' => 'required|string|max:255',
                                                'numero_de_caso' => 'required|string|max:255',
                                                'solicitante_apellidos_nombres' => 'required|string|max:255',
                                                'solicitante_identificacion' => 'required|string|max:255',
                                            ]);

                                            DetallesRequerimientoFiscal::create([
                                                'solicitud_id' => $solicitud->id,
                                                'fiscal_apellidos_nombres' => $request->input('fiscal_apellidos_nombres'),
                                                'fiscal_de_materia' => $request->input('fiscal_de_materia'),
                                                'numero_de_caso' => $request->input('numero_de_caso'),
                                                'solicitante_apellidos_nombres' => $request->input('solicitante_apellidos_nombres'),
                                                'solicitante_identificacion' => $request->input('solicitante_identificacion'),
                                            ]);
                                            break;
                                    }
                });
                if ($solicitud) {
                    return response()->json([
                        'success' => true,
                        'solicitud_id' => $solicitud->id,
                        'has_results' => (bool)$solicitud->resultado_busqueda,
                        'message' => $solicitud->resultado_busqueda ? 'Solicitud creada con éxito. Se encontraron estancias.' : 'Solicitud creada con éxito. No se encontraron estancias.'
                    ]);
                }

                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo crear la solicitud.'
                ], 400);

            } catch (\Exception $e) {
                Log::error('Error al crear la solicitud: ' . $e->getMessage());
                 return response()->json([
                    'success' => false,
                    'message' => 'Error al crear la solicitud: ' . $e->getMessage()
                ], 500);
            }
        }

    public function download(Solicitud $solicitud)
    {
        $estanciasArray = json_decode($solicitud->resultado_busqueda, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('Error al decodificar JSON: ' . json_last_error_msg());
            return redirect()->back()->with('error', 'Error interno al procesar los datos.');
        }

        Log::info('Contenido de resultado_busqueda (decoded): ', $estanciasArray ?? []);

        $list = collect($estanciasArray)->map(function ($estancia) {
            try {
                Log::info('Procesando estancia: ', $estancia);

                $persona = $estancia['persona'] ?? null;
                $reserva = $estancia['reserva'] ?? null;
                $establecimiento = $reserva['establecimiento'] ?? null;
                $sucursal = $establecimiento ? ($establecimiento['sucursales'][0] ?? null) : null;
                $departamento = $sucursal ? ($sucursal['departamento']['nombre'] ?? 'N/A') : 'N/A';
                $nacionalidad = $persona['nacionalidad']['pais'] ?? 'N/A';
                $tipoCuarto = $estancia['tipo_cuarto']['nombre'] ?? 'N/A';


                $mappedData = [
                    'Nombre Completo' => ($persona ? $persona['nombres'] . ' ' . $persona['apellido_paterno'] . ' ' . $persona['apellido_materno'] : 'N/A'),
                    'Documento' => ($persona ? $persona['tipo_documento'] . ': ' . $persona['nro_documento'] : 'N/A'),
                    'Nacionalidad' => $nacionalidad,
                    'Establecimiento' => $establecimiento ? $establecimiento['razon_social'] : 'N/A',
                    'Sucursal' => $sucursal ? $sucursal['nombre_sucursal'] : 'N/A',
                    'Departamento' => $departamento,
                    'Fecha de Entrada' => $reserva['fecha_entrada'] ?? 'N/A',
                    'Fecha de Salida' => $reserva['fecha_salida'] ?? 'N/A',
                    'Nro. Cuarto' => $estancia['nro_cuarto'] ?? 'N/A',
                    'Tipo Cuarto' => $tipoCuarto,
                ];

                Log::info('Estancia mapeada: ', $mappedData);
                return $mappedData;

            } catch (\Exception $e) {
                Log::error('Error al mapear una estancia: ' . $e->getMessage(), ['estancia' => $estancia]);
                return []; // Return empty array for the failed item
            }
        });
        Log::info('Datos finales para Excel: ', $list->toArray());

        try {

            if (headers_sent($file, $line)) {
                Log::error("Headers already sent in {$file} on line {$line}");
                return redirect()->back()->with('error', 'Error interno del servidor (headers sent).');
            }

            $fileName = 'solicitud-' . $solicitud->id . '.xlsx';
            $fullPath = storage_path('app/public/' . $fileName);

            (new FastExcel($list))->export($fullPath);
            Log::info('Archivo Excel generado en: ' . $fullPath);

            if (!file_exists($fullPath)) {
                Log::error('El archivo Excel no se encontró en la ruta esperada: ' . $fullPath);
                return redirect()->back()->with('error', 'No se pudo generar el archivo Excel (file not found).');
            }

            // Clean output buffer before download
            if (ob_get_level()) {
                ob_end_clean();
            }

            return response()->download($fullPath, $fileName)->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            Log::error('Error al generar o descargar el archivo Excel: ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
            return redirect()->back()->with('error', 'No se pudo generar el archivo Excel.');
        }
    }
}
