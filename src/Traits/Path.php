<?php

namespace Srustamov\FileManager\Traits;



use Illuminate\Support\Str;

trait Path
{

    private $ds = DIRECTORY_SEPARATOR;

    /**
     * @param $path
     * @return string
     */
    protected function preparePath($path): string
    {
        $path = $this->ds
          . trim($this->base_path, $this->ds)
          . $this->ds
          . Str::replaceFirst($this->base_path, '', $path);

        return str_replace([$this->ds.$this->ds,'..'], [$this->ds,''],$path);
    }
}
