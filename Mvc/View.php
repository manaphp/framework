<?php

namespace ManaPHP\Mvc;

use ManaPHP\Component;
use ManaPHP\Mvc\View\Exception;
use ManaPHP\Utility\Text;

/**
 * ManaPHP\Mvc\View
 *
 * ManaPHP\Mvc\View is a class for working with the "view" portion of the model-view-controller pattern.
 * That is, it exists to help keep the view script separate from the model and controller scripts.
 * It provides a system of helpers, output filters, and variable escaping.
 *
 * <code>
 * //Setting views directory
 * $view = new ManaPHP\Mvc\View();
 * $view->setViewsDir('app/views/');
 *
 * $view->start();
 * //Shows recent posts view (app/views/posts/recent.phtml)
 * $view->render('posts', 'recent');
 * $view->finish();
 *
 * //Printing views output
 * echo $view->getContent();
 * </code>
 *
 *
 * @property \ManaPHP\Renderer       $renderer
 * @property \ManaPHP\CacheInterface $viewsCache
 * @property \ManaPHP\Http\Request   $request
 */
class View extends Component implements ViewInterface
{
    /**
     * @var string
     */
    protected $_content;

    /**
     * @var array
     */
    protected $_viewVars = [];

    /**
     * @var false|string|null
     */
    protected $_layout;

    /**
     * @var string
     */
    protected $_moduleName;

    /**
     * @var string
     */
    protected $_controllerName;

    /**
     * @var string
     */
    protected $_actionName;

    /**
     * @var array
     */
    protected $_cacheOptions;

    /**
     * @param false|string $layout
     *
     * @return static
     */
    public function setLayout($layout = 'Default')
    {
        $this->_layout = $layout;

        return $this;
    }

    /**
     * Set a single view parameter
     *
     *<code>
     *    $this->view->setVar('products', $products);
     *</code>
     *
     * @param string $name
     * @param mixed  $value
     *
     * @return static
     */
    public function setVar($name, $value)
    {
        $this->_viewVars[$name] = $value;

        return $this;
    }

    /**
     * Adds parameters to view
     *
     * @param $vars
     *
     * @return static
     */
    public function setVars($vars)
    {
        $this->_viewVars = array_merge($this->_viewVars, $vars);

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
        if ($name === null) {
            return $this->_viewVars;
        } else {
            return isset($this->_viewVars[$name]) ? $this->_viewVars[$name] : null;
        }
    }

    /**
     * Gets the name of the module rendered
     *
     * @return string
     */
    public function getModuleName()
    {
        return $this->_moduleName;
    }

    /**
     * Gets the name of the controller rendered
     *
     * @return string
     */
    public function getControllerName()
    {
        return $this->_controllerName;
    }

    /**
     * Gets the name of the action rendered
     *
     * @return string
     */
    public function getActionName()
    {
        return $this->_actionName;
    }

    /**
     * @param int|array $cacheOptions
     *
     * @return string|false
     */
    public function cache($cacheOptions)
    {
        if (!is_array($cacheOptions)) {
            $cacheOptions = ['ttl' => $cacheOptions];
        }

        if (!isset($cacheOptions['key'])) {
            $cacheOptions['key'] = 'Views/' . $this->request->getUrl();
        }

        $this->_cacheOptions = $cacheOptions;

        return $this->viewsCache->get($this->_cacheOptions['key']);
    }

    /**
     * Executes render process from dispatching data
     *
     *<code>
     * //Shows recent posts view (app/views/posts/recent.phtml)
     * $view->start()->render('posts', 'recent')->finish();
     *</code>
     *
     * @param string $module
     * @param string $controller
     * @param string $action
     *
     * @return static
     * @throws \ManaPHP\Mvc\View\Exception|\ManaPHP\Renderer\Exception
     */
    public function render($module, $controller, $action)
    {
        if ($this->_moduleName === null) {
            $this->_moduleName = $module;
        }

        if ($this->_controllerName === null) {
            $this->_controllerName = $controller;
        }

        if ($this->_actionName === null) {
            $this->_actionName = $action;
        }

        if ($this->_cacheOptions !== null) {
            $content = $this->viewsCache->get($this->_cacheOptions['key']);
            if ($content !== false) {
                $this->_content = $content;
                return $this;
            }
        }

        $this->fireEvent('view:beforeRender');

        $view = "/{$this->_moduleName}/Views/{$this->_controllerName}/" . ucfirst($this->_actionName);

        $this->_content = $this->renderer->render("@app{$view}", $this->_viewVars, false);

        if ($this->_layout !== false) {
            if (is_string($this->_layout)) {
                $layout = $this->_layout;
            } else {
                $layout = $this->_controllerName;
            }

            $view = "/$this->_moduleName/Views/Layouts/" . ucfirst($layout);
            $this->_content = $this->renderer->render("@app{$view}", $this->_viewVars, false);
        }

        $this->fireEvent('view:afterRender');

        if ($this->_cacheOptions !== null) {
            /** @noinspection PhpParamsInspection */
            $this->viewsCache->set($this->_cacheOptions['key'], $this->_content, $this->_cacheOptions['ttl']);
        }

        return $this;
    }

