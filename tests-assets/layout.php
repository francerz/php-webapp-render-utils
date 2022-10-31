<?php

header("X-Test-Header: New Test Header");
$view->header('X-Test-Header: Other Header');
?>
<html>
    <?=$view->include(__DIR__ . '/head.php', ['style' => 'styles.css'])?>
    <body>
        <?=$layout->section('content')?>
    </body>
</html>
