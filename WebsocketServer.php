<?php
class WebsocketServer
{
    private $_server;

    public static $uid = [];

    public static $uidInfo = [];

    private $close;

    private $admin = false;

    private $config = [];

    public  static $from_id;

    public  static $to_id;

    public static $message = [];

    public function __construct()
    {
        require_once __DIR__.'/config.php';
        $this->config = $config;

        $this->_server = new Swoole\Websocket\Server($this->config['base']['host'], $this->config['base']['port']);

        $this->_server->set($this->config['swoole']);


        $this->_server->on('Open', [$this, 'onOpen']);

        $this->_server->on('Message', [$this, 'onMessage']);

        $this->_server->on('Close', [$this, 'onClose']);

        $this->_server->start();
    }

    public function showData()
    {
        echo '当前在线人数: '.count(self::$uid).PHP_EOL;
    }

    public function onOpen($server, $req)
    {
        //判断是否为统计数据的链接
        if(isset($req->get['type']) && $req->get['type'] == 'count') {
        }else{
            //先绑定uid与fd
            $this->bindUid(['id' => $req->get['id'], 'name' => ''], $req->fd);
            self::$from_id = $req->get['id'];
            $this->showData();
        }
//        $server->push($frame->fd, json_encode(['toid' => 'system', 'content'=> '连接成功']));
    }

    protected  function connect_mysql()
    {
        $link = mysqli_connect('localhost','root','654321');
        if(!$link){
            exit('数据库连接失败');
        }
        mysqli_set_charset($link,'utf8');
        mysqli_select_db($link,'swoole');
        return $link;
    }

    protected function ecex_sql($from_id,$to_id,$content)
    {
        $link = $this->connect_mysql();
        $sql = "insert into message(from_id,to_id,content) values ($from_id,$to_id,$content)";
        $result = mysqli_query($link,$sql);
        $res = mysqli_fetch_assoc($result);
        mysqli_close($link);
        return $res;
    }

    public function onMessage($server, $frame)
    {
//        $result = json_decode($frame->data, true);
//        $server->push($frame->fd, json_encode(['toid' => 'system', 'content'=> 'from_id:'.self::$from_id."; to_id:".$result['toid'] ]));die;
        if (!empty($server)) {
            $result = json_decode($frame->data, true);
            if(isset($result['type'])) {
                if($result['type'] == 'chat') {
                    if(self::$uidInfo[$this->getUid($frame->fd)]['role'] == 'admin') {
                        $i = 0;
                        foreach (self::$uid as $v) {
                            if($v != $frame->fd) {
                                $i++;
                                $server->push($v, json_encode(['toid' => 'system', 'content'=> '[System Broadcast]: '.$result['content']]));
                            }
                        }
                        $server->push($frame->fd, json_encode(['toid' => 'system', 'content'=> '[System]: 广播发送成功, 成功广播到'.$i.'个用户']));
                    }else {
                        //根据前端传递的toid获取要发送到此toid绑定的fd
                        $sendToFd = isset(self::$uid[$result['toid']]) ? self::$uid[$result['toid']] : false;
                        if ($sendToFd === false) {
                            $server->push($frame->fd, json_encode(['toid' => 'system', 'content' => '[System]: 对方不在线，请重试']));
                        } else {
                            if ($sendToFd == $frame->fd) {
                                $server->push($frame->fd, json_encode(['toid' => 'system', 'content' => '[System]: 不能给自己发送消息哦！']));
                            } else {
                                if ($result['content']) {
                                    //发送到此toid的fd中
                                    $uid = $this->getUid($frame->fd);
                                    $server->push($sendToFd, json_encode(['toid' => 'id = ' . $uid, 'content' =>  $uid .': ' . $result['content']]));
                                } else {
                                    $server->push($frame->fd, json_encode(['toid' => 'system', 'content' => '[System]: 发送信息不能为空']));
                                }
                            }
                        }
                    }
                }elseif ($result['type'] == 'count') {
                    $server->push($frame->fd, json_encode(['toid' => 'system', 'content' => array_values(array_keys(self::$uid))]));
                }
            }
        }
    }

    public function onClose($server, $fd)
    {
        //当断开连接时，取消fd与此uid的绑定，并将uid绑定的fd取消
        $uid = $this->getUid($fd);
        $this->unsetFd($fd);
        echo "user : " . $uid.' closed'.PHP_EOL;
        $this->showData();
    }

    public function close($fd, $message)
    {
        $this->_server->close($fd, $message);
    }

    public function bindUid($uidInfo, $fd)
    {
        $isAdmin = $this->checkAdmin($uidInfo['id']);
        self::$uid[$uidInfo['id']] = $fd;
        self::$uidInfo[$uidInfo['id']] = [
            'role' => $isAdmin,
            'name' => isset($uidInfo['name']) ? $uidInfo['name'] : rand(100000, 999999),
        ];
    }

    public function getUid($fd)
    {
        $result = array_search($fd, self::$uid);
        if($result !== false) {
            return $result;
        } else {
            return false;
        }
    }

    public function unsetFd($fd)
    {
        $result = array_search($fd, self::$uid);
        if($result !== false) {
            unset(self::$uid[$result]);
            unset(self::$uidInfo[$result]);
        }
    }

    public function checkAdmin($userId)
    {
        if($userId == $this->config['manage']['adminID']) {
            echo '管理员: '.$this->config['manage']['adminID'].'登录 '.PHP_EOL;
            return 'admin';
        }
        return 'user';
    }
}

new WebsocketServer();
