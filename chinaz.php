<?php 

/**
* 抓取http://seo.ahinaz.com/ 域名数据
*/

header("Content-type:text/html; charset=utf-8");
set_time_limit(0);

class Ahinaz {

    protected $data;
    protected $content;
    protected $errorMax = 5; //最大错误次数
    protected $pagesize = 50;
    protected $runCount = 300; //每次抓取域名最大数量
    public $proxyIP = array(); //代理ip集合
    protected $currProxyIp = ''; //当前正在使用代理ip
    protected $currProxyPort = ''; //当前正在使用代理ip端口
    public $allowProxy = false; //是否开启代理 默认不开启
    
    public function __construct() {}
    
    public function init()
    {
       
        //锁状态 0-允许操作， 1-已经有其他进程在使用 不允许操作
        $lock = file_get_contents(ROOTPATH.'lock.txt');
        if($lock == '1') {
            print_log("上一次任务还没结束");
            exit;   
        }
        $total = DB::result_first("SELECT count(*) FROM ".DB::table('cj_domain')." WHERE status=0");
        if($total) {
            writeLock(1);
            $formax = ceil($total/$this->pagesize);
            $run = 0;
            for($i=0; $i<=$formax; $i++) {
                $limit = ($i-1)*$this->pagesize.','.$this->pagesize;
                $query = DB::query("SELECT domain FROM ".DB::table('cj_domain')." WHERE status=0 order by addtime limit {$limit}");
                while($row = DB::fetch($query)) { 
                    if($run >= $this->runCount) {
                        break;
                    }
                    
                    if($this->allowProxy && !$this->proxyIP) {
                        writeLock(0);
                        print_log("当前没有可用的代理ip");
                        $this->delDomain();
                        exit;
                    }
                    
                    $this->data['domain'] = rtrim($row['domain'], '/');
                    print_log("------------ 开始抓取域名 {$this->data['domain']} ------------");

                    $header[] = "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8"; 
                    $header[] = "Accept-Encoding: gzip, deflate, sdch"; 
                    $header[] = "Accept-Language: zh-CN,zh;q=0.8"; 
                    $header[] = "Cache-Control: max-age=0"; 
                    $header[] = "Connection: keep-alive"; 
                    $header[] = "Host: rank.chinaz.com";  
                    $header[] = "User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64; rv:40.0) Gecko/20100101 Firefox/40.0";
       
                    $whois_html = curl_http("http://whois.22.cn/{$this->data['domain']}");
                    
                    //file_put_contents('/home/libaoan/11.txt', $whois_html['content']);
                    //exit;
                     if($whois_html['content'] && $whois_html['httpcode'] == '200') {
                         if(preg_match('/<br \/>   注册日期：(.*)<br \/>   过期日期/', $whois_html['content'], $whois_match)) {
                             $this->data['creatdate'] = $whois_match[1];
                         }
                     }
                     
                     if(empty($this->data['creatdate'])) {
                          $whois_html = curl_http("http://tool.admin5.com/whois/?q={$this->data['domain']}");
                          //file_put_contents('/home/libaoan/11.txt', $whois_html['content']);
                          if(preg_match('/<td class=\"tahoma dgreen bold\">(.*)<\/td>/',$whois_html['content'], $whois_match)) {
                            $this->data['creatdate'] = $whois_match[1];    
                          }
                          if(empty($this->data['creatdate'])) {
                            if(preg_match('/Registration Time: (.*)<br\/>Expiration/',$whois_html['content'], $whois_match)) {
                                $whois_match = explode(" ", $whois_match[1]);
                                $whois_match = $whois_match[0];
                                $this->data['creatdate'] = $whois_match;    
                              }    
                          }
                           
                     }
                     unset($whois_html);
                     
                     //var_dump($this->data['creatdate']);exit;
                     
                     if(empty($this->data['creatdate'])) {
                         print_log("************ 域名{$this->data['domain']} 获取注册日期失败 正在切换下一个 ***********");
                         $run++;
                         continue;
                     }
                     
                     $this->cjSiteUrl();
                     $this->updateDomain();
                     $this->addDomain(); 
                     
                    $this->data = array();
                    $this->content = '';
                    $run++;
                    
                    $this->allowProxy? sleep(10): sleep(10);
                }
            }
            $this->delDomain();    
        } 
        writeLock(0);             
        
    }

    
    /**
    * 每次获取一个代理ip
    */
    protected function changeProxy()
    {
        if(empty($this->proxyIP)) return false;

        //如果有代理ip则删除当前，取下一个
        if($this->currProxyIp) {
            unset($this->proxyIP[$this->currProxyIp]);
        }
        
        if(empty($this->proxyIP)) return false;
        
        foreach($this->proxyIP as $val) {
            $this->currProxyIp = $val['ip'];
            $this->currProxyPort = $val['port'];
            print_log("当前代理ip {$this->currProxyIp}:{$this->currProxyPort}  剩余代理IP数量：".count($this->proxyIP). "   总代理数: ".count($this->proxyIP));
            break;
        }
        return true;
    }
    
    
    /**
    * 采集域名url
    */
    public function cjSiteUrl()
    {
        $error = 0;

        $this->data['baidu_ipv_pc'] = 0;
        $this->data['baidu_ipv_m'] = 0;
        
        $postdata = curl_post('http://whois.chinaz.com/getTitleInfo.ashx', array('host'=>$this->data['domain']));
        if($postdata) {
            $domain_name = pos_html('<h5>标题（Title）</h5><p>', '</p><h5>关键词（KeyWords）</h5>', $postdata);
            if($domain_name) {
                $this->data['domain_name'] = $domain_name;
            }
        }
             
        //获取百度权重 和 相关域名
        $page_html = curl_http('http://rank.chinaz.com/?host='.$this->data['domain']);
        if($page_html['content'] && $page_html['httpcode'] == '200') {
            if(preg_match('/<span>百度权重：<i id=\"br\">(.*)<\/i><\/span>/', $page_html['content'], $pr_match)) {
                $this->data['baidu_pc_pr'] = $pr_match[1];
            }
            if(preg_match('/<span>预估百度流量：<i id=\"uvc\">(.*)<\/i>IP<\/span>/', $page_html['content'], $ipv_match)) {
                $this->data['baidu_ipv_pc'] = $ipv_match[1];
            }
            
            $domain_html = pos_html('<p class="SimSun RankOthers">', '<!--IcpMain02-end-->', $page_html['content']);
            if($domain_html && preg_match_all('/<a\s.*?href=\"([^\"]+)\"[^>]*>(.*?)<\/a>/', $domain_html, $domain_match)) {
                if($domain_match[2]) {
                    foreach($domain_match[2] as $url) {
                        $tmp_url = $url;
                        if(strpos($tmp_url, '.')>0) {
                            $tmp_url = substr($tmp_url, strrpos(rtrim($tmp_url, '/'), '.') + 1);
                            if(in_array($tmp_url, array('com','net','org','edu','cn','us','top','co','info','tk','biz','in','eu','cc','jp','tw','vip','tk','tv','hk','pw','so','la'))) {
                                if($url != $this->data['domain'] && !strpos($url, 'chinaz.com') && !strpos($url, $this->data['domain']) && !preg_match("/[\x7f-\xff]/", $url)) {
                                    $domain_url[$url] = $url;
                                } 
                            }    
                        }
                    }
                    $this->data['domain_url'] = $domain_url;
                }
            }
        }
        
        //获取百度移动权重 和 相关域名
        $page_html = curl_http('http://wapseo.chinaz.com/rank?host='.$this->data['domain']);
        if($page_html['content'] && $page_html['httpcode'] == '200') {
            if(preg_match('/<span class=\"fl mr20\">移动权重：<i id=\"kwc\">(.*)<\/i><\/span>/', $page_html['content'], $pr_match)) {
                $this->data['baidu_m_pr'] = $pr_match[1];
            }
            if(preg_match('/<span class=\"fl mr20 ml10\">预估百度流量：<i id=\"uvc\">(.*)<\/i>IP<\/span>/', $page_html['content'], $ipv_match)) {
                $this->data['baidu_ipv_m'] = $ipv_match[1];
            }
        }
        unset($page_html);
        
        $this->data['baidu_ipv_min'] = min($this->data['baidu_ipv_pc'], $this->data['baidu_ipv_m']);
        $this->data['baidu_ipv_max'] = max($this->data['baidu_ipv_pc'], $this->data['baidu_ipv_m']);
        
    }
    
