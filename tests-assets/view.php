<?php

header("X-Test-Header: New Test Header");
?>
<html>
    <?=$view->include(__DIR__ . '/head.php', ['style' => 'styles.css'])?>
    <body>
        <h1><?=$title?></h1>
        <p><?=$content?></p>
    </body>
</html>
