<?php

declare(strict_types=1);

namespace User\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use User\Model\CvTable;
use User\Model\UserTable;
use ZfcDatagrid\Datagrid;
use ZfcDatagrid\Column;
use ZfcDatagrid\Column\Select as ColumnSelect;
use ZfcDatagrid\Column\Action as ColumnAction;
use ZfcDatagrid\Column\Action\Button as ActionButton;
use ZfcDatagrid\Column\Type;
use ZfcDatagrid\Column\Style;
use ZfcDatagrid\Filter;
use Laminas\Db\Sql\Sql;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\Expression;
use User\Service\CvCacheService;

class CvController extends AbstractActionController
{
    private $cvTable;
    private $userTable;
    private $skillTable;
    private $cacheService;

    public function __construct(CvTable $cvTable, UserTable $userTable, $skillTable = null, CvCacheService $cacheService = null)
    {
        $this->cvTable = $cvTable;
        $this->userTable = $userTable;
        $this->skillTable = $skillTable;
        $this->cacheService = $cacheService;
    }

    public function indexAction()
    {
        $userId = (int) $this->params()->fromRoute('userId', 0);
        
        if ($userId === 0) {
            return $this->redirect()->toRoute('user');
        }

        try {
            $user = $this->userTable->getUser($userId);
            $cvs = $this->cvTable->getCvsByUserId($userId);
            
            return new ViewModel([
                'user' => $user,
                'cvs' => $cvs,
            ]);
        } catch (\Exception $e) {
            $this->flashMessenger()->addErrorMessage('Usuario no encontrado');
            return $this->redirect()->toRoute('user');
        }
    }

    public function viewAction()
    {
        $cvId = (int) $this->params()->fromRoute('id', 0);
        
        if ($cvId === 0) {
            return $this->redirect()->toRoute('user');
        }

        try {
            $cvWithUser = $this->cvTable->getCvWithUser($cvId);
            
            return new ViewModel([
                'cv' => $cvWithUser,
            ]);
        } catch (\Exception $e) {
            $this->flashMessenger()->addErrorMessage('CV no encontrado');
            return $this->redirect()->toRoute('user');
        }
    }

    // Nuevo método para vista general de todos los CVs usando ZfcDatagrid
    public function allAction()
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
        $datagrid->setTitle('Gestión de CVs');
        $datagrid->setDefaultItemsPerPage(25);
        
        // Configurar la fuente de datos usando Laminas\Db\Sql\Select
        $adapter = $this->cvTable->getTableGateway()->getAdapter();
        $sql = new Sql($adapter);
        $select = $sql->select();
        
        // Usar la vista personalizada para evitar JOINs complejos y IDs ambiguos
        $select->from('cv_datagrid_view')
               ->order(['createdAt DESC']);
        
        $datagrid->setDataSource($select, $adapter);
        
        // Configurar columnas
        $this->configureCvColumns($datagrid);
        
        // Configurar acciones
        $this->configureCvActions($datagrid);
        
        // Agregar botón para crear nuevo CV
        $this->configureCvToolbar($datagrid);
        