    /**
    * 第一次将采集到的域名入库
    */
    protected function addDomain()
    {
        $this->filterDomainUrl();
        
        if($this->data['domain_url']) {
            $values = $insert_domain = '';
            foreach($this->data['domain_url'] as $domain) {
                $time = time();
                $values .= "('{$domain}',{$time}),";
                $insert_domain .= "{$domain} ";
            }
            $values = rtrim($values, ',');
            if($values) {
                DB::query("INSERT INTO ".DB::table('cj_domain')." (domain, addtime) VALUES {$values}");
                print_log("新增加域名 {$insert_domain}");
            }
        }
    }
    
    protected function filterDomainUrl()
    {
        if($this->data['domain_url']) {
            $map = '';
            foreach($this->data['domain_url'] as $domain) {
                $map .= "'{$domain}',";
            }
            $map = rtrim($map, ',');
            
            //找域名主表查是否有域名记录
            $query = DB::query("SELECT domain FROM ".DB::table('cj_domain')." WHERE domain in({$map})");
            while($row = DB::fetch($query)) {
                if(isset($this->data['domain_url'][$row['domain']])) {
                    unset($this->data['domain_url'][$row['domain']]);
                }
            }
            
            //找历史域名表查是否有域名记录
            $query = DB::query("SELECT domain FROM ".DB::table('history_domain')." WHERE domain in({$map})");
            while($row = DB::fetch($query)) {
                if(isset($this->data['domain_url'][$row['domain']])) {
                    unset($this->data['domain_url'][$row['domain']]);
                }
            }
        }
    }
    
    
    /**
    * 更新采集到的域名数据
    */
    protected function updateDomain()
    {
        

        if(empty($this->data['creatdate'])) {
            echo "【 域名 {$this->data['domain']} 注册时间为空 】\r\n";
        }
        
        /*
        if($this->data['baidu_ipv'] && strpos($this->data['baidu_ipv'], '>')) {
            file_put_contents("/home/libaoan/error_{$this->data['domain']}.txt", $this->content);  
        }
        */
        
        $data['domain_name'] = addslashes($this->data['domain_name']);
        $data['creatdate'] = $this->data['creatdate'];
        $data['baidu_indexs'] = $this->data['baidu_indexs'];
        $data['baidu_ipv'] = $this->data['baidu_ipv'];
        $data['baidu_ipv_min'] = $this->data['baidu_ipv_min'];
        $data['baidu_ipv_max'] = $this->data['baidu_ipv_max'];
        $data['baidu_pc_pr'] = $this->data['baidu_pc_pr'];
        $data['baidu_m_pr'] = $this->data['baidu_m_pr'];
        $data['status'] = 1;
        $data['updatetime'] = time();
        
        $setsql = '';
        foreach($data as $key=>$val) {
            $setsql .= "{$key}='{$val}',";
        }
        $setsql = rtrim($setsql, ',');
        
        DB::query("update ".DB::table('cj_domain')." SET {$setsql} WHERE domain='{$this->data['domain']}'");
        print_log("更新域名 {$this->data['domain']}   注册时间".$this->data['creatdate']). "  网站名称".$this->data['domain_name'];
    }
    
