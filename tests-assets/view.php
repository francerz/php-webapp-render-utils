<?=$layout = $view->loadLayout('layout')?>
<!-- This comment is ignored because out of layout section -->
<?=$layout->startSection('content')?>
<h1><?=$title?></h1>
<p><?=$content?></p>
<?=$layout->endSection()?>
