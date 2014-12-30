<?php
class cuzy_batchAction extends backendAction {
	private $_tbconfig = NULL;
	private $_cuzyconfig = NULL;

	public function init_cuzy() {
		$item_site = M('item_site');
		$api_config      = $item_site->where(array('code' => 'taobao'))->getField('config');
		$cuzy_config      = $item_site->where(array('code' => 'cuzy'))->getField('config');
		$this->_tbconfig = unserialize($api_config);
		$this->_cuzyconfig = unserialize($cuzy_config);
	}
    public function index() {
        //采集马甲
        $auto_user = M('auto_user')->select();
        $this->assign('auto_user', $auto_user);
    	$this->display();
    }

    public function import() {
		$this->init_cuzy();
        $type = $this->_post('type', 'trim', 'input');
        $auid = $this->_post('auid', 'intval');
        !$auid && $this->error('auto_user_error');
        
        switch ($type) {
            case 'input':
                $url_list = $this->_post('url_list', 'urldecode');
                break;
            
            case 'file':
                $url_file = $_FILES['url_file'];
                $url_list = file_get_contents($url_file['tmp_name']);
                break;
        }
        $url_list = split(PHP_EOL, $url_list);

        //获取马甲
        $auto_user_mod = M('auto_user');
        $user_mod = M('user');
        $unames = $auto_user_mod->where(array('id'=>$auid))->getField('users');
        $unamea = explode(',', $unames);
        $users = $user_mod->field('id,username')->where(array('username'=>array('in', $unamea)))->select();
        !$users && $this->error('auto_user_error');
        //开始处理
        $item_mod = D('item');
		$baseUrl = 'http://www.cuzy.com/Webapi/getNumiidToItems?appkey='.$this->_cuzyconfig['app_key'].'&appsecret='.$this->_cuzyconfig['app_secret'];
        foreach ($url_list as $url) {
            if (!$url) continue;
            //获取商品信息
			$numiid = $this->get_id($url);
			$res = json_decode(file_get_contents($baseUrl.'&numiid='.$numiid), TRUE);
			if($res['error_response']['code'] == 0) {
				$item = $res['cuzy_items_get_response']['cuzy_items']['item'][0];
				count($item) == 0 && $this->error('抱歉cuzy库暂未有此商品数据。');
			}else{
				$this->error($res['error_response']['msg']);
			}
            //判断是否已经存在(避免后期修改过商品信息，如果存在则不处理)
            $item_id = $item_mod->where(array('key_id'=>$item['key_id']))->getField('id');
            if ($item_id) continue;

            //添加商品
            $result = $this->_publish_insert($item, $users);
        }
        $this->success(L('operation_success'));
    }
	
	private function _publish_insert($item, $users) {
		//获取商品相册信息
		$tb_top = $this->_get_tb_top();
		$req = $tb_top->load_api('ItemsListGetRequest');
		$req->setFields('num_iid,item_img');
		$req->setNumIids($item['num_iid']);
		$resp = $tb_top->execute($req);
		$item_imgs = (array)$resp->items->item->item_imgs;
		$imgs = array();
		foreach ($item_imgs[item_img] as $_img) {
			$_img = (array) $_img;
			if ($_img['url']) {
				$imgs[] = array(
					'url' => $_img['url'],
					'surl' => $_img['url'] . '_100x100.jpg',
					'ordid' => $_img['position']
				);
			}
		}
		 //随机取一个用户
		 $user_rand = array_rand($users);
		 $item['title'] = strip_tags($item['title']);
		 $insert_item = array(
			 'key_id' => 'taobao_' . $item['num_iid'],
			 'taobao_sid' => $item['taobao_sid'],
			 'cate_id' => $item['cate_id'],
			 'uid' => $users[$user_rand]['id'],
			 'uname' => $users[$user_rand]['username'],
			 'title' => $item['title'],
			 'intro' => $item['title'],
			 'img' => $item['pic_url'],
			 'price' => $item['promotion_price'],
			 'url' => $item['click_url'],
			 'rates' => $item['commission_rate'] / 100,
			 'likes' => $item['likes']?$item['likes']:0,
			 'imgs' => $imgs?$imgs:array(array('url' =>$insert_item['img']))
		 );

		 $result = D('item')->publish($insert_item);
		 return $result;
	}
	private function _get_tb_top() {
        vendor('Taobaotop.TopClient');
        vendor('Taobaotop.RequestCheckUtil');
        vendor('Taobaotop.Logger');
        $tb_top = new TopClient;
        $tb_top->appkey = $this->_tbconfig['app_key'];
        $tb_top->secretKey = $this->_tbconfig['app_secret'];
        return $tb_top;
    }
	public function get_id($url) {
        $id = 0;
        $parse = parse_url($url);
        if (isset($parse['query'])) {
            parse_str($parse['query'], $params);
            if (isset($params['id'])) {
                $id = $params['id'];
            } elseif (isset($params['item_id'])) {
                $id = $params['item_id'];
            } elseif (isset($params['default_item_id'])) {
                $id = $params['default_item_id'];
            }
        }
        return $id;
    }
	
}