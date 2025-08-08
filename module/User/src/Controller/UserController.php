<?php

declare(strict_types=1);

namespace User\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Laminas\Http\Request;
use User\Model\UserTable;
use User\Model\CvTable;
use User\Form\UserForm;
use User\Model\User;
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

class UserController extends AbstractActionController
{
    private $userTable;
    private $cvTable;

    public function __construct(UserTable $userTable, CvTable $cvTable = null)
    {
        $this->userTable = $userTable;
        $this->cvTable = $cvTable;
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
        $datagrid->setTitle('Lista de Usuarios');
        $datagrid->setDefaultItemsPerPage(25);
        
        // Configurar la fuente de datos usando Laminas\Db\Sql\Select
        $adapter = $this->userTable->getTableGateway()->getAdapter();
        $sql = new Sql($adapter);
        $select = $sql->select();
        $select->from('users')
               ->columns(['id', 'nombre', 'apellidos', 'email', 'fechaNacimiento'])
               ->where(['deletedAt' => 0])
               ->order(['id DESC']);
        
        $datagrid->setDataSource($select, $adapter);
        
        // Configurar columnas
        $this->configureUserColumns($datagrid);
        
        // Configurar acciones
        $this->configureUserActions($datagrid);
        
        // Agregar botón para crear nuevo usuario
        $this->configureUserToolbar($datagrid);
        
        // Renderizar y obtener la respuesta del datagrid
        return $datagrid->getResponse();
    }
    
    public function viewAction()
    {
        $id = (int) $this->params()->fromRoute('id', 0);
        if ($id === 0) {
            return $this->redirect()->toRoute('user');
        }
        
        try {
            $user = $this->userTable->getUser($id);
            $cvs = [];
            
            // Si tenemos CvTable disponible, obtenemos los CVs
            if ($this->cvTable) {
                $cvs = $this->cvTable->getCvsByUserId($id);
            }

            return new ViewModel([
                'user' => $user,
                'cvs' => $cvs,
            ]);
        } catch (\Exception $e) {
            $this->flashMessenger()->addErrorMessage('Usuario no encontrado');
            return $this->redirect()->toRoute('user');
        }
    }
    
    public function addAction()
    {
        $form = new UserForm($this->userTable);
        $form->get('submit')->setValue('Crear Usuario');
        
        $request = $this->getRequest();
        
        if (!$request->isPost()) {
            return new ViewModel(['form' => $form]);
        }
        
        // Debug: Ver qué datos se están enviando
        $postData = $request->getPost();
        error_log('POST Data: ' . print_r($postData->toArray(), true));
        
        $form->setData($postData);
        
        if (!$form->isValid()) {
            error_log('Form validation failed: ' . print_r($form->getMessages(), true));
            return new ViewModel([
                'form' => $form,
                'errors' => $form->getMessages(),
            ]);
        }
        
        $formData = $form->getData();
        error_log('Form data after validation: ' . print_r($formData, true));
        
        $user = new User();
        $user->exchangeArray($formData);
        
        try {
            $this->userTable->saveUser($user);
            $this->flashMessenger()->addSuccessMessage('Usuario creado exitosamente.');
        } catch (\Exception $e) {
            error_log('Error saving user: ' . $e->getMessage());
            $this->flashMessenger()->addErrorMessage('Error al crear el usuario: ' . $e->getMessage());
        }
        
        return $this->redirect()->toRoute('user');
    }
    
    public function editAction()
    {
        $id = (int) $this->params()->fromRoute('id', 0);
        if ($id === 0) {
            return $this->redirect()->toRoute('user');
        }
        
        try {
            $user = $this->userTable->getUser($id);
        } catch (\Exception $e) {
            $this->flashMessenger()->addErrorMessage('Usuario no encontrado.');
            return $this->redirect()->toRoute('user');
        }
        
        $form = new UserForm($this->userTable, $id);
        $form->bind($user);
        $form->get('submit')->setValue('Actualizar Usuario');
        
        $request = $this->getRequest();
        
        if (!$request->isPost()) {
            return new ViewModel([
                'id' => $id,
                'form' => $form,
            ]);
        }
        
        $form->setData($request->getPost());
        
        if (!$form->isValid()) {
            return new ViewModel([
                'id' => $id,
                'form' => $form,
                'errors' => $form->getMessages(),
            ]);
        }
        
        $this->userTable->saveUser($user);
        
        // Mensaje de éxito
        $this->flashMessenger()->addSuccessMessage('Usuario actualizado exitosamente.');
        
        return $this->redirect()->toRoute('user');
    }
    
    public function deleteAction()
    {
        $id = (int) $this->params()->fromRoute('id', 0);
        if ($id === 0) {
            return $this->redirect()->toRoute('user');
        }
        
        $request = $this->getRequest();
        
        if ($request->isPost()) {
            $del = $request->getPost('del', 'No');
            
            if ($del === 'Sí') {
                $id = (int) $request->getPost('id');
                
                try {
                    $user = $this->userTable->getUser($id);
                    $user->softDelete();
                    $this->userTable->saveUser($user);
                    
                    $this->flashMessenger()->addSuccessMessage('Usuario eliminado exitosamente.');
                } catch (\Exception $e) {
                    $this->flashMessenger()->addErrorMessage('Error al eliminar el usuario.');
                }
            }
            
            return $this->redirect()->toRoute('user');
        }
        
        try {
            $user = $this->userTable->getUser($id);
        } catch (\Exception $e) {
            $this->flashMessenger()->addErrorMessage('Usuario no encontrado.');
            return $this->redirect()->toRoute('user');
        }
        
        return new ViewModel([
            'id' => $id,
            'user' => $user,
        ]);
    }

