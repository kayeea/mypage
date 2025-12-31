<?php
// demo_sse.php

header("Content-Type: text/event-stream");
header("Cache-Control: no-cache");

echo "data: Server time: " . date("h:i:s A") . "\n\n";
flush();
?>
