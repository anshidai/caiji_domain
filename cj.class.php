<?php

/**
* 页面抓取方法、 基于simple_html功能实现 
*/
class FetchHtml{
    
    public $html = ''; #存放html页面内容
    public $url = ''; #要抓取的页面的url
    
    /**
    * 构造函数 初始化 网址 和 获取网址内容
    */
    public function __construct($url = '', $html = '')
    {
        $this->url = $url; 
        if(!empty($html)) {
            //$this->html = $html;
            $this->html = $this->createHtml($html);
        }
        else {
            $this->html = $this->getHtml($url);
        }
    }
    
    /**
    * 获取整个页面内容
    */
    public function getHtml() 
    {
        if($this->url) {
            return file_get_html($this->url);
        }
    }
    
    /**
    * 传入自定义页面内容
    */
    public function createHtml($html = '') 
    {
         return get_create_html($html);
    }
    
    /**
    * 获取网页部分的内容
    * @param $paramArr 参数：如下参数详情
    * array(
    *   'node'=>array(
    *       'element' => '', #节点元素名称 可以是id、class
    *       'index' => 0, #节点索引位置 0第一个 1第二个...依次类推
    *   )
    * )
    * @return html
    */
    public function getNodeHtml($paramArr)
    {
        $options = array(
            'node' => array(
                'element' => '', #节点元素名称 可以是id、class
                'index' => '0', #节点索引位置 0第一个 1第二个...依次类推
            )
        );
        if(is_array($paramArr)) {
            $options = array_merge($options, $paramArr);
        }
        extract($options);
        if($this->html == '') {
            $this->html = $this->getHtml($this -> url);
            if(!$this->html){
                return false;
            }
        }      
        return $this->html->find($node['element'],$node['index']);
    }
    
        /**
    * 获取指定区域的内容
    * @param $paramArr 参数：如下参数详情
    *   'node'=>array(
    *       'element' => '', #节点元素名称 可以是id(如：div#nav) 、class(如：div.nav)
    *       'index' => 0, #节点索引位置 0第一个 1第二个...依次类推
    *   ),
    *   'items'=>array(
    *       #键名
    *       'name'=>array( 
    *            'element' => 'li>a', #查找的节点元素 多个用> 目前就支持2级 支持元素class(如：div.nav)和元素id(如：div#nav) 
    *                                还可以连贯 div#nav>li
    *           'node' => 'all', //是否匹配所有此元素 all-匹配所有 留空或其他值 则表示取一次元素
    *            'index' => 0, #子节点索引位置 默认0 0第一个 1第二个...依次类推
    *           'attr' => 'href' #获取元素属性,留空或不设置将获取 element元素的内容
    *       ),
    *       //键名可以有多个
    *       'linkurl'=>array(
    *           'index' => '0',
    *           'element' => 'ul#nav>li',
    *           'attr' => 'href'
    *       ),
    *   )
    * )
    * @param $simple_html 针对区域内容再次进行抓取 默认为空
    * @return html
    */
    public function getNodeAttribute($paramArr, $simple_html = '')
    {
        $options = array('items'=>array());
        if(!isset($paramArr['items']) || empty($paramArr['items'])) {
            return $this->getNodeHtml($this->$paramArr);
        }
        if(is_array($paramArr)) {
            $options = array_merge($options, $paramArr);
        }
        if($simple_html) {
            $html = $simple_html;
        } 
        else {
            $html = $this->getNodeHtml($options);
            if(!$html) return false;
        }
        $data = array();
        foreach($paramArr['items'] as $k=>$item) {
            $item['index'] = $item['index']? $item['index']: 0;
            $nodes = explode('>',$item['element']);
            $len = count($nodes);
            if($item['node'] === 'all') {
                foreach($html->find($nodes[0]) as $element) {
                    if($len == 1) {
                        $data[$k][] = empty($item['attr'])? $element->innertext: $element->$item['attr'];
                    }else if($len == 2) {
                        $data[$k][] = empty($item['attr'])? $element->find($nodes[1], $item['index'])->innertext: $element->find($nodes[1],$item['index'])->$item['attr'];
                    }
                }
            }else {
                if($len == 1) {
                    $data[$k] = empty($item['attr'])? $html->find($nodes[0],$item['index'])->innertext: $html->find($nodes[0],$item['index'])->$item['attr'];
                }else if($len == 2) {
                    $data[$k] = empty($item['attr'])? $html->find($nodes[0],$item['index'])->find($nodes[1],0)->innertext: $html->find($nodes[0],$item['index'])->find($nodes[1],0)->$item['attr'];
                }
            }
        }
        unset($html);
        return $data;
    }
    
    
    /*析构函数*/
    public function __destruct() 
    {
        unset($this->html);
    }
}

