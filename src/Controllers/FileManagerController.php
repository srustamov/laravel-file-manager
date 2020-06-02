<?php

namespace Srustamov\FileManager\Controllers;

use Exception;
use Illuminate\Routing\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Srustamov\FileManager\Requests\FileCopyRequest;
use Srustamov\FileManager\Requests\FileCreateRequest;
use Srustamov\FileManager\Requests\FileDeleteRequest;
use Srustamov\FileManager\Requests\FileUploadRequest;
use Srustamov\FileManager\Requests\UnzipRequest;
use Srustamov\FileManager\Requests\ZipRequest;
use Srustamov\FileManager\Services\FileService;
use Srustamov\FileManager\Traits\Path;
use Srustamov\FileManager\Traits\FileTrait;
use Srustamov\FileManager\Translation;

class FileManagerController extends Controller
{

    use FileTrait, Path;

    private $base_path;

    /**
     * FileManagerController constructor.
     */
    public function __construct()
    {
        $this->base_path = realpath(config('file-manager.paths.base', '/'));
    }


    public function index()
    {
        return view('file-manager::index');
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function showBaseItems(Request $request): JsonResponse
    {
        return response()->json($this->getBasePathItems($request->post('open', [])));
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function children(Request $request): JsonResponse
    {
        return response()->json(
            $this->getChildren($request->post('path'))
        );
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getFileContent(Request $request): JsonResponse
    {
        $path = $this->preparePath($request->post('path'));

        if (File::isFile($path)) {

            try {

                $type = File::mimeType($path);

                $isText = Str::of($type)->startsWith('text') || $type === 'inode/x-empty';
                $isImage = Str::of($type)->startsWith('image');

                if ($isText) {
                    $content = File::get($path);
                } elseif ($isImage) {
                    $content = 'data:image/' . $type . ';base64,' . base64_encode(File::get($path));
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => Translation::get('file_not_read'),
                        'type' => $type,
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'content' => $content,
                    'is_text' => $isText,
                    'is_image' => $isImage,
                ]);
            } catch (Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ]);
            }

        }

        return response()->json([
            'success' => false,
            'path' => $path
        ]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function saveFileContent(Request $request): JsonResponse
    {
        $path = $this->preparePath($request->post('path'));

        $content = $request->post('content');

        if (File::isFile($path)) {
            $success = (bool)File::put($path, $content);

            return response()->json([
                'success' => $success,
                'message' => Translation::getIf($success, 'content_saved', 'content_not_saved')
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => Translation::get('file_not_read')
        ]);
    }


    /**
     * @param FileCreateRequest $request
     * @return JsonResponse
     */
    public function createFile(FileCreateRequest $request): ?JsonResponse
    {
        $parent = rtrim($this->preparePath($request->post('parent')), $this->ds);

        $filename = trim($request->name, $this->ds);

        $fullpath = $parent . $this->ds . $filename;

        try {
            if (File::exists($fullpath)) {
                return response()->json([
                    'success' => false,
                    'message' => Translation::get('already_created', ['type' => 'File'])
                ]);
            }

            if (strpos($filename, $this->ds) !== false) {
                File::ensureDirectoryExists(File::dirname($fullpath));
            }

            $create = File::put($fullpath, '') !== false;

            if ($create) {
                File::chmod($fullpath, 0755);
            }

            return response()->json([
                'success' => $create,
                'path' => $fullpath,
                'message' => Translation::getIf(
                    $create,
                    'created',
                    'not_created',
                    ['type' => 'File']
                ),
                'items' => $create ? $this->getBasePathItems($request->post('open', [])) : []
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * @param FileCreateRequest $request
     * @return JsonResponse
     */
    public function createFolder(FileCreateRequest $request): ?JsonResponse
    {
        $parent = rtrim($this->preparePath($request->post('parent')), $this->ds);
        $name = trim($request->post('name'), $this->ds);

        $fullpath = $parent . $this->ds . $name;

        try {
            if (File::exists($fullpath)) {
                return response()->json([
                    'success' => false,
                    'message' => Translation::get('already_create', ['type' => 'Folder'])
                ]);
            }


            $create = File::makeDirectory($fullpath, 0755, true) !== false;


            return response()->json([
                'success' => $create,
                'items' => $create ? $this->getBasePathItems() : [],
                'message' => Translation::getIf(
                    $create,
                    'created',
                    'not_created',
                    ['type' => 'Folder']
                ),
                'items' => $create ? $this->getBasePathItems($request->post('open', [])) : []
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }


    /**
     * @param FileCopyRequest $request
     * @return JsonResponse
     */
    public function copy(FileCopyRequest $request): JsonResponse
    {

        $path = $this->preparePath($request->post('from'));

        $to = $this->preparePath($request->post('to'));

        if (File::isFile($path)) {
            $success = File::copy($path, rtrim($to, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $request->post('name'));
            //exec("cp $path $to")
        } elseif (File::isDirectory($path)) {
            $success = File::copyDirectory($path, $to);
            //exec("cp-r $path $to")
        } else {
            $success = false;
        }


        return response()->json([
            'success' => $success,
            'message' => Translation::getIf($success,'copied','not_copied'),
            'items' => $success ? $this->getBasePathItems($request->post('open', [])) : []
        ]);
    }


    /**
     * @param FileCopyRequest $request
     * @return JsonResponse
     */
    public function cut(FileCopyRequest $request): JsonResponse
    {

        $path = $this->preparePath($request->post('from'));

        $to = $this->preparePath($request->post('to'));

        if (File::isFile($path)) {
            $success = File::move($path, rtrim($to, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $request->post('name'));
        } elseif (File::isDirectory($path)) {
            $success = rename($path, $to);
        } else {
            $success = false;
        }

        return response()->json([
            'success' => $success,
            'message' => Translation::getIf($success,'operation_success','operation_failed'),
            'items' => $success ? $this->getBasePathItems($request->post('open', [])) : []
        ]);
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function rename(Request $request): JsonResponse
    {
        $path = $this->preparePath($request->post('path'));
        $name = $this->preparePath($request->post('name'));

        try {
            $success = null;

            if (File::isFile($path)) {
                $success = File::move($path, $name);
            } elseif (File::isDirectory($path)) {
                $success = File::moveDirectory($path, $name);
            }

            $message = Translation::getIf($success,'rename_success','rename_failed');

        } catch (Exception $e) {
            $success = false;
            $message = $e->getMessage();
        }


        return response()->json([
            'success' => $success,
            'message' => $message,
            'items' => $success ? $this->getBasePathItems($request->post('open', [])) : []
        ]);
    }


    /**
     * @param FileDeleteRequest $request
     * @return JsonResponse
     */
    public function delete(FileDeleteRequest $request): JsonResponse
    {
        $path = $this->preparePath($request->post('path'));

        if (File::isFile($path)) {
            $success = File::delete($path);

            $message = Translation::getIf($success,'delete_success','delete_failed',['type' => 'File']);


        } elseif (File::isDirectory($path)) {

            $success = File::deleteDirectory($path);

            $message = Translation::getIf($success,'delete_success','delete_failed',['type' => 'Folder']);

        } else {

            $success = false;
            $message = Translation::get('item_not_found_or_not_read');
        }

        return response()->json([
            'success' => $success,
            'message' => $message,
        ]);
    }


    /**
     * @param ZipRequest $request
     * @return JsonResponse
     */
    public function compress(ZipRequest $request): JsonResponse
    {
        $response = (new FileService($request->post('path')))->zip(
            $this->preparePath($request->post('path')),
            $this->preparePath($request->post('name'))
        );

        if ($response['success']) {
            $response['items'] = $this->getBasePathItems($request->post('open', []));
        }

        return response()->json($response);
    }


    /**
     * @param UnzipRequest $request
     * @param FileService $service
     * @return JsonResponse
     */
    public function unzip(UnzipRequest $request, FileService $service): JsonResponse
    {
        $response = $service->unzip(
            $this->preparePath($request->post('path')),
            $this->preparePath($request->post('target'))
        );

        if ($response['success']) {
            $response['items'] = $this->getBasePathItems($request->post('open', []));
        }

        return response()->json($response);
    }


    /**
     * @param FileUploadRequest $request
     * @return JsonResponse
     */
    public function upload(FileUploadRequest $request): JsonResponse
    {
        $service = new FileService(
            $this->preparePath($request->post('target'))
        );

        [$success, $message] = $service->upload($request->file('file'));

        return response()->json([
            'success' => $success,
            'message' => $message,
            'items' => $success ? $this->getBasePathItems($request->post('open', [])) : []
        ]);
    }
}
