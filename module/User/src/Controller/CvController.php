<?php

declare(strict_types=1);

namespace User\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use User\Model\CvTable;
use User\Model\UserTable;

class CvController extends AbstractActionController
{
    private $cvTable;
    private $userTable;
    private $skillTable;

    public function __construct(CvTable $cvTable, UserTable $userTable, $skillTable = null)
    {
        $this->cvTable = $cvTable;
        $this->userTable = $userTable;
        $this->skillTable = $skillTable;
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

    // Nuevo método para vista general de todos los CVs
    public function allAction()
    {
        try {
            $allCvs = $this->cvTable->getAllCvsWithUsers();
            
            return new ViewModel([
                'cvs' => $allCvs,
            ]);
        } catch (\Exception $e) {
            $this->flashMessenger()->addErrorMessage('Error al cargar los CVs');
            return $this->redirect()->toRoute('user');
        }
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
}