<?php
declare(strict_types=1);
namespace karthus\support\view\Simple;

use RuntimeException;

class Template {
    protected string $template_path      = 'template'; //模板目录
    protected string $template_c_path    = 'runtime/tpl'; //编译目录
    protected string $template_type      = 'html'; //模板文件类型
    protected string $template_c_type    = 'tpl.php'; //模板编译文件类型
    protected string $template_tag_left  = '<!--{'; //左标签
    protected string $template_tag_right = '}-->'; //右标签
    protected bool $is_compile 		= true; //是否需要每次编译
    protected bool $is_view_filter = false; //是否过滤
    protected string $version = '1.0'; // 版本

    /**
     * 模板编译-设置模板信息
     * Controller中使用方法：$this->view->set_template_config($config)
     * @param array $config 设置参数
     * @return bool
     */
    public function setConfig(array $config): bool {
        $this->template_path    = $config['template_path'] ?? $this->template_path;
        $this->version          = $config['version'] ?? $this->version;
        $this->template_c_path  = $config['template_c_path'] ?? $this->template_c_path;
        $this->template_type    = $config['template_type'] ?? $this->template_type;
        $this->template_c_type  = $config['template_c_type'] ?? $this->template_c_type;
        $this->template_tag_left= $config['template_tag_left'] ?? $this->template_tag_left;
        $this->template_tag_right = $config['template_tag_right'] ?? $this->template_tag_right;
        $this->is_compile       = (bool)($config['is_compile'] ?? $this->is_compile);
        $this->is_view_filter = (bool)($config['is_view_filter'] ?? $this->is_view_filter);
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
     */
    protected function run(string $file_name) :string{
        $this->check_path(); //检测模板目录和编译目录
        [$template_file_name, $compile_file_name] = $this->get_file_name($file_name);
        if (($this->is_compile === true) ||
            ($this->is_compile === false &&
                !file_exists($compile_file_name))) { //是否强制编译
            $str = $this->read($template_file_name);
            $str = $this->layout($str); //layout模板页面中加载模板页
            $str = $this->replace($str);
            $str = $this->compileVersion($str, $template_file_name);
            $this->compile($compile_file_name, $str);
        }
        return $compile_file_name;
    }

    /**
     * 模板编译-读取静态模板
     * @param  string $template_file_name 文件名称，例如：test，不带文件.htm类型
     * @return string
     * @throws
     */
    private function read(string $template_file_name): string {
        if (!file_exists($template_file_name)) {
            throw new RuntimeException("$template_file_name is not exist!");
        }
        return @file_get_contents($template_file_name);
    }

    /**
     * 模板编译-编译模板
     * @param string $compile_file_name 文件名称，例如：test，不带文件.htm类型
     * @param string $str 写入编译文件的数据
     * @throws
     */
    private function compile(string $compile_file_name, string $str): void {
        if (($path = dirname($compile_file_name)) !== $this->template_c_path) { //自动创建文件夹
            $this->create_dir($path);
        }
        $ret = file_put_contents($compile_file_name, $str);
        if ($ret === false) {
            throw new RuntimeException("Please check the Directory have read/write permissions. 
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
     * @return void
     * @throws
     */
    private function check_path() : void {
        if (!is_dir($this->template_path) || !is_readable($this->template_path)) {
            throw new RuntimeException("template path ($this->template_path) is unread!");
        }
        if (!is_dir($this->template_c_path) || !is_readable($this->template_c_path)) {
            throw new RuntimeException("compiled path ($this->template_c_path) is unread! ");
        }
    }

    /**
     * 模板编译-编译文件-头部版本信息.
     * @param string $str 模板文件数据
     * @param string $template_file_name 模版文件
     */
    private function compileVersion(string $str, string $template_file_name): string
    {
        $date = date('Y-m-d H:i:s');
        return <<<EOT
<?php 
# WARNING: This file was auto-generated. Do not edit!
#          All your edit might be overwritten!
# 
# VERSION: $this->version
# CREATED: $date
# COMPILED: $template_file_name
# 
declare(strict_types=1); 
?>
$str
EOT;
    }

    /**
     * 模板编译-标签正则替换
     * @param  string $str 模板文件数据
     * @return string
     */
    private function replace(string $str): string {
        return $this->init($str, $this->template_tag_left, $this->template_tag_right); //编译
    }

    /**
     * 模板编译-layout 模板layout加载机制
     * 1. 在HTML模板中直接使用<!--{layout:user/version}-->就可以调用模板
     * @param  string $str 模板文件数据
     * @return string
     */
    private function layout(string $str) : string {
        preg_match_all("/(".$this->template_tag_left."layout:)(.*)(".$this->template_tag_right.")/",
            $str, $matches);
        $matches[2] = array_unique($matches[2]); //重复值移除
        $matches[0] = array_unique($matches[0]);
        foreach ($matches[2] as $val) {
            $this->run($val);
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
        return "<?php include('$this->template_c_path/$template_name.$this->template_c_type'); ?>";
    }


    /**
     * 创建目录
     * @param string $path
     * @return void
     */
    private function create_dir(string $path): void {
        if (is_dir($path)) {
            return;
        }
        if (!mkdir($path, 0777, true) && !is_dir($path)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $path));
        }
    }
}
