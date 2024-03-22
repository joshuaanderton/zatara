<?php

if (! function_exists('zatara_path')) {
    function zatara_path(string ...$path)
    {
        return collect(str(__DIR__)->rtrim('/src'))->concat($path)->flatten()->join('/');
    }
}
