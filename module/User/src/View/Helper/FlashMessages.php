<?php

declare(strict_types=1);

namespace User\View\Helper;

use Laminas\View\Helper\AbstractHelper;
use Laminas\Mvc\Plugin\FlashMessenger\FlashMessenger;

class FlashMessages extends AbstractHelper
{
    public function __invoke()
    {
        $flashMessenger = new FlashMessenger();
        $html = '';
        
        // Mensajes de éxito
        if ($flashMessenger->hasSuccessMessages()) {
            foreach ($flashMessenger->getSuccessMessages() as $message) {
                $html .= sprintf(
                    '<div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle"></i> %s
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>',
                    $this->getView()->escapeHtml($message)
                );
            }
        }
        
        // Mensajes de error
        if ($flashMessenger->hasErrorMessages()) {
            foreach ($flashMessenger->getErrorMessages() as $message) {
                $html .= sprintf(
                    '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle"></i> %s
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>',
                    $this->getView()->escapeHtml($message)
                );
            }
        }
        
        // Mensajes de información
        if ($flashMessenger->hasInfoMessages()) {
            foreach ($flashMessenger->getInfoMessages() as $message) {
                $html .= sprintf(
                    '<div class="alert alert-info alert-dismissible fade show" role="alert">
                        <i class="fas fa-info-circle"></i> %s
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>',
                    $this->getView()->escapeHtml($message)
                );
            }
        }
        
        // Mensajes de advertencia
        if ($flashMessenger->hasWarningMessages()) {
            foreach ($flashMessenger->getWarningMessages() as $message) {
                $html .= sprintf(
                    '<div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle"></i> %s
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>',
                    $this->getView()->escapeHtml($message)
                );
            }
        }
        
        return $html;
    }
} 