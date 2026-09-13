<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Equipo;
use App\Models\Mantenimiento;
use App\Models\User;
use App\Models\Contrato;
use App\Models\Cliente;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ExcelImportSeeder extends Seeder
{
    /**
     * Ejecuta la migración de datos desde la estructura del Excel actual.
     * Estructura del Excel:
     * [0] Inventario | [1] Tipo | [2] Marca | [3] Modelo | [4] Serie 
     * [5] Usuario | [6] Unidad | [7] Técnico encargado | [8] Fecha último mtto | [9] Comentarios
     *
     * @return void
     */
    public function run()
    {
        // 1. Asegurar un Cliente por defecto usando 'nombre_cliente'
        $cliente = Cliente::firstOrCreate(
            ['nombre_cliente' => 'Cliente Demostración'],
            [
                'contacto_nombre' => 'Administrador de Pruebas',
                'contacto_email'  => 'admin@cliente.com',
                'telefono'        => '2200-0000'
            ]
        );

        // 2. Asegurar un Contrato activo por defecto
        $contrato = Contrato::firstOrCreate(
            ['cliente_id' => $cliente->id, 'estado' => 'ACTIVO'],
            [
                'meta_equipos_total'        => 100,
                'mantenimientos_por_equipo' => 1,
                'fecha_inicio'              => now()->startOfYear(),
                'ubicacion_general'         => 'Sede Central'
            ]
        );

        // Ruta esperada del archivo CSV exportado desde tu Excel
        $filePath = database_path('seeders/data/inventario.csv');

        if (!file_exists($filePath)) {
            $this->command->error("No se encontró el archivo en: {$filePath}");
            $this->command->info("Por favor guarda tu Excel como CSV en 'database/seeders/data/inventario.csv'");
            return;
        }

        $file = fopen($filePath, 'r');
        
        // Detectar automáticamente el separador (coma ',' o punto y coma ';')
        $firstLine = fgets($file);
        $delimiter = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';
        rewind($file);

        // Omitir la fila de encabezados
        $header = fgetcsv($file, 1000, $delimiter);

        DB::beginTransaction();

        try {
            $registrosProcesados = 0;

            while (($row = fgetcsv($file, 1000, $delimiter)) !== FALSE) {
                // Mapeo directo de columnas según tu estructura de Excel
                $codigoInventario = trim($row[0] ?? '');
                $tipoRaw          = strtoupper(trim($row[1] ?? 'OTRO'));
                $marca            = trim($row[2] ?? 'DESCONOCIDA');
                $modelo           = trim($row[3] ?? 'DESCONOCIDO');
                $numeroSerie      = trim($row[4] ?? '');
                $usuarioAsignado  = trim($row[5] ?? '');
                $departamento     = trim($row[6] ?? '');
                $nombreTecnico    = trim($row[7] ?? 'Técnico Asignado');
                $fechaMttoRaw     = trim($row[8] ?? '');
                $comentarios      = trim($row[9] ?? '');

                // Omitir filas sin número de serie
                // if (empty($numeroSerie)) {
                //     continue;
                // }

                // Normalizar la categoría al ENUM de la base de datos
                $tipoEquipo = match (true) {
                    str_contains($tipoRaw, 'DESK') || str_contains($tipoRaw, 'PC') => 'DESKTOP',
                    str_contains($tipoRaw, 'LAP') || str_contains($tipoRaw, 'NOTE') => 'LAPTOP',
                    str_contains($tipoRaw, 'IMP') || str_contains($tipoRaw, 'PRINT') => 'IMPRESORA',
                    default => 'OTRO',
                };

                // 3. Buscar o registrar el Técnico en la tabla 'usuarios'
                $tecnico = User::firstOrCreate(
                    ['name' => $nombreTecnico],
                    [
                        'username' => strtolower(str_replace(' ', '', $nombreTecnico)),
                        'email'    => strtolower(str_replace(' ', '.', $nombreTecnico)) . '@rilaz.com',
                        'password' => bcrypt('password123'),
                        'rol'      => 'TECNICO',
                        'activo'   => true
                    ]
                );

                // 4. Registrar o actualizar el Equipo en la tabla 'equipos'
                $equipo = Equipo::updateOrCreate(
                    ['numero_serie' => $numeroSerie],
                    [
                        'codigo_inventario'   => $codigoInventario ?: null,
                        'tipo_equipo'         => $tipoEquipo,
                        'marca'               => $marca,
                        'modelo'              => $modelo,
                        'departamento_unidad' => $departamento ?: null,
                        'usuario_asignado'    => $usuarioAsignado ?: null,
                    ]
                );

                // Parsear fecha de mantenimiento con soporte para formato latino (d/m/Y)
                $fechaMantenimiento = now()->format('Y-m-d H:i:s');
                if (!empty($fechaMttoRaw)) {
                    try {
                        // En PHP, las fechas con barras '/' se asumen en formato estadounidense (m/d/Y).
                        // Al reemplazar '/' por '-', PHP/Carbon las interpreta correctamente como día-mes-año (d-m-Y).
                        $fechaLimpia = str_replace('/', '-', $fechaMttoRaw);
                        $fechaMantenimiento = Carbon::parse($fechaLimpia)->format('Y-m-d H:i:s');
                    } catch (\Exception $e) {
                        $fechaMantenimiento = now()->format('Y-m-d H:i:s');
                    }
                }

                // 5. Crear el registro en la tabla 'mantenimientos'
                Mantenimiento::create([
                    'contrato_id'         => $contrato->id,
                    'equipo_id'           => $equipo->id,
                    'tecnico_id'          => $tecnico->id,
                    'fecha_mantenimiento' => $fechaMantenimiento,
                    'observaciones'       => $comentarios ?: 'Mantenimiento preventivo migrado desde Excel.',
                    'estado_firma'        => 'FIRMADO_FISICO',
                    'impreso'             => 1,
                    'fecha_impresion'     => $fechaMantenimiento,
                    'impreso_por'         => $tecnico->id
                ]);

                $registrosProcesados++;
            }

            fclose($file);

            // 6. Crear un segundo Cliente y Contrato de prueba para demostración multicliente
            $cliente2 = Cliente::firstOrCreate(
                ['nombre_cliente' => 'Banco Agrícola S.A.'],
                [
                    'contacto_nombre' => 'Ing. Roberto Gómez',
                    'contacto_email'  => 'rgomez@bancoagricola.com',
                    'telefono'        => '2255-8888'
                ]
            );

            $contrato2 = Contrato::firstOrCreate(
                ['cliente_id' => $cliente2->id, 'ubicacion_general' => 'Torre Financiera Escalón'],
                [
                    'meta_equipos_total'        => 50,
                    'mantenimientos_por_equipo' => 2,
                    'fecha_inicio'              => now()->subMonths(2)->startOfMonth(),
                    'fecha_limite'              => now()->addMonths(10)->endOfMonth(),
                    'estado'                    => 'ACTIVO'
                ]
            );

            // Sincronizar técnicos con los contratos activos
            $tecnicos = User::where('rol', 'TECNICO')->get();
            if ($tecnicos->isNotEmpty()) {
                $contrato->tecnicos()->syncWithoutDetaching($tecnicos->pluck('id'));
                $contrato2->tecnicos()->syncWithoutDetaching($tecnicos->pluck('id'));
            }

            // Crear mantenimientos de prueba para el segundo contrato
            $tecnicoDemo = $tecnicos->first();
            if ($tecnicoDemo) {
                $equiposPrueba = [
                    ['serie' => 'SN-BA-001', 'inv' => 'INV-BA-101', 'tipo' => 'DESKTOP', 'marca' => 'DELL', 'modelo' => 'OptiPlex 7090', 'user' => 'Carlos Mendoza', 'dpto' => 'Caja Central'],
                    ['serie' => 'SN-BA-002', 'inv' => 'INV-BA-102', 'tipo' => 'LAPTOP', 'marca' => 'HP', 'modelo' => 'EliteBook 840', 'user' => 'Ana Rivas', 'dpto' => 'Gerencia General'],
                    ['serie' => 'SN-BA-003', 'inv' => 'INV-BA-103', 'tipo' => 'IMPRESORA', 'marca' => 'KYOCERA', 'modelo' => 'ECOSYS M3655', 'user' => 'Área de Impresión', 'dpto' => 'Operaciones'],
                ];

                foreach ($equiposPrueba as $eqData) {
                    $eq = Equipo::firstOrCreate(
                        ['numero_serie' => $eqData['serie']],
                        [
                            'codigo_inventario'   => $eqData['inv'],
                            'tipo_equipo'         => $eqData['tipo'],
                            'marca'               => $eqData['marca'],
                            'modelo'              => $eqData['modelo'],
                            'departamento_unidad' => $eqData['dpto'],
                            'usuario_asignado'    => $eqData['user'],
                        ]
                    );

                    Mantenimiento::firstOrCreate(
                        ['contrato_id' => $contrato2->id, 'equipo_id' => $eq->id],
                        [
                            'tecnico_id'          => $tecnicoDemo->id,
                            'fecha_mantenimiento' => now()->subDays(rand(1, 15)),
                            'observaciones'       => 'Mantenimiento preventivo periódico ejecutado sin novedades.',
                            'estado_firma'        => 'FIRMADO_FISICO',
                            'impreso'             => 1,
                            'fecha_impresion'     => now(),
                            'impreso_por'         => $tecnicoDemo->id
                        ]
                    );
                }
            }

            DB::commit();

            $this->command->info("¡Éxito! Se procesaron {$registrosProcesados} registros del Excel y se creó el segundo contrato de prueba ('Banco Agrícola S.A.').");

        } catch (\Exception $e) {
            DB::rollBack();
            if (isset($file) && is_resource($file)) {
                fclose($file);
            }
            $this->command->error("Error al importar los datos: " . $e->getMessage());
        }
    }
}
