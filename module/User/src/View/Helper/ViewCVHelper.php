<?php

declare(strict_types=1);

namespace User\View\Helper;

use Laminas\View\Helper\AbstractHelper;
use User\Model\CvTable;

class ViewCVHelper extends AbstractHelper
{
    private $cvTable;
    private $idCv;
    private $options = [];

    public function __construct(CvTable $cvTable)
    {
        $this->cvTable = $cvTable;
    }

    public function __invoke($idCv = null)
    {
        // Si se llama sin parámetros, devolver la instancia para encadenamiento
        if ($idCv === null) {
            return $this;
        }
        
        // Si se llama con parámetros, configurar y devolver la instancia
        $this->idCv = $idCv;
        $this->resetOptions();
        return $this;
    }
    
    /**
     * Resetear opciones a valores por defecto
     */
    private function resetOptions()
    {
        $this->options = [
            'showButtons' => true,
            'showPrintButton' => true,
            'showEditButton' => true,
        ];
    }
    
    /**
     * Configurar si mostrar la sección de botones
     */
    public function showButtons(bool $show = true): self
    {
        $this->options['showButtons'] = $show;
        return $this;
    }
    
    /**
     * Configurar si mostrar el botón de imprimir
     */
    public function showPrintButton(bool $show = true): self
    {
        $this->options['showPrintButton'] = $show;
        return $this;
    }
    
    /**
     * Configurar si mostrar el botón de editar
     */
    public function showEditButton(bool $show = true): self
    {
        $this->options['showEditButton'] = $show;
        return $this;
    }
    
    /**
     * Renderizar el CV
     */
    public function render(): string
    {
        try {
            // Obtener los datos del CV con información del usuario
            $cv = $this->cvTable->getCvWithUser($this->idCv);
            
            // Renderizar el template usando el view renderer
            return $this->view->render('view-helpers/cv-display', [
                'cv' => $cv,
                'options' => $this->options
            ]);

        } catch (\Exception $e) {
            return '<div class="alert alert-danger">Error al cargar el CV: ' . $this->view->escapeHtml($e->getMessage()) . '</div>';
        }
    }
    
    /**
     * Método mágico para convertir a string automáticamente
     */
    public function __toString(): string
    {
        return $this->render();
    }
}