    /**
     * Choose a different view to render instead of last-controller/last-action
     *
     * <code>
     * class ProductsController extends \ManaPHP\Mvc\Controller
     * {
     *
     *    public function saveAction()
     *    {
     *
     *         //Do some save stuff...
     *
     *         //Then show the list view
     *         $this->view->pick("products/list");
     *    }
     * }
     * </code>
     *
     * @param string $view
     *
     * @return static
     */
    public function pick($view)
    {
        $parts = array_pad(explode('/', $view), -3, null);

        $this->_moduleName = $parts[0];
        $this->_controllerName = $parts[1];
        $this->_actionName = $parts[2];

        return $this;
    }

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
    public function partial($path, $vars = [], $cacheOptions = null)
    {
        if (!Text::contains($path, '/')) {
            $path = $this->_controllerName . '/' . $path;
        }

        $view = "/$this->_moduleName/Views/$path";

        if ($cacheOptions !== null) {
            if (!is_array($cacheOptions)) {
                $cacheOptions = ['ttl' => $cacheOptions];
            }

            if (!isset($cacheOptions['key'])) {
                $cacheOptions['key'] = 'Views/' . md5($view);
            }

            $content = $this->viewsCache->get($cacheOptions['key']);
            if ($content === false) {
                $content = $this->renderer->render("@app{$view}", $vars, false);
                $this->viewsCache->set($cacheOptions['key'], $content, $cacheOptions['ttl']);
            }
            echo $content;
        } else {
            $this->renderer->render("@app{$view}", $vars, true);
        }
    }

    /**
     * @param string    $widget
     * @param array     $options
     * @param int|array $cacheOptions
     *
     * @throws \ManaPHP\Mvc\View\Exception|\ManaPHP\Alias\Exception|\ManaPHP\Renderer\Exception
     */
    public function widget($widget, $options = [], $cacheOptions = null)
    {
        $widgetClassName = basename($this->alias->get('@app')) . "\\{$this->_moduleName}\\Widgets\\{$widget}Widget";

        if (!class_exists($widgetClassName)) {
            throw new Exception("widget '$widget' is not exist: " . $widgetClassName);
        }

        /**
         * @var \ManaPHP\Mvc\WidgetInterface $widgetInstance
         */
        $widgetInstance = $this->_dependencyInjector->get($widgetClassName);
        $vars = $widgetInstance->run($options);

        $view = "/$this->_moduleName/Views/Widgets/" . $widget;

        if ($cacheOptions !== null) {
            /** @noinspection CallableParameterUseCaseInTypeContextInspection */
            if (!is_array($cacheOptions)) {
                /** @noinspection CallableParameterUseCaseInTypeContextInspection */
                $cacheOptions = ['ttl' => $cacheOptions];
            }

            if (!isset($cacheOptions['key'])) {
                $cacheOptions['key'] = 'Views/' . md5($view);
            }

            $content = $this->viewsCache->get($cacheOptions['key']);
            if ($content === false) {
                if (is_string($vars)) {
                    $content = $vars;
                } else {
                    $content = $this->renderer->render("@app{$view}", $vars, false);
                }

                $this->viewsCache->set($cacheOptions['key'], $content, $cacheOptions['ttl']);
            }

            echo $content;
        } else {
            if (is_string($vars)) {
                echo $vars;
            } else {
                $this->renderer->render("@app{$view}", $vars, true);
            }
        }
    }

    /**
     * Externally sets the view content
     *
     *<code>
     *    $this->view->setContent("<h1>hello</h1>");
     *</code>
     *
     * @param string $content
     *
     * @return static
     */
    public function setContent($content)
    {
        $this->_content = $content;

        return $this;
    }

    /**
     * Returns cached output from another view stage
     *
     * @return string
     */
    public function getContent()
    {
        return $this->_content;
    }

    public function dump()
    {
        $data = parent::dump();
        if (is_string($data['_content'])) {
            $data['_content'] = 'content length: ' . strlen($data['_content']);
        }

        return $data;
    }
}