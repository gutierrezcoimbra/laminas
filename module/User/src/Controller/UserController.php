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
        
        // Configurar la fuente de datos usando la vista personalizada
        $adapter = $this->userTable->getTableGateway()->getAdapter();
        $sql = new Sql($adapter);
        $select = $sql->select();
        $select->from('users_datagrid_view')
               ->order(['user_id DESC']);
        
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
        $colId = new ColumnSelect('user_id');
        $colId->setIdentity(true);
        $colId->setLabel('ID');
        $colId->setWidth(5);
        $colId->setType(new Type\Number());
        $datagrid->addColumn($colId);

        // Columna Nombre Completo (combinado)
        $colNombreCompleto = new ColumnSelect('nombre_completo');
        $colNombreCompleto->setLabel('Nombre Completo');
        $colNombreCompleto->setWidth(20);
        $colNombreCompleto->setSortDefault(1, 'ASC');
        $colNombreCompleto->setFilterDefaultOperation(Filter::LIKE);
        $datagrid->addColumn($colNombreCompleto);

        // Columna Email
        $colEmail = new ColumnSelect('email');
        $colEmail->setLabel('Email');
        $colEmail->setWidth(18);
        $colEmail->setFilterDefaultOperation(Filter::LIKE);
        $datagrid->addColumn($colEmail);

        // Columna Edad (calculada desde la vista)
        $colEdad = new ColumnSelect('edad');
        $colEdad->setLabel('Edad');
        $colEdad->setWidth(8);
        $colEdad->setType(new Type\Number());
        $colEdad->setFilterDefaultOperation(Filter::EQUAL);
        $datagrid->addColumn($colEdad);

      

        // Columna Profesiones
        $colProfesiones = new ColumnSelect('profesiones');
        $colProfesiones->setLabel('Profesiones');
        $colProfesiones->setWidth(25);
        $colProfesiones->setFilterDefaultOperation(Filter::LIKE);
        $datagrid->addColumn($colProfesiones);

     
    }

    private function configureUserActions(Datagrid $datagrid)
    {
        // Crear columna de acciones
        $actionColumn = new ColumnAction();
        $actionColumn->setLabel('Acciones');
        $actionColumn->setWidth(80);
        
        // Acción Ver - Sin tooltips para evitar errores de Popper
        $actionView = new ActionButton();
        $actionView->setLabel('<i class="fas fa-eye"></i> <span class="d-none d-md-inline">Ver</span>');
        $actionView->setLink('/user/view/:user_id');
        $actionView->setAttribute('class', 'btn btn-action btn-view btn-sm');
        $actionView->setAttribute('title', 'Ver detalles del usuario');
        $actionColumn->addAction($actionView);

        // Acción Editar - Sin tooltips para evitar errores de Popper
        $actionEdit = new ActionButton();
        $actionEdit->setLabel('<i class="fas fa-edit"></i> <span class="d-none d-md-inline">Editar</span>');
        $actionEdit->setLink('/user/edit/:user_id');
        $actionEdit->setAttribute('class', 'btn btn-action btn-edit btn-sm');
        $actionEdit->setAttribute('title', 'Editar información del usuario');
        $actionColumn->addAction($actionEdit);

        // Acción CVs - Sin tooltips para evitar errores de Popper
        $actionCvs = new ActionButton();
        $actionCvs->setLabel('<i class="fas fa-file-alt"></i> <span class="d-none d-md-inline">CVs</span>');
        $actionCvs->setLink('/user/:user_id/cv');
        $actionCvs->setAttribute('class', 'btn btn-action btn-success btn-sm');
        $actionCvs->setAttribute('title', 'Ver y gestionar CVs del usuario');
        $actionColumn->addAction($actionCvs);

        // Acción Eliminar - Sin tooltips para evitar errores de Popper
        $actionDelete = new ActionButton();
        $actionDelete->setLabel('<i class="fas fa-trash"></i> <span class="d-none d-md-inline">Eliminar</span>');
        $actionDelete->setLink('/user/delete/:user_id');
        $actionDelete->setAttribute('class', 'btn btn-action btn-delete btn-sm');
        $actionDelete->setAttribute('title', 'Eliminar usuario permanentemente');
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

    public function getNamesAction()
    {
        $request = $this->getRequest();
        
        if (!$request->isXmlHttpRequest() || !$request->isPost()) {
            return $this->getResponse()->setStatusCode(400);
        }

        $data = json_decode($request->getContent(), true);
        $userIds = $data['userIds'] ?? [];
        
        if (empty($userIds) || !is_array($userIds)) {
            return $this->getResponse()->setStatusCode(400);
        }

        try {
            $users = [];
            foreach ($userIds as $userId) {
                $userId = (int) $userId;
                if ($userId > 0) {
                    try {
                        $user = $this->userTable->getUser($userId);
                        $users[$userId] = [
                            'nombre' => $user->nombre,
                            'apellidos' => $user->apellidos,
                            'email' => $user->email
                        ];
                    } catch (\Exception $e) {
                        // Usuario no encontrado, continuar
                        continue;
                    }
                }
            }

            $response = $this->getResponse();
            $response->getHeaders()->addHeaderLine('Content-Type', 'application/json');
            $response->setContent(json_encode([
                'success' => true,
                'users' => $users
            ]));
            
            return $response;
            
        } catch (\Exception $e) {
            $response = $this->getResponse();
            $response->setStatusCode(500);
            $response->getHeaders()->addHeaderLine('Content-Type', 'application/json');
            $response->setContent(json_encode([
                'success' => false,
                'error' => 'Error al obtener usuarios'
            ]));
            
            return $response;
        }
    }
}
