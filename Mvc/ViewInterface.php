<?php

namespace ManaPHP\Mvc;

/**
 * ManaPHP\Mvc\ViewInterface initializer
 */
interface ViewInterface
{
    /**
     * @param false|string $layout
     *
     * @return static
     */

    public function setLayout($layout = 'Default');

    /**
     * Adds parameter to view
     *
     * @param string $name
     * @param mixed  $value
     *
     * @return static
     */
    public function setVar($name, $value);

    /**
     * Adds parameters to view
     *
     * @param $vars
     *
     * @return static
     */
    public function setVars($vars);

    /**
     * Returns a parameter previously set in the view
     *
     * @param string $name
     *
     * @return mixed
     */
    public function getVar($name = null);

    /**
     * Gets the name of the controller rendered
     *
     * @return string
     */
    public function getControllerName();

    /**
     * Gets the name of the action rendered
     *
     * @return string
     */
    public function getActionName();

    /**
     * Executes render process from dispatching data
     *
     * @param string $module
     * @param string $controller
     * @param string $action
     */
    public function render($module, $controller, $action);

    /**
     * @param int|array $cacheOptions
     *
     * @return string|false
     */
    public function cache($cacheOptions);

    /**
     * Choose a view different to render than last-controller/last-action
     *
     * @param string $view
     *
     * @return static
     */
    public function pick($view);

    /**
     * Renders a partial view
     *
     * <code>
     *    //Show a partial inside another view
     *    $this->partial('shared/footer');
     * </code>
     *
     * <code>
     *    //Show a partial inside another view with parameters
     *    $this->partial('shared/footer', array('content' => $html));
     * </code>
     *
     * @param string    $path
     * @param array     $vars
     * @param int|array $cacheOptions
     *
     * @throws \ManaPHP\Mvc\View\Exception|\ManaPHP\Renderer\Exception
     */
    public function partial($path, $vars = [], $cacheOptions = null);

    /**
     * Renders a widget
     *
     * @param string    $widget
     * @param array     $vars
     * @param int|array $cacheOptions
     */
    public function widget($widget, $vars = [], $cacheOptions = null);

    /**
     * Externally sets the view content
     *
     * @param string $content
     *
     * @return static
     */
    public function setContent($content);

    /**
     * Returns cached output from another view stage
     *
     * @return string
     */
    public function getContent();
}