<?php
declare(strict_types=1);
namespace Karthus\Helper;

class Colors {
    // 式样
    public const RESET = "\033[0m";
    public const BOLD = "\033[1m";
    public const FAINT = "\033[2m";
    public const ITALIC = "\033[3m";
    public const UNDERLINE = "\033[4m";
    public const BLINK_SLOW = "\033[5m";
    public const BLINK_RAPID = "\033[6m";
    public const REVERSE_VIDEO = "\033[7m";
    public const CONCEALED = "\033[8m";
    public const CROSSED_OUT = "\033[9m";
    // 前景色
    public const FG_BLACK = "\033[30m";
    public const FG_RED = "\033[31m";
    public const FG_GREEN = "\033[32m";
    public const FG_YELLOW = "\033[33m";
    public const FG_BLUE = "\033[34m";
    public const FG_MAGENTA = "\033[35m";
    public const FG_CYAN = "\033[36m";
    public const FG_WHITE = "\033[37m";
    // 背景色
    public const BG_BLACK = "\033[40m";
    public const BG_RED = "\033[41m";
    public const BG_GREEN = "\033[42m";
    public const BG_YELLOW = "\033[43m";
    public const BG_BLUE = "\033[44m";
    public const BG_MAGENTA = "\033[45m";
    public const BG_CYAN = "\033[46m";
    public const BG_WHITE = "\033[47m";
    // 前景色高亮
    public const FG_HI_BLACK = "\033[90m";
    public const FG_HI_RED = "\033[91m";
    public const FG_HI_GREEN = "\033[92m";
    public const FG_HI_YELLOW = "\033[93m";
    public const FG_HI_BLUE = "\033[94m";
    public const FG_HI_MAGENTA = "\033[95m";
    public const FG_HI_CYAN = "\033[96m";
    public const FG_HI_WHITE = "\033[97m";
    // 背景色高亮
    public const BG_HI_BLACK = "\033[100m";
    public const BG_HI_RED = "\033[101m";
    public const BG_HI_GREEN = "\033[102m";
    public const BG_HI_YELLOW = "\033[103m";
    public const BG_HI_BLUE = "\033[104m";
    public const BG_HI_MAGENTA = "\033[105m";
    public const BG_HI_CYAN = "\033[106m";
    public const BG_HI_WHITE = "\033[107m";
}