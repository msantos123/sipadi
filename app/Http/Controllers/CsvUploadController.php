<?php

namespace App\Http\Controllers;

use App\Models\Departamento;
use App\Models\Municipio;
use App\Models\Nacionalidad;
use App\Models\TipoCuarto;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CsvUploadController extends Controller
{
    public function create()
    {
        // Mensaje de ayuda para el formato del CSV
        $csvFormatMessage = "El archivo CSV debe tener las siguientes columnas separadas por punto y coma (;): fecha_ingreso, fecha_salida, apellido_paterno, apellido_materno, nombres, tipo_documento, identificacion, complemento, fecha_nacimiento, sexo, estado_civil, ocupacion, codigo_nacionalidad, departamento_sigla, codigo_municipio, ciudad_origen, nro_cuarto, tipo_cuarto. Las fechas deben estar en formato AAAA-MM-DD.";

        return Inertia::render('CsvUpload/Index', [
            'csvFormatMessage' => $csvFormatMessage
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt,xlsx',
        ]);

        $user = auth()->user();
        $sucursalId = $user->sucursal_id;
        $establecimientoId = $user->establecimiento_id;
        $departamentoId = $user->departamento_id;

        if (!$establecimientoId || !$departamentoId) {
            return Inertia::render('CsvUpload/Index', [
                'uploadResult' => [
                    'success' => false,
                    'message' => 'El usuario no tiene un establecimiento o departamento asignado.',
                    'errors' => [],
                ]
            ]);
        }

        $path = $request->file('csv_file')->getRealPath();

        try {
            $reader = \League\Csv\Reader::createFromPath($path, 'r');
            $reader->setHeaderOffset(0);
            $reader->setDelimiter(';');

            $records = $reader->getRecords();
            $errors = [];
            $processedRows = 0;
            $processedData = [];
            $existingPersons = [];

            DB::transaction(function () use ($records, $user, $establecimientoId, $sucursalId, $departamentoId, &$errors, &$processedRows, &$processedData, &$existingPersons) {
                $lote = \App\Models\Lote::firstOrCreate(
                    [
                        'establecimiento_id' => $establecimientoId,
                        'sucursal_id' => $sucursalId,
                        'fecha_lote' => now()->toDateString(),
                        'estado_lote' => 'COMPLETADO',
                    ],
                    [
                        'departamento_id' => $departamentoId,
                        'usuario_registra_id' => $user->id,
                    ]
                );

                foreach ($records as $key => $record) {
                    $rowNumber = $key + 2; // Se suma 2 para compensar el encabezado y el índice base 0
                    $validator = \Illuminate\Support\Facades\Validator::make($record, [
                        'fecha_ingreso' => 'required|date_format:Y-m-d H:i:s,Y-m-d',
                        'fecha_salida' => 'required|date_format:Y-m-d H:i:s,Y-m-d|after_or_equal:fecha_ingreso',
                        'apellido_paterno' => 'required|string|max:100',
                        'apellido_materno' => 'nullable|string|max:100',
                        'nombres' => 'required|string|max:100',
                        'tipo_documento' => 'required|string|in:CI,PASAPORTE',
                        'identificacion' => 'required|string|max:50', // nro_documento
                        'complemento' => 'nullable|string|max:10',
                        'fecha_nacimiento' => 'required|date_format:Y-m-d,d/m/Y',
                        'sexo' => 'required|string|in:M,F,O',
                        'estado_civil' => 'required|string|max:50',
                        'ocupacion' => 'nullable|string|max:100',
                        'codigo_nacionalidad' => 'required|string|exists:nacionalidades,codigo_nacionalidad',
                        // Campos condicionales para bolivianos
                        'departamento_sigla' => 'nullable|required_if:codigo_nacionalidad,BOL|exists:departamentos,sigla',
                        'codigo_municipio' => 'nullable|required_if:codigo_nacionalidad,BOL|exists:municipios,codigo_municipio',
                        // Campo condicional para extranjeros
                        'ciudad_origen' => 'nullable|required_unless:codigo_nacionalidad,BOL|string|max:100',
                        'nro_cuarto' => 'required|string|max:50',
                        'tipo_cuarto' => 'required|string|exists:tipo_cuartos,nombre',
                    ]);

                    if ($validator->fails()) {
                        foreach ($validator->errors()->all() as $error) {
                            $errors[] = ['row' => $rowNumber, 'message' => $error];
                        }
                        continue;
                    }

                    $validatedData = $validator->validated();

                    // Buscar persona existente por nro_documento y complemento
                    $persona = \App\Models\Persona::where('nro_documento', $validatedData['identificacion'])
                        ->where('complemento', $validatedData['complemento'] ?? null)
                        ->first();

                    $nacionalidad = Nacionalidad::where('codigo_nacionalidad', $validatedData['codigo_nacionalidad'])->firstOrFail();

                    $personaData = [
                        'nombres' => $validatedData['nombres'],
                        'apellido_paterno' => $validatedData['apellido_paterno'],
                        'apellido_materno' => $validatedData['apellido_materno'],
                        'tipo_documento' => $validatedData['tipo_documento'],
                        'fecha_nacimiento' => \Carbon\Carbon::parse($validatedData['fecha_nacimiento'])->format('Y-m-d'),
                        'sexo' => $validatedData['sexo'],
                        'estado_civil' => $validatedData['estado_civil'],
                        'ocupacion' => $validatedData['ocupacion'],
                        'nacionalidad_id' => $nacionalidad->id,
                    ];

                    if ($nacionalidad->codigo_nacionalidad === 'BOL') {
                        $departamento = Departamento::where('sigla', $validatedData['departamento_sigla'])->first();
                        $municipio = Municipio::where('codigo_municipio', $validatedData['codigo_municipio'])->first();
                        $personaData['departamento_id'] = $departamento ? $departamento->id : null;
                        $personaData['municipio_id'] = $municipio ? $municipio->id : null;
                        $personaData['ciudad_origen'] = null;
                    } else {
                        $personaData['departamento_id'] = null;
                        $personaData['municipio_id'] = null;
                        $personaData['ciudad_origen'] = $validatedData['ciudad_origen'];
                    }

                    if ($persona) {
                        // La persona ya existe, no la creamos. Opcional: podrías actualizarla.
                        // $persona->update($personaData);
                        $existingPersons[] = ['row' => $rowNumber, 'message' => "La persona con identificación {$validatedData['identificacion']} ya existe. Se usó el registro existente."];
                    } else {
                        // La persona no existe, la creamos.
                        $persona = \App\Models\Persona::create(array_merge(
                            ['nro_documento' => $validatedData['identificacion'], 'complemento' => $validatedData['complemento']],
                            $personaData
                        ));
                    }

                    $tipoCuarto = TipoCuarto::where('nombre', $validatedData['tipo_cuarto'])->first();

                    $reserva = \App\Models\Reserva::create([
                        'usuario_registra_id' => $user->id,
                        'fecha_entrada' => \Carbon\Carbon::parse($validatedData['fecha_ingreso'])->format('Y-m-d'),
                        'fecha_salida' => \Carbon\Carbon::parse($validatedData['fecha_salida'])->format('Y-m-d'),
                        'establecimiento_id' => $establecimientoId,
                        'sucursal_id' => $sucursalId,
                    ]);

                    \App\Models\Estancia::create([
                        'lote_id' => $lote->id,
                        'reserva_id' => $reserva->id,
                        'persona_id' => $persona->id,
                        'nro_cuarto' => $validatedData['nro_cuarto'],
                        'tipo_cuarto_id' => $tipoCuarto->id,
                        'fecha_hora_ingreso' => \Carbon\Carbon::parse($validatedData['fecha_ingreso']),
                        'fecha_hora_salida_efectiva' => \Carbon\Carbon::parse($validatedData['fecha_salida']),
                        'estado_estancia' => 'FINALIZADA',
                        'es_titular' => true,
                    ]);

                    $processedRows++;
                    $processedData[] = $record;
                }
            });

            $csvFormatMessage = "El archivo CSV debe tener las siguientes columnas separadas por punto y coma (;): fecha_ingreso, fecha_salida, apellido_paterno, apellido_materno, nombres, tipo_documento, identificacion, complemento, fecha_nacimiento, sexo, estado_civil, ocupacion, codigo_nacionalidad, departamento_sigla, codigo_municipio, ciudad_origen, nro_cuarto, tipo_cuarto. Las fechas deben estar en formato AAAA-MM-DD.";

            return Inertia::render('CsvUpload/Index', [
                'uploadResult' => [
                    'success' => count($errors) === 0,
                    'message' => 'Archivo procesado. ' . (count($errors) > 0 ? 'Se encontraron errores.' : 'Todos los registros se importaron con éxito.'),
                    'processed_rows' => $processedRows,
                    'errors' => $errors,
                    'existing_persons' => $existingPersons, // Enviamos las personas que ya existían
                    'data' => $processedData,
                ]
            ]);

        } catch (\Exception $e) {
            return Inertia::render('CsvUpload/Index', [
                'uploadResult' => [
                    'success' => false,
                    'message' => 'Error al procesar el archivo: ' . $e->getMessage(),
                    'errors' => [],
                    'data' => [],
                ]
            ]);
        }
    }
}
