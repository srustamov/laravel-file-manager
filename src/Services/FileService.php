<?php


namespace Srustamov\FileManager\Services;

use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Illuminate\Http\UploadedFile;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Srustamov\FileManager\Contracts\FileServiceInterface;
use Srustamov\FileManager\Translation;
use ZipArchive;

class FileService implements FileServiceInterface
{

    /**
     * @var string
     */
    public $path_pattern;

    /**
     * @var string
     */
    public $base_path;


    /**
     * @var array
     */
    public $only = [];

    /**
     * @var array
     */
    public $hidden = [];


    public const DS = DIRECTORY_SEPARATOR;


    /**
     * FileService constructor.
     * @param string $base_path
     */
    public function __construct(string $base_path)
    {
        if (!File::exists($base_path)) {
            throw new FileManagerException('File manager base path not found');
        }
        $this->base_path = realpath($base_path);
    }

    /**
     * @param string $pattern
     * @return $this
     */
    public function setPathPattern(string $pattern)
    {
        $this->path_pattern = $pattern;

        return $this;
    }

    /**
     * @return string
     */
    public function getPathPattern(): string
    {
        return $this->path_pattern;
    }

    /**
     * @param string $path
     * @return $this
     */
    public function setBasePath(string $path)
    {
        $this->base_path = $path;

        return $this;
    }

    /**
     * @return string
     */
    public function getBasePath(): string
    {
        return $this->base_path;
    }

    /**
     * @param array $paths
     * @return $this
     */
    public function setHidden(array $paths)
    {
        $this->hidden = $paths;

        return $this;
    }


    /**
     * @return array
     */
    public function getHidden(): array
    {
        return $this->hidden;
    }

    /**
     * @param array $paths
     * @return $this
     */
    public function setOnly(array $paths)
    {
        $this->only = $paths;

        return $this;
    }

    /**
     * @return array
     */
    public function getOnly(): array
    {
        return $this->only;
    }


    /**
     * @param $path
     * @return array
     */
    public function prepareFileItem($path): array
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
    public function getFiles(string $path): Collection
    {
        return $this->filterHidden(
          $this->filterOnly(
            collect(glob(
                rtrim($this->absolutePath($path), self::DS) . $this->getPathPattern(),
                GLOB_MARK | GLOB_BRACE
            ))->map(function ($path) {
                return $this->prepareFileItem($path);
            })
            ->sortBy('name')
            ->sortBy(static function ($item) {
                return isset($item['extension']);
            }),
            $this->getOnly()
          ),
          $this->getHidden()
        );
    }


    /**
     * @param Collection $files
     * @param array $hidden
     * @return Collection
     */
    public function filterHidden(Collection $files, array $hidden = []): Collection
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
     * @param Collection $files
     * @param array $only
     * @return Collection
     */
    public function filterOnly(Collection $files, array $only = ['*']): Collection
    {
        if (in_array('*', $only, true)) {
            return $files;
        }

        return $files->reject(static function ($item) use (&$only) {
            if (in_array($item['path'], $only, true)) {
                Arr::forget($only, $item['path']);
                return false;
            }
            return true;
        })->values();
    }

    /**
     * @param string $path
     * @return array|false
     */
    public function getChildren(string $path): array
    {
        return $this->getFiles($path)->toArray();
    }


    /**
     * @param $path
     * @param array $open
     * @return array
     */
    public function getItems($path, array $open = []): array
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
    public function getBasePathItems(array $open = []): array
    {
        return [[
            'name' => File::basename($this->base_path),
            'path' => $this->base_path,
            'children' => $this->getItems($this->base_path, $open)
        ]];
    }


    /**
     * @param string $path
     * @param UploadedFile $file
     * @return array
     */
    public function upload(string $path, UploadedFile $file): array
    {
        $success = false;

        $path = $this->absolutePath($path);

        if ($file->isValid()) {
            $success = $file->move($path, $file->getClientOriginalName());
            $message = Translation::getIf($success, 'upload_success', 'upload_failed');
        } else {
            $message = Translation::get('file_error_loading');
        }

        return [$success, $message];
    }

    /**
     * @param $source
     * @param $destination
     * @return array|null
     */
    public function zip($source, $destination): ?array
    {
        ini_set('max_execution_time', 600);
        ini_set('memory_limit', '1024M');

        $exists = File::exists($source);

        if (!$exists || !extension_loaded('zip')) {
            return [
                'success' => false,
                'message' => Translation::getIf($exists, 'file_not_found', 'zip_extension_not_loaded')
            ];
        }

        $zip = new ZipArchive();

        if (!$zip->open($destination, ZipArchive::CREATE)) {
            return [
                'success' => false,
                'message' => Translation::get('archive_not_open'),
            ];
        }

        $source = str_replace('\\', self::DS, realpath($source));

        if (File::isDirectory($source)) {
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);

            foreach ($files as $file) {
                $file = str_replace('\\', self::DS, $file);

                if (in_array(substr($file, strrpos($file, self::DS) + 1), array('.', '..'))) {
                    continue;
                }

                $file = realpath($file);

                if (File::isDirectory($file)) {
                    $zip->addEmptyDir(str_replace($source . self::DS, '', $file . self::DS));
                } else if (File::isFile($file)) {
                    $zip->addFromString(str_replace($source . self::DS, '', $file), file_get_contents($file));
                }
            }
        } else if (File::isFile($source)) {
            $zip->addFromString(basename($source), file_get_contents($source));
        }

        $success = $zip->close();

        return [
            'success' => $success,
            'message' => Translation::getIf($success, 'compressed_success', 'compressed_failed')
        ];

    }

    /**
     * @param string $path
     * @param string $target
     * @return array
     */
    public function unzip(string $path, string $target): array
    {
        try {
            $unzip = new ZipArchive;

            $success = false;

            if ($unzip->open($path) === true) {

                $unzip->extractTo($target);

                $success = $unzip->close();
            }
            return [
                'success' => $success,
                'message' => Translation::getIf($success, 'unzip_success', 'unzip_failed'),
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * @param $path
     * @return string
     */
    public function absolutePath($path): string
    {
        $path = self::DS
            . trim($this->base_path, self::DS)
            . self::DS
            . Str::replaceFirst($this->base_path, '', $path);

        return str_replace([self::DS . self::DS, '..'], [self::DS, ''], $path);
    }


}