        // Renderizar y obtener la respuesta del datagrid
        return $datagrid->getResponse();
    }

    // Vista general de CV individual (desde /cvs)
    public function viewGeneralAction()
    {
        $cvId = (int) $this->params()->fromRoute('id', 0);
        
        if ($cvId === 0) {
            return $this->redirect()->toRoute('cvs');
        }

        try {
            $cvWithUser = $this->cvTable->getCvWithUser($cvId);
            
            return new ViewModel([
                'cv' => $cvWithUser,
                'fromGeneral' => true, // Para saber que viene de la vista general
            ]);
        } catch (\Exception $e) {
            $this->flashMessenger()->addErrorMessage('CV no encontrado');
            return $this->redirect()->toRoute('cvs');
        }
    }

    public function editAction()
    {
        $cvId = (int) $this->params()->fromRoute('id', 0);
        
        if ($cvId === 0) {
            return $this->redirect()->toRoute('cvs');
        }

        try {
            $cv = $this->cvTable->getCvWithSkills($cvId);
            
            $form = new \User\Form\CvForm($this->userTable, $this->skillTable);
            $form->get('submit')->setValue('Actualizar CV');
            
            $request = $this->getRequest();
            
            if (!$request->isPost()) {
                // Poblar el formulario con los datos del CV
                $cvData = $cv->getArrayCopy();
                
                // Convertir skills array a JSON string para el campo hidden
                if (isset($cvData['skills']) && is_array($cvData['skills'])) {
                    $cvData['skills'] = json_encode($cvData['skills']);
                }
                
                $form->setData($cvData);
                
                $availableSkills = [];
                if ($this->skillTable) {
                    try {
                        $availableSkills = $form->getAvailableSkills();
                    } catch (\Exception $e) {
                        // En caso de error, usar array vacío
                    }
                }
                
                return new ViewModel([
                    'form' => $form,
                    'cv' => $cv,
                    'availableSkills' => $availableSkills,
                ]);
            }
            
            $form->setData($request->getPost());
            
            if (!$form->isValid()) {
                $availableSkills = [];
                if ($this->skillTable) {
                    try {
                        $availableSkills = $form->getAvailableSkills();
                    } catch (\Exception $e) {
                        // En caso de error, usar array vacío
                    }
                }
                
                return new ViewModel([
                    'form' => $form,
                    'cv' => $cv,
                    'availableSkills' => $availableSkills,
                ]);
            }
            
            // Verificar si se presionó cancelar
            if ($request->getPost('cancel')) {
                return $this->redirect()->toRoute('cvs');
            }
            
            // Mantener el ID original
            $data = $form->getData();
            $data['id'] = $cv->getId();
            $cv->exchangeArray($data);
            
            try {
                $this->cvTable->saveCv($cv);
                
                // Invalidar cache y marcar para una sola visita
                if ($this->cacheService) {
                    // Invalidar cache
                    $this->cacheService->invalidateCvCache($cvId);
                    
                    // Marcar en sesión que este CV debe ignorar cache existente SOLO en la próxima visita
                    $session = new \Laminas\Session\Container('cv_cache_control');
                    $session->ignore_cache_once = $session->ignore_cache_once ?? [];
                    $session->ignore_cache_once[$cvId] = true; // Solo una vez
                }
                
                $this->flashMessenger()->addSuccessMessage('CV actualizado exitosamente');
                return $this->redirect()->toRoute('cvs');
            } catch (\Exception $e) {
                $this->flashMessenger()->addErrorMessage('Error al actualizar el CV: ' . $e->getMessage());
                return new ViewModel([
                    'form' => $form,
                    'cv' => $cv,
                ]);
            }
            
        } catch (\Exception $e) {
            $this->flashMessenger()->addErrorMessage('CV no encontrado');
            return $this->redirect()->toRoute('cvs');
        }
    }

    public function deleteAction()
    {
        $cvId = (int) $this->params()->fromRoute('id', 0);
        
        if ($cvId === 0) {
            return $this->redirect()->toRoute('cvs');
        }

        try {
            $cv = $this->cvTable->getCv($cvId);
            
            $request = $this->getRequest();
            if ($request->isPost()) {
                $del = $request->getPost('del', 'No');
                
                if ($del === 'Sí') {
                    $this->cvTable->deleteCv($cvId);
                    
                    // Invalidar cache del CV eliminado
                    if ($this->cacheService) {
                        $this->cacheService->invalidateCvCache($cvId);
                    }
                    
                    $this->flashMessenger()->addSuccessMessage('CV eliminado correctamente');
                } else {
                    $this->flashMessenger()->addInfoMessage('Eliminación cancelada');
                }
                
                return $this->redirect()->toRoute('cvs');
            }
            
            return new ViewModel([
                'cv' => $cv,
            ]);
        } catch (\Exception $e) {
            $this->flashMessenger()->addErrorMessage('CV no encontrado');
            return $this->redirect()->toRoute('cvs');
        }
    }

    public function addAction()
    {
        $form = new \User\Form\CvForm($this->userTable, $this->skillTable);
        $form->get('submit')->setValue('Crear CV');
        
        $request = $this->getRequest();
        
        if (!$request->isPost()) {
            $availableSkills = [];
            if ($this->skillTable) {
                try {
                    $availableSkills = $form->getAvailableSkills();
                } catch (\Exception $e) {
                    // En caso de error, usar array vacío
                }
            }
            
            return new ViewModel([
                'form' => $form,
                'availableSkills' => $availableSkills,
            ]);
        }
        
        $cv = new \User\Model\Cv();
        $form->setData($request->getPost());
        
        if (!$form->isValid()) {
            return new ViewModel([
                'form' => $form,
            ]);
        }
        
        // Verificar si se presionó cancelar
        if ($request->getPost('cancel')) {
            return $this->redirect()->toRoute('cvs');
        }
        
        $cv->exchangeArray($form->getData());
        
        try {
            $this->cvTable->saveCv($cv);
            $this->flashMessenger()->addSuccessMessage('CV creado exitosamente');
            return $this->redirect()->toRoute('cvs');
        } catch (\Exception $e) {
            $this->flashMessenger()->addErrorMessage('Error al crear el CV: ' . $e->getMessage());
            return new ViewModel([
                'form' => $form,
            ]);
        }
    }

    private function configureCvColumns(Datagrid $datagrid)
    {
        // Columna ID (oculta pero necesaria para los botones de acción)
        $colId = new ColumnSelect('cv_id');
        $colId->setIdentity(true);
        $colId->setLabel('ID');
        $colId->setWidth(5);
        $colId->setType(new Type\Number());
        $datagrid->addColumn($colId);

       

        // Columna Usuario (nombre completo + email en una sola columna)
        $colUsuario = new ColumnSelect('user_info_combined');
        $colUsuario->setLabel('Usuario');
        $colUsuario->setWidth(18); // Reducido de 22 a 18
        $colUsuario->setFilterDefaultOperation(Filter::LIKE);
        $datagrid->addColumn($colUsuario);

         // Columna Título del CV
         $colTitulo = new ColumnSelect('titulo');
         $colTitulo->setLabel('Título del CV');
         $colTitulo->setWidth(16); // Reducido de 20 a 16
         $colTitulo->setSortDefault(1, 'ASC');
         $colTitulo->setFilterDefaultOperation(Filter::LIKE);
         $datagrid->addColumn($colTitulo);
         
        // Columna Skills con niveles (desde la vista)
        $colSkills = new ColumnSelect('skills_with_levels');
        $colSkills->setLabel('Habilidades');
        $colSkills->setWidth(12); // Reducido de 18 a 12
        $colSkills->setFilterDefaultOperation(Filter::LIKE);
        $datagrid->addColumn($colSkills);


        // Columna Resumen (con más espacio)
        $colResumen = new ColumnSelect('resumen');
        $colResumen->setLabel('Resumen');
        $colResumen->setWidth(25); // Reducido de 32 a 25
        $colResumen->setFilterDefaultOperation(Filter::LIKE);
        $datagrid->addColumn($colResumen);

        // Columna Pretensión Salarial
        $colSalario = new ColumnSelect('pretension_salarial');
        $colSalario->setLabel('Salario');
        $colSalario->setWidth(10);
        $colSalario->setType(new Type\Number());
        
        $datagrid->addColumn($colSalario);

        // Columna Fecha de Creación - REMOVIDA según solicitud
    }

    private function configureCvActions(Datagrid $datagrid)
    {
        // Crear columna de acciones
        $actionColumn = new ColumnAction();
        $actionColumn->setLabel('Acciones');
        $actionColumn->setWidth(39); // Aumentado para que los 3 botones (Ver, Editar, Eliminar) entren en una línea
        
        // Acción Ver - Mejorada con Bootstrap
        $actionView = new ActionButton();
        $actionView->setLabel('<i class="fas fa-eye"></i> <span class="d-none d-md-inline">Ver</span>');
        $actionView->setLink('/cvs/view/:cv_id');
        $actionView->setAttribute('class', 'btn btn-action btn-view btn-sm');
        $actionView->setAttribute('title', 'Ver detalles del CV');
        $actionView->setAttribute('data-bs-toggle', 'tooltip');
        $actionView->setAttribute('data-bs-placement', 'top');
        $actionColumn->addAction($actionView);

        // Acción Editar - Mejorada con Bootstrap
        $actionEdit = new ActionButton();
        $actionEdit->setLabel('<i class="fas fa-edit"></i> <span class="d-none d-md-inline">Editar</span>');
        $actionEdit->setLink('/cvs/edit/:cv_id');
        $actionEdit->setAttribute('class', 'btn btn-action btn-edit btn-sm');
        $actionEdit->setAttribute('title', 'Editar información del CV');
        $actionEdit->setAttribute('data-bs-toggle', 'tooltip');
        $actionEdit->setAttribute('data-bs-placement', 'top');
        $actionColumn->addAction($actionEdit);

        // Acción Eliminar - Mejorada con Bootstrap
        $actionDelete = new ActionButton();
        $actionDelete->setLabel('<i class="fas fa-trash"></i> <span class="d-none d-md-inline">Eliminar</span>');
        $actionDelete->setLink('/cvs/delete/:cv_id');
        $actionDelete->setAttribute('class', 'btn btn-action btn-delete btn-sm');
        $actionDelete->setAttribute('title', 'Eliminar CV permanentemente');
        $actionDelete->setAttribute('data-bs-toggle', 'tooltip');
        $actionDelete->setAttribute('data-bs-placement', 'top');
        $actionDelete->setAttribute('onclick', 'return confirm("¿Está seguro de que desea eliminar este CV? Esta acción no se puede deshacer.")');
        $actionColumn->addAction($actionDelete);
        
        // Agregar la columna de acciones al datagrid
        $datagrid->addColumn($actionColumn);
    }

    private function configureCvToolbar(Datagrid $datagrid)
    {
        // Configurar plantilla personalizada para la toolbar
        $datagrid->setToolbarTemplate('zfc-datagrid/toolbar/cv-toolbar');
        
        // Pasar variables adicionales a la plantilla si es necesario
        $toolbarVariables = [
            'addCvUrl' => $this->url()->fromRoute('cvs/add'),
            'userManagementUrl' => $this->url()->fromRoute('user'),
            'title' => 'Gestión de CVs'
        ];
        $datagrid->setToolbarTemplateVariables($toolbarVariables);
    }

    /**
     * Acción AJAX para búsqueda avanzada de skills
     * Responde con JSON para autocompletado dinámico
     */
    public function searchSkillsAction()
    {
        $request = $this->getRequest();
        
        // Verificar que sea una petición AJAX
        if (!$request->isXmlHttpRequest()) {
            return $this->getResponse()->setStatusCode(400);
        }
        
        // Obtener término de búsqueda
        $searchTerm = $request->getQuery('q', '');
        $limit = (int) $request->getQuery('limit', 20);
        
        // Validar límite
        if ($limit > 50) {
            $limit = 50;
        }
        
        $results = [];
        
        if (!empty($searchTerm) && $this->skillTable) {
            try {
                // Usar búsqueda FULLTEXT
                $skills = $this->skillTable->searchSkillsFulltext($searchTerm, $limit);
                
                // Formatear resultados para Select2
                foreach ($skills as $skill) {
                    $results[] = [
                        'id' => $skill['id'],
                        'text' => $skill['nombre'],
                        'score' => $skill['score'] ?? 1.0
                    ];
                }
            } catch (\Exception $e) {
                // Log error pero no exponer detalles al cliente
                error_log('Error en búsqueda de skills: ' . $e->getMessage());
                $results = [];
            }
        }
        
        // Respuesta JSON compatible con Select2
        $response = [
            'results' => $results,
            'pagination' => [
                'more' => count($results) >= $limit
            ]
        ];
        
        // Configurar respuesta JSON
        $jsonResponse = new \Laminas\View\Model\JsonModel($response);
        $jsonResponse->setTerminal(true);
        
        return $jsonResponse;
    }
}