    private function configureUserColumns(Datagrid $datagrid)
    {
        // Columna ID (oculta pero necesaria para los botones de acción)
        $colId = new ColumnSelect('id');
        $colId->setIdentity(true);
        $colId->setLabel('ID');
        $colId->setWidth(5);
        $colId->setType(new Type\Number());
        $datagrid->addColumn($colId);

                // Columna Nombre Completo
        $colNombre = new ColumnSelect('nombre');
        $colNombre->setLabel('Nombre');
        $colNombre->setWidth(15);
        $colNombre->setSortDefault(1, 'ASC');
        $colNombre->setFilterDefaultOperation(Filter::LIKE); // Filtro tipo "contiene"
        $datagrid->addColumn($colNombre);

        // Columna Apellidos
        $colApellidos = new ColumnSelect('apellidos');
        $colApellidos->setLabel('Apellidos');
        $colApellidos->setWidth(15);
        $colApellidos->setFilterDefaultOperation(Filter::LIKE); // Filtro tipo "contiene"
        $datagrid->addColumn($colApellidos);

        // Columna Email
        $colEmail = new ColumnSelect('email');
        $colEmail->setLabel('Email');
        $colEmail->setWidth(15);
        $colEmail->setFilterDefaultOperation(Filter::LIKE); // Filtro tipo "contiene"
        $datagrid->addColumn($colEmail);

        // Columna Fecha de Nacimiento
        $colFechaNacimiento = new ColumnSelect('fechaNacimiento');
        $colFechaNacimiento->setLabel('Fecha Nacimiento');
        $colFechaNacimiento->setWidth(17);
        // Formato fuente: Y-m-d (como viene de la DB), formato salida: MEDIUM (localizado)
        $dateTimeType = new Type\DateTime('Y-m-d', \IntlDateFormatter::MEDIUM, \IntlDateFormatter::NONE);
        $colFechaNacimiento->setType($dateTimeType);
        // Configurar filtro personalizado para fechas
        $colFechaNacimiento->setFilterDefaultOperation(Filter::GREATER_EQUAL);
        $datagrid->addColumn($colFechaNacimiento);


    }

    private function configureUserActions(Datagrid $datagrid)
    {
        // Crear columna de acciones
        $actionColumn = new ColumnAction();
        $actionColumn->setLabel('Acciones');
        $actionColumn->setWidth(38);
        
        // Acción Ver - Mejorada con Bootstrap
        $actionView = new ActionButton();
        $actionView->setLabel('<i class="fas fa-eye"></i> <span class="d-none d-md-inline">Ver</span>');
        $actionView->setLink('/user/view/:id');
        $actionView->setAttribute('class', 'btn btn-action btn-view btn-sm');
        $actionView->setAttribute('title', 'Ver detalles del usuario');
        $actionView->setAttribute('data-bs-toggle', 'tooltip');
        $actionView->setAttribute('data-bs-placement', 'top');
        $actionColumn->addAction($actionView);

        // Acción Editar - Mejorada con Bootstrap
        $actionEdit = new ActionButton();
        $actionEdit->setLabel('<i class="fas fa-edit"></i> <span class="d-none d-md-inline">Editar</span>');
        $actionEdit->setLink('/user/edit/:id');
        $actionEdit->setAttribute('class', 'btn btn-action btn-edit btn-sm');
        $actionEdit->setAttribute('title', 'Editar información del usuario');
        $actionEdit->setAttribute('data-bs-toggle', 'tooltip');
        $actionEdit->setAttribute('data-bs-placement', 'top');
        $actionColumn->addAction($actionEdit);

        // Acción CVs - Mejorada con Bootstrap
        $actionCvs = new ActionButton();
        $actionCvs->setLabel('<i class="fas fa-file-alt"></i> <span class="d-none d-md-inline">CVs</span>');
        $actionCvs->setLink('/user/:id/cv');
        $actionCvs->setAttribute('class', 'btn btn-action btn-success btn-sm');
        $actionCvs->setAttribute('title', 'Ver y gestionar CVs del usuario');
        $actionCvs->setAttribute('data-bs-toggle', 'tooltip');
        $actionCvs->setAttribute('data-bs-placement', 'top');
        $actionColumn->addAction($actionCvs);

        // Acción Eliminar - Mejorada con Bootstrap
        $actionDelete = new ActionButton();
        $actionDelete->setLabel('<i class="fas fa-trash"></i> <span class="d-none d-md-inline">Eliminar</span>');
        $actionDelete->setLink('/user/delete/:id');
        $actionDelete->setAttribute('class', 'btn btn-action btn-delete btn-sm');
        $actionDelete->setAttribute('title', 'Eliminar usuario permanentemente');
        $actionDelete->setAttribute('data-bs-toggle', 'tooltip');
        $actionDelete->setAttribute('data-bs-placement', 'top');
        $actionDelete->setAttribute('onclick', 'return confirm("¿Está seguro de que desea eliminar este usuario? Esta acción no se puede deshacer.")');
        $actionColumn->addAction($actionDelete);
        
        // Agregar la columna de acciones al datagrid
        $datagrid->addColumn($actionColumn);
    }

    private function configureUserToolbar(Datagrid $datagrid)
    {
        // Configurar plantilla personalizada para la toolbar
        $datagrid->setToolbarTemplate('zfc-datagrid/toolbar/custom-toolbar');
        
        // Pasar variables adicionales a la plantilla si es necesario
        $toolbarVariables = [
            'addUserUrl' => $this->url()->fromRoute('user/add'),
            'title' => 'Gestión de Usuarios'
        ];
        $datagrid->setToolbarTemplateVariables($toolbarVariables);
    }
}
