<?php
namespace Karthus\Template;

use Karthus\Component\Singleton;
use Karthus\Config;
use Karthus\Exception\Exception;

/**
 * Class View
 * @package Karthus\Template
 */
class View extends Template {
    use Singleton;

    public  static $view            = array(); //视图变量
    private static $template_arr    = array(); //视图存放器
    private static $remove_tpl_arr  = array(); //待移除

    /**
     * 模板-设置模板变量-将PHP中是变量输出到模板上，都需要经过此函数
     * 1. 模板赋值核心函数，$key => $value , 模板变量名称 => 控制器中变量
     * 2. 也可以直接用$this->view->view['key'] = $value
     * 3. 模板中对应$key
     * Controller中使用方法：$this->view->assign($key, $value);
     * @param  string  $key   KEY值-模板中的变量名称
     * @param  string|array|boolean|object|null|int  $value value值
     */
    public static function assign(string $key, $value): void {
        self::$view[$key] = $value;
    }

    /**
     * 模板-设置模板 设置HTML模板
     * 1. 设置模板，模板名称和类型，类型选择F和L的时候，是最先显示和最后显示的模板
     * 2. 比如设置 user目录下的userinfo.htm目录，则 set_tpl('user/userinfo') 不需要填写.htm
     * Controller中使用方法：$this->view->set_tpl($template_name, $type = '');
     * @param  string  $template_name 模板名称
     * @param  string  $type 类型，F-头模板，L-脚步模板
     */
    public static function set_tpl(string $template_name, string $type = ''): void{
        if ($type === 'F') {
            self::$template_arr['F'] = $template_name;
        } elseif ($type === 'L') {
            self::$template_arr['L'] = $template_name;
        } else {
            self::$template_arr[] = $template_name;
        }
    }

    /**
     * 模板-移除模板
     * 1. 如果在控制器的基类中已经导入头部和脚步模板，应用中需要替换头部模板
     * 2. 移除模板需要在display() 模板显示前使用
     * Controller中使用方法：$this->view->remove_tpl($remove_tpl);
     * @param string $remove_tpl 需要移除模板名称
     */
    public static function remove_tpl(string $remove_tpl) {
        self::$remove_tpl_arr[] = $remove_tpl;
    }

    /**
     * 模板-获取模板数组
     * Controller中使用方法：$this->view->get_tpl();
     * @return array
     */
    public static function get_tpl(): array {
        return self::$template_arr;
    }

    /**
     * 模板-显示视图
     *
     * Controller中使用方法：view->display();
     * @param string $template
     * @return string
     * @throws
     */
    public static function display(string $template = ''): string {
        ob_start();
        if ($template !== '') {
            self::set_tpl($template);
        }
        $config   = Config::getInstance();
        $instance = self::getInstance();
        $conf     = $config->getConf("VIEW");
        $instance->set_template_config($conf);
        if (is_array(self::$view)) {
            if ((bool)$conf['is_view_filter']) {
                $instance->output(self::$view);
            }
            foreach (self::$view as $key => $val) {
                ${$key} = $val;
            }
        }
        self::$template_arr = $instance->parse_template_arr(self::$template_arr); //模板设置
        foreach (self::$template_arr as $file_name) {
            if (in_array($file_name, self::$remove_tpl_arr)) {
                continue;
            }
            $complie_file_name = $instance->template_run($file_name); //模板编译
            if (!file_exists($complie_file_name)) {
                throw new Exception($complie_file_name. ' is not exist!');
            }
            include_once($complie_file_name);
        }
        return ob_get_clean();
    }

    /**
     * 模板-处理视图存放器数组，分离头模板和脚模板顺序
     * @param  array  $arr 视图存放器数组
     * @return array
     */
    private function parse_template_arr(array $arr): array {
        $temp = $arr;
        unset($temp['F'], $temp['L']);
        if (isset(self::$template_arr['F'])) { //头模板
            array_unshift($temp, self::$template_arr['F']);
        }
        if (isset(self::$template_arr['L'])) {
            $temp[] = self::$template_arr['L'];
        }
        return $temp;
    }

    /**
     * 模板-模板变量输出过滤
     * @param array $value 视图存放器数组
     */
    private function output(array $value): void {
        foreach ($value as $key => $val) {
            if (is_array($val)) {
                $this->output($value[$key]);
            } elseif (is_object($val)) {
                $value[$key] = $val;
            } else {
                if (function_exists('htmlspecialchars')) {
                    $value[$key] =  htmlspecialchars($val);
                } else {
                    $value[$key] =  str_replace(["&", '"', "'", "<", ">", "%3C", "%3E", ],
                        ["&amp;", "&quot;", "&#039;", "&lt;", "&gt;", "&lt;", "&gt;", ], $val);
                }
            }
        }
    }
}