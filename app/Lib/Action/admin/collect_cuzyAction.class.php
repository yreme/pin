<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Lion
 * Date: 13-7-26
 * Time: 下午3:15
 * To change this template use File | Settings | File Templates.
 */

class collect_cuzyAction extends backendAction {
	private $_tbconfig = NULL;
	private $_cuzyconfig = NULL;

	public function _initialize() {
		parent::_initialize();
		$item_site = M('item_site');
		$api_config      = $item_site->where(array('code' => 'taobao'))->getField('config');
		$cuzy_config      = $item_site->where(array('code' => 'cuzy'))->getField('config');
		$this->_tbconfig = unserialize($api_config);
		$this->_cuzyconfig = unserialize($cuzy_config);
	}

	/**
	 * 阿里妈妈筛选采集
	 * 为了减少API调用次数，搜索出来的结果保存到缓存,第二次搜索清空上一次
	 */
	public function index() {
		//判断CURL
		if(! function_exists("curl_getinfo")) {
			$this->error(L('curl_not_open'));
		}
		//获取淘宝商品分类
		$res = $this->_get_tbcats();
		if(!empty($res['error'])) {
			$this->error($res['error'], 'admin/index/index');
		}
		$this->assign('item_cate', $res['item_cate']);
		$this->display();
	}

	public function ajax_get_tbcats() {
		$cid       = $this->_get('cid', 'intval', 0);
		$res = $this->_get_tbcats($cid);

		if($res['error']) {
			$this->ajaxReturn(0, $res['error']);
		} else {
			$this->ajaxReturn(1, '', $res['item_cate']);
		}
	}

	private function _get_tbcats($cid = 0) {
		$cuzy_top = $this->_get_cuzy_top();
		$req      = $cuzy_top->load_api("GetItemcatsByCid");
		$req->setCid($cid);
		$resp = $cuzy_top->advExecute($req);
		$error = $resp->getErrorResponse();
		if($error->code > 0) {
			($data['error'] = $error->msg);
		}else{
			$res_cats  = $resp->GetItemcatsByCidData();
			$data['item_cate'] = array();
			foreach ($res_cats as $val) {
				$val         = (array)$val;
				$data['item_cate'][] = $val;
			}
		}
		
		return $data;
	}


	private function _get_cuzy_top() {
		vendor('cuzySDK.CuzyClient');
		$cuzy_top         = new CuzyClient();
		$cuzy_top->appkey = $this->_cuzyconfig['app_key'];
		$cuzy_top->appsecret = $this->_cuzyconfig['app_secret'];
		$cuzy_top->webFrom = 'pinphp3';

		return $cuzy_top;
	}

	/**
	 * 准备采集
	 */
	public function search() {
	       //搜索结果
        $cuzy_item_list = array();
        if ($this->_get('search')) {
			$map = $this->_get_params();
            if (!$map['keyword'] && !$map['cid']) {
                $this->error(L('select_cid_or_keyword'));
            }
	        $map['like_init'] = $this->_get('like_init', 'trim');
	        
            $result = $this->_get_list($map);
				if(!empty($result['error'])) {
				$this->error($result['error'], 'admin/index/index');
			}
			
            //分页
	        $sum = $result['count'];
	        if($result['count'] > 400) $sum =400;
            $pager = new Page($sum, 20,"","",$result['count']);
            $page = $pager->show();
            $this->assign("page", $page);
            //列表内容
            $cuzy_item_list = $result['item_list'];
        }
        $cuzy_item_list && F('cuzy_item_list', $cuzy_item_list);
        $this->assign('list', $cuzy_item_list);
        $this->assign('list_table', true);
        $this->display();
	}

	private function _get_params() {
		$map['keyword'] = $this->_get('keyword', 'trim'); //关键词
		$map['cid'] = $this->_get('cid', 'intval'); //分类ID
		$map['page'] = $this->_get('p', 'intval'); //批量
			
		$map['sort'] = $this->_get('sort', 'trim');
		$map['start_commissionRate'] = $this->_get('start_commissionRate', 'intval');
		$map['end_commissionRate'] = $this->_get('end_commissionRate', 'intval');
		$map['start_price'] = $this->_get('start_price', 'intval');
		$map['end_price'] = $this->_get('end_price', 'intval');
		$map['start_credit'] = $this->_get('start_credit', 'intval');
		$map['end_credit'] = $this->_get('end_credit', 'intval');
		$map['itemType'] = $this->_get('itemType', 'intval');
		$map['start_commissionVolume'] = $this->_get('start_commissionVolume', 'intval');
		$map['end_commissionVolume'] = $this->_get('end_commissionVolume', 'intval');
		return $map;
	}
	
