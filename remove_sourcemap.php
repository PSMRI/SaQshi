<?php
$path = './assets/js/';

function removeSourceMap($path){
    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));

    foreach ($rii as $file) {
        if (!$file->isDir() && pathinfo($file->getPathname(), PATHINFO_EXTENSION) == "js") {
            $filepath = $file->getPathname();
            $contents = file($filepath);

            $changed = false;
            foreach($contents as $key => $line){
                if (strpos($line, 'sourceMappingURL') !== false) {
                    unset($contents[$key]);
                    $changed = true;
                }
            }

            if ($changed) {
                file_put_contents($filepath, implode("", $contents));
                echo "Fixed source map line in: $filepath\n";
            }
        }
    }
}

removeSourceMap($path);
echo "\nCompleted. All sourceMappingURL lines removed.\n";
?>
