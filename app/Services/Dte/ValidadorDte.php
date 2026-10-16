<?php

namespace App\Services\Dte;

use InvalidArgumentException;
use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Validator;

/**
 * Validación local del JSON del DTE contra el JSON Schema oficial del MH.
 * La emisión SIEMPRE pasa por aquí antes de firmar: un DTE que no cumple
 * el schema se rechaza localmente y jamás se firma ni transmite.
 *
 * Los schemas del MH son draft-07 (exclusiveMinimum/Maximum numéricos);
 * por eso se usa opis/json-schema (draft-06 a 2020-12) en vez de
 * justinrainbow/json-schema, que solo soporta draft-04.
 *
 * OJO: el validador exige stdClass (objetos JSON); hay que decodificar con
 * json_decode() SIN el flag assoc (ver FirmarTransmitirDocumento).
 */
class ValidadorDte
{
    /** Mapa tipoDte (CAT-002) → schema oficial en resources/mh-schemas/. */
    private const SCHEMAS = [
        '01' => 'fe-f-v2.json',   // Factura (consumidor final) — schema v2
        '03' => 'fe-ccf-v4.json', // CCF — schema v4
        '05' => 'fe-nc-v4.json',  // Nota de crédito — schema v4
        '06' => 'fe-nd-v4.json',  // Nota de débito — schema v4
    ];

    /**
     * Valida el DTE contra el schema oficial de su tipo.
     *
     * @param object $dte JSON ya decodificado como stdClass (sin assoc).
     * @throws InvalidArgumentException si el JSON no cumple el schema.
     */
    public function validar(object $dte): void
    {
        $tipoDte = $dte->identificacion->tipoDte ?? null;

        if (! isset(self::SCHEMAS[$tipoDte])) {
            throw new InvalidArgumentException("Tipo de DTE '{$tipoDte}' sin schema registrado en el validador.");
        }

        $archivo = self::SCHEMAS[$tipoDte];
        $ruta = resource_path('mh-schemas/' . $archivo);

        if (! is_file($ruta)) {
            throw new InvalidArgumentException("No se encuentra el schema: resources/mh-schemas/{$archivo}");
        }

        $validator = new Validator();
        $validator->setMaxErrors(10);
        $resultado = $validator->validate($dte, json_decode(file_get_contents($ruta)));

        if ($resultado->isValid()) {
            return;
        }

        // En opis 4.x la ruta del error se obtiene con ErrorFormatter:
        // devuelve ['/ruta/al/campo' => ['mensaje 1', 'mensaje 2'], ...]
        $conRutas = (new ErrorFormatter())->format($resultado->error(), true);

        $mensajes = [];

        foreach ($conRutas as $rutaError => $lista) {
            foreach ($lista as $mensaje) {
                $mensajes[] = trim($rutaError . ': ' . $mensaje);

                if (count($mensajes) >= 5) {
                    break 2;
                }
            }
        }

        if (empty($mensajes)) {
            $mensajes[] = 'error sin detalle (ver logs)';
        }

        throw new InvalidArgumentException(
            "El JSON no cumple el schema {$archivo} → " . implode(' | ', $mensajes)
        );
    }
}