<?php

namespace App\Livewire\Tenant\Receptores;

use App\Models\CatalogoMh;
use App\Models\Receptor;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Módulo 11 — CRUD de receptores con catálogos del MH en BD central:
 * departamentos (CAT-012), municipios/zonas (CAT-013) y distritos (CAT-008)
 * en cascada. Consumidor final (37): N° documento opcional (default 'CF'),
 * datos fiscales ocultos.
 */
class Lista extends Component
{
    use WithPagination;

    public string $buscar = '';

    public ?int $receptorId = null;
    public string $tipo_documento = '36';
    public string $num_documento = '';
    public string $nrc = '';
    public string $nombre = '';
    public string $nombre_comercial = '';
    public string $actividad_economica = '';
    public string $desc_actividad = '';
    public string $telefono = '';
    public string $correo = '';
    public string $departamento = '06';
    public string $municipio = '';
    public string $distrito = '';
    public string $direccion_complemento = '';
    public bool $activo = true;

    public bool $editando = false;

    public function getEsConsumidorFinalProperty(): bool
    {
        return $this->tipo_documento === '37';
    }

    protected function rules(): array
    {
        return [
            'tipo_documento'        => ['required', 'string', 'max:2'],
            'num_documento'         => ['required_unless:tipo_documento,37', 'string', 'max:20'],
            'nrc'                   => ['nullable', 'string', 'max:8'],
            'nombre'                => ['required', 'string', 'max:200'],
            'nombre_comercial'      => ['nullable', 'string', 'max:200'],
            'actividad_economica'   => ['nullable', 'string', 'max:10'],
            'desc_actividad'        => ['nullable', 'string', 'max:150'],
            'telefono'              => ['nullable', 'string', 'max:25'],
            'correo'                => ['nullable', 'email', 'max:100'],
            'departamento'          => ['required', 'string', 'size:2'],
            'municipio'             => ['required', 'string', 'max:2'],
            'distrito'              => ['nullable', 'string', 'max:2'],
            'direccion_complemento' => ['nullable', 'string', 'max:200'],
            'activo'                => ['boolean'],
        ];
    }

    public function updatingBuscar(): void
    {
        $this->resetPage();
    }

    public function updatedTipoDocumento(): void
    {
        if ($this->tipo_documento === '37' && trim($this->num_documento) === '') {
            $this->num_documento = 'CF';
        }
        if ($this->tipo_documento !== '37' && $this->num_documento === 'CF') {
            $this->num_documento = '';
        }
    }

    public function updatedDepartamento(): void
    {
        $this->municipio = '';
        $this->distrito = '';
    }

        public function guardar(): void
    {
        $datos = $this->validate();

        // Consumidor final: defaults que exige el schema FE v2 (codActividad min 5,
        // telefono min 8). La actividad se hereda del emisor.
        if ($datos['tipo_documento'] === '37') {
            if (trim((string) $datos['num_documento']) === '') {
                $datos['num_documento'] = 'CF';
            }
            if (trim((string) $datos['telefono']) === '') {
                $datos['telefono'] = '00000000';
            }
            if (trim((string) $datos['actividad_economica']) === '') {
                $emisor = \App\Models\Emisor::activo()->first();
                $datos['actividad_economica'] = (string) ($emisor->actividad_economica ?? '10005');
                if (trim((string) $datos['desc_actividad']) === '') {
                    $datos['desc_actividad'] = (string) ($emisor->actividad_economica_desc ?? '');
                }
            }
        }

        if ($this->editando) {
            Receptor::findOrFail($this->receptorId)->update($datos);
            session()->flash('ok', 'Cliente actualizado.');
        } else {
            Receptor::create($datos);
            session()->flash('ok', 'Cliente creado.');
        }

        $this->cancelar();
    }

    public function editar(int $id): void
    {
        $r = Receptor::findOrFail($id);

        $this->receptorId            = $r->id;
        $this->tipo_documento        = (string) $r->tipo_documento;
        $this->num_documento         = (string) $r->num_documento;
        $this->nrc                   = (string) $r->nrc;
        $this->nombre                = (string) $r->nombre;
        $this->nombre_comercial      = (string) $r->nombre_comercial;
        $this->actividad_economica   = (string) $r->actividad_economica;
        $this->desc_actividad        = (string) $r->desc_actividad;
        $this->telefono              = (string) $r->telefono;
        $this->correo                = (string) $r->correo;
        $this->departamento          = (string) $r->departamento;
        $this->municipio             = (string) $r->municipio;
        $this->distrito              = (string) $r->distrito;
        $this->direccion_complemento = (string) $r->direccion_complemento;
        $this->activo                = (bool) $r->activo;

        $this->editando = true;
    }

    public function borrar(int $id): void
    {
        Receptor::findOrFail($id)->delete();
        session()->flash('ok', 'Cliente eliminado.');
    }

    public function cancelar(): void
    {
        $this->reset([
            'receptorId', 'num_documento', 'nrc', 'nombre',
            'nombre_comercial', 'actividad_economica', 'desc_actividad',
            'telefono', 'correo', 'municipio', 'distrito',
            'direccion_complemento', 'editando',
        ]);
        $this->tipo_documento = '36';
        $this->departamento = '06';
        $this->activo = true;
        $this->resetValidation();
    }

    public function render()
    {
        $receptores = Receptor::query()
            ->when($this->buscar, fn ($q) => $q->where(function ($w) {
                $w->where('nombre', 'like', "%{$this->buscar}%")
                  ->orWhere('num_documento', 'like', "%{$this->buscar}%")
                  ->orWhere('nrc', 'like', "%{$this->buscar}%");
            }))
            ->orderBy('nombre')
            ->paginate(10);

        // Catálogos oficiales: BD central, cacheados
        $departamentos = Cache::rememberForever('mh:CAT-012', fn () => CatalogoMh::deTipo('CAT-012'));
        $municipios    = Cache::rememberForever("mh:CAT-013:{$this->departamento}",
            fn () => CatalogoMh::deTipo('CAT-013', $this->departamento));
        $distritos     = Cache::rememberForever("mh:CAT-008:{$this->departamento}",
            fn () => CatalogoMh::deTipo('CAT-008', $this->departamento));

        return view('livewire.tenant.receptores.lista', [
            'receptores'    => $receptores,
            'departamentos' => $departamentos->except('00'), // '00' = extranjeros
            'municipios'    => $municipios,
            'distritos'     => $distritos,
        ])->layout('tenant.layout');
    }
}