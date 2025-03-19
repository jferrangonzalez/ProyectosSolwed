<?php
/**
 * This file is part of ProyectosSolwed plugin for FacturaScripts
 * Copyright (C) 2024 Jose Ferran
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScripts\Plugins\ProyectosSolwed\Lib;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Dinamic\Model\FacturaCliente;
use FacturaScripts\Plugins\Proyectos\Lib\ProjectTotalManager as ParentProjectTotalManager;
use FacturaScripts\Plugins\Proyectos\Model\Proyecto;

class ProjectTotalManager extends ParentProjectTotalManager
{
    public static function recalculate(int $idproyecto)
    {
        $project = new Proyecto();
        if (false === $project->loadFromCode($idproyecto)) {
            return;
        }

        $project->totalcompras = 0.0;
        foreach (static::purchaseInvoices($idproyecto) as $invoice) {
            $project->totalcompras += $invoice->total;
        }
        foreach (static::purchaseDeliveryNotes($idproyecto) as $delivery) {
            $project->totalcompras += $delivery->total;
        }
        foreach (static::purchaseOrders($idproyecto) as $order) {
            $project->totalcompras += $order->total;
        }

        $netoFacturas = 0.0;
        $project->totalventas = 0.0;

        // Solo sumamos las facturas pagadas al total de ventas
        foreach (static::paidSalesInvoices($idproyecto) as $invoice) {
            $project->totalventas += $invoice->total;
            $netoFacturas += $invoice->neto;
        }

        // Calculamos el total pendiente de facturar sin incluir pedidos ni albaranes
        $project->totalpendientefacturar = -$netoFacturas;
        if ($project->totalpendientefacturar < 0) {
            $project->totalpendientefacturar = 0;
        }

        $project->save();
    }

    /**
     * Obtiene las facturas de cliente pagadas para un proyecto.
     *
     * @param int $idproyecto
     * @return FacturaCliente[]
     */
    protected static function paidSalesInvoices(int $idproyecto): array
    {
        $invoice = new FacturaCliente();
        $where = [
            new DataBaseWhere('idproyecto', $idproyecto),
            new DataBaseWhere('pagada', true)
        ];
        return $invoice->all($where, [], 0, 0);
    }
}