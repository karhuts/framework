<?php
namespace Karthus\Template;

use Karthus\Exception\Exception;
use RuntimeException;

class Template {
    private $template_path      = 'tpl'; //模板目录
    private $template_c_path    = 'data/tpl'; //编译目录
    private $template_type      = 'html'; //模板文件类型
    private $template_c_type    = 'tpl.php'; //模板编译文件类型
    private $template_tag_left  = '<!--{'; //左标签
    private $template_tag_right = '}-->'; //右标签
    private $is_compile 		= true; //是否需要每次编译

    /**
     * 模板编译-设置模板信息
     * Controller中使用方法：$this->view->set_template_config($config)
     * @param array $config 设置参数
     * @return bool
     */
    public function set_template_config(array $config): bool {
        if (!is_array($config)) {
            return false;
        }

        $this->template_path    = $config['template_path'] ?? $this->template_path;
        $this->template_c_path  = $config['template_c_path'] ?? $this->template_c_path;
        $this->template_type    = $config['template_type'] ?? $this->template_type;
        $this->template_c_type  = $config['template_c_type'] ?? $this->template_c_type;
        $this->template_tag_left= $config['template_tag_left'] ?? $this->template_tag_left;
        $this->template_tag_right = $config['template_tag_right'] ?? $this->template_tag_right;
        $this->is_compile       = (bool)($config['is_compile'] ?? $this->is_compile);
        return true;
    }


    /**
     * 初始化
     * @param $str string
     * @param $left string
     * @param $right string
     * @return string
     */
    public function init(string $str, string $left, string $right) : string {
        //if操作
        $str = preg_replace( "/".$left."if([^{]+?)".$right."/", "<?php if \\1 { ?>", $str );
        $str = preg_replace( "/".$left."else".$right."/", "<?php } else { ?>", $str );
        $str = preg_replace( "/".$left."elseif([^{]+?)".$right."/", "<?php } elseif \\1 { ?>", $str );
        //foreach操作
        $str = preg_replace("/".$left."foreach([^{]+?)".$right."/","<?php foreach \\1 { ?>",$str);
        $str = preg_replace("/".$left."\/foreach".$right."/","<?php } ?>",$str);
        //for操作
        $str = preg_replace("/".$left."for([^{]+?)".$right."/","<?php for \\1 { ?>",$str);
        $str = preg_replace("/".$left."\/for".$right."/","<?php } ?>",$str);
        //输出变量
        $str = preg_replace( "/".$left."(\\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_$\x7f-\xff\[\]\'\'\"]*)".$right."/",
            "<?php echo \\1;?>", $str );
        //常量输出
        $str = preg_replace( "/".$left."([A-Z_\x7f-\xff][A-Z0-9_\x7f-\xff]*)".$right."/s", "<?php echo \\1;?>", $str);
        //标签解析
        $str = preg_replace ( "/".$left."\/if".$right."/", "<?php } ?>", $str );
        $pattern        = array('/'.$left.'/', '/'.$right.'/');
        $replacement    = array('<?php ', ' ?>');
        return preg_replace($pattern, $replacement, $str);
    }



    /***
     * 模板编译-模板类入口函数
     * 1. 获取模板，如果模板未编译，则编译
     * @param  string $file_name 文件名称，例如：test，不带文件.htm类型
     * @return string
     * @throws Exception
     */
    protected function template_run(string $file_name) :string{
        $this->check_path(); //检测模板目录和编译目录
        [$template_file_name, $compile_file_name] = $this->get_file_name($file_name);
        if (($this->is_compile === true) ||
            ($this->is_compile === false &&
                !file_exists($compile_file_name))) { //是否强制编译
            $str = $this->read_template($template_file_name);
            $str = $this->layout($str); //layout模板页面中加载模板页
            $str = $this->replace_tag($str);
            $str = $this->compile_version($str, $template_file_name);
            $this->compile_template($compile_file_name, $str);
        }
        return $compile_file_name;
    }

