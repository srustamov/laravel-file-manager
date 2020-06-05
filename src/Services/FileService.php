<?php


namespace Srustamov\FileManager\Services;

use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Srustamov\FileManager\Translation;
use ZipArchive;

class FileService
{

    /**
     * @var string
     */
    private $path;

    public const DS = DIRECTORY_SEPARATOR;


    public function __construct(string $path = null)
    {
        $this->path = $path;
    }


    /**
     * @param UploadedFile $file
     * @return array
     */
    public function upload(UploadedFile $file): array
    {
        $success = false;

        if ($file->isValid()) {
            $success = $file->move($this->path, $file->getClientOriginalName());
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


}