	private function _get_list($map) {
		$cuzySDK = $this->_get_cuzy_top();

		$cuzyDataBySearch =$cuzySDK->load_api("GetItemsBySearch");
		$cuzyDataBySearch->setRedirectByWebRoot(substr(dirname(__FILE__), 0, -22));
		$cuzyDataBySearch->setPerpage(20);
		empty($map['page']) && ($map["page"] = 1);
		$cuzyDataBySearch->setPage($map["page"]);
		$cuzyDataBySearch->setPicSize("100x100");
		
		//搜索条件，排序
		$map['keyword'] && $cuzyDataBySearch->setSearchKey($map['keyword']); //关键词
		$map['cid'] && $cuzyDataBySearch->setCid($map['cid']); //分类
		$map['sort'] && $cuzyDataBySearch->setSort($map['sort']);
		$map['start_commissionRate'] && $cuzyDataBySearch->setStartCommissionRate($map['start_commissionRate']*100); 
		$map['end_commissionRate'] && $cuzyDataBySearch->setEndCommissionRate($map['end_commissionRate']*100);
		$map['start_price'] && $cuzyDataBySearch->setStartPromotion($map['start_price']); 
		$map['end_price'] && $cuzyDataBySearch->setEndPromotion($map['end_price']); 
		$map['start_credit'] && $cuzyDataBySearch->setStartCredit($map['start_credit']); 
		$map['end_credit'] && $cuzyDataBySearch->setEndCredit($map['end_credit']); 
		$map['itemType'] && $cuzyDataBySearch->setItemType($map['itemType']); 
		$map['start_commissionVolume'] && $cuzyDataBySearch->setStartCommissionVolume($map['start_commissionVolume']); 
		$map['end_commissionVolume'] && $cuzyDataBySearch->setEndCommissionVolume($map['end_commissionVolume']); 

		$resp = $cuzySDK->advExecute($cuzyDataBySearch);
		$error = $resp->getErrorResponse();
		$return = array();
		
		if($error->code > 0) {
			$return['error'] = $error->msg;
		}else{		
			$realItemData = $resp->getData();
			$count = $resp->getCount();

			//列表内容
			$itemList = array();
			foreach ($realItemData as $val) {
				$val = (array) $val;
				switch ($map['like_init']) {
					case 'volume':
						$val['likes'] = $val['volume'];
					break;
					default:
						$val['likes'] = 0;
					break;
				}
				// 获取商品相册信息
				if($val['pic_urls'] && is_array($val['pic_urls'])) {
					$pic_urls = array();
					foreach($val['pic_urls'] as $pic_url) {
						$pic_urls[] = array('url'=>$pic_url,'surl' => $pic_url . '_100x100.jpg','ordid' => 1);
					}
					$val['pic_urls'] = $pic_urls;
				}
				$itemList[$val['num_iid']] = $val;
			}
			
			$return['count'] = $count;
			$return['item_list'] = $itemList;
		}
		return $return;
  }
  
	public function publish() {
     if (IS_POST) {
         $ids = $this->_post('ids', 'trim');
         $cate_id = $this->_post('cate_id', 'intval');
         !$cate_id && $this->ajaxReturn(0, L('please_select') . L('publish_item_cate'));
         $auid = $this->_post('auid', 'intval');
         //必须指定用户
         !$auid && $this->ajaxReturn(0, L('please_select') . L('auto_user'));
         //获取马甲
         $auto_user_mod = M('auto_user');
         $user_mod = M('user');
         $unames = $auto_user_mod->where(array('id' => $auid))->getField('users');
         $unamea = explode(',', $unames);
         $users = $user_mod->field('id,username')->where(array('username' => array('in', $unamea)))->select();
         !$users && $this->ajaxReturn(0, L('auto_user_error'));
         //从缓存中获取本页商品数据
         $ids_arr = explode(',', $ids);
         $cuzy_item_list = F('cuzy_item_list');
		
		 $orig_id = D('item_orig')->where(array('name' => 'cuzy'))->getField('id');
         foreach ($cuzy_item_list as $key => $val) {
             if (in_array($key, $ids_arr)) {
				 $val['orig_id'] = $orig_id;
                 $this->_publish_insert($val, $cate_id, $users);
             }
         }
         $this->ajaxReturn(1, L('operation_success'), '', 'publish');
     } else {
         $ids = trim($this->_get('id'), ',');
         $this->assign('ids', $ids);
         //采集马甲
         $auto_user = M('auto_user')->select();
         $this->assign('auto_user', $auto_user);
         $response = $this->fetch();
         $this->ajaxReturn(1, '', $response);
     }
 }

