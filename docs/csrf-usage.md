# CSRF Protection Usage

## Setup

```php
use Compose\Web\Form\Form;
use Compose\Web\Form\DTO\Field;
use Compose\Http\Session\Session;
use Compose\Http\Session\Storage\NativeSessionStorage;
use Compose\Web\Security\SessionCsrfTokenProvider;

// Boot the Compose session (NativeSessionStorage will call session_start())
$session = new Session(new NativeSessionStorage());

// Create CSRF provider with the Compose session instance
$csrfProvider = new SessionCsrfTokenProvider($session);

// Create form and attach CSRF provider
$form = (new Form('/contact'))
    ->setCsrfProvider($csrfProvider)
    ->setFields(Field::createMany([
        ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true],
        ['name' => 'message', 'label' => 'Message', 'type' => 'textarea', 'required' => true],
    ]));
```

## Controller

```php
// Process request
$submission = $form->processRequest($request);

if ($submission->isSubmitted() && $submission->isValid()) {
    $data = $submission->getValues();
    // Form is valid AND CSRF token is valid
    // Safe to process
} else {
    // Either not submitted, validation failed, or CSRF failed
    $errors = $submission->getErrors();
    if (isset($errors['_csrf'])) {
        // CSRF validation failed
    }
}

// Pass to template
return $view->render('contact', ['form' => $submission]);
```

## Template

```php
<form action="<?= $this->e($form->getAction()) ?>"
      method="<?= $this->e(strtolower($form->getMethod())) ?>">
    
    <?php $idField = $form->getFormIdField(); ?>
    <input type="hidden"
           name="<?= $this->e($idField['name']) ?>"
           value="<?= $this->e($idField['value']) ?>">
    
    <?php if ($csrfField = $form->getCsrfField()): ?>
        <input type="hidden"
               name="<?= $this->e($csrfField['name']) ?>"
               value="<?= $this->e($csrfField['value']) ?>">
    <?php endif; ?>
    
    <?php foreach ($form->getFields() as $field): ?>
        <!-- render fields -->
    <?php endforeach; ?>
    
    <button type="submit">Submit</button>
</form>
```

## How It Works

1. **First Request (GET):** CSRF token is generated and stored in session, embedded in hidden field
2. **Form Submit (POST):** Token from request is validated against session
3. **Validation:** If token is missing or invalid, form processing is skipped and CSRF error is added
4. **New Token:** A fresh token is always generated for the next render (prevents replay attacks)

## Security Notes

- Tokens are session-bound (different users get different tokens)
- Uses `hash_equals()` for timing-safe comparison
- Tokens are regenerated on each form render
- CSRF validation only runs for submitted forms (not on initial GET)
