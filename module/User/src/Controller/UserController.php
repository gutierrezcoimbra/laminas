<?php

declare(strict_types=1);

namespace User\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Laminas\Http\Request;
use User\Model\UserTable;
use User\Form\UserForm;
use User\Model\User;

class UserController extends AbstractActionController
{
    private $userTable;

    public function __construct(UserTable $userTable)
    {
        $this->userTable = $userTable;
    }

    public function indexAction()
    {
        return new ViewModel([
            'users' => $this->userTable->getActiveUsers(),
        ]);
    }
    
    public function viewAction()
    {
        $id = (int) $this->params()->fromRoute('id', 0);
        if ($id === 0) {
            return $this->redirect()->toRoute('user');
        }
        $user = $this->userTable->getUser($id);

        return new ViewModel([
            'user' => $user,
        ]);
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
}
