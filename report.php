<?php
// Legacy entry point kept for old bookmarks; the app now runs through index.php (MVC router).
header('Location: index.php?r=booking/history', true, 301);
exit;
