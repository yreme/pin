<?php
/**
 * 获取单条淘宝商品信息
 */

class GetTbItem {
	private $apiParas;
	private $picSize;
	private $numiid;
	private $redirectUrl;

	/**
	 * @param mixed $redirctUrl
	 */
	public function setRedirectByWebRoot($webRoot) {
		$dirname = dirname(dirname(__FILE__));
		$dirname = substr($dirname, strlen($webRoot)+1);
		$dirname = str_replace('\\', '/', $dirname);
		$redirectUrl = '/'. $dirname. '/Debug/tao.php';
		$redirectUrl = str_replace('//', '/', $redirectUrl);
		$this->setRedirectUrl($redirectUrl);
	}
	/**
	 * @return mixed
	 */
	public function setRedirectUrl($redirectUrl) {
		$this->redirectUrl = $redirectUrl;
		$this->apiParas["redirect_url"] = $redirectUrl;
	}


	public function getNumiid() {
		return $this->numiid;
	}


	public function setNumiid($numiid) {
		$this->numiid = $numiid;
		$this->apiParas['numiid'] = $numiid;
	}

	public function check() {
	}

	function  getApiParas() {
		return $this->apiParas;
	}

	public function getApiMethodName() {
		return "getTbItem";
	}

}
