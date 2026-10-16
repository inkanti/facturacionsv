<?php

namespace App\Livewire\Tenant\Productos;

use App\Enums\TipoIva;
use App\Models\Producto;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Módulo 11 — CRUD de productos/servicios.
 * Columnas verificadas contra tenant_demo.productos:
 *   codigo(30), nombre(150), descripcion(500, opt), tipo(1=Bienes/2=Servicios CAT-011),
 *   unidad_medida(CAT-014), precio_unitario(dec 12,2, SIN IVA), tipo_iva,
 *   porcentaje_iva(dec 4,2), activo.
 */
class Lista extends Component
{
    use WithPagination;

    public string $buscar = '';

    public ?int $productoId = null;
    public string $codigo = '';
    public string $nombre = '';
    public string $descripcion = '';
    public string $tipo = '1';               // CAT-011: 1 = Bienes, 2 = Servicios
    public string $precio_unitario = '';
    public string $tipo_iva = '';
    public string $porcentaje_iva = '13.00';
    public string $unidad_medida = '59';     // CAT-014: 59 = Unidad, 99 = Otra
    public bool $activo = true;

    public bool $editando = false;

    protected function rules(): array
    {
        return [
            'codigo' => [
                'required', 'string', 'max:30',
                Rule::unique('productos', 'codigo')->ignore($this->productoId),
            ],
            'nombre'          => ['required', 'string', 'max:150'],
            'descripcion'     => ['nullable', 'string', 'max:500'],
            'tipo'            => ['required', 'in:1,2'],
            'precio_unitario' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'tipo_iva'        => ['required', Rule::enum(TipoIva::class)],
            'porcentaje_iva'  => ['required', 'numeric', 'min:0', 'max:99.99'],
            'unidad_medida'   => ['required', 'integer', 'between:1,99'],
            'activo'          => ['boolean'],
        ];
    }

    public function updatingBuscar(): void
    {
        $this->resetPage();
    }

    public function guardar(): void
    {
        $datos = $this->validate();
        $datos['precio_unitario'] = number_format((float) $datos['precio_unitario'], 2, '.', '');
        $datos['porcentaje_iva']  = number_format((float) $datos['porcentaje_iva'], 2, '.', '');
        // Coherencia: exento/excluido no lleva porcentaje
        if ($datos['tipo_iva'] !== 'gravado') {
            $datos['porcentaje_iva'] = '0.00';
        }

        if ($this->editando) {
            Producto::findOrFail($this->productoId)->update($datos);
            session()->flash('ok', 'Producto actualizado.');
        } else {
            Producto::create($datos);
            session()->flash('ok', 'Producto creado.');
        }

        $this->cancelar();
    }

    public function editar(int $id): void
    {
        $p = Producto::findOrFail($id);

        $this->productoId      = $p->id;
        $this->codigo          = (string) $p->codigo;
        $this->nombre          = (string) $p->nombre;
        $this->descripcion     = (string) $p->descripcion;
        $this->tipo            = (string) $p->tipo;
        $this->precio_unitario = (string) $p->precio_unitario;
        $this->tipo_iva        = (string) $p->tipo_iva->value;
        $this->porcentaje_iva  = (string) $p->porcentaje_iva;
        $this->unidad_medida   = (string) $p->unidad_medida;
        $this->activo          = (bool) $p->activo;

        $this->editando = true;
    }

    public function borrar(int $id): void
    {
        Producto::findOrFail($id)->delete();
        session()->flash('ok', 'Producto eliminado.');
    }

    public function cancelar(): void
    {
        $this->reset([
            'productoId', 'codigo', 'nombre', 'descripcion', 'precio_unitario',
            'tipo_iva', 'porcentaje_iva', 'editando',
        ]);
        $this->tipo = '1';
        $this->unidad_medida = '59';
        $this->porcentaje_iva = '13.00';
        $this->activo = true;
        $this->resetValidation();
    }

    public function render()
    {
        $productos = Producto::query()
            ->when($this->buscar, fn ($q) => $q->where(function ($w) {
                $w->where('nombre', 'like', "%{$this->buscar}%")
                  ->orWhere('codigo', 'like', "%{$this->buscar}%");
            }))
            ->orderBy('codigo')
            ->paginate(10);

        return view('livewire.tenant.productos.lista', [
            'productos' => $productos,
            'tiposIva'  => TipoIva::cases(),
        ])->layout('tenant.layout');
    }
}