    /**
     * 模板编译-读取静态模板
     * @param  string $template_file_name 文件名称，例如：test，不带文件.htm类型
     * @return string
     * @throws
     */
    private function read_template(string $template_file_name): string {
        if (!file_exists($template_file_name)) {
            throw new Exception($template_file_name. ' is not exist!');
        }
        return @file_get_contents($template_file_name);
    }

    /**
     * 模板编译-编译模板
     * @param string $compile_file_name 文件名称，例如：test，不带文件.htm类型
     * @param string $str 写入编译文件的数据
     */
    private function compile_template(string $compile_file_name, string $str): void {
        if (($path = dirname($compile_file_name)) !== $this->template_c_path) { //自动创建文件夹
            $this->create_dir($path);
        }
        $ret = @file_put_contents($compile_file_name, $str);
        if ($ret === false) {
            throw new Exception("Please check the Directory have read/write permissions. 
                If it's not, please set 777 limits. 
                Can not write $compile_file_name");
        }
    }

    /**
     * 模板编译-通过传入的filename，获取要编译的静态页面和生成编译文件的文件名
     * @param string $file_name 文件名称，例如：test，不带文件.htm类型
     * @return array
     */
    private function get_file_name(string $file_name): array {
        return array(
            $this->template_path .'/'. $file_name . '.' . $this->template_type, //组装模板文件路径
            $this->template_c_path .'/'. $file_name . '.' . $this->template_c_type //模板编译路径
        );
    }

    /**
     * 模板编译-检测模板目录和编译目录是否可写
     * @return boolean
     * @throws
     */
    private function check_path() : bool {
        if (!is_dir($this->template_path) || !is_readable($this->template_path)) {
            throw new Exception('template path is unread! '. $this->template_path);
        }
        if (!is_dir($this->template_c_path) || !is_readable($this->template_c_path)) {
            throw new Exception('compiled path is unread! '. $this->template_c_path);
        }
        return true;
    }

    /**
     * 模板编译-编译文件-头部版本信息
     * @param string $str 模板文件数据
     * @param string $template_file_name 模版文件
     * @return string
     */
    private function compile_version(string $str, string $template_file_name) : string {
        $date           = date('Y-m-d H:i:s');
        $version_str    = "<?php  \n/* Version 1.0 ,Create on $date";
        $version_str    = "$version_str, compiled from  $template_file_name  */ \n?>" . "\r\n";
        return $version_str . $str;
    }

    /**
     * 模板编译-标签正则替换
     * @param  string $str 模板文件数据
     * @return string
     */
    private function replace_tag(string $str): string {
        return $this->init($str, $this->template_tag_left, $this->template_tag_right); //编译
    }

    /**
     * 模板编译-layout 模板layout加载机制
     * 1. 在HTML模板中直接使用<!--{layout:user/version}-->就可以调用模板
     * @param  string $str 模板文件数据
     * @return string
     * @throws Exception
     */
    private function layout(string $str) : string {
        preg_match_all("/(".$this->template_tag_left."layout:)(.*)(".$this->template_tag_right.")/",
            $str, $matches);
        $matches[2] = array_unique($matches[2]); //重复值移除
        $matches[0] = array_unique($matches[0]);
        foreach ($matches[2] as $val) {
            $this->template_run($val);
        }
        foreach ($matches[0] as $k => $v) {
            $str = str_replace($v, $this->layout_path($matches[2][$k]), $str);
        }
        return $str;
    }

    /**
     * 模板编译-layout路径
     * @param  string $template_name 模板名称
     * @return string
     */
    private function layout_path(string $template_name) :string {
        return "<?php include('{$this->template_c_path}/{$template_name}.{$this->template_c_type}'); ?>";
    }


    /**
     * 创建目录
     * @param string $path
     * @return bool
     */
    private function create_dir(string $path): bool {
        if (is_dir($path)) {
            return false;
        }
        if (!mkdir($path, 0777, true) && !is_dir($path)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $path));
        }
        return true;
    }

}
