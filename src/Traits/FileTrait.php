<?php

namespace Srustamov\FileManager\Traits;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Collection;
use Illuminate\Support\Arr;

trait FileTrait
{
    protected $glob_pattern = '/{,.}[!.,!..]*';


    /**
     * @param $path
     * @return array
     */
    protected function prepareFileItem($path): array
    {
        $item = [
            'path' => $path,
            'name' => File::basename($path),
            'size' => File::size($path),
            'lastModified' => File::lastModified($path),
        ];
        if (File::isDirectory($path)) {
            $item['children'] = [];
        } elseif (File::isFile($path)) {
            $item['extension'] = File::extension($path);
            $item['type'] = File::type($path);
        }

        return $item;
    }


    /**
     * @param string $path
     * @return Collection
     */
    protected function getFiles(string $path): Collection
    {
        return $this->filterHidden(
            collect(glob(
              rtrim($this->preparePath($path), $this->ds) . $this->glob_pattern,
              GLOB_MARK | GLOB_BRACE
          ))->map(function ($path) {
              return $this->prepareFileItem($path);
          })
          ->sortBy('name')
          ->sortBy(static function($item){
            return isset($item['extension']);
          }),
            config('file-manager.paths.hidden', [])
        );
    }


    /**
     * @param Collection $files
     * @param array $hidden
     * @return Collection
     */
    protected function filterHidden(Collection $files, array $hidden = []): Collection
    {
        return $files->reject(static function ($item) use (&$hidden) {
            if (in_array($item['path'], $hidden, true)) {
                Arr::forget($hidden, $item['path']);
                return true;
            }
            return false;
        })->values();
    }

    /**
     * @param string $path
     * @return array|false
     */
    protected function getChildren(string $path): array
    {
        return $this->getFiles($path)->toArray();
    }


    /**
     * @param $path
     * @param array $open
     * @return array
     */
    protected function getItems($path, array $open = []): array
    {
        $files = $this->getFiles($path);

        return $files->map(function ($item) use (&$open) {
            if (in_array($item['path'], $open, true)) {
                Arr::forget($open, $item['path']);

                $item['children'] = $this->getItems($item['path'], $open);

                return $item;
            }

            return $item;
        })->toArray();
    }


    /**
     * @param array $open
     * @return array
     */
    protected function getBasePathItems(array $open = []): array
    {
        return [[
            'name' => File::basename($this->base_path),
            'path' => $this->base_path,
            'children' => $this->getItems($this->base_path, $open)
        ]];
    }
}
