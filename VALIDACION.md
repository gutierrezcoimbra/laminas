# Sistema de Validación - Módulo User

## Descripción General

Se ha implementado un sistema completo de validación para el módulo User utilizando los componentes de Laminas Framework. El sistema incluye validación de formularios, filtros de datos, validadores personalizados y manejo de mensajes de error.

## Componentes Implementados

### 1. Formulario Principal (`UserForm`)

**Ubicación:** `module/User/src/Form/UserForm.php`

**Características:**
- Formulario completo para crear y editar usuarios
- Validación integrada con InputFilter
- Campos: ID (oculto), Nombre, Apellidos, Email, Fecha de Nacimiento
- Botones de envío y cancelación

**Campos del formulario:**
- **Nombre:** Requerido, 2-50 caracteres, filtrado y formateado
- **Apellidos:** Requerido, 2-100 caracteres, filtrado y formateado
- **Email:** Requerido, formato válido, único en la base de datos
- **Fecha de Nacimiento:** Requerido, formato Y-m-d, edad mínima 18 años

### 2. Validadores Personalizados

#### UniqueEmail (`module/User/src/Validator/UniqueEmail.php`)
- Valida que el email sea único en la base de datos
- Permite excluir un ID específico (útil para edición)
- Mensaje personalizado en español

#### MinimumAge (`module/User/src/Validator/MinimumAge.php`)
- Valida la edad mínima requerida (por defecto 18 años)
- Configurable a través de opciones
- Maneja fechas inválidas

### 3. Filtros Personalizados

#### NameFilter (`module/User/src/Filter/NameFilter.php`)
- Limpia y formatea nombres y apellidos
- Elimina espacios extra y caracteres no deseados
- Capitaliza la primera letra de cada palabra
- Soporta caracteres especiales españoles (á, é, í, ó, ú, ñ)

### 4. Helper de Vista

#### FlashMessages (`module/User/src/View/Helper/FlashMessages.php`)
- Muestra mensajes flash de manera elegante
- Soporta diferentes tipos: éxito, error, información, advertencia
- Incluye iconos de Font Awesome
- Botones de cierre automático

## Validaciones Implementadas

### Validaciones de Nombre y Apellidos
```php
// Filtros aplicados
- StringTrim: Elimina espacios al inicio y final
- StripTags: Elimina etiquetas HTML
- NameFilter: Formatea y limpia el texto

// Validaciones
- NotEmpty: Campo obligatorio
- StringLength: Longitud entre 2-50 caracteres (nombre) o 2-100 (apellidos)
```

### Validaciones de Email
```php
// Filtros aplicados
- StringTrim: Elimina espacios
- StringToLower: Convierte a minúsculas

// Validaciones
- NotEmpty: Campo obligatorio
- EmailAddress: Formato válido de email
- UniqueEmail: Email único en la base de datos
```

### Validaciones de Fecha de Nacimiento
```php
// Filtros aplicados
- StringTrim: Elimina espacios

// Validaciones
- NotEmpty: Campo obligatorio
- Date: Formato válido (Y-m-d)
- MinimumAge: Edad mínima de 18 años
```

## Uso en el Controlador

### Crear Usuario
```php
public function addAction()
{
    $form = new UserForm($this->userTable);
    $form->get('submit')->setValue('Crear Usuario');
    
    $request = $this->getRequest();
    
    if (!$request->isPost()) {
        return new ViewModel(['form' => $form]);
    }
    
    $form->setData($request->getPost());
    
    if (!$form->isValid()) {
        return new ViewModel([
            'form' => $form,
            'errors' => $form->getMessages(),
        ]);
    }
    
    $user = new User();
    $user->exchangeArray($form->getData());
    $this->userTable->saveUser($user);
    
    $this->flashMessenger()->addSuccessMessage('Usuario creado exitosamente.');
    return $this->redirect()->toRoute('user');
}
```

### Editar Usuario
```php
public function editAction()
{
    $id = (int) $this->params()->fromRoute('id', 0);
    $user = $this->userTable->getUser($id);
    
    $form = new UserForm($this->userTable, $id); // Pasa el ID para excluir en validación
    $form->bind($user);
    
    // ... resto del código similar a addAction
}
```

## Vistas con Validación

### Mostrar Errores en Vistas
```php
<?= $this->formElement($form->get('nombre')); ?>
<?= $this->formElementErrors($form->get('nombre'), ['class' => 'text-danger small']); ?>
```

### Mostrar Mensajes Flash
```php
<?php if (isset($this->flashMessenger)): ?>
    <?php foreach ($this->flashMessenger()->getCurrentSuccessMessages() as $message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= $this->escapeHtml($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
```

## Configuración de Rutas

Las rutas están configuradas en `module/User/config/module.config.php`:

```php
'child_routes' => [
    'add' => [
        'type' => Literal::class,
        'options' => [
            'route' => '/add',
            'defaults' => [
                'controller' => Controller\UserController::class,
                'action' => 'add',
            ],
        ],
    ],
    'edit' => [
        'type' => Segment::class,
        'options' => [
            'route' => '/edit/:id',
            'defaults' => [
                'controller' => Controller\UserController::class,
                'action' => 'edit',
            ],
            'constraints' => [
                'id' => '[0-9]+',
            ],
        ],
    ],
    // ... más rutas
],
```

## Características de Seguridad

1. **Escape HTML:** Todos los datos se escapan antes de mostrar en vistas
2. **Validación del lado del servidor:** Todas las validaciones se ejecutan en el servidor
3. **Filtrado de datos:** Los datos se limpian antes de procesarse
4. **Validación de tipos:** Se valida que los datos sean del tipo correcto
5. **Mensajes de error personalizados:** Mensajes claros en español

## Extensibilidad

El sistema está diseñado para ser fácilmente extensible:

1. **Nuevos validadores:** Crear clases que extiendan `AbstractValidator`
2. **Nuevos filtros:** Crear clases que extiendan `AbstractFilter`
3. **Nuevos campos:** Agregar elementos al formulario y sus validaciones correspondientes
4. **Configuración:** Modificar opciones de validación sin cambiar el código

## Próximos Pasos

Para mejorar el sistema de validación, se podría considerar:

1. **Validación del lado del cliente:** Agregar JavaScript para validación en tiempo real
2. **Validación AJAX:** Validar campos sin recargar la página
3. **Validación de archivos:** Para futuras funcionalidades de subida de archivos
4. **Validación condicional:** Validaciones que dependan de otros campos
5. **Internacionalización:** Soporte para múltiples idiomas en mensajes de error 