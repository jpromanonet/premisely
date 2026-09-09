<?php
$success = flash('success');
$error = flash('error');
?>
<?php if ($success): ?>
<div class="alert alert--success" role="status"><?= e((string) $success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert alert--error" role="alert"><?= e((string) $error) ?></div>
<?php endif; ?>
