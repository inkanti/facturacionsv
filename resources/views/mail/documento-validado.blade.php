<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
</head>
<body style="font-family: Arial, sans-serif; color: #1f2937; line-height: 1.5;">
    <p>Estimado(a) <strong>{{ data_get($documento->receptor_snapshot, 'nombre') ?? 'cliente' }}</strong>:</p>

    <p>
        Su documento tributario electrónico ha sido validado ante el Ministerio de Hacienda.
        Adjuntamos la representación gráfica (PDF) y el documento electrónico (JSON).
    </p>

    <table style="border-collapse: collapse; margin: 16px 0;">
        <tr>
            <td style="padding: 4px 12px 4px 0; color: #6b7280;">Número de control</td>
            <td style="padding: 4px 0;"><strong>{{ $documento->numero_control }}</strong></td>
        </tr>
        <tr>
            <td style="padding: 4px 12px 4px 0; color: #6b7280;">Código de generación</td>
            <td style="padding: 4px 0;">{{ $documento->codigo_generacion }}</td>
        </tr>
        <tr>
            <td style="padding: 4px 12px 4px 0; color: #6b7280;">Fecha de emisión</td>
            <td style="padding: 4px 0;">{{ $documento->created_at->format('d/m/Y H:i') }}</td>
        </tr>
        <tr>
            <td style="padding: 4px 12px 4px 0; color: #6b7280;">Total</td>
            <td style="padding: 4px 0;"><strong>${{ number_format((float) $documento->total, 2) }}</strong></td>
        </tr>
    </table>

    <p style="color: #6b7280; font-size: 12px;">
        Este correo se genera automáticamente; no responda a esta dirección.
    </p>
</body>
</html>