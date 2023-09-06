<?php

declare(strict_types=1);
/**
 * This file is part of Karthus.
 *
 * @link     https://github.com/karhuts
 * @document https://github.com/karhuts/framework
 * @contact  294953530@qq.com
 * @license  https://github.com/karhuts/framework/blob/master/LICENSE
 */

namespace karthus\support\view\Simple;

use RuntimeException;

/**
 * Class view.
 */
class View extends Template
{
    public array $view = []; // 视图变量

    private array $template_arr = []; // 视图存放器

    private array $remove_tpl_arr = []; // 待移除

    public function __construct(array $config = [])
    {
        $this->setConfig($config);
    }

    /**
     * 模板-设置模板变量-将PHP中是变量输出到模板上，都需要经过此函数
     * 1. 模板赋值核心函数，$key => $value , 模板变量名称 => 控制器中变量
     * 2. 也可以直接用$this->view->view['key'] = $value
     * 3. 模板中对应$key
     * Controller中使用方法：$this->view->assign($key, $value);.
     * @param string $key KEY值-模板中的变量名称
     * @param null|array|bool|int|object|string $value value值
     */
    public function assign(string $key, object|int|bool|array|string|null $value): void
    {
        $this->view[$key] = $value;
    }

    /**
     * 模板-设置模板 设置HTML模板
     * 1. 设置模板，模板名称和类型，类型选择F和L的时候，是最先显示和最后显示的模板
     * 2. 比如设置 user目录下的userinfo.htm目录，则 set_tpl('user/userinfo') 不需要填写.htm
     * Controller中使用方法：$this->view->set_tpl($template_name, $type = '');.
     * @param string $template_name 模板名称
     * @param string $type 类型，F-头模板，L-脚步模板
     */
    public function set(string $template_name, string $type = ''): void
    {
        if ($type === 'F') {
            $this->template_arr['F'] = $template_name;
        } elseif ($type === 'L') {
            $this->template_arr['L'] = $template_name;
        } else {
            $this->template_arr[] = $template_name;
        }
    }

    /**
     * 模板-移除模板
     * 1. 如果在控制器的基类中已经导入头部和脚步模板，应用中需要替换头部模板
     * 2. 移除模板需要在display() 模板显示前使用
     * Controller中使用方法：$this->view->remove_tpl($remove_tpl);.
     * @param string $remove_tpl 需要移除模板名称
     */
    public function remove(string $remove_tpl): void
    {
        $this->remove_tpl_arr[] = $remove_tpl;
    }

    /**
     * 模板-获取模板数组
     * Controller中使用方法：$this->view->get_tpl();.
     */
    public function get(): array
    {
        return $this->template_arr;
    }

    /**
     * 模板-显示视图.
     *
     * Controller中使用方法：view->display();
     */
    public function display(string $template = ''): string
    {
        ob_clean();
        ob_start();
        if ($template !== '') {
            $this->set($template);
        }
        if ($this->view) {
            if ($this->is_view_filter) {
                $this->output($this->view);
            }
            foreach ($this->view as $key => $val) {
                ${$key} = $val;
            }
        }
        $this->template_arr = $this->parse($this->template_arr); // 模板设置
        foreach ($this->template_arr as $file_name) {
            if (in_array($file_name, $this->remove_tpl_arr, true)) {
                continue;
            }
            $complie_file_name = $this->run($file_name); // 模板编译
            if (! file_exists($complie_file_name)) {
                throw new RuntimeException($complie_file_name . ' is not exist!');
            }
            include_once $complie_file_name;
        }
        $content = ob_get_clean();
        ob_end_clean();

        return $this->after($content);
    }

    /**
     * 模板-模板变量输出过滤.
     * @param array $value 视图存放器数组
     */
    protected function output(array $value): void
    {
        foreach ($value as $key => $val) {
            if (is_array($val)) {
                $this->output($val);
            } elseif (is_object($val)) {
                $value[$key] = $val;
            } elseif (function_exists('htmlspecialchars')) {
                $value[$key] = htmlspecialchars((string) $val);
            } else {
                $value[$key] = str_replace(
                    ['&', '"', "'", '<', '>', '%3C', '%3E'],
                    ['&amp;', '&quot;', '&#039;', '&lt;', '&gt;', '&lt;', '&gt;'],
                    $val
                );
            }
        }
    }

    protected function after(string $content): string
    {
        $time = microtime(true);
        $spend = $time - $GLOBALS['g_timestamp'];
        $spend = round($spend, 6) * 1000;
        $spend = round($spend, 2);

        return "{$content}\r\n<!--Run Times:{$spend}ms-->";
    }

    /**
     * 模板-处理视图存放器数组，分离头模板和脚模板顺序.
     * @param array $arr 视图存放器数组
     */
    private function parse(array $arr): array
    {
        $temp = $arr;
        unset($temp['F'], $temp['L']);
        if (isset($this->template_arr['F'])) { // 头模板
            array_unshift($temp, $this->template_arr['F']);
        }
        if (isset($this->template_arr['L'])) {
            $temp[] = $this->template_arr['L'];
        }
        return $temp;
    }
}
