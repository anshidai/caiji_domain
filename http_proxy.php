<?php 


/**
*采集 66ip.cn 代理ip 高匿名
* $maxPage最大采集页码
*/
function cj_66ip_ip($maxPage = 4) 
{
    
    print_log("正在 66ip.cn 采集代理ip");
    
    $proxy_ips = array();
    
    for($i=1; $i<=32; $i++) {
        $web_urls[] = "http://www.66ip.cn/areaindex_{$i}/1.html";
    }

    $indexs = array_rand($web_urls, $maxPage);
    for($i=0; $i<count($indexs); $i++) {
        $url[] = $web_urls[$indexs[$i]];
    }
    
    for($i = 0; $i<= count($url); $i++) {
        
        $pageurl = $url[$i];
        
        $header[] = "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8"; 
        $header[] = "AAccept-Encoding: gzip, deflate"; 
        $header[] = "Accept-Language: zh-CN,zh;q=0.8"; 
        $header[] = "Cache-Control: max-age=0"; 
        $header[] = "Connection: keep-alive"; 
        $header[] = "Host: www.66ip.cn"; 
        //$header[] = "Referer: {$pageurl}"; 
        $header[] = "User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64; rv:40.0) Gecko/20100101 Firefox/40.0";
        
        $daili_html = curl_http($pageurl, $header, '', true);
        $daili_html = mb_convert_encoding($daili_html['content'], 'UTF-8', 'GBK');
        
        $daili_content = pos_html('验证时间</td></tr>', '<style>#pagelist', $daili_html);

        if($daili_content && preg_match_all('/<tr>(.*)<\/tr>/iUs', $daili_content, $tr_match)) {
            if(!empty($tr_match[1])) {
                foreach($tr_match[1] as $key=>$tr) {
                    if(preg_match_all('/<td>(.*)<\/td>/iUs', $tr, $td_match)) {
                        $ip = $td_match[1][0];
                        if(!isset($proxy_ips[$ip])) {
                            $proxy_ips[$ip]['speed'] = 1;
                            $proxy_ips[$ip]['ip'] = $ip;
                            $proxy_ips[$ip]['port'] = intval($td_match[1][1]);
                            $proxy_ips[$ip]['http'] = 'http';
                            $proxy_ips[$ip]['url'] = $pageurl;
                            $proxy_ips[$ip]['verify'] = str_replace(array(' 验证','验证'), '', $td_match[1][4]);
                        }
                    }    
                }
            }
        }
        unset($daili_html, $daili_content);
        sleep(2);
    }
    return $proxy_ips;
}

/**
*采集 xicidaili.com 代理ip 高匿名
* $maxPage最大采集页码
*/
function cj_xicidaili_ip($maxPage = 4) 
{
    print_log("正在 xicidaili.com 采集代理ip");
    
    $proxy_ips = array();

    for($page = 1; $page<= $maxPage; $page++) {
        $header[] = "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8"; 
        $header[] = "Accept-Encoding: gzip, deflate, sdch"; 
        $header[] = "Accept-Language: zh-CN,zh;q=0.8"; 
        $header[] = "Cache-Control: max-age=0"; 
        $header[] = "Connection: keep-alive"; 
        $header[] = "Host: www.xicidaili.com"; 
        //$header[] = "Referer: http://www.xicidaili.com/nn/"+($page+1); 
        $header[] = "Upgrade-Insecure-Requests: 1"; 
        $header[] = "User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/44.0.2403.130 Safari/537.36";
        
        $daili_html = curl_http("http://www.xicidaili.com/nn/{$page}", $header, '', true);
        $daili_content = pos_html('<table id="ip_list">', '</table>', $daili_html['content']);
        
        if($daili_content && preg_match_all('/<tr class=\".*">(.*)<\/tr>/iUs', $daili_content, $tr_match)) {
            if(!empty($tr_match[0])) {
                foreach($tr_match[0] as $key=>$tr) {
                    $speed = '';
                    if(preg_match_all('/<div title=\"(.*)\" class=\"bar\">/', $tr, $speed_match)) {
                        $speed = str_replace('秒', '', $speed_match[1][1]);
                    }
                    if(preg_match_all('/<td>(.*)<\/td>/', $tr, $td_match)) {
                       
                        $ip = $td_match[1][0];
                        if(!isset($proxy_ips[$ip])) {
                            $proxy_ips[$ip]['speed'] = $speed;
                            $proxy_ips[$ip]['ip'] = $ip;
                            $proxy_ips[$ip]['port'] = intval($td_match[1][1]);
                            $proxy_ips[$ip]['http'] = strtolower($td_match[1][2]);
                            $proxy_ips[$ip]['verify'] = $td_match[1][4];
                        }
                    }
                }
            }
        }
        unset($daili_html, $daili_content);
        sleep(2);
    }
    return $proxy_ips;
}


/**
* 针对采集到的代理ip 按照条件进行过滤筛选
* $ips 代理ip数组
* $allowSpeed 允许最大上限时间单位/秒
*/
function filter_proxy_ips($ips = array(), $allowSpeed = 1, $allowDate = '')
{
    $data = array();
    if($ips) {
        foreach($ips as $val) {
            if($val['ip'] && $val['port'] && $val['speed']<=$allowSpeed) {
                $data[$val['ip']] = $val;
            }
        }
    }
    return $data;
}