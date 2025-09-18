<?php

use BaseApi\App;

if (!function_exists('storage_path')) {
    /**
     * Get the path to the storage directory.
     * 
     * @param string $path Path within storage directory
     * @return string Full path to storage location
     */
    function storage_path(string $path = ''): string
    {
        return App::storagePath($path);
    }
}
