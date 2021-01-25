<?php

declare(strict_types=1);

namespace PERUCOVID;

function autoload(string $className) { //{{{
    $parts = explode('\\', $className);
    $path = sprintf('%sincludes/%s.php', BASE_PATH, end($parts));
    
    require_once $path;
}
//}}}

spl_autoload_register('\PERUCOVID\autoload');

?>
