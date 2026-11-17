<!DOCTYPE html>
<html lang="<?php echo e(app()->getLocale()); ?>">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
  <meta name="description" content="Teras Ngulik — belajar coding, data, keamanan digital, dan riset lewat praktik interaktif.">
  <meta name="theme-color" content="#2F55D4">
  <link rel="icon" type="image/png" href="/assets/logo.png">
  <?php echo app('Illuminate\Foundation\Vite')->reactRefresh(); ?>
  <?php echo app('Illuminate\Foundation\Vite')('resources/js/app.tsx'); ?>
  <?php $__inertiaSsrResponse = app(\Inertia\Ssr\SsrState::class)->setPage($page)->dispatch();  if ($__inertiaSsrResponse) { echo $__inertiaSsrResponse->head; } ?>
</head>
<body><?php $__inertiaSsrResponse = app(\Inertia\Ssr\SsrState::class)->setPage($page)->dispatch();  if ($__inertiaSsrResponse) { echo $__inertiaSsrResponse->body; } else { ?><script data-page="app" type="application/json"><?php echo json_encode($page, JSON_HEX_TAG); ?></script><div id="app"></div><?php } ?></body>
</html>
<?php /**PATH D:\laragon\www\Teras Ngulik\resources\views/app.blade.php ENDPATH**/ ?>