<?php

namespace App\Enums;

/** Tipos de documento — CAT-002 oficial del MH. */
enum TipoDocumento: string
{
    case Fe = '01';    // Factura
    case Ccf = '03';   // Comprobante de crédito fiscal
    case Nr = '04';    // Nota de remisión
    case Nc = '05';    // Nota de crédito
    case Nd = '06';    // Nota de débito
    case Cr = '07';    // Comprobante de retención
    case Cl = '08';    // Comprobante de liquidación
    case Dcl = '09';   // Documento contable de liquidación
    case Fexe = '11';  // Factura de exportación
    case Fse = '14';   // Factura de sujeto excluido
    case Cd = '15';    // Comprobante de donación
}