<?php

header("X-Test-Header: New Test Header");
$view->header('X-Test-Header: Other Header');
?>
<html>
    <?=$view->include('head', ['style' => 'styles.css'])?>
    <body>
        <?=$layout->section('content')?>
    </body>
</html>
