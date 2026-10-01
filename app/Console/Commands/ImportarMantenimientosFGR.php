<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Equipo;
use App\Models\Sede;
use App\Models\Mantenimiento;
use App\Models\Contrato;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ImportarMantenimientosFGR extends Command
{
    // Firma del comando: recibe la ruta del archivo CSV
    protected $signature = 'importar:mantenimientos {ruta_csv}';
    protected $description = 'Importa mantenimientos desde un archivo CSV y crea los equipos si no existen.';

    public function handle()
    {
        $rutaCsv = $this->argument('ruta_csv');

        if (!file_exists($rutaCsv)) {
            $this->error("El archivo no existe en la ruta: $rutaCsv");
            return;
        }

        // Obtener la Sede "COMPLEJO FGR"
        $sedeFGR = Sede::where('nombre_sede', 'COMPLEJO FGR')->first();
        
        if (!$sedeFGR) {
            $this->error("La sede 'COMPLEJO FGR' no existe en la base de datos.");
            return;
        }

        $this->info("Iniciando importación. Sede base: {$sedeFGR->nombre_sede}");

        // Leer el CSV
        $archivo = fopen($rutaCsv, "r");
        $cabeceras = fgetcsv($archivo, 0, ';'); // Leer la primera línea (cabeceras)
        
        $filaNumero = 2;
        $creados = 0;
        $errores = 0;

        $this->output->progressStart(); // Barra de progreso visual

        while (($fila = fgetcsv($archivo, 0, ';')) !== FALSE) {
            // Mapeo según la imagen enviada (9 columnas):
            // 0: FECHA | 1: TIPO | 2: CONTRATO | 3: UNIDAD | 4: OFICINA | 5: USUARIO | 6: ACTIVO | 7: TECNICO | 8: OBSERVACIONES
            
            DB::beginTransaction();
            try {
                $fechaCsv = trim($fila[0]);
                // Según la imagen, la fecha ya viene en formato Y-m-d (ej. 2026-08-24)
                $fechaMantenimiento = Carbon::parse($fechaCsv)->format('Y-m-d H:i:s');

                $contratoId = trim($fila[1]);
                $tipoEquipo = strtoupper(trim($fila[2]));
                $unidad = trim($fila[3]);
                $oficina = trim($fila[4]); // Ya sabemos que es COMPLEJO FGR, usaremos el id de la sede arriba
                $usuarioAsignado = trim($fila[5]);
                $activoInventario = trim($fila[6]); // Lo usaremos como numero de serie / codigo inventario
                $tecnicoId = trim($fila[7]);
                $observaciones = trim($fila[8]);

                // 1. Validar que el contrato exista en BD (importante)
                $contrato = Contrato::find($contratoId);
                if (!$contrato) {
                    throw new \Exception("Contrato ID: $contratoId no encontrado.");
                }

                // 2. Validar que el tipo de equipo sea válido en tu base de datos
                $tiposValidos = ['DESKTOP', 'LAPTOP', 'IMPRESORA', 'ESCANER', 'OTRO'];
                if (!in_array($tipoEquipo, $tiposValidos)) {
                    $tipoEquipo = 'OTRO'; 
                }

                // 3. Buscar o Crear el Equipo
                $equipo = Equipo::firstOrCreate(
                    ['codigo_inventario' => $activoInventario], // Identificador único principal
                    [
                        // Estos datos SOLO se insertan si el equipo no existía previamente
                        'tipo_equipo' => $tipoEquipo,
                        'marca' => 'POR DEFINIR', // Obligatorio en BD
                        'modelo' => 'POR DEFINIR', // Obligatorio en BD
                        'departamento_unidad' => $unidad,
                        'usuario_asignado' => $usuarioAsignado,
                        'sede_id' => $sedeFGR->id,
                        'creado_por' => 1 
                    ]
                );

                // Opcional: Actualizar unidad/usuario del equipo si ya existía pero con datos distintos
                // $equipo->update(['usuario_asignado' => $usuarioAsignado, 'departamento_unidad' => $unidad]);

                // 4. Registrar el Mantenimiento
                Mantenimiento::create([
                    'contrato_id' => $contrato->id,
                    'equipo_id' => $equipo->id,
                    'tecnico_id' => $tecnicoId, // Toma el ID que dejaste en Excel
                    'sede_id' => $sedeFGR->id,
                    'fecha_mantenimiento' => $fechaMantenimiento,
                    'observaciones' => $observaciones,
                    'estado_firma' => 'PENDIENTE',
                    'creado_por' => 1 
                ]);

                DB::commit();
                $creados++;
            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("\nError en la fila $filaNumero (Activo: " . ($fila[6] ?? 'N/A') . "): " . $e->getMessage());
                $errores++;
            }
            
            $filaNumero++;
            $this->output->progressAdvance();
        }

        $this->output->progressFinish();
        fclose($archivo);

        $this->info("Importación finalizada.");
        $this->info("Mantenimientos procesados exitosamente: $creados");
        if ($errores > 0) $this->error("Filas con errores: $errores");
    }
}
