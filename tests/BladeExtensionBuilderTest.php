<?php

namespace JobinjaTeam\BladeMacro\Test;

use Illuminate\Filesystem\Filesystem;
use Illuminate\View\FileViewFinder;
use JobinjaTeam\BladeMacro\BladeExtensionBuilder;

class BladeExtensionBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Close mockery after each test.
     *
     * @return void
     */
    public function tearDown()
    {
        \Mockery::close();
        parent::setUp();
    }

    /**
     * Test macros are compiled
     *
     * @return void
     */
    public function test_macros_are_compiled()
    {
        $builder = new BladeExtensionBuilder($finder = $this->getViewFinderMock(), $files = $this->getFilesMock());

        $finder->shouldReceive('find')->once()->withAnyArgs()->andReturn($fileToFind = 'some_file_to_find');
        
        $files->shouldReceive('get')->once()->with($fileToFind)->andReturn($content = '{{$variable}}');

        $view = "@macro('variable_shower', ['variable' => 'hello'])";



        $this->assertContains($content, $builder->processForMacro($view));
    }

    /**
     * Test macros are nestedly compiled.
     *
     * @return void
     */
    public function test_macros_are_nestedly_compiled()
    {
        $builder = new BladeExtensionBuilder($finder = $this->getViewFinderMock(), $files = $this->getFilesMock());

        $finder->shouldReceive('find')->once()->with('variable_shower')->andReturn($fileToFind = 'some_file_to_find');

        $files->shouldReceive('get')->once()->with($fileToFind)->andReturn($content = '@macro(\'some_other_macro\')');

        $finder->shouldReceive('find')->once()->with('some_other_macro')->andReturn($someOtherFile = 'some_other_file_to_find');

        $files->shouldReceive('get')->once()->with($someOtherFile)->andReturn($finally = 'FINALLY_HERE');

        $view = "@macro('variable_shower', ['variable' => 'hello'])";

        $this->assertContains($finally, $builder->processForMacro($view));
    }


    /**
     * @return \Mockery\MockInterface|Filesystem
     */
    public function getFilesMock()
    {
        return \Mockery::mock(Filesystem::class);
    }

    /**
     * @return \Mockery\MockInterface|FileViewFinder
     */
    public function getViewFinderMock()
    {
        return \Mockery::mock(FileViewFinder::class);
    }

    /**
     * Test that when variable are passed to the macro directive it will
     * throw proper exception.
     *
     * @return void
     */
    public function test_exception_on_invalid_macro_name()
    {
        $builder = new BladeExtensionBuilder($finder = $this->getViewFinderMock(), $files = $this->getFilesMock());

        // will be thrown if a variable is passed as macro name
        $this->expectException(\ErrorException::class);

        $builder->processForMacro('@macro($hello)');

        $this->expectException(\ErrorException::class);

        // Even when like this.
        $builder->processForMacro('@macro("some_var." . $hello)');

        $this->expectException(\ErrorException::class);

        $builder->processForMacro('@macro(view_name_generator())');
    }
}