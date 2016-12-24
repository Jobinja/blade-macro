<?php

namespace JobinjaTeam\BladeMacro;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Illuminate\View\ViewFinderInterface;

class BladeExtensionBuilder
{
    /**
     * @var ViewFinderInterface
     */
    private $viewFinder;

    /**
     * @var Filesystem
     */
    private $files;

    /**
     * BladeExtensionBuilder constructor.
     * @param ViewFinderInterface $viewFinder
     * @param Filesystem $files
     */
    public function __construct(ViewFinderInterface $viewFinder, Filesystem $files)
    {
        $this->viewFinder = $viewFinder;
        $this->files = $files;
    }

    /**
     * Process for macro
     *
     * @param string $value
     * @return string
     */
    public function processForMacro($value)
    {
        $pattern = '/\B@(macro)([ \t]*)(\( ( (?>[^()]+) | (?3) )* \))?/x';

        while (preg_match($pattern, $value, $matches)) {
            
            $expression = $this->stripParentheses($matches[3]);

            if (Str::endsWith($expression, ')')) {
                $expression = substr($expression, 0, -1);
            }

            // We replace each occurrence of the @macro
            // with the contents of its file, and wrap the
            // imported content around a self-invokable function to satisfy the macro scope,
            // So file changes are not detected, there is a need to clear compiled views on
            // development environments.
            $codeStart = $this->getMacroStartCode();

            $codeEnd = $this->getMacroCodeEnd($expression);

            $view = $this->extractFindableViewForMacro($expression);

            $viewContent = $this->files->get($this->viewFinder->find($view));

            $code = $codeStart.$viewContent."\n".$codeEnd;

            $value = Str::replaceFirst($matches[0], $code, $value);
            
        }

        return $value;
    }

    /**
     * Macro start code.
     *
     * @return  string
     */
    private function getMacroStartCode()
    {
        $codeStart = <<<'HTML'
<?php 
call_user_func(function ($macroName, array $firstMerging, array $secondMerging = null) {
    if ($secondMerging !== null) {
        $firstMerging = array_merge($secondMerging, $firstMerging);
    }
    $secondMerging = null;
    extract($firstMerging);
?>
HTML;

        return str_replace("\n", '', $codeStart);
    }

    /**
     * Extract findable view from macro.
     *
     * @param   string $expression
     * @return  string
     * @throws \ErrorException
     */
    private function extractFindableViewForMacro($expression)
    {
        $exploded = explode(',', $expression);

        $view = trim($exploded[0]);

        if (! Str::startsWith($view, "'") || ! Str::endsWith($view, "'") || substr_count($view, "'") !== 2) {
            // What error to throw ?
            throw new \ErrorException(
                "The 'macro' directive can only be used with literal strings beginning and ending with \" ' \""
            );
        }

        $view = str_replace('\'', '', $view);

        $delimiter = ViewFinderInterface::HINT_PATH_DELIMITER;

        if (strpos($view, $delimiter) === false) {
            $view = str_replace('/', '.', $view);

            return $view;
        }

        list($namespace, $view) = explode($delimiter, $view);

        $view = $namespace.$delimiter.str_replace('/', '.', $view);

        return $view;
    }

    /**
     * Macro end code.
     *
     * @param   string $expression
     * @return  string
     */
    private function getMacroCodeEnd($expression)
    {
        return sprintf("<?php }, %s, array_except(get_defined_vars(), array('__data', '__path'))) ;?>", $expression);
    }

    /**
     * Strip Parentheses
     *
     * @param $expression
     * @return string
     */
    private function stripParentheses($expression)
    {
        if (Str::startsWith($expression, '(')) {
            $expression = substr($expression, 1, -1);
        }

        return $expression;
    }
}