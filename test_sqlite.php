<?php
if (class_exists('SQLite3')) {
    echo "SQLite is available. Version: " . SQLite3::version()['versionString'];
} else {
    echo "SQLite is NOT available on this server.";
}
?>