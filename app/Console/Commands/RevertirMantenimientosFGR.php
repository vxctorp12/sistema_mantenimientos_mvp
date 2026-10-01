<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Equipo;
use App\Models\Sede;
use App\Models\Mantenimiento;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RevertirMantenimientosFGR extends Command
{
    // Firma del comando: recibe la ruta del archivo CSV
    protected $signature = 'revertir:mantenimientos {ruta_csv}';
    protected $description = 'Revierte los mantenimientos importados desde un archivo CSV y limpia los equipos huérfanos.';

    public function handle()
    {
        $rutaCsv = $this->argument('ruta_csv');

        if (!file_exists($rutaCsv)) {
            $this->error("El archivo no existe en la ruta: $rutaCsv");
            return;
        }

        $sedeFGR = Sede::where('nombre_sede', 'COMPLEJO FGR')->first();
        
        if (!$sedeFGR) {
            $this->error("La sede 'COMPLEJO FGR' no existe en la base de datos.");
            return;
        }

        $this->info("Iniciando reversión. Sede base: {$sedeFGR->nombre_sede}");

        $archivo = fopen($rutaCsv, "r");
        $cabeceras = fgetcsv($archivo, 0, ';'); // Leer la primera línea (cabeceras)
        
        $eliminados = 0;
        $equiposEliminados = 0;
        $errores = 0;

        $this->output->progressStart();

        while (($fila = fgetcsv($archivo, 0, ';')) !== FALSE) {
            DB::beginTransaction();
            try {
                $fechaCsv = trim($fila[0]);
                $fechaMantenimiento = Carbon::parse($fechaCsv)->format('Y-m-d H:i:s');
                $contratoId = trim($fila[2]);
                $activoInventario = trim($fila[6]); 
                $tecnicoId = trim($fila[7]);

                $equipo = Equipo::where('numero_serie', $activoInventario)->first();

                if ($equipo) {
                    // Buscar el mantenimiento exacto y eliminarlo
                    $mantenimiento = Mantenimiento::where([
                        'contrato_id' => $contratoId,
                        'equipo_id' => $equipo->id,
                        'tecnico_id' => $tecnicoId,
                        'sede_id' => $sedeFGR->id,
                        'fecha_mantenimiento' => $fechaMantenimiento
                    ])->first();

                    if ($mantenimiento) {
                        $mantenimiento->delete();
                        $eliminados++;
                    }

                    // Si el equipo ya no tiene mantenimientos asociados, lo borramos también para dejar la BD limpia
                    if ($equipo->mantenimientos()->count() === 0) {
                        $equipo->delete();
                        $equiposEliminados++;
                    }
                }

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                $errores++;
            }
            
            $this->output->progressAdvance();
        }

        $this->output->progressFinish();
        fclose($archivo);

        $this->info("Reversión finalizada.");
        $this->info("Mantenimientos eliminados: $eliminados");
        $this->info("Equipos eliminados (por quedar sin mantenimientos): $equiposEliminados");
    }
}
