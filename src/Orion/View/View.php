<?php

namespace Orion\View;

/**
 * Class View
 * @package Orion\View
 */
class View {

    public $view;
    public $data;
    public $isJson;

    /**
     * View constructor.
     * @param $view
     * @param bool $isJson
     */
    public function __construct($view, $isJson = false)
    {
        $this->view = $view;
        $this->isJson = $isJson;
    }

    /**
     * @param null $viewName
     * @return View
     */
    public static function make($viewName = null)
    {
        // 检查某常量是否存在
        if ( !defined('VIEW_BASE_PATH') ) {
            throw new \InvalidArgumentException("VIEW_BASE_PATH is undefined!");
        }
        // 检查模板名
        if ( ! $viewName ) {
            throw new \InvalidArgumentException("View name can not be empty!");
        } else {
            $viewFilePath = self::getFilePath($viewName);
            if ( is_file($viewFilePath) ) {
                return new View($viewFilePath);
            } else {
                throw new \UnexpectedValueException("View file does not exist!");
            }
        }
    }

    /**
     * @param $arr
     * @return View
     */
    public static function json($arr)
    {
        if ( !is_array($arr) ) {
            throw new \UnexpectedValueException("View::json can only receive Array!");
        } else {
            return new View($arr, true);
        }
    }

    /**
     * @param null $view
     */
    public static function process($view = null)
    {
        if ( is_string($view) ) {
            echo $view;
            return;
        }
        if ( isset($view) && $view->isJson ) {
            echo json_encode($view->view);
        } else {
            if ( $view instanceof View ) {
                if ($view->data) {
                    extract($view->data); // 分配数据到模板
                }
                require $view->view; // 加载模板视图
            }
        }
    }

    /**
     * @param $key
     * @param null $value
     * @return $this
     */
    public function with($key, $value = null)
    {
        $this->data[$key] = $value;
        return $this;
    }

    /**
     * @param $viewName
     * @return string
     */
    private static function getFilePath($viewName)
    {
        $filePath = str_replace('.', '/', $viewName);
        return VIEW_BASE_PATH .$filePath. '.php';
    }

    /**
     * @param $method
     * @param $parameters
     * @return $this
     */
    public function __call($method, $parameters)
    {
        if (starts_with($method, 'with'))
        {
            return $this->with(snake_case(substr($method, 4)), $parameters[0]);
        }
        throw new \BadMethodCallException("Function [$method] does not exist!");
    }

}