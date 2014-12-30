<?php
/* 如果已经完成安装，在./data下面会有一个  install.lock 文件  */
if (is_file('./data/install.lock')) {
    header('Location: ./');
    exit;
}

/* 如果还没有完成安装的话，xxx  */
/* 应用名称*/
define('APP_NAME', 'install');
/* 应用目录*/
define('APP_PATH', './install/');
/* DEBUG开关*/
define('APP_DEBUG', false);
require("./_core/setup.php");
