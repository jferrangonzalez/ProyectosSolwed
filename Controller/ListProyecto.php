<?php

namespace FacturaScripts\Plugins\ProyectosSolwed\Controller;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\ExtendedController\ListController;
use FacturaScripts\Core\Lib\ExtendedController\ListView;
use FacturaScripts\Core\Tools;
use FacturaScripts\Plugins\Proyectos\Model\EstadoProyecto;
use FacturaScripts\Plugins\Proyectos\Controller\ListProyecto as ParentClass;

class ListProyecto extends ParentClass
{
    protected $estadoInfo = []; // Inicializar la variable

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'projects';
        $data['title'] = 'Proyectos';
        $data['icon'] = 'fab fa-stack-overflow';
        return $data;
    }

    protected function createViews()
    {
        // Crear la pestaña para "Todos los Proyectos"
        $this->addView('ListProyecto', 'Proyecto', 'Proyectos', 'fab fa-stack-overflow')
            ->addOrderBy(['fecha', 'idproyecto'], 'date', 2)
            ->addOrderBy(['fechainicio'], 'start-date')
            ->addOrderBy(['fechafin'], 'end-date')
            ->addOrderBy(['nombre'], 'name')
            ->addOrderBy(['totalcompras'], 'total-purchases')
            ->addOrderBy(['totalventas'], 'total-sales')
            ->addSearchFields(['nombre', 'descripcion'])
            ->addFilterPeriod('fecha', 'date', 'fecha')
            ->addFilterAutocomplete('codcliente', 'customer', 'codcliente', 'clientes', 'codcliente', 'nombre')
            ->addFilterSelect('nick', 'admin', 'nick', $this->codeModel->all('users', 'nick', 'nick'))
            ->addFilterNumber('totalcompras-gt', 'total-purchases', 'totalcompras', '>=')
            ->addFilterNumber('totalcompras-lt', 'total-purchases', 'totalcompras', '<=')
            ->addFilterNumber('totalventas-gt', 'total-sales', 'totalventas', '>=')
            ->addFilterNumber('totalventas-lt', 'total-sales', 'totalventas', '<=');
    
        // Obtener todos los estados de la tabla proyectos_estados
        $estados = EstadoProyecto::all([], ['idestado' => 'ASC'], 0, 0);
    
        // Crear una vista para cada estado
        foreach ($estados as $estado) {
            $viewName = 'ListProyecto-' . str_replace(' ', '', $estado->nombre); // Nombre único para la vista
            $this->estadoInfo[$viewName] = [
                'nombre' => $estado->nombre,
                'idestado' => $estado->idestado,
            ];
            
            // Pasar el nombre del estado como etiqueta y un icono predeterminado
            $this->createViewsProjects($viewName, $estado->nombre, 'fab fa-stack-overflow');
        }
    }

protected function createViewsProjects(string $viewName, string $label = '', string $icon = ''): void
{
    // Verificar si hay información del estado para esta vista
    $estadoInfo = $this->estadoInfo[$viewName] ?? null;
    if (!$estadoInfo) {
        return; // Si no hay información, no hacemos nada
    }

    $estadoNombre = $estadoInfo['nombre'];
    
    // Si no se proporcionan label e icon, usar valores predeterminados
    if (empty($label)) {
        $label = $estadoNombre;
    }
    if (empty($icon)) {
        $icon = 'fab fa-stack-overflow';
    }

    // Filtros comunes
    $users = $this->codeModel->all('users', 'nick', 'nick');

    $where = [
        ['label' => Tools::lang()->trans('only-active'), 'where' => [new DataBaseWhere('editable', true)]],
        ['label' => Tools::lang()->trans('only-closed'), 'where' => [new DataBaseWhere('editable', false)]],
        ['label' => Tools::lang()->trans('all'), 'where' => []]
    ];

    // Crear la vista con el nombre del estado
    $this->addView($viewName, 'Proyecto', $label, $icon)
        ->addOrderBy(['fecha', 'idproyecto'], 'date', 2)
        ->addOrderBy(['fechainicio'], 'start-date')
        ->addOrderBy(['fechafin'], 'end-date')
        ->addOrderBy(['nombre'], 'name')
        ->addOrderBy(['totalcompras'], 'total-purchases')
        ->addOrderBy(['totalventas'], 'total-sales')
        ->addSearchFields(['nombre', 'descripcion'])
        ->addFilterSelectWhere('status', $where)
        ->addFilterPeriod('fecha', 'date', 'fecha')
        ->addFilterAutocomplete('codcliente', 'customer', 'codcliente', 'clientes', 'codcliente', 'nombre')
        ->addFilterSelect('nick', 'admin', 'nick', $users)
        ->addFilterNumber('totalcompras-gt', 'total-purchases', 'totalcompras', '>=')
        ->addFilterNumber('totalcompras-lt', 'total-purchases', 'totalcompras', '<=')
        ->addFilterNumber('totalventas-gt', 'total-sales', 'totalventas', '>=')
        ->addFilterNumber('totalventas-lt', 'total-sales', 'totalventas', '<=');

    $this->setProjectColors($viewName);
}

protected function loadData($viewName, $view)
{
    // Verificar si hay información del estado para esta vista
    $estadoInfo = $this->estadoInfo[$viewName] ?? null;
    if ($estadoInfo) {
        $idEstado = $estadoInfo['idestado'];
        $where = [];

        // Filtrar por el estado correspondiente
        $where[] = new DataBaseWhere('idestado', $idEstado);

        // Si NO es admin, mostrar solo sus proyectos asignados
        if ($this->user->admin != 1) {
            $where[] = new DataBaseWhere('nick', $this->user->nick);
        }

        $view->loadData('', $where);
        return;
    }

    // Para la vista principal
    if ($viewName === 'ListProyecto') {
        $where = [];
        
        // Si NO es admin, mostrar solo sus proyectos asignados
        if ($this->user->admin != 1) {
            $where[] = new DataBaseWhere('nick', $this->user->nick);
        }

        $view->loadData('', $where);
        return;
    }

    // Cargar datos por defecto para otras vistas
    $view->loadData();
}

    protected function setProjectColors(string $viewName): void
    {
        // Asignar colores
        foreach (EstadoProyecto::all([], [], 0, 0) as $estado) {
            if (empty($estado->color)) {
                continue;
            }

            $this->views[$viewName]->getRow('status')->options[] = [
                'tag' => 'option',
                'children' => [],
                'color' => $estado->color,
                'fieldname' => 'idestado',
                'text' => $estado->idestado,
                'title' => 'siempre'
            ];
        }
    }
}