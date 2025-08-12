<?php

declare(strict_types=1);

namespace User\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use User\Model\SkillTable;
use ZfcDatagrid\Datagrid;
use ZfcDatagrid\Column\Select as ColumnSelect;
use ZfcDatagrid\Column\Action as ColumnAction;
use ZfcDatagrid\Column\Action\Button as ActionButton;
use ZfcDatagrid\Column\Type;
use ZfcDatagrid\Filter;
use Laminas\Db\Sql\Sql;

class SkillController extends AbstractActionController
{
    private $skillTable;

    public function __construct(SkillTable $skillTable)
    {
        $this->skillTable = $skillTable;
    }

    public function indexAction()
    {
        // Configurar localización en español
        setlocale(LC_TIME, 'es_ES.UTF-8', 'es_ES', 'Spanish_Spain', 'spanish');
        
        // Agregar CSS personalizado mejorado con Bootstrap
        $viewHelperManager = $this->getEvent()->getApplication()->getServiceManager()
             ->get('ViewHelperManager');
             
        // CSS base optimizado con Bootstrap
        $viewHelperManager->get('headLink')
             ->appendStylesheet('/css/datagrid-simple.css');
             
        // CSS avanzado con componentes Bootstrap
        $viewHelperManager->get('headLink')
             ->appendStylesheet('/css/datagrid-bootstrap-enhanced.css');
             
        // Asegurar que FontAwesome esté disponible para los iconos
        $viewHelperManager->get('headLink')
             ->appendStylesheet('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css');
             
        // Agregar JavaScript de Bootstrap para componentes interactivos
        $viewHelperManager->get('headScript')
             ->appendFile('/js/bootstrap.min.js')
             ->appendFile('/js/datagrid-bootstrap.js');
             
        // Obtener instancia del datagrid desde el ServiceManager
        $serviceManager = $this->getEvent()->getApplication()->getServiceManager();
        $datagrid = $serviceManager->get(Datagrid::class);
        $datagrid->setTitle('Lista de Skills');
        $datagrid->setDefaultItemsPerPage(25);
        
        // Configurar la fuente de datos usando la tabla skills directamente
        $adapter = $this->skillTable->getTableGateway()->getAdapter();
        $sql = new Sql($adapter);
        $select = $sql->select();
        $select->from('skills')
               ->where(['deletedAt' => 0])
               ->order(['id DESC']);
        
        $datagrid->setDataSource($select, $adapter);
        
        // Configurar columnas
        $this->configureSkillColumns($datagrid);
        
        // Configurar acciones
        $this->configureSkillActions($datagrid);
        
        // Agregar botón para crear nueva skill
        $this->configureSkillToolbar($datagrid);
        
        // Renderizar y obtener la respuesta del datagrid
        return $datagrid->getResponse();
    }

    private function configureSkillColumns(Datagrid $datagrid)
    {
        // Columna ID (oculta pero necesaria para los botones de acción)
        $colId = new ColumnSelect('id');
        $colId->setIdentity(true);
        $colId->setLabel('ID');
        $colId->setWidth(5);
        $colId->setType(new Type\Number());
        $datagrid->addColumn($colId);

        // Columna Nombre
        $colNombre = new ColumnSelect('nombre');
        $colNombre->setLabel('Nombre de la Skill');
        $colNombre->setWidth(60);
        $colNombre->setSortDefault(1, 'ASC');
        $colNombre->setFilterDefaultOperation(Filter::LIKE);
        $datagrid->addColumn($colNombre);

        // Columna Fecha de Creación
        $colCreatedAt = new ColumnSelect('createdAt');
        $colCreatedAt->setLabel('Fecha de Creación');
        $colCreatedAt->setWidth(20);
        $colCreatedAt->setType(new Type\DateTime());
        $datagrid->addColumn($colCreatedAt);
    }

    private function configureSkillActions(Datagrid $datagrid)
    {
        // Crear columna de acciones
        $actionColumn = new ColumnAction();
        $actionColumn->setLabel('Acciones');
        $actionColumn->setWidth(15);
        
        // Acción Ver
        $actionView = new ActionButton();
        $actionView->setLabel('<i class="fas fa-eye"></i> <span class="d-none d-md-inline">Ver</span>');
        $actionView->setLink('/skill/view/:id');
        $actionView->setAttribute('class', 'btn btn-action btn-view btn-sm');
        $actionView->setAttribute('title', 'Ver detalles de la skill');
        $actionColumn->addAction($actionView);

        // Acción Editar
        $actionEdit = new ActionButton();
        $actionEdit->setLabel('<i class="fas fa-edit"></i> <span class="d-none d-md-inline">Editar</span>');
        $actionEdit->setLink('/skill/edit/:id');
        $actionEdit->setAttribute('class', 'btn btn-action btn-edit btn-sm');
        $actionEdit->setAttribute('title', 'Editar skill');
        $actionColumn->addAction($actionEdit);

        // Acción Eliminar
        $actionDelete = new ActionButton();
        $actionDelete->setLabel('<i class="fas fa-trash"></i> <span class="d-none d-md-inline">Eliminar</span>');
        $actionDelete->setLink('/skill/delete/:id');
        $actionDelete->setAttribute('class', 'btn btn-action btn-delete btn-sm');
        $actionDelete->setAttribute('title', 'Eliminar skill');
        $actionDelete->setAttribute('onclick', 'return confirm("¿Está seguro de que desea eliminar esta skill? Esta acción no se puede deshacer.")');
        $actionColumn->addAction($actionDelete);
        
        // Agregar la columna de acciones al datagrid
        $datagrid->addColumn($actionColumn);
    }

    private function configureSkillToolbar(Datagrid $datagrid)
    {
        // Configurar plantilla personalizada para la toolbar de skills
        $datagrid->setToolbarTemplate('zfc-datagrid/toolbar/skill-toolbar');
        
        // Pasar variables adicionales a la plantilla
        $toolbarVariables = [
            'title' => 'Gestión de Skills'
        ];
        $datagrid->setToolbarTemplateVariables($toolbarVariables);
    }
}