    /**
    * 符合条件的域名数据移动到历史表
    */
    protected function delDomain()
    {
        $delete_domain = $values = $insert_domain = '';
        $domains = array();
        $query = DB::query("SELECT domain FROM ".DB::table('cj_domain')." WHERE status=1 AND (creatdate<='2013-01-01' OR baidu_ipv_min<300) and creatdate !='0000-00-00'");
        while($row = DB::fetch($query)) {
            $domains[] = $row['domain'];
            $delete_domain .= "'{$row['domain']}',";
        }
        $delete_domain = rtrim($delete_domain, ',');
        
        if($delete_domain) {
            
            /*** 将不符合域名保存到历史表 ***/
            $query = DB::query("SELECT domain FROM ".DB::table('history_domain')." WHERE domain in({$delete_domain})");
            while($row = DB::fetch($query)) {
                if(isset($domains[$row['domain']])) {
                    unset($domains[$row['domain']]);
                }
            }
            if($domains) {
                foreach($domains as $val) {
                    $time = time();
                    $values .= "('{$val}',{$time}),";
                    $insert_domain .= "{$domain} ";
                }
                $values = rtrim($values, ',');
                if($values) {
                    DB::query("INSERT INTO ".DB::table('history_domain')." (domain, addtime) VALUES {$values}");
                    print_log("不符合条件域名保存到历史表 {$insert_domain}");
                }
            }
            /*** 将不符合域名保存到历史表 end ***/
            
            //域名主表删除不符合要求的记录
            DB::query("DELETE FROM ".DB::table('cj_domain')." WHERE domain in({$delete_domain})");
            
            print_log("delete domain");
            
        }
    }
    
    /**
    * 判断百度权重值
    */
    protected function parseBaiduPr($content = '')
    {
        $pr = 0;
        
        if(stripos($content, '/brs/1.gif') || stripos($content, '/brs/br1.gif')) {
            $pr = 1;
        }else if(stripos($content, '/brs/2.gif') || stripos($content, '/brs/br2.gif')) {
            $pr = 2;
        }else if(stripos($content, '/brs/3.gif') || stripos($content, '/brs/br3.gif')) {
            $pr = 3;
        }else if(stripos($content, '/brs/4.gif') || stripos($content, '/brs/br4.gif')) {
            $pr = 4;
        }else if(stripos($content, '/brs/5.gif') || stripos($content, '/brs/br5.gif')) {
            $pr = 5;
        }else if(stripos($content, '/brs/6.gif') || stripos($content, '/brs/br6.gif')) {
            $pr = 6;
        }else if(stripos($content, '/brs/7.gif') || stripos($content, '/brs/br7.gif')) {
            $pr = 7;
        }else if(stripos($content, '/brs/8.gif') || stripos($content, '/brs/br8.gif')) {
            $pr = 8;
        }else if(stripos($content, '/brs/9.gif') || stripos($content, '/brs/br9.gif')) {
            $pr = 9;
        }else if(stripos($content, '/brs/10.gif') || stripos($content, '/brs/br10.gif')) {
            $pr = 10;
        }
        return $pr;
    }
    
    
    //字符编码转换
    public function diconv($data, $formcode = 'GBK', $tocode = 'UTF-8')
    {
        $data = preg_replace('/jsonp\d+\(/', '', $data);
        $data = preg_replace('/\)/', '', $data);
        return iconv($formcode, $tocode, $data);
    }

    function __destruct() {}
    
    
    
}



