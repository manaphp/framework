<?php

namespace ManaPHP;

use ManaPHP\Exception\InvalidValueException;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Helper\LocalFS;

class ViewContext
{
    /**
     * @var false|string|null
     */
    public $layout;

    /**
     * @var array
     */
    public $vars = [];

    /**
     * @var string
     */
    public $content;
}

/**
 * Class ManaPHP\View
 *
 * @package view
 *
 * @property-read \ManaPHP\RendererInterface   $renderer
 * @property-read \ManaPHP\DispatcherInterface $dispatcher
 * @property-read \ManaPHP\ViewContext         $_context
 */
class View extends Component implements ViewInterface
{
    /**
     * @var array
     */
    protected $_dirs = [];

    /**
     * @param false|string $layout
     *
     * @return static
     */
    public function setLayout($layout = 'Default')
    {
        $context = $this->_context;

        $context->layout = $layout;

        return $this;
    }

    /**
     * Set a single view parameter
     *
     * @param string $name
     * @param mixed  $value
     *
     * @return static
     */
    public function setVar($name, $value)
    {
        $context = $this->_context;

        $context->vars[$name] = $value;

        return $this;
    }

    /**
     * Adds parameters to view
     *
     * @param array $vars
     *
     * @return static
     */
    public function setVars($vars)
    {
        $context = $this->_context;

        $context->vars = array_merge($context->vars, $vars);

        return $this;
    }

