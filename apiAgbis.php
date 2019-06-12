<?php

class apiAgbis
{
    protected $sessionId;
    
    protected $refreshId;
    
    protected $timeSession;
    
    protected $host = 'https://www.himstat.ru/cl/contrast/api/?';
        
    public $user;
            
    public $pwd;
            
    public function config($user, $pwd)
    {
        $this->user = $user;
        $this->pwd = $pwd;
        $this->auth();
    }



// Посылатель GET-запроса
    public function senderGET($link){
        $curl = curl_init(); 
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_URL, $link);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($curl, CURLOPT_HEADER, false);
        $out = curl_exec($curl); 

        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $this->errors($code);

        // $Response = json_decode($out, true);
        // return $Response;

        return $out;
    }



// Посылатель POST-запроса
    // public function senderPOST(){
    //     return NULL;
    // }



// Авторизация
    public function auth()
    {
        $pwd = sha1($this->pwd);
        $str = '{"User": "' . $this->user . '",'
            . ' "Pwd": "' . $pwd . '",'
            . ' "AsUser": "1"}';
        $link = $this->host . 'Login=' . urlencode($str) . '&json=yes';

        $out = senderGET($link);
        $Response = json_decode($out, true);

        $this->sessionId = $Response['Session_id'];
        $this->refreshId = $Response['Refresh_id'];
        $this->timeSession = time();
    }



// Обновление сессии
    public function refreshSession()
    {
        if (($this->timeSession+540) <= time()) {
            return;
        } else {
            $str = '{"Refresh_ID": "' . $this->refreshId . '"}';
            $link = $this->host . 'RefreshSession=' . urlencode($str) . '&json=yes';

            $out = senderGET($link);
            $Response = json_decode($out, true);

            $this->sessionId = $Response['Session_id'];
            $this->refreshId = $Response['Refresh_id'];
        }
    }



// Функция обработки ошибок
    private function errors($code)
    {
        $code = (int)$code;
        $errors = array(
            301=>'Moved permanently',
            400=>'Bad request',
            401=>'Unauthorized',
            403=>'Forbidden',
            404=>'Not found',
            500=>'Internal server error',
            502=>'Bad gateway',
            503=>'Service unavailable'
        );
        try
        {
            if($code!=200 && $code!=204) {
                throw new Exception(isset($errors[$code]) ? $errors[$code] : 'Undescribed error', $code);
            }
        }
        catch(Exception $E)
        {
            die('Ошибка: ' . $E->getMessage() . PHP_EOL . 'Код ошибки: ' . $E->getCode());
        }
    }
  


// Запрос количества контактов в Агбис
    public function contragInfoBetweenForAMOCuont()
    {
        $this->refreshSession();
        $str = '{"line_count": "1"}';
        
        $link = $this->host . 'ContragInfoBetweenForAMO='
                . '' . urlencode($str) . '&SessionID=' . $this->sessionId . '&json=yes';

        $out = senderGET($link);
        $Response = json_decode($out, true);

        if ($Response['error'] !== 0) {
            $Response = urldecode($Response['Msg']);
            return $Response;
        } else {
            return $Response['contr_count'];
        }
        
    }



// Запрос данных о всех клиентах, находящихся в системе Агбис
    public function contragInfoBetweenForAMO($count, $offset=null)
    {
        $this->refreshSession();
        if ($offset == 0){$offset = 1;}
        if ($offset == null){$offset = 1;}
        $count = $count + $offset;
        error_log($offset . PHP_EOL, 3, "my-errors.log");
        error_log($count . PHP_EOL, 3, "my-errors.log");
        $str = '{"line_first":"' . $offset . '", "line_last":"' . $count . '"}';
        $link = $this->host . 'ContragInfoBetweenForAMO='
                . '' . urlencode($str) . '&SessionID=' . $this->sessionId . '&json=yes';

        // $Response = json_decode( urldecode($out), true);
        $out = senderGET($link);
        $Response = json_decode( urldecode($out), true);

        if ($Response['error'] !== 0) {
            $Response = urldecode($Response['Msg']);
            //$Response = 'ошибка';
        }
        return $Response;
    }



//Запрос данных о всех заказах, которые находятся в системе Агбис
    public function OrdersInfoForAMO($contrId)
    {
        $this->refreshSession();
        
        $str = '{"contr_id":"' . $contrId . '"}';
        $link = $this->host . 'OrdersInfoForAMO='
                . '' . urlencode($str) . '&SessionID=' . $this->sessionId . '&json=yes';

        // $Response = json_decode( urldecode($out), true);
        $out = senderGET($link);
        $Response = json_decode( urldecode($out), true);

        if ($Response['error'] !== 0) {
            $Response = urldecode($Response['Msg']);
        }
        return $Response;
    }
}
