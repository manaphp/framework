<?php

declare(strict_types=1);

namespace ManaPHP\Rendering\Engine;

use ManaPHP\AliasInterface;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Di\Attribute\Config;
use ManaPHP\Rendering\Engine\Sword\Compiler;
use ManaPHP\Rendering\EngineInterface;
use function strlen;

class Sword implements EngineInterface
{
    #[Autowired] protected AliasInterface $alias;
    #[Autowired] protected Compiler $swordCompiler;

    #[Config] protected bool $app_debug;

    protected string $doc_root;

    protected array $compiled = [];

    public function __construct(?string $doc_root = null)
    {
        $this->doc_root = $doc_root ?? $_SERVER['DOCUMENT_ROOT'];
    }

    public function getCompiledFile(string $source): string
    {
        if (str_starts_with($source, $root = $this->alias->get('@root'))) {
            $compiled = '@runtime/sword' . substr($source, strlen($root));
        } elseif ($this->doc_root !== '' && str_starts_with($source, $this->doc_root)) {
            $compiled = '@runtime/sword/' . substr($source, strlen($this->doc_root));
        } else {
            $compiled = "@runtime/sword/$source";
            if (DIRECTORY_SEPARATOR === '\\') {
                $compiled = str_replace(':', '_', $compiled);
            }
        }

        $compiled = $this->alias->resolve($compiled);

        if ($this->app_debug || !file_exists($compiled) || filemtime($source) > filemtime($compiled)) {
            $this->swordCompiler->compileFile($source, $compiled);
        }

        return $compiled;
    }

    public function render(string $file, array $vars = []): void
    {
        extract($vars, EXTR_SKIP);

        $this->compiled[$file] ??= $this->getCompiledFile($file);

        require $this->compiled[$file];
    }
}