    /**
     * Returns a parameter previously set in the view
     *
     * @param string $name
     *
     * @return mixed
     */
    public function getVar($name = null)
    {
        $context = $this->_context;

        if ($name === null) {
            return $context->vars;
        } else {
            return $context->vars[$name] ?? null;
        }
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasVar($name)
    {
        $context = $this->_context;

        return isset($context->_vars[$name]);
    }

    /**
     * @param string $template
     * @param array  $vars
     * @param bool   $directOutput
     *
     * @return string
     */
    protected function _render($template, $vars, $directOutput)
    {
        if (isset($vars['view'])) {
            throw new MisuseException('variable `view` is reserved for view');
        }
        $vars['view'] = $this;

        if (isset($vars['request'])) {
            throw new MisuseException('variable `request` is reserved for view');
        }
        $vars['request'] = $this->request ?? null;

        return $this->renderer->render($template, $vars, $directOutput);
    }

    /**
     * @return string
     */
    protected function _findLayout()
    {
        $context = $this->_context;

        if ($context->layout === null) {
            $controller = $this->dispatcher->getController();
            if ($area = $this->dispatcher->getArea()) {
                if ($this->renderer->exists("@app/Areas/$area/Views/Layouts/$controller")) {
                    $layout = "@app/Areas/$area/Views/Layouts/$controller";
                } elseif ($this->renderer->exists("@app/Areas/$area/Views/Layouts/Default")) {
                    $layout = "@app/Areas/$area/Views/Layouts/Default";
                } else {
                    $layout = '@views/Layouts/Default';
                }
            } else {
                $layout = $this->renderer->exists("@views/Layouts/$controller") ? "@views/Layouts/$controller" : '@views/Layouts/Default';
            }
        } elseif (is_string($context->layout)) {
            $layout = $context->layout;
            if ($layout[0] !== '@') {
                $layout = ucfirst($layout);
                if (($area = $this->dispatcher->getArea()) && $this->renderer->exists("@app/Areas/$area/Views/Layouts/$layout")) {
                    $layout = "@app/Areas/$area/Views/Layouts/$layout";
                } else {
                    $layout = "@views/Layouts/$layout";
                }
            }
        } else {
            $layout = false;
        }

        return $layout;
    }

    /**
     * Executes render process from dispatching data
     *
     * @param string $template
     * @param array  $vars
     *
     * @return string
     */
    public function render($template = null, $vars = null)
    {
        $context = $this->_context;

        if ($vars !== null) {
            $context->vars = $vars;
        }

        if ($template === null) {
            $action = $this->dispatcher->getAction();
        } elseif (strpos($template, '/') === false) {
            $action = $template;
            $template = null;
        } else {
            $action = null;
        }

        if ($template === null) {
            $area = $this->dispatcher->getArea();
            $controller = $this->dispatcher->getController();

            if ($area) {
                $dir = "@app/Areas/$area/Views/$controller";
            } else {
                $dir = "@views/$controller";
            }

            if (!isset($this->_dirs[$dir])) {
                $this->_dirs[$dir] = LocalFS::dirExists($dir);
            }

            if ($this->_dirs[$dir]) {
                $template = $dir . '/' . ucfirst($action);
            } elseif ($action === 'index') {
                $template = $dir;
            } else {
                $template = $dir . '/' . ucfirst($action);
            }
        }

        $this->fireEvent('view:rendering');

        $context->content = $this->_render($template, $context->vars, false);

        if ($context->layout !== false) {
            $layout = $this->_findLayout();
            $context->content = $this->_render($layout, $context->vars, false);
        }

        $this->fireEvent('view:rendered');

        return $context->content;
    }

    /**
     * @param string $template
     *
     * @return string|false
     */
    public function exists($template = null)
    {
        static $cached = [];

        if ($template === null) {
            $action = $this->dispatcher->getAction();
        } elseif (strpos($template, '/') === false) {
            $action = $template;
            $template = null;
        } else {
            $action = null;
        }

        if ($template === null) {
            $area = $this->dispatcher->getArea();
            $controller = $this->dispatcher->getController();

            if ($area) {
                $dir = "@app/Areas/$area/Views/$controller";
            } else {
                $dir = "@views/$controller";
            }

            if (!isset($this->_dirs[$dir])) {
                $this->_dirs[$dir] = LocalFS::dirExists($dir);
            }

            if ($this->_dirs[$dir]) {
                $template = $dir . '/' . ucfirst($action);
            } elseif ($action === 'index') {
                $template = $dir;
            } else {
                return false;
            }
        }

        if (isset($cached[$template])) {
            return $cached[$template];
        } else {
            return $cached[$template] = $this->renderer->exists($template);
        }
    }

    /**
     * Renders a partial view
     *
     * @param string $path
     * @param array  $vars
     *
     * @return void
     */
    public function partial($path, $vars = [])
    {
        $this->_render($path, $vars, true);
    }

    /**
     * @param string $widget
     *
     * @return string|false
     */
    public function getWidgetClassName($widget)
    {
        if (strpos($widget, '/') !== false) {
            throw new MisuseException(['it is not allowed to access other area `:widget` widget', 'widget' => $widget]);
        }

        $area = $this->dispatcher->getArea();
        if ($area && class_exists($widgetClassName = "App\\Areas\\$area\\Widgets\\{$widget}Widget")) {
            return $widgetClassName;
        }

        return class_exists($widgetClassName = "App\\Widgets\\{$widget}Widget") ? $widgetClassName : false;
    }

    /**
     * @param string $widget
     * @param array  $options
     *
     * @return void
     */
    public function widget($widget, $options = [])
    {
        if (!$widgetClassName = $this->getWidgetClassName($widget)) {
            throw new InvalidValueException(['`:widget` widget is invalid: `:class` class is not exists', 'widget' => $widget, 'class' => $widgetClassName]);
        }

        if (strpos($widgetClassName, '\\Areas\\')) {
            $view = "@app/Areas/{$this->dispatcher->getArea()}/Views/Widgets/$widget";
        } else {
            $view = "@views/Widgets/$widget";
        }

        /** @var \ManaPHP\View\WidgetInterface $widgetInstance */
        $widgetInstance = $this->_di->getShared($widgetClassName);
        $vars = $widgetInstance->run($options);

        if (is_string($vars)) {
            echo $vars;
        } else {
            $this->_render($view, $vars, true);
        }
    }

    /**
     * @param string $path
     * @param array  $vars
     *
     * @return void
     */
    public function block($path, $vars = [])
    {
        if ($path[0] !== '@' && strpos($path, '/') === false) {
            $path = "@views/Blocks/$path";
        }

        $this->_render($path, $vars, true);
    }

    /**
     * Externally sets the view content
     *
     * @param string $content
     *
     * @return static
     */
    public function setContent($content)
    {
        $context = $this->_context;

        $context->content = $content;

        return $this;
    }

    /**
     * Returns cached output from another view stage
     *
     * @return string
     */
    public function getContent()
    {
        return $this->_context->content;
    }

    public function dump()
    {
        $data = parent::dump();

        $data['_context']['content'] = '***';

        return $data;
    }
}