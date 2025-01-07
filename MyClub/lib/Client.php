<?php

class Client{

    function getBrowser() {
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        $browser        = "Inconnu";
        $browser_array = array( '/mobile/i'    => 'Handheld Browser',
                    '/msie/i'      => 'Internet Explorer',
                    '/trident/i'   => 'Internet Explorer',
                    '/firefox/i'   => 'Firefox',
                    '/safari/i'    => 'Safari',
                    '/chrome/i'    => 'Chrome',
                    '/edg/i'      => 'Edge',
                    '/opera/i'     => 'Opera',
                    '/netscape/i'  => 'Netscape',
                    '/maxthon/i'   => 'Maxthon',
                    '/konqueror/i' => 'Konqueror'
        );
        foreach ($browser_array as $regex => $value)
        if (preg_match($regex, $user_agent))
            $browser = $value;
        return $browser;
    }

    function getIp() {
        if(!empty($_SERVER['HTTP_CLIENT_IP'])){
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }else{
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }

    function getOs() {
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        $os_platform  = "Inconnu";
        $os_array     = array(  
            '/windows nt 10/i'      =>  'Windows 10',
            '/windows nt 6.3/i'     =>  'Windows 8.1',
            '/windows nt 6.2/i'     =>  'Windows 8',
            '/windows nt 6.1/i'     =>  'Windows 7',
            '/windows nt 6.0/i'     =>  'Windows Vista',
            '/windows nt 5.2/i'     =>  'Windows Server 2003/XP x64',
            '/windows nt 5.1/i'     =>  'Windows XP',
            '/windows xp/i'         =>  'Windows XP',
            '/windows nt 5.0/i'     =>  'Windows 2000',
            '/windows me/i'         =>  'Windows ME',
            '/win98/i'              =>  'Windows 98',
            '/win95/i'              =>  'Windows 95',
            '/win16/i'              =>  'Windows 3.11',
            '/macintosh|mac os x/i' =>  'Mac OS X',
            '/mac_powerpc/i'        =>  'Mac OS 9',
            '/linux/i'              =>  'Linux',
            '/ubuntu/i'             =>  'Ubuntu',
            '/iphone/i'             =>  'iPhone',
            '/ipod/i'               =>  'iPod',
            '/ipad/i'               =>  'iPad',
            '/android/i'            =>  'Android',
            '/blackberry/i'         =>  'BlackBerry',
            '/webos/i'              =>  'Mobile'
        );
        foreach ($os_array as $regex => $value)
        if (preg_match($regex, $user_agent))
            $os_platform = $value;
        return $os_platform;
    }

    function getScreenResolution(){
        if(isset($_COOKIE['screen_resolution'])) {
            $resolution = $_COOKIE['screen_resolution'];
        }
        else {
            $resolution = '';
        }
        return $resolution;
    }

    function getType(){
        $useragent = $_SERVER['HTTP_USER_AGENT'];
        if (preg_match('/(android|bb\\d+|meego).+mobile|avantgo|bada\\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i', $useragent)) {
            return 'Mobile';
        }
        if (preg_match('/(tablet|ipad|playbook|silk)|(android(?!.*mobile))/i', $useragent)) {
            return 'Tablette';
        }
        return 'PC';
    }

    function getUri(){
        return $_SERVER['REQUEST_URI'];
    }

    function getToken(){
        return $_SESSION['token'] ?? '';
    }
}

?>