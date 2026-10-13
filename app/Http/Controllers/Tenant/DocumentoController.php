<?php

namespace App\Http\Controllers\Tenant;

use App\Contracts\ProveedorDte;
use App\Enums\EstadoDocumento;
use App\Http\Controllers\Controller;
use App\Models\Documento;
use App\Models\Emisor;
use App\Services\Dte\AlmacenDte;
use Barryvdh\DomPDF\Facade\Pdf;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Http\Request;



class DocumentoController extends Controller
{
    public function index(Request $request)
    {
        $documentos = Documento::query()
            ->when($request->filled('estado'), fn ($q, $e) => $q->where('estado', $e))
            ->when($request->filled('buscar'), fn ($q, $b) => $q->buscar($b))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('tenant.documentos.index', [
            'documentos' => $documentos,
            'estados' => EstadoDocumento::cases(),
            'filtros' => $request->only('estado', 'buscar'),
        ]);
    }

    public function show(Documento $documento)
    {
        $documento->load('detalles', 'receptor');

        return view('tenant.documentos.show', compact('documento'));
    }

    /** Descarga el JSON oficial: identificacion + firmaElectronica + selloRecibido (manual 6.5). */
    public function descargarJson(Documento $documento, AlmacenDte $almacen)
    {
        $contenido = $almacen->leerCopiaReceptor($documento) ?? json_encode([
            'identificacion' => $documento->json_dte['identificacion'] ?? null,
            'firmaElectronica' => $documento->firma_jws,
            'selloRecibido' => $documento->respuesta_mh['raw']['selloRecibido'] ?? null,
        ], JSON_UNESCAPED_UNICODE);

        return response($contenido, 200, [
            'Content-Type' => 'application/json; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . ($documento->numero_control ?? 'dte') . '.json"',
        ]);
    }

      /** Representación gráfica (PDF) con QR de consulta pública. */
    public function descargarPdf(Documento $documento)
    {
        $documento->load('detalles', 'receptor');

        $qrUrl = sprintf(
            'https://admin.factura.gob.sv/consultaPublica?ambiente=%s&codGen=%s&fechaEmi=%s',
            $documento->json_dte['identificacion']['ambiente'] ?? '00',
            $documento->codigo_generacion,
            optional($documento->fecha_emision)->format('Y-m-d')
        );

        // render() ya devuelve un data URI completo (outputBase64=true por defecto en chillerlan v5)
        $qr = (new QRCode(new QROptions([
            'outputType' => 'png',
            'scale' => 4,
        ])))->render($qrUrl);

        $pdf = Pdf::loadView('tenant.documentos.pdf', compact('documento', 'qr'));

        return $pdf->download(($documento->numero_control ?? 'dte') . '.pdf');
    }

    /** Consulta el estado del DTE en el MH (servicio consultadte, manual 4.3.1). */
    public function consultarMh(Documento $documento, ProveedorDte $proveedor)
    {
        abort_unless($documento->codigo_generacion, 404, 'El documento aún no fue emitido.');

        $emisor = Emisor::activo()->firstOrFail();

        $resultado = $proveedor->consultar(
            $emisor->nit,
            $documento->tipo_documento,
            $documento->codigo_generacion
        );

        return back()->with('consulta_mh', $resultado);
    }
}