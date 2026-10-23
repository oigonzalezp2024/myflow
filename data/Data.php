<?php

// Mapping de ind_ordenes_fabricacion

class Data
{

    private $data = [];

    public function __construct()
    {
        $data['numero_orden'] = (string) 'numero_orden4';
        $data['cliente_id'] = (int) 1;
        $data['asesor_id'] = (int) 1;
        $data['fabricante_id'] = (int) 1;
        $data['operario_id'] = (int) 1;
        $data['producto_id'] = (int) 1;
        $data['unidades'] = (int) 1;
        $data['estado'] = (string) 'estado';
        $data['fecha_pedido'] = (string) 'fecha_pedido';
        $data['fecha_entrega'] = (string) 'fecha_entrega';
        $data['costo_subtotal'] = (string) 'costo_subtotal';
        $data['costo_mod'] = (string) 'costo_mod';
        $data['costo_cif'] = (string) 'costo_cif';
        $data['porcentaje_utilidad'] = (string) 'porcentaje_utilidad';
        $data['monto_utilidad'] = (string) 'monto_utilidad';
        $data['monto_total'] = (string) 'monto_total';
        $data['creado_en'] = (int) 'creado_en';
        $data['fecha_actualizacion'] = (string) 'fecha_actualizacion';
        $this->data = $data;
    }

    public function getData()
    {
        return $this->data;
    }
}
