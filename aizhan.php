<?php 

/**
* 抓取http://www.aizhan.com/ 域名数据
*/

header("Content-type:text/html; charset=utf-8");
set_time_limit(0);

class Aizhan {

    protected $data;
    protected $content;
    protected $errorMax = 5; //最大错误次数
    protected $pagesize = 50;
    protected $runCount = 300; //每次抓取域名最大数量
    public $proxyIP = array(); //代理ip集合
    protected $currProxyIp = ''; //当前正在使用代理ip
    protected $currProxyPort = ''; //当前正在使用代理ip端口
    public $allowProxy = false; //是否开启代理 默认不开启
    
    protected $baidu_sousuo = array(
        'https://www.baidu.com/s?wd=%E7%88%B1%E5%A5%87%E8%89%BA%E6%9C%80%E6%96%B0vip%E8%B4%A6%E5%8F%B7vip%E7%99%BE%E5%A7%93%E7%BD%91&rsv_spt=1&rsv_iqid=0x86710efb00134db9&issp=1&f=3&rsv_bp=1&rsv_idx=2&ie=utf-8&rqlang=cn&tn=baiduhome_pg&rsv_enter=0&rsv_t=a541aIXMC2as8G8CCkWJP5QvVWLHZjIaXfM%2BV%2B3glULORtiOVGQO164WG49v4rE3kHiX&oq=%E8%BF%85%E9%9B%B7%E4%BC%9A%E5%91%98%E8%B4%A6%E5%8F%B7%E5%88%86%E4%BA%AB%E7%BD%91&rsv_pq=e9e52c9a0013e7bf&rsv_sug3=120&rsv_sug1=90&rsv_sug7=100&rsv_n=2&prefixsug=%E7%88%B1%E5%A5%87%E8%89%BA%E6%9C%80%E6%96%B0vip%E8%B4%A6%E5%8F%B7vip%E7%99%BE%E5%A7%93%E7%BD%91&rsp=0&inputT=29710&rsv_sug4=30364&rsv_sug=1',
        'https://www.baidu.com/s?wd=%E7%88%B1%E5%A5%87%E8%89%BA%E9%BB%84%E9%87%91vip%E8%B4%A6%E5%8F%B7vip%E7%99%BE%E5%A7%93%E7%BD%91&rsv_spt=1&rsv_iqid=0x86710efb00134db9&issp=1&f=3&rsv_bp=1&rsv_idx=2&ie=utf-8&rqlang=cn&tn=baiduhome_pg&rsv_enter=0&rsv_t=cfaezyOsf6ZpZp35rI2g3DjTHe8LzQwEyOWhiMWKLnRUM24kU9VInRrJVgHs%2F%2Bkv36Ua&oq=%E7%88%B1%E5%A5%87%E8%89%BA%E6%9C%80%E6%96%B0vip%E8%B4%A6%E5%8F%B7vip%E7%99%BE%E5%A7%93%E7%BD%91&rsv_pq=db9ae664001319e8&inputT=533&rsv_sug3=124&rsv_sug1=93&rsv_sug7=000&rsv_n=2&prefixsug=%E7%88%B1%E5%A5%87%E8%89%BA%E9%BB%84%E9%87%91vip%E8%B4%A6%E5%8F%B7vip%E7%99%BE%E5%A7%93%E7%BD%91&rsp=0&rsv_sug4=1097&rsv_sug=1',
        'https://www.baidu.com/s?wd=%E7%88%B1%E5%A5%87%E8%89%BA%E4%BC%9A%E5%91%98%E5%8F%B7%E5%A4%A7%E5%85%A8vip%E7%99%BE%E5%A7%93%E7%BD%91&rsv_spt=1&rsv_iqid=0x86710efb00134db9&issp=1&f=3&rsv_bp=1&rsv_idx=2&ie=utf-8&rqlang=cn&tn=baiduhome_pg&rsv_enter=0&rsv_t=3a99m4iCsoXo94KrGk7lzRUssu36L53MfI%2FoRbmfqt0C8pUHYXOzs%2FshtnvJ7UyLWpfc&oq=%E7%88%B1%E5%A5%87%E8%89%BA%E9%BB%84%E9%87%91vip%E8%B4%A6%E5%8F%B7vip%E7%99%BE%E5%A7%93%E7%BD%91&rsv_pq=9ddb4dd800124ded&inputT=440&rsv_sug3=128&rsv_sug1=96&rsv_sug7=000&rsv_n=2&prefixsug=%E7%88%B1%E5%A5%87%E8%89%BA%E4%BC%9A%E5%91%98%E5%8F%B7%E5%A4%A7%E5%85%A8vip%E7%99%BE%E5%A7%93%E7%BD%91&rsp=0&rsv_sug4=953&rsv_sug=1',
        'https://www.baidu.com/s?wd=%E7%88%B1%E5%A5%87%E8%89%BAvip%E5%85%8D%E8%B4%B9%E8%AF%95%E7%94%A8vip%E7%99%BE%E5%A7%93%E7%BD%91&rsv_spt=1&rsv_iqid=0x86710efb00134db9&issp=1&f=8&rsv_bp=1&rsv_idx=2&ie=utf-8&rqlang=cn&tn=baiduhome_pg&rsv_enter=0&rsv_t=252fskVjyKlW%2BVm5bonIzJPiOfvDq0RjfoRZc7ZqM5Z8ONeI7uPLjDU8xeAfTbpvxX%2FV&oq=%E7%88%B1%E5%A5%87%E8%89%BA%E4%BC%9A%E5%91%98%E5%8F%B7%E5%A4%A7%E5%85%A8vip%E7%99%BE%E5%A7%93%E7%BD%91&rsv_pq=f1848456001759e7&inputT=569&rsv_sug3=132&rsv_sug1=99&rsv_sug7=000&rsv_n=2&rsv_sug4=1093&rsv_sug=1',
        'https://www.baidu.com/s?wd=%E7%88%B1%E5%A5%87%E8%89%BA%E4%BC%9A%E5%91%98%E8%B4%A6%E5%8F%B7%E5%85%8D%E8%B4%B9vip%E7%99%BE%E5%A7%93%E7%BD%91&rsv_spt=1&rsv_iqid=0x86710efb00134db9&issp=1&f=3&rsv_bp=1&rsv_idx=2&ie=utf-8&rqlang=cn&tn=baiduhome_pg&rsv_enter=0&rsv_t=227eV7fVumaWIn86tp68Wx237PYJDshfDzCnRPuy3syMb0b%2Fivw7JPy5AcTLcrE2REhS&oq=%E7%88%B1%E5%A5%87%E8%89%BAvip%E5%85%8D%E8%B4%B9%E8%AF%95%E7%94%A8vip%E7%99%BE%E5%A7%93%E7%BD%91&rsv_pq=90df419800188944&inputT=515&rsv_sug3=136&rsv_sug1=102&rsv_sug7=000&rsv_n=2&prefixsug=%E7%88%B1%E5%A5%87%E8%89%BA%E4%BC%9A%E5%91%98%E8%B4%A6%E5%8F%B7%E5%85%8D%E8%B4%B9vip%E7%99%BE%E5%A7%93%E7%BD%91&rsp=0&rsv_sug4=978&rsv_sug=1',
        'https://www.baidu.com/s?wd=%E7%88%B1%E5%A5%87%E8%89%BA%E4%BC%9A%E5%91%98%E8%B4%A6%E5%8F%B7vip%E7%99%BE%E5%A7%93%E7%BD%91&rsv_spt=1&rsv_iqid=0x86710efb00134db9&issp=1&f=3&rsv_bp=1&rsv_idx=2&ie=utf-8&rqlang=cn&tn=baiduhome_pg&rsv_enter=0&rsv_t=f017%2FLLRO7uDTqoSZv0T2VsAyCrVVpizYvY2im1EdTq5fyI3Mre6qq5C3i72RkaXBpfi&oq=%E7%88%B1%E5%A5%87%E8%89%BA%E4%BC%9A%E5%91%98%E8%B4%A6%E5%8F%B7%E5%85%8D%E8%B4%B9vip%E7%99%BE%E5%A7%93%E7%BD%91&rsv_pq=8f05c6fa0017d201&inputT=458&rsv_sug3=140&rsv_sug1=105&rsv_sug7=000&rsv_n=2&prefixsug=%E7%88%B1%E5%A5%87%E8%89%BA%E4%BC%9A%E5%91%98%E8%B4%A6%E5%8F%B7vip%E7%99%BE%E5%A7%93%E7%BD%91&rsp=0&rsv_sug4=937&rsv_sug=1',
        'https://www.baidu.com/s?wd=%E7%88%B1%E5%A5%87%E8%89%BA%E4%BC%9A%E5%91%98%E8%B4%A6%E5%8F%B7%E5%AF%86%E7%A0%81vip%E7%99%BE%E5%A7%93%E7%BD%91&rsv_spt=1&rsv_iqid=0x86710efb00134db9&issp=1&f=8&rsv_bp=1&rsv_idx=2&ie=utf-8&rqlang=cn&tn=baiduhome_pg&rsv_enter=0&rsv_t=c870uXo5FnylXksL39trAVAxndBA24W31sHNpa9pU8CaeDQooSfjiDy7P6Os3Gjr3ffu&oq=%E7%88%B1%E5%A5%87%E8%89%BA%E4%BC%9A%E5%91%98%E8%B4%A6%E5%8F%B7vip%E7%99%BE%E5%A7%93%E7%BD%91&rsv_pq=e562a97100156c89&inputT=576&rsv_sug3=144&rsv_sug1=108&rsv_sug7=000&rsv_n=2&rsv_sug4=1126&rsv_sug=1',
        'https://www.baidu.com/s?wd=%E4%BC%98%E9%85%B7vip%E8%B4%A6%E5%8F%B7vip%E7%99%BE%E5%A7%93%E7%BD%91&rsv_spt=1&rsv_iqid=0x86710efb00134db9&issp=1&f=3&rsv_bp=1&rsv_idx=2&ie=utf-8&rqlang=cn&tn=baiduhome_pg&rsv_enter=0&rsv_t=2a1fCCohXsUR%2BVl1koM235rOIu5V8PFUPNn5j9%2FSrCE5uu3pEhMNBewkrvZ9Q62QRrN7&oq=%E7%88%B1%E5%A5%87%E8%89%BA%E4%BC%9A%E5%91%98%E8%B4%A6%E5%8F%B7%E5%AF%86%E7%A0%81vip%E7%99%BE%E5%A7%93%E7%BD%91&rsv_pq=8089f04e0015123c&inputT=614&rsv_sug3=148&rsv_sug1=111&rsv_sug7=000&rsv_n=2&prefixsug=%E4%BC%98%E9%85%B7vip%E8%B4%A6%E5%8F%B7vip%E7%99%BE%E5%A7%93%E7%BD%91&rsp=0&rsv_sug4=1131&rsv_sug=1',
        'https://www.baidu.com/s?wd=%E4%BC%98%E9%85%B7%E4%BC%9A%E5%91%98%E8%B4%A6%E5%8F%B7vip%E7%99%BE%E5%A7%93%E7%BD%91&rsv_spt=1&rsv_iqid=0x86710efb00134db9&issp=1&f=8&rsv_bp=1&rsv_idx=2&ie=utf-8&rqlang=cn&tn=baiduhome_pg&rsv_enter=0&rsv_t=7651ZGG6G%2BVS8UrG0Jo9LQ%2BTRo%2Bh%2BDOnIPwYYnuJ5CJZqXabDA%2BUpdXNLFnrQ74B7gnT&oq=%E4%BC%98%E9%85%B7vip%E8%B4%A6%E5%8F%B7vip%E7%99%BE%E5%A7%93%E7%BD%91&rsv_pq=86386e010012b2fd&inputT=493&rsv_sug3=152&rsv_sug1=114&rsv_sug7=000&rsv_n=2&rsv_sug4=1080&rsv_sug=1',
        'https://www.baidu.com/s?wd=%E4%BC%98%E9%85%B7vip%E8%B4%A6%E5%8F%B7%E5%85%B1%E4%BA%ABvip%E7%99%BE%E5%A7%93%E7%BD%91&rsv_spt=1&rsv_iqid=0x86710efb00134db9&issp=1&f=8&rsv_bp=1&rsv_idx=2&ie=utf-8&rqlang=cn&tn=baiduhome_pg&rsv_enter=0&rsv_t=afffnUh4hHx4iOQrbi8VO8oHD8qgMroaxb616ZGx6NJINeHbEFnzEsatmqJxCQpjpjem&oq=%E4%BC%98%E9%85%B7%E4%BC%9A%E5%91%98%E8%B4%A6%E5%8F%B7vip%E7%99%BE%E5%A7%93%E7%BD%91&rsv_pq=f2b9e8920014eacd&inputT=581&rsv_sug3=156&rsv_sug1=117&rsv_sug7=000&rsv_n=2&rsv_sug2=0&rsv_sug4=1105&rsv_sug=1',
        'https://www.baidu.com/s?wd=%E4%BC%98%E9%85%B7%E4%BC%9A%E5%91%98%E8%B4%A6%E5%8F%B7%E5%85%B1%E4%BA%AB2016vip%E7%99%BE%E5%A7%93%E7%BD%91&rsv_spt=1&rsv_iqid=0x86710efb00134db9&issp=1&f=8&rsv_bp=1&rsv_idx=2&ie=utf-8&rqlang=cn&tn=baiduhome_pg&rsv_enter=0&rsv_t=299dAezj8qqSDpkahzez7vxO0AbN6QYDh3TD0B%2FFL%2FXsj5ebBUThHIh8v8Ecg2jAOMYB&oq=%E4%BC%98%E9%85%B7vip%E8%B4%A6%E5%8F%B7%E5%85%B1%E4%BA%ABvip%E7%99%BE%E5%A7%93%E7%BD%91&rsv_pq=963b4d240013eecb&inputT=495&rsv_sug3=160&rsv_sug1=120&rsv_sug7=000&rsv_n=2&rsv_sug4=1015&rsv_sug=1',
        'https://www.baidu.com/s?wd=%E4%BC%98%E9%85%B7%E9%BB%84%E9%87%91%E4%BC%9A%E5%91%98%E8%B4%A6%E5%8F%B7%E5%85%B1%E4%BA%ABvip%E7%99%BE%E5%A7%93%E7%BD%91&rsv_spt=1&rsv_iqid=0x86710efb00134db9&issp=1&f=8&rsv_bp=1&rsv_idx=2&ie=utf-8&rqlang=cn&tn=baiduhome_pg&rsv_enter=0&rsv_t=a7fdNmCsydyUCRIb0L%2FhYW1%2BOOBeb%2Bk2QhhKtJy9okHOcuqgcsfcfhscdJSzQQ6jfR2v&oq=%E4%BC%98%E9%85%B7%E4%BC%9A%E5%91%98%E8%B4%A6%E5%8F%B7%E5%85%B1%E4%BA%AB2016vip%E7%99%BE%E5%A7%93%E7%BD%91&rsv_pq=8fca61ee00129720&inputT=505&rsv_sug3=164&rsv_sug1=123&rsv_sug7=000&rsv_n=2&rsv_sug4=977&rsv_sug=1',
        'https://www.baidu.com/s?wd=%E8%BF%85%E9%9B%B7%E7%99%BD%E9%87%91%E4%BC%9A%E5%91%98%E8%B4%A6%E5%8F%B7%E5%88%86%E4%BA%ABvip%E7%99%BE%E5%A7%93%E7%BD%91&rsv_spt=1&rsv_iqid=0x86710efb00134db9&issp=1&f=8&rsv_bp=1&rsv_idx=2&ie=utf-8&rqlang=cn&tn=baiduhome_pg&rsv_enter=0&rsv_t=19fe%2B3mY0DqsjQOTXcfy%2BcvKoZAlPJKTOA9sJV%2BRrt95LMV7sWUFTuTNH0O75ql5l%2FaT&oq=%E4%BC%98%E9%85%B7%E9%BB%84%E9%87%91%E4%BC%9A%E5%91%98%E8%B4%A6%E5%8F%B7%E5%85%B1%E4%BA%ABvip%E7%99%BE%E5%A7%93%E7%BD%91&rsv_pq=db9ae66400133264&inputT=449&rsv_sug3=168&rsv_sug1=126&rsv_sug7=000&rsv_n=2&rsv_sug4=873&rsv_sug=1',
        'https://www.baidu.com/s?wd=%E8%BF%85%E9%9B%B7%E4%BC%9A%E5%91%98%E8%B4%A6%E5%8F%B7vip%E7%99%BE%E5%A7%93%E7%BD%91&rsv_spt=1&rsv_iqid=0x86710efb00134db9&issp=1&f=8&rsv_bp=1&rsv_idx=2&ie=utf-8&rqlang=cn&tn=baiduhome_pg&rsv_enter=0&rsv_t=c2a1JIo6lCQmaIoeA%2FGnpLisv5XnAsDnjfsG9pJdeBfWuDWPITaBHS8WVc3llIdZushD&oq=%E8%BF%85%E9%9B%B7%E7%99%BD%E9%87%91%E4%BC%9A%E5%91%98%E8%B4%A6%E5%8F%B7%E5%88%86%E4%BA%ABvip%E7%99%BE%E5%A7%93%E7%BD%91&rsv_pq=82e591380012dda6&inputT=505&rsv_sug3=172&rsv_sug1=129&rsv_sug7=000&rsv_n=2&rsv_sug4=1035&rsv_sug=1',
        'https://www.baidu.com/s?wd=%E8%BF%85%E9%9B%B7%E4%BC%9A%E5%91%98%E8%B4%A6%E5%8F%B7%E5%88%86%E4%BA%AB%E7%BD%91vip%E7%99%BE%E5%A7%93%E7%BD%91&rsv_spt=1&rsv_iqid=0x86710efb00134db9&issp=1&f=3&rsv_bp=1&rsv_idx=2&ie=utf-8&rqlang=cn&tn=baiduhome_pg&rsv_enter=0&rsv_t=d609BuUdZ2zLwFo1lyHuV61k2GS1D7x5RQYCfTJZLtVjfrB6l9kD1SU690g9WeDwrLNY&oq=%E8%BF%85%E9%9B%B7%E4%BC%9A%E5%91%98%E8%B4%A6%E5%8F%B7vip%E7%99%BE%E5%A7%93%E7%BD%91&rsv_pq=904cae0100131f4e&inputT=503&rsv_sug3=176&rsv_sug1=132&rsv_sug7=000&rsv_n=2&prefixsug=%E8%BF%85%E9%9B%B7%E4%BC%9A%E5%91%98%E8%B4%A6%E5%8F%B7%E5%88%86%E4%BA%AB%E7%BD%91vip%E7%99%BE%E5%A7%93%E7%BD%91&rsp=0&rsv_sug4=1089&rsv_sug=1',
    );
    
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
                    
