<?php

namespace Srustamov\FileManager\Tests;

use Orchestra\Testbench\TestCase as Orchestra;

use Srustamov\FileManager\FileManagerServiceProvider;

class TestCase extends Orchestra
{

  protected $config;


  protected function getPackageProviders($app)
  {
      return [FileManagerServiceProvider::class];
  }


  protected function setUp(): void
  {
      parent::setUp();

      config()->set('file-manager.paths.base',__DIR__.'/__test_path__');
      config()->set('file-manager.paths.hidden',[]);
      config()->set('file-manager.paths.only',[]);

      $this->config = config('file-manager');

  }

}