<?php


//echo "all complete\n";


function pos_html($start_tag, $end_tag, $html = '', $addslashes = false)
{
    //$start_tag = str_replace('"', '\"', $start_tag);
    //$end_tag = str_replace('"', '\"', $end_tag);
    
    if($addslashes) {
        $start_tag = str_replace(array('"', '/', '(', ')'), array('\"', '\/', '\(', '\)'), $start_tag);
        $end_tag = str_replace(array('"', '/', '(', ')'), array('\"', '\/', '\(', '\)'), $end_tag);
        
        //$start_tag = addslashes($start_tag);
        //$end_tag = addslashes($end_tag);
    }
    $start_pos = strpos($html, $start_tag) + strlen($start_tag);
    $end_pos = strpos($html, $end_tag);

    return substr($html, $start_pos, $end_pos - $start_pos);
    
}


function curl_post($url, $post = array(), $header = array(), $proxy = array(), $gzip = false)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //将curl_exec()获取的信息以文件流的形式返回，而不是直接输出
    curl_setopt($ch, CURLOPT_HEADER, 0); //是否取得头信息
    curl_setopt($ch, CURLOPT_TIMEOUT, 60); //设置超时 秒 
    
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt ($ch, CURLOPT_POSTFIELDS, $post);
    
    if($proxy['ip'] && $proxy['port']) {
        curl_setopt($ch, CURLOPT_PROXYTYPE, 'HTTP');
        curl_setopt($ch, CURLOPT_PROXY, $proxy['ip']); //设置代理ip
        curl_setopt($ch, CURLOPT_PROXYPORT, $proxy['port']); //设置代理端口号
        
        if($proxy['loginpwd']) {
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy['loginpwd']); //设置代理密码   
        }
    }
    
    if($gzip) {
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip'); //针对已gzip压缩过的进行解压，不然返回内容会是乱码
    }
    
    if(!empty($header)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header); //设置http请求头信息
    }
    
    //执行并获取HTML文档内容
    $data['content'] = curl_exec($ch);
    $data['httpcode'] = curl_getinfo($ch, CURLINFO_HTTP_CODE); //返回http_code状态码
    
    //释放curl句柄
    curl_close($ch);
    
    return $data;
    
}

/**
* curl 提交
* $url 请求url地址
* $header 请求头信息
* $proxy 代理信息 ip=>代理ip, port=>代理端口, loginpwd=>代理密码
* $gzip 是否需要gzip解压
*/
function curl_http($url, $header = array(), $proxy = array(), $gzip = false, $cookie = '')
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //将curl_exec()获取的信息以文件流的形式返回，而不是直接输出
    curl_setopt($ch, CURLOPT_HEADER, 0); //是否取得头信息 
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); //是否抓取跳转后的页面 
    curl_setopt($ch, CURLOPT_TIMEOUT, 60); //设置超时 秒 
    //curl_setopt($ch, CURLOPT_USERAGENT,'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/536.11 (KHTML, like Gecko) Chrome/20.0.1132.47 Safari/536.11');

    if($proxy['ip'] && $proxy['port']) {
        curl_setopt($ch, CURLOPT_PROXYTYPE, 'HTTP');
        curl_setopt($ch, CURLOPT_PROXY, $proxy['ip']); //设置代理ip
        curl_setopt($ch, CURLOPT_PROXYPORT, $proxy['port']); //设置代理端口号
        
        if($proxy['loginpwd']) {
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy['loginpwd']); //设置代理密码   
        }
    }

    if($gzip) {
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip'); //针对已gzip压缩过的进行解压，不然返回内容会是乱码
    }
    
    if(!empty($header)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header); //设置http请求头信息
    }

    //执行并获取HTML文档内容
    $data['content'] = curl_exec($ch);
    
    //正则匹配 Cookie
    /*
    if(preg_match_all('/Set-Cookie:(.*);/iU', $output, $cookie_match)) {
        foreach($cookie_match[1] as $val) {
            $data['cookie'][] = $val;
        }    
    }
    */ 
    
    //提交cookie
    if($cookie) {
        curl_setopt($ch, CURLOPT_COOKIE, $cookie);
    }

    $data['httpcode'] = curl_getinfo($ch, CURLINFO_HTTP_CODE); //返回http_code状态码
    
    //释放curl句柄
    curl_close($ch);
    
    return $data;
} 


