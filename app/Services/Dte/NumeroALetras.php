<?php

namespace App\Services\Dte;

/** Convierte un monto a letras en formato MH: "QUINCE DÓLARES CON 00/100". */
class NumeroALetras
{
    private const UNIDADES = ['', 'UN', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE', 'DIEZ',
        'ONCE', 'DOCE', 'TRECE', 'CATORCE', 'QUINCE', 'DIECISÉIS', 'DIECISIETE', 'DIECIOCHO', 'DIECINUEVE', 'VEINTE'];
    private const DECENAS = ['', '', 'VEINTI', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA'];
    private const CENTENAS = ['', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS',
        'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS'];

    public function aLetras(float $monto): string
    {
        $entero = (int) floor($monto);
        $centavos = (int) round(($monto - $entero) * 100);

        return $this->convertir($entero) . ' DÓLARES CON ' . sprintf('%02d', $centavos) . '/100';
    }

    private function convertir(int $n): string
    {
        if ($n === 0) return 'CERO';
        if ($n === 100) return 'CIEN';

        $partes = [];
        $millones = intdiv($n, 1_000_000);
        $miles = intdiv($n % 1_000_000, 1000);
        $resto = $n % 1000;

        if ($millones > 0) {
            $partes[] = $millones === 1 ? 'UN MILLÓN' : $this->convertir($millones) . ' MILLONES';
        }
        if ($miles > 0) {
            $partes[] = $miles === 1 ? 'MIL' : $this->convertir($miles) . ' MIL';
        }
        if ($resto > 0) {
            $partes[] = $this->tresDigitos($resto);
        }

        return implode(' ', $partes);
    }

    private function tresDigitos(int $n): string
    {
        $centena = intdiv($n, 100);
        $resto = $n % 100;
        $partes = [];

        if ($centena > 0) $partes[] = self::CENTENAS[$centena];
        if ($resto > 0) {
            $partes[] = $resto <= 20
                ? self::UNIDADES[$resto]
                : self::DECENAS[intdiv($resto, 10)] . ($resto % 10 > 0 ? ' Y ' . self::UNIDADES[$resto % 10] : '');
        }

        return implode(' ', array_filter($partes));
    }
}