<?php

return array(
    'code'      => 'cuzy',
    'name'      => 'cuzy',
    'desc'      => ' 通过cuzy SDK获取商品数据，可到 http://www.cuzy.com/index/doc_web_app 查看详细',
    'author'    => 'CUZY TEAM',
    'domain'   => 'cuzy.com',
    'url'   => 'http://www.cuzy.com',
    'version'   => '1.0',
    'config'    => array(
        'app_key'   => array(        //账号
            'text'  => 'App Key',
            'desc'  => 'cuzy SDK申请应用获取',
            'type'  => 'text',
        ),
        'app_secret'       => array(        //密钥
            'text'  => 'App Secret',
            'desc'  => 'cuzy SDK申请应用获取',
            'type'  => 'text',
        )
    )
);