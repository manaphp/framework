<?php

declare(strict_types=1);

namespace ManaPHP\I18n;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Exception\RuntimeException;
use ManaPHP\Helper\LocalFS;
use ManaPHP\Http\RequestInterface;
use function pathinfo;
use function str_contains;
use function strtolower;
use function strtr;

class Translator implements TranslatorInterface
{
    #[Autowired] protected LocaleInterface $locale;
    #[Autowired] protected RequestInterface $request;

    #[Autowired] protected string $dir = '@resources/Translator';

    protected array $files = [];
    protected array $templates = [];

    public function __construct()
    {
        foreach (LocalFS::glob($this->dir . '/*.php') as $file) {
            $this->files[strtolower(pathinfo($file, PATHINFO_FILENAME))] = $file;
        }
    }

    public function translate(string $template, array $placeholders = []): string
    {
        $locale = $this->locale->get();

        if (!isset($this->templates[$locale])) {
            if (($file = $this->files[$locale] ?? null) === null) {
                throw new RuntimeException(['`{1}` locale file is not exists', $locale]);
            }

            $templates = require $file;
            $this->templates[$locale] = $templates;
        } else {
            $templates = $this->templates[$locale];
        }

        $message = $templates[$template] ?? $template;

        if ($placeholders) {
            $replaces = [];

            if (str_contains($message, ':')) {
                foreach ($placeholders as $k => $v) {
                    $replaces[':' . $k] = $v;
                }
            }

            if (str_contains($message, '{')) {
                foreach ($placeholders as $k => $v) {
                    $replaces['{' . $k . '}'] = $v;
                }
            }

            return strtr($message, $replaces);
        } else {
            return $message;
        }
    }
}