	private function _publish_insert($item, $cate_id, $users) {
     //随机取一个用户
     $user_rand = array_rand($users);
     $item['title'] = strip_tags($item['title']);
	 $item['pic_url']= rtrim(rtrim($item['pic_url'], '100x100.jpg'), '_');
     $insert_item = array(
         'key_id' => 'taobao_' . $item['num_iid'],
         'taobao_sid' => $item['taobao_sid'],
         'cate_id' => $cate_id,
         'uid' => $users[$user_rand]['id'],
         'uname' => $users[$user_rand]['username'],
         'title' => $item['title'],
         'intro' => $item['title'],
         'img' => $item['pic_url'],
         'price' => $item['promotion_price'],
         'url' => $item['click_url'],
         'rates' => $item['commission_rate'] / 100,
         'likes' => $item['likes'],
         'orig_id' => $item['orig_id'],
         'imgs' => $item['imgs']
     );
     //如果多图为空
     if (empty($item['imgs'])) {
         $insert_item['imgs'] = array(array(
                 'url' => $insert_item['img'],
                 ));
     }
     $result = D('item')->publish($insert_item);
     return $result;
 }


	/**
  * 直接入库准备
  */
 public function batch_publish() {

     if (IS_POST) {
         $cate_id = $this->_post('cate_id', 'intval');
         !$cate_id && $this->ajaxReturn(0, L('please_select') . L('publish_item_cate'));
         $auid = $this->_post('auid', 'intval');
         //必须指定用户
         !$auid && $this->ajaxReturn(0, L('please_select') . L('auto_user'));
         //采集页数
         $page_num = $this->_post('page_num', 'intval', 10);
         //获取马甲
         $auto_user_mod = M('auto_user');
         $user_mod = M('user');
         $unames = $auto_user_mod->where(array('id' => $auid))->getField('users');
         $unamea = explode(',', $unames);
         $users = $user_mod->field('id,username')->where(array('username' => array('in', $unamea)))->select();
         !$users && $this->ajaxReturn(0, L('auto_user_error'));
         //搜索条件
         $form_data = $this->_post('form_data', 'urldecode');
         parse_str($form_data, $form_data);
         //把采集信息写入缓存
         F('batch_publish_cache', array(
             'cate_id' => $cate_id,
             'users' => $users,
             'page_num' => $page_num,
             'form_data' => $form_data,
         ));
         $this->ajaxReturn(1);
     } else {
         $auto_user = M('auto_user')->select(); //采集马甲
         $this->assign('auto_user', $auto_user);
         $response = $this->fetch();
         $this->ajaxReturn(1, '', $response);
     }
 }

	/**
	* 开始入库
	*/
	public function batch_publish_do() {
		if (false === $batch_publish_cache = F('batch_publish_cache')) {
			$this->ajaxReturn(0, L('illegal_parameters'));
		}
		$p = $this->_get('p', 'intval', 1);
		if ($p > $batch_publish_cache['page_num']) {
			$this->ajaxReturn(0, L('import_success'));
		}
		$result = $this->_get_list($batch_publish_cache['form_data'], $p);

		$orig_id = D('item_orig')->where(array('code' => 'cuzy'))->getField('id');
		if ($result['item_list']) {
			foreach ($result['item_list'] as $val) {
				$val['orig_id'] = $orig_id;
				$this->_publish_insert($val, $batch_publish_cache['cate_id'], $batch_publish_cache['users']);
			}
			$this->ajaxReturn(1);
		} else {
			$this->ajaxReturn(0, L('import_success'));
		}
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
}
