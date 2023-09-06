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

namespace karthus\support;

use karthus\Singleton;

class Pager
{
    use Singleton;

    public const LIMIT = 20;

    private int $show_num = 9; // 分页中显示多少个

    private bool $isAjax = false; // 是否是AJAX翻页

    private array $conf = [
        'first_last' => 0, // 首页-尾页 0-关闭 1-开启
        'back_next' => 1, // 上一页-下一页 0-关闭 1-开启
        'total_num' => 0, // 是否显示总页数 0-关闭 1-开启
        'page_num' => 1, // 翻页数 0-关闭 1-开启
        'select' => 0,  // 下拉列表选择 0-关闭 1-开启
    ];

    private string $style_config = '<style type="text/css">
	.karthus_pages {font:12px/1.6em Helvetica, Arial, sans-serif;overflow:hidden; text-align:center; font-family:Verdana,serif;margin-bottom:5px;  }
	.karthus_pages a, .pages{ margin:0 1px; padding:1px 6px; border:1px solid #E4E4E4; text-decoration:none!important; }
	.karthus_pages a:hover { border-color:#369; }
	.karthus_pages strong { margin:0 1px; padding:2px 6px; border-color:#369; background:#369; color:#FFF; text-decoration:none!important; }
	.karthus_pages .back { padding:4px 6px 2px 20px!important;  font-family:simsun,serif; }
	.karthus_pages .next {  padding:4px 20px 2px 6px!important; font-family:simsun,serif; }
	.karthus_pages .first { padding:4px 6px 2px 4px!important;  font-family:simsun,serif; }
	.karthus_pages .last { padding:4px 4px 2px 6px!important; font-family:simsun,serif; }
	</style>';

    public function __construct(array $config = [])
    {
        $this->conf = empty($config) ? $this->conf : $config;
    }

    /**
     * 分页-分页入口.
     * @param int $count 总共多少数据
     * @param int $prepage 每页显示多少条
     * @param int $npage 当前页
     * @param string $url URL
     * @param string $endhtml 结束添加的HTMNL
     * @param int $show_end 是否显示结尾标识
     */
    public function execute(
        int $count,
        int $prepage = self::LIMIT,
        int $npage = 1,
        string $url = '/',
        bool $isAjax = false,
        bool $default_style = false,
        string $endhtml = '',
        int $show_end = 1
    ): string {
        $page_num = ceil($count / $prepage); // 总共多少页
        $page = $npage; // 当前分页
        $page = max($page, 1);
        $page = ($page > $page_num) ? $page_num : $page;
        $url = ! str_contains($url, '?') ? $url . '?' : $url;
        $this->isAjax = $isAjax;
        return $this->pager_html((int) $page_num, $url, $page, $default_style, $endhtml, (bool) $show_end);
    }

    /**
     * 分页-获取分页HTML显示.
     * @param int $page_num 页数
     * @param string $url URL
     * @param int $page 当前页
     * @param bool $show_end 是否展示末尾
     */
    protected function pager_html(
        int $page_num,
        string $url,
        int $page,
        bool $default_style = false,
        string $endhtml = '',
        bool $show_end = false
    ): string {
        if ($page_num >= 1) {
            [$start, $end] = $this->get_start_and_end($page, $page_num);
            [$back, $next] = $this->get_pager_next_back_html($url, $page, $page_num);
            [$first, $last] = $this->get_first_last_html($page_num, $url, $show_end);
            if ($default_style === true) {
                $html = $this->style_config . "<div class='karthus_pages'>";
            } else {
                $html = '<ul class="pagination m-0">';
            }
            $html .= $first;
            $html .= $back;
            $html .= $this->get_pager_num_html($start, $end, $url, $page);
            $html .= $next;
            $html .= $last;
            $html .= $this->get_total_num_html($page_num);
            $html .= $this->get_select_html($page_num, $url, $page);
            if ($default_style === true) {
                if ($endhtml) {
                    $html .= "<li>{$endhtml}</li>";
                }
                $html .= '</div>';
            } else {
                if ($endhtml) {
                    $html .= "<li class='page-item'>{$endhtml}</li>";
                }
                $html .= '</ul>';
            }
            return $html;
        }
        return '';
    }

    /**
     * 分页-获取分页数字的列表.
     * @param int $start 开始数
     * @param int $end 结束数
     * @param string $url URL地址
     * @param int $page 当前页
     */
    private function get_pager_num_html(int $start, int $end, string $url, int $page): string
    {
        // 是否开启
        if ($this->conf['page_num'] === 0) {
            return '';
        }
        $html = '';
        for ($i = $start; $i <= $end; ++$i) {
            if ($i === $page) {
                $html .= "<li class=\"page-item active\"><a class='page-link' href='javascript:void(0);'>{$i}</a></li>";
            } elseif ($this->isAjax) {
                $html .= "<li class='page-item'><a class='page-link' href='javascript:void(0)' onclick='return common_page($(this))' data-url=\"{$url}&page={$i}\" data-id='{$i}'>{$i}</a></li>";
            } else {
                $html .= "<li class='page-item'><a class='page-link' href='{$url}&page={$i}'>{$i}</a></li>";
            }
        }
        return $html;
    }

    /**
     * 分页-分页总页数显示.
     */
    private function get_total_num_html(int $page_num): string
    {
        if ($this->conf['total_num'] === 0) {
            return '';
        } // 是否开启
        return "&nbsp;&nbsp;共{$page_num}页";
    }

    /**
     * 分页-分页首页和尾页显示.
     * @param int $page_num 页数
     * @param string $url URL地址
     * @param bool $show_end 是否显示尾页
     * @return string[]
     */
    private function get_first_last_html(int $page_num, string $url, bool $show_end = true): array
    {
        $last = $first = '';
        // 是否开启
        if ($this->conf['first_last'] === 0) {
            return [$first, $last];
        }
        if ($this->isAjax) {
            $first = "<li class='page-item'>
                        <a class='page-link' href='javascript:void(0)' data-url=\"{$url}&page=1\" data-id='1' onclick='return common_page($(this))'>首页</a>
                      </li>";
            if ($show_end === true) {
                $last = "<li class='page-item'><a class='page-link' href='javascript:void(0)' data-url=\"{$url}&page={$page_num}\" onclick='return common_page($(this))' data-id='{$page_num}'>尾页</a></li>";
            }
        } else {
            $first = "<li class='page-item'><a class='page-link' href='{$url}&page=1'>首页</a></li>";
            if ($show_end === true) {
                $last = "<li class='page-item'><a class='page-link' href='{$url}&page={$page_num}'>尾页</a></li>";
            }
        }
        return [$first, $last];
    }

    /**
     *	分页-获取分页上一页-下一页HTML.
     * @param string $url URL地址
     * @param int $page 当前页
     * @param int $page_num 页数
     * @return string[]
     */
    private function get_pager_next_back_html(string $url, int $page, int $page_num): array
    {
        if ($this->conf['back_next'] === 0) {
            return ['', ''];
        } // 是否开启
        $prevText = '<!-- Download SVG icon from http://tabler-icons.io/i/chevron-left -->
                     <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><polyline points="15 6 9 12 15 18" /></svg>
                     上一页';
        $nextText = '下一页 
                     <!-- Download SVG icon from http://tabler-icons.io/i/chevron-right -->
                     <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><polyline points="9 6 15 12 9 18" /></svg>';
        $next_page = $page + 1;
        if ($this->isAjax) {
            $next = "<li class='page-item'><a class='page-link' href='javascript:void(0)' data-url=\"{$url}&page={$next_page}\" onclick='return common_page($(this))' data-id='{$next_page}'>{$nextText}</a></li>";
        } else {
            $next = "<li class='page-item'><a class='page-link' href='{$url}&page={$next_page}'>{$nextText}</a></li>";
        }
        if ($page === $page_num) {
            $next = '';
        }
        $back_page = $page - 1;
        if ($this->isAjax) {
            $back = "<li class='page-item'><a class='page-link' href='javascript:void(0)' data-url=\"{$url}&page={$back_page}\" onclick='return common_page($(this))' data-id='{$back_page}'>{$prevText}</a></li>";
        } else {
            $back = "<li class='page-item'><a class='page-link' href='{$url}&page={$back_page}'>{$prevText}</a></li>";
        }
        if ($page === 1) {
            $back = '';
        }
        return [$back, $next];
    }

    /**
     *	分页-Select选择器.
     * @param int $page_num 页数
     * @param string $url URL地址
     * @param int $page 当前页
     */
    private function get_select_html(int $page_num, string $url, int $page): string
    {
        if ($this->conf['select'] === 0) {
            return '';
        }
        $html = '&nbsp;&nbsp;<select name="select" onchange="javascript:window.location.href=this.options[this.selectedIndex].value">';
        for ($i = 1; $i <= $page_num; ++$i) {
            if ($page === $i) {
                $selected = ' selected';
            } else {
                $selected = '';
            }
            $html .= "<option value='{$url}&page={$i}' {$selected}>{$i}</option>";
        }
        $html .= '</select>';
        return $html;
    }

    /**
     *	分页-获取分页显示数字.
     * @param int $page 当前页
     * @param int $page_num 页数
     * @return array(start, end)
     */
    private function get_start_and_end(int $page, int $page_num): array
    {
        $temp = floor($this->show_num / 2);
        if ($page_num < $this->show_num) {
            return [1, $page_num];
        }
        if ($page <= $temp) {
            $start = 1;
            $end = $this->show_num;
        } elseif (($page_num - $temp) < $page) {
            $start = $page_num - $this->show_num + 1;
            $end = $page_num;
        } else {
            $start = $page - $temp;
            $end = $page - $temp + $this->show_num - 1;
        }
        return [$start, $end];
    }
}
