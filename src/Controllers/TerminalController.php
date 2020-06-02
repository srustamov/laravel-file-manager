<?php

namespace Srustamov\FileManager\Controllers;

use Exception;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;


class TerminalController extends Controller
{

    /**
     * @param Request $request
     * @return string
     */
    public function run(Request $request)
    {
        $command = trim($request->post('command'));
        $artisan = trim(Str::replaceFirst('php artisan','',$command));

        if(!Arr::has(Artisan::all(), $artisan)) {
            $process = new Process(explode(' ',$command));
            $process->run();
            return $process->getOutput();
        }

        try {
            Artisan::call($artisan);

            return Artisan::output();
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
}
