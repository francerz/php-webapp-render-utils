<?php
/** @var \Francerz\WebappRenderUtils\View */
$view = $view;
$layout = $view->loadLayout('layout');
?>
<!-- This comment is ignored because out of layout section -->
<?php $layout->startSection('content'); ?>
<h1><?=$title?></h1>
<p><?=$content?></p>
<?php $layout->endSection(); ?>
