<?php

/**
 * cuzy商品获取
 *
 * @author andery
 */
class cuzy_itemcollect {

    private $_code = 'cuzy';

    public function fetch($url) {
        $id = $this->get_id($url);
        if (!$id) {
            return false;
        }
        $key = 'taobao_' . $id;
        $item_site = M('item_site')->where(array('code' => $this->_code))->find();
        $api_config = unserialize($item_site['config']);

        //使用淘宝开放平台API
		vendor('cuzySDK.CuzyClient');
		$cuzy_top         = new CuzyClient();
		$cuzy_top->appkey = $api_config['app_key'];
		$cuzy_top->appsecret = $api_config['app_secret'];
		$cuzy_top->webFrom = 'pinphp3';

		$req = $cuzy_top->load_api("GetTbItem");
		$req->setRedirectByWebRoot(substr(dirname(__FILE__), 0, -33));
		$req->setNumiid($id);

		$resp = $cuzy_top->advExecute($req);
		$item = $resp->getData() ; // 获取商品数据
		if(empty($item) || !is_array($item))
			return false;

        $result = array();
        $result['item']['key_id'] = $key;
        $result['item']['title'] = strip_tags($item['title']);
        $result['item']['price'] = $item['promotion_price'] ? $item['promotion_price'] : $item['price'];
        $result['item']['img'] = $item['pic_url'];
        $result['item']['url'] = $item['click_url'];
        $result['item']['rates'] = $item['commission_rate'] / 100;
        $result['item']['orig_id'] = D('item_orig')->get_id_by_url($url);

        //商品相册
        $result['item']['imgs'] = array();
        $item_imgs = (array) $item['pic_urls'];
        $item_imgs = (array) $item_imgs['pic_url'];
        foreach ($item_imgs as $img_key=>$_img) {
            if ($_img) {
                $result['item']['imgs'][] = array(
                    'url' => $_img,
                    'ordid' => $img_key
                );
            }
        }
        if (empty($result['item']['imgs'])) {
            $result['item']['imgs'][] = array(
                'url' => $result['item']['img'],
            );
        }
        
        return $result;
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

    public function get_key($url) {
        $id = $this->get_id($url);
        return 'taobao_' . $id;
    }

}
