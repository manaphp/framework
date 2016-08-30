<?php
namespace ManaPHP;

interface RendererInterface
{
    /**
     * Checks whether view exists on registered extensions and render it
     *
     * @param string  $template
     * @param array   $vars
     * @param boolean $directOutput
     *
     * @return string
     */
    public function render($template, $vars, $directOutput = false);

    /**
     * @param string $template
     *
     * @return bool
     */
    public function exists($template);

    /**
     * Get the string contents of a section.
     *
     * @param  string $section
     * @param  string $default
     *
     * @return string
     */
    public function getSection($section, $default = '');

    /**
     * Start injecting content into a section.
     *
     * @param  string $section
     *
     * @return void
     */
    public function startSection($section);

    /**
     * Stop injecting content into a section.
     *
     * @param  bool $overwrite
     *
     * @return string
     */
    public function stopSection($overwrite = false);

    /**
     * @return void
     */
    public function appendSection();
}