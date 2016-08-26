<?php 

/**
* 抓取http://www.aizhan.com/ 域名数据
*/

header("Content-type:text/html; charset=utf-8");
set_time_limit(0);

class Aizhan {
	
    protected $data;
	
	//页面抓取内容
    protected $content;
	
	//最大错误次数
    protected $errorMax = 5; 
	
	//每次抓取域名最大数量
    public $total = 300; 
	
	//代理ip集合
    public $proxyIP = array(); 
	
	//当前正在使用代理ip
    protected $currProxyIp = ''; 
	
	//当前正在使用代理ip端口
    protected $currProxyPort = ''; 
	
	//是否开启代理 默认不开启
    public $allowProxy = false; 
	
	public $logfile = '';
	
	//延时 毫秒
	public $delay = 1000;
    
    public function __construct() {}
    
    public function init()
    {
        //锁状态 0-允许操作， 1-已经有其他进程在使用 不允许操作
        $lock = file_get_contents(ROOTPATH.'lock.txt');
        if($lock == '1') {
            $this->writeLog("上一次任务还没结束");
            exit;   
        }
		writeLock(1);
		$query = DB::query("SELECT domain FROM ".DB::table('cj_domain')." WHERE status=0 order by addtime limit {$this->total}");
		while($row = DB::fetch($query)) { 
			if($this->allowProxy) {
				if(!$this->proxyIP) {
					writeLock(0);
					$this->writeLog("当前没有可用的代理ip");
					$this->delDomain();
					exit;
				}
				$this->changeProxy();
			}
			
			$this->data['domain'] = rtrim($row['domain'], '/');
			$this->writeLog("------------ 开始抓取域名 {$this->data['domain']} ------------");

			$header[] = "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8"; 
			$header[] = "Accept-Encoding: gzip, deflate"; 
			$header[] = "Accept-Language: zh-CN,zh;q=0.8"; 
			$header[] = "Cache-Control: max-age=0"; 
			$header[] = "Connection: keep-alive"; 
			$header[] = "Host: www.aizhan.com"; 
			$header[] = "Referer: http://www.aizhan.com/cha/{$this->data['domain']}"; 
			//$header[] = "Referer: {$pageurl}"; 
			$header[] = "User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64; rv:40.0) Gecko/20100101 Firefox/40.0";
			
			$cookie = 'update=1;siteallSite={$domain};baidurankSite={$domain};defaultSiteState=1;{"{$domain}":true}';
			$cookie = str_replace('{$domain}', $row['domain'], $cookie);
			
			$tem_content = curl_http('http://www.aizhan.com/cha/'.$this->data['domain'], $header, array('ip'=>$this->currProxyIp, 'port'=>$this->currProxyPort), true);
			$this->content = $tem_content['content'];
			$httpcode = $tem_content['httpcode'];
			unset($tem_content);
			
			if(empty($this->content) || $httpcode != '200') {
				$this->writeLog("抓取域名内容失败 {$this->data['domain']}");
				continue; 
			}else if(strpos($this->content, '查询太频繁了，休息一下吧') || strpos($this->content, '<h1>400 Bad Request</h1>') || strpos($this->content, '<title>404 Not Found</title>') || strpos($this->content, '500 Internal Server Error') || strpos($this->content,'301 Moved Permanently')) {
				$this->writeLog("************ 查询太频繁或301提示 **********");
				continue;
			}
			$this->cjSiteUrl();
			$this->addDomain();
		
			$this->data = array();
			$this->content = '';
			
			if($this->delay) {
				usleep($this->delay * 1000);
			}
		}
		$this->delDomain();    
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
            $this->writeLog("当前代理ip {$this->currProxyIp}:{$this->currProxyPort}  剩余代理IP数量：".count($this->proxyIP));
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
        
        if(preg_match('/<script type=\"text\/javascript\">set_whois\((.*)\);<\/script>/', $this->content, $whois_html)) {
            if(!empty($whois_html[1])) {
                if($whois = json_decode(trim($whois_html[1]), true)) {
                    $this->data['creatdate'] = $whois['created'];
                }
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
        
        
        if(preg_match('/\$\(\"\#webpage_title\"\)\.html\(\"(.*)\"\);/', $this->content, $domain_name_html)) {
            if($domain_name_html[1]) {
                $this->data['domain_name'] = trim($domain_name_html[1]);
                $this->data['domain_name'] = str_replace('&nbsp;', '', $this->data['domain_name']);
            }
        }
        
        if(empty($this->data['domain_name'])) {
            if(preg_match('/<span id=\"main_title\" style=\"color:#00F\">(.*)<\/span><\/td>/', $this->content, $domain_name_html)) {
                if($domain_name_html[1]) {
                    $this->data['domain_name'] = trim($domain_name_html[1]);
                    $this->data['domain_name'] = str_replace('&nbsp;', '', $this->data['domain_name']);
                }
            }
        }
        
        
        $baidu_indexs = pos_html('<td>百度索引量:<span id="baiduindex">', '</span>&nbsp;&nbsp;预计来路', $this->content);
        if($baidu_indexs) {
            $baidu_indexs = str_replace(',', '', $baidu_indexs);
            $this->data['baidu_indexs'] = $baidu_indexs;
        }
        $baidu_ipv = pos_html('预计来路：<span id="baidu_ip">', '</span>&nbsp;IP&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span id="seo_link">', $this->content);
        if($baidu_ipv) {
            $this->data['baidu_ipv_min'] = 0;
            $this->data['baidu_ipv_max'] = 0;
            if(strpos($baidu_ipv, '~')) {
                $baidu_ipv_arr = explode("~", $baidu_ipv);
                $this->data['baidu_ipv_min'] = trim($baidu_ipv_arr[0])? intval(trim($baidu_ipv_arr[0])): 0;
                $this->data['baidu_ipv_max'] = trim($baidu_ipv_arr[1])? intval(trim($baidu_ipv_arr[1])): 0;
            }
            $this->data['baidu_ipv'] = $baidu_ipv;
        }
        
        $baidu_pc_pr = pos_html('id="baidu_rank">', 'id="baidu_mBR"', $this->content);
        if($baidu_pc_pr) {
            $this->data['baidu_pc_pr'] = $this->parseBaiduPr($baidu_pc_pr);
        }
        $baidu_m_pr = pos_html('id="baidu_mBR">', '网站历史', $this->content);
        if($baidu_m_pr) {
            $this->data['baidu_m_pr'] = $this->parseBaiduPr($baidu_m_pr);
        }
        
        $domain_html = pos_html('<div class="gg01"><span>', '<div class="gg02">', $this->content);
        if(preg_match_all('/<a\s.*?href=\"([^\"]+)\"[^>]*>(.*?)<\/a>/', $domain_html, $domain_match)) {
            if($domain_match[2]) {
                foreach($domain_match[2] as $url) {
                    $tmp_url = $url;
                    if(strpos($tmp_url, '.')>0) {
                        $tmp_url = substr($tmp_url, strrpos(rtrim($tmp_url, '/'), '.') + 1);
                        if(in_array($tmp_url, array('com','net','org','edu','cn','us','top','co','info','tk','biz','in','eu','cc','jp','tw','vip','tk','tv','hk','pw','so','la'))) {
                            if($url != $this->data['domain'] && !strpos($url, 'aizhan.com') && !strpos($url, $this->data['domain']) && !preg_match("/[\x7f-\xff]/", $url)) {
                                $domain_url[$url] = $url;
                            } 
                        }    
                    }
                }
                $this->data['domain_url'] = $domain_url;
            }
        }
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
                $this->writeLog("新增加域名 {$insert_domain}");
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

        if($this->data['baidu_ipv'] && strpos($this->data['baidu_ipv'], '>')) {
            file_put_contents("/home/libaoan/error_{$this->data['domain']}.txt", $this->content);  
        }
        
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
        $this->writeLog("更新域名 {$this->data['domain']}   注册时间".$this->data['creatdate']);
    }
    
    protected function updaetErrorNum()
    {
       DB::query("UPDATE ".DB::table('cj_domain')." SET error_count=error_count+1 WHERE domain='{$this->data['domain']}'");     
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
                    $this->writeLog("不符合条件域名保存到历史表 {$insert_domain}");
                }
            }
            /*** 将不符合域名保存到历史表 end ***/
            
            //域名主表删除不符合要求的记录
            DB::query("DELETE FROM ".DB::table('cj_domain')." WHERE domain in({$delete_domain})");
            
            $this->writeLog("delete domain");
            
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
    
    protected function getRandArr($arr = array(), $num = 1)
    {        
        if($num> count($arr)) {
            $num = count($arr);
        }
        $rand_keys = array_rand($arr, $num);
        for($i=0; $i<count($rand_keys); $i++) {
            $data[] = $arr[$rand_keys[$i]];  
        }
        return $data;
    }
	
	public function writeLog($msg = '')
    {
        $msg = date('Y-m-d H:i:s')." {$msg}\n";
		if($this->logfile) {
			file_put_contents($this->logfile, $msg, FILE_APPEND);    
		}else {
			echo $msg;
		}
    }

    function __destruct() {}
    
}
