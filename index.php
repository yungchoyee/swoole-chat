<html>

<head>
    <title>IM - zmis.me官网</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<style>
    body {
        margin: 0;
        padding: 0;
        font-family: 'HanHei SC', 'PingFang SC', 'CenturyGothic', 'Helvetica Neue', 'Helvetica', 'STHeitiSC-Light', 'SimHei', 'Arial', sans-serif;
    }

    .source-content {
        font-size: 18px;
        text-align: center
    }

    .demo {
        display: block;
        width: 100%;
        height: auto;
        float: left;
        flex-direction: column;
        box-shadow: 2px 2px 2px #888888;
    }

    .main div {
        padding-top: 5px;
    }


    @media all and (max-width: 766px) {
        .main {
            text-align: center;
            width: auto;
            margin: 0 auto;
        }
        #content {
            border: none;
            width: auto;
            height: auto;
        }
        #lists {
            height: 250px;
            overflow-y: scroll;
            padding: 15px;
            text-align: left;
        }
        .main-header {
            padding-top: 10px;
            font-size: 18px;
            text-align: center;
        }
        p{
            margin: 0 3px;
        }
    }

    @media all and (min-width: 767px) {
        #content {
            border: none;
            width: 500px;
            height: auto;
        }
        .main {
            width: 600px;
            margin: 0 auto;
        }
        #lists {
            height: 400px;
            overflow-y: scroll;
            padding: 15px;
            text-align: left;
        }
        .main-header {
            padding-top: 30px;
            font-size: 18px;
            text-align: center;
        }
    }

    textarea,
    input,
    button {
        font-size: 18px;
        border: none;
        border-radius: 3px;
        padding: 10px;
        background-color: #f1f1f1;
    }

    button {
        padding: 6px 18px;
    }

    ul {
        padding-left: 0
    }

    ul li {
        list-style: none
    }

    .send {
        text-align: right;
    }

    .receive {
        text-align: left
    }

    #online {
        background-color: green;
        color: white;
    }

    #offline {
        background-color: red;
        color: white;
    }

    #tpointer {
        cursor: pointer;
    }

    .peoplelists {
        float: left;
        display: flex;
        padding:0 5px;
    }
    #home{
        margin-top: 20px;
        text-align: center;
    }
    #qq{
        text-align: center;
        margin-right: 10px;
    }
</style>

<body>
<?php
require_once __DIR__."/config.php";
if (!isset($_GET['id']) || empty($_GET['id']) || empty((int)$_GET['id']) ) {
    ?>
    <div id="home">
        <input type="number" placeholder="您的QQ号码" id = "qq">
        <button onclick="qqGo()">确定!</button>
    </div>
    <script>
        function qqGo() {
            var qq = document.getElementById("qq")
            if(qq.value !== undefined || qq.value !==  0 || qq.value !== '') {
                location.href= '?id='+ qq.value
            }
        }
    </script>
    <?php
    die;
} else {
    $isAdmin = false;
    if ($_GET['id'] == $config['manage']['adminID']) {
        $isAdmin = true;
    }
}
?>
<div class="demo">
    <h1 class="main-header">
        欢迎使用PHP + Swoole + Websocket 的SimpleChatOnline测试平台
    </h1>
    <p class="source-content">源码请访问<a href="https://github.com/zmisgod/SimpleChatOnline">Github</a></p>
    <?php if($isAdmin): ?>
        <p class="source-content">管理员，您好</p>
    <?php else: ?>
        <p class="source-content">欢迎您，用户ID = "<?php echo $_GET['id']; ?>"</p>
    <?php endif; ?>
    <div class="main">
        <p>You are <span id="online">Online</span> Now!</p>
        <div>
            <textarea rows="3" cols="" id="content"
                      placeholder="<?php if ($isAdmin): ?>What do you want to broadcast<?php else: ?>What do you want to send<?php endif; ?>"></textarea>
        </div>

        <div>
            <?php if ($isAdmin): ?>
                <input type="hidden" value="" id="toid">
            <?php else: ?>
                <input type="text" value="" id="toid" placeholder="to id">
            <?php endif; ?>
        </div>
        <div>
            <button onclick="sendMessage()">发送</button>
        </div>

        <p>发送列表：</p>
        <ul id="lists">

        </ul>
    </div>
</div>
<div class="peoplelists">
    <a>在线列表</a>
    <ul id="peopleullists">

    </ul>
</div>
</body>
<script type="text/javascript">
    var socket = new WebSocket('ws://<?php echo $config['base']['host']; ?>:<?php echo $config['base']['port']; ?>?id=<?php echo $_GET['id']; ?>&type=chat')
    // 打开Socket
    socket.onopen = function (event) {
    };
    //收到信息
    socket.onmessage = function (event) {
        var data = JSON.parse(event.data)
        var list = document.getElementById("lists")
        var toidObj = document.getElementById("toid")
        var toid = toidObj.value
        //添加 li

        var li = document.createElement("li");
        li.setAttribute('class', 'receive')
        li.innerHTML = data.content
        list.appendChild(li)
    };
    //关闭连接通知
    socket.onclose = function (event) {
        var obj = document.getElementById('online')
        obj.innerHTML = 'offline <a id="tpointer" onclick="tryAgain()">click me to try again</a>'
        obj.setAttribute('id', 'Offline')
    };

    function tryAgain() {
        history.go(0);
    }

    //发送消息
    function sendMessage() {
        var obj = document.getElementById('content')
        var content = obj.value
        var toidObj = document.getElementById("toid")
        var toid = toidObj.value
        socket.send('{"toid": "' + toid + '", "content": "' + content + '", "type": "chat"}')
        var list = document.getElementById("lists")
        //添加 li
        var li = document.createElement("li")
        li.setAttribute('class', 'send')
        li.innerHTML = content
        list.appendChild(li)
        document.getElementById("content").value = ""
    }
</script>

<script>
    var sockets = new WebSocket('ws://<?php echo $config['base']['host']; ?>:<?php echo $config['base']['port']; ?>?type=count')
    // 打开Socket
    sockets.onopen = function (event) {};
    //收到信息
    sockets.onmessage = function (event) {
        var data = JSON.parse(event.data)
        var content = data.content
        var list = document.getElementById("peopleullists")
        var li = ''
        for(var  i = 0 ; i < content.length; i++){
            li += '<li>'+ content[i] +'</li>';
        }
        list.innerHTML = li
    };
    setInterval(function () {
        sockets.send('{"type": "count"}')
    }, 5000);
</script>

</html>