/*
'header'=>"Host: xxx.com\r\n" . 
        "Accept-language: zh-cn\r\n" . 
        "User-Agent: Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1; Trident/4.0; 4399Box.560; .NET4.0C; .NET4.0E)" .
        "Accept: *//*"
*/ 
function dfile_get_contents($url, $header = '', $timeout = 60)
{
    $opts = array(
        'http' => array(
            'method' => "GET",
            'timeout' => $timeout, 
        )
    ); 
    if(!empty($header)) {
        $opts['http']['header'] = $header;
    }
    
    $context = stream_context_create($opts);
    $content = @file_get_contents($url, false, $context);
    return trim($content);
}

//生成http header头信息
function http_header($referer = '')
{
    $header = array ();
    $header [] = 'User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; zh-CN; rv:1.9.2.8) Gecko/20100722 Firefox/3.6.8';
    $header [] = 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8';
    $header [] = 'Accept-Encoding: gzip, deflate';
    $header [] = 'Accept-Language: zh-cn,zh;q=0.5';
    $header [] = 'Connection: Keep-Alive';
    if($referer) $header [] = 'Referer: '.$referer;
    return $header;
} 

//生成指定位数时间戳
function getTimestamp($digits = false) 
{  
    $digits = $digits > 10 ? $digits : 10;  
    $digits = $digits - 10;  
    if ((!$digits) || ($digits == 10)) {  
        return time();  
    }  
    else {  
        return number_format(microtime(true), $digits, '', '');  
    }  
} 

function sendEmail($to, $title = '', $content = '')
{
    
    require 'PHPMailer/class.phpmailer.php';
    require 'PHPMailer/class.smtp.php';

    $mail = new PHPMailer(true); 
    $mail->IsSMTP(); //启用SMTP
    $mail->CharSet = 'UTF-8'; //设置邮件的字符编码，这很重要，不然中文乱码 
    $mail->SMTPAuth = true; //启用smtp认证
    $mail->Port = 25; 
    $mail->Host = "smtp.163.com";  //smtp服务器的名称
    $mail->Username = "lba8610@163.com"; //发件人邮箱名
    $mail->Password = "153421423163"; //发件人邮箱密码
    //$mail->IsSendmail(); //如果没有sendmail组件就注释掉，否则出现“Could not execute: /var/qmail/bin/sendmail ”的错误提示 
    //$mail->AddReplyTo("phpddt1990@163.com", "mckee"); //回复地址 
    $mail->From = "lba8610@163.com";  //址发件人地
    $mail->FromName = "www.panziyuan.com";  //发件人姓名
    $mail->AddAddress($to); 
    $mail->Subject = $title? $title: "采集网址内容出现错误"; //邮件主题
    $mail->Body = $content? $content: "采集网址内容出现错误，请及时查看"; //邮件内容 
    $mail->AltBody = "采集网址内容出现错误，请及时查看"; //当邮件不支持html时备用显示，可以省略 
    $mail->WordWrap = 80; // 设置每行字符串的长度 
    //$mail->AddAttachment("f:/test.png"); //可以添加附件 
    $mail->IsHTML(true); 
    $mail->Send(); 
    echo "邮件已发送\r\n"; 
}

function print_log($str = '')
{
    echo date('Y-m-d H:i:s')." {$str}\r\n";
}

function writeLock($status = 1)
{
    file_put_contents(ROOTPATH.'lock.txt', $status);
}


function dump($str)
{
    echo '<pre>';
    var_dump($str);
}