                    /* 临时加入百度搜索词 开始 */
                    //$baidu_sousuo_url = $this->getRandArr($this->baidu_sousuo, 1);
                    //curl_http($baidu_sousuo_url);
                    /* 临时加入百度搜索词 结束 */

                    if($this->content && $httpcode == '200') {
                        //file_put_contents('/home/libaoan/dd.txt', $this->content);
                        //exit;
                        
                        if(strpos($this->content, '查询太频繁了，休息一下吧') || strpos($this->content, '<h1>400 Bad Request</h1>') || strpos($this->content, '<title>404 Not Found</title>') || strpos($this->content, '500 Internal Server Error') || strpos($this->content,'301 Moved Permanently')) {
                            if($this->allowProxy) {
                                
                                $this->cjSiteUrl();
                                $this->addDomain();
                                
                                print_log("************ 查询太频繁或301提示 正在切换代理 ***********");
                                $this->changeProxy();
                                $run++;
                                continue;
                            }else {
                                writeLock(0);
                                
                                $this->cjSiteUrl();
                                $this->addDomain();
                                $this->delDomain();
                                //echo $this->content;
                                print_log("查询太频繁或301提示 暂停采集");
                                exit;
                            }     
                        }else {
                            $this->cjSiteUrl();
                            $this->updateDomain();
                            $this->addDomain();    
                        } 
                    }else {
                         if($this->allowProxy) {
                             print_log("抓取域名内容失败 正在切换代理");
                            $this->changeProxy();
                            $run++;
                            continue;   
                        }else {
                            print_log("抓取域名内容失败 {$this->data['domain']}");
                        }
                        
                    }
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
            print_log("当前代理ip {$this->currProxyIp}:{$this->currProxyPort}  剩余代理IP数量：".count($this->proxyIP));
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
        print_log("更新域名 {$this->data['domain']}   注册时间".$this->data['creatdate']);
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

    function __destruct() {}
    
}
