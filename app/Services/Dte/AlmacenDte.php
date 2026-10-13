<?php

namespace App\Services\Dte;

use App\Models\Documento;
use Illuminate\Support\Facades\Storage;

/**
 * Copia del DTE en disco (convención 5: retención 10 años).
 * Con el FilesystemTenancyBootstrapper de stancl activo, el disco "local"
 * ya resuelve dentro de storage/app/tenant{id}/ → aislamiento por tenant.
 */
class AlmacenDte
{
    public function guardarJson(Documento $documento, string $json): string
    {
        $ruta = $this->rutaPara($documento, 'json');
        Storage::disk('local')->put($ruta, $json);
        return $ruta;
    }

    public function guardarFirma(Documento $documento, string $firmaJws): string
    {
        $ruta = $this->rutaPara($documento, 'jws');
        Storage::disk('local')->put($ruta, $firmaJws);
        return $ruta;
    }

    private function rutaPara(Documento $documento, string $extension): string
    {
        $fecha = $documento->fecha_emision ?? now();
        return sprintf('dtes/%s/%s/%s.%s',
            $fecha->format('Y'), $fecha->format('m'),
            $documento->codigo_generacion, $extension
        );
    }
}