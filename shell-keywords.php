<?php
session_start();
error_reporting(E_ALL ^ E_NOTICE);

$pattern = '/(\.html|\.htm|\.js)$/';  //只修改html htm txt 后缀的文件
$arr = empty($_SESSION['htmlArr'])?array():$_SESSION['htmlArr'];     //html文件的数组
$pathArr = empty($_SESSION['dirArr'])?array():$_SESSION['dirArr']; //文件夹数组
$curPath = empty($_SESSION['curPath'])?".":$_SESSION['curPath'];
$filename = "text";
$type = $_GET['type'];
$term = $_GET['term'];
function microtime_float()
{
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}
function scanCurDir($filePath){
    global $pattern,$arr,$pathArr;
    $files = scandir($filePath);
    for($i = 0 ; $i < count($files) ; $i++ ){
        $f = $filePath."/".$files[$i];
        if( is_file($f) && preg_match($pattern,$files[$i])){
            array_push($arr,$f);
            continue;
        }elseif(is_dir($f) && !preg_match('/\./',$files[$i]) ){
            array_push($pathArr,$f);
            scanCurDir($f);
        }
    }
}

function changeHtml(){
    global $arr;
    for($i=0;$i<count($arr);$i++){
        $body = file_get_contents($arr[$i]);
        $bm = mb_detect_encoding($body);
        print_r("编码为$bm------".$arr[$i]."<br/>");
    }
}

if (empty($_SESSION['dirArr']) && empty($_SESSION['htmlArr'])){
    scanCurDir($curPath);
    $_SESSION['htmlArr'] = $arr;
    $_SESSION['dirArr'] = $pathArr;
}


if( empty($type) ){

?>
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>违禁词替换工具</title>
    <link rel="stylesheet" href="http://apps.bdimg.com/libs/bootstrap/3.3.4/css/bootstrap.min.css">
    <script src="http://apps.bdimg.com/libs/jquery/1.10.2/jquery.js"></script>
    <script src="http://apps.bdimg.com/libs/bjui/1.2/bjui-min.js"></script>
    <style>
        .bjui-tags {
            position: relative;
            display: inline-block;
            padding-top: 0px;
            width: auto;
            min-height: 24px;
            vertical-align: middle;
            background: #FFF;
            border: 1px solid #CCCCCC;
            border-radius: 2px;
            cursor: text;
        }
        .bjui-tags > .tags-menu{position:absolute; top:100%; left:0; z-index:1000; display:none; float:left; margin:2px 0 0; padding:5px 0; list-style:none; background-color:#ffffff; border:1px solid #ccc; border:1px solid rgba(0, 0, 0, 0.2); *border-right-width:2px; *border-bottom-width:2px; -webkit-border-radius:6px; -moz-border-radius:6px; border-radius:6px; -webkit-box-shadow:0 5px 10px rgba(0, 0, 0, 0.2); -moz-box-shadow:0 5px 10px rgba(0, 0, 0, 0.2); box-shadow:0 5px 10px rgba(0, 0, 0, 0.2); -webkit-background-clip:padding-box; -moz-background-clip:padding; background-clip:padding-box;}
        .bjui-tags > .tags-menu > .tags-item{cursor:pointer;}
        .bjui-tags > .tags-menu > li{padding-left:12px; padding-right:12px; min-height:20px; line-height:20px;}
        .bjui-tags > .label-tag {
            color: #c3ced5;
            background-color: #f3f8fc;
        }
        .bjui-tags > .label-tag {
            position: absolute;
            z-index: 1;
            display: inline-block;
            margin-top: 0px;
            font-size: 16px;
            height: 30px;
            line-height: 30px;
            cursor: pointer;
            vertical-align: middle;
        }
    </style>
</head>
<body>

<div class="container">
    <h1 class="text-center">文章中的字去掉</h1>
    <div class="panel panel-default">
        <div class="panel-heading">上传需要去掉文字的文件</div>
        <div class="panel-body">
            <form action="?type=upload"  class="form-inline" id="uploadImg" >
                <div class="form-group">
                    <label for="file" class="sr-only">文件名:</label>
                    <input type="file" name="file" id="file" class="form-control"  />
                </div>
                <div class="form-group">
                    <input type="submit" name="submit" value="上传" class="btn btn-default"  />
                 </div>
                <div class="form-group">
                    <span id="uploadmsg"></span>
                 </div>
            </form>
        </div>
    </div>

    <div class="panel panel-default">
        <div class="panel-heading">选择要替换的目录</div>
        <div class="panel-body">
            <form action="?type=start"  id="getStart" >
                <div class="form-group">
                    <label for="file" class="sr-only">文件夹:</label>
                    <input type="text" name="tags" id="tags" class="form-control" size="80"  data-toggle="tags" data-url="ajaxTags.html" autocomplete="off"/>

                </div>
                <div class="form-group">
                    <span>如果要全站替换就不用选择文件夹</span>
                 </div>
                <div class="form-group">
                    <input type="submit" name="submit" value="开始替换" class="btn btn-default"  />
                 </div>
            </form>
            <input type="hidden" name="startnum" id="startnum" value="100" />
        </div>
        <div class="progress">
          <div class="progress-bar progress-bar-striped active" role="progressbar" id="progress" aria-valuenow="500" aria-valuemin="0" aria-valuemax="<?php echo count($arr); ?>" style="width: 0%">
            <span>0%</span>
          </div>
        </div>
    </div>
</div>

<script>
    var uploadFlag = false;
    var complete = false;

    $("#tags").tags({
        url:"?type=taglist",
        lightCls:"tags-highlight",
        width: 618
    })
    $("#uploadImg").submit(function(event) {
        /* Act on the event */
        event.preventDefault();
        $("#uploadmsg").html("");
        $data = new FormData($('#uploadImg')[0]);
        console.log($data)
        $("#progress").attr("aria-valuenow",0);
        $("#startnum").val(0);
        $("#progress").css("width","0%");
        $("#progress > span").html(0);
        //用FormData做了个异步文件上传
        $.ajax({
            url: '?type=upload',
            type: 'POST',
            cache: false,
            data: $data,
            processData: false,
            contentType: false
        }).done(function(res) {
            console.log("ok")
            var obj = JSON.parse(res);
            if (obj.flag == "ok") {
                $("#uploadmsg").html(obj.msg);
                uploadFlag = true;
            } else {
                uploadFlag = false;
                $("#uploadmsg").html(obj.msg);
            }

        }).fail(function(res) {
            console.log("upload err")
        });
    });
    $("#getStart").submit(function(event) {
        /* Act on the event */
        event.preventDefault();
        $("#progress").attr("aria-valuenow",0);
        $("#startnum").val(0);
        $("#progress").css("width","0%");
        $("#progress > span").html(0);
        if ($("#file").val() == "") {
            alert("请先选择要上传的文件");
            return ;
        }
        submimTo("1");

    });


    function submimTo(temp) {

        // body...
        $data = $("#getStart").serialize();
        $startnum = $("#startnum").val();
        $now = $("#progress").attr("aria-valuenow");
        $max = $("#progress").attr("aria-valuemax");
        $curnum = Math.round(($now/$max)*100)+"%";
        $("#progress").css("width",$curnum);
        $("#progress > span").html($curnum);
         console.log(parseInt($now)  >= parseInt($max))
        if(parseInt($now)  >= parseInt($max)){
            complete = false;
            return;
        }
        console.log(temp+$now+":"+$max)
  //      alert($now);


        $.ajax({
            url: '?type=start&startnum='+$startnum,
            type: 'POST',
            cache: false,
            async: false,
            data: $data
        }).done(function(json) {
            var obj = JSON.parse(json);
            console.log("反回来："+obj.valuenow);
            if (obj.flag == "err") {
                alert(obj.msg);
                return;
            }
            $("#progress").attr("aria-valuenow",obj.valuenow);
            console.log("aria-valuenow："+ $("#progress").attr("aria-valuenow"));
            $("#startnum").val(obj.valuenow+1)
            console.log("反回来："+$("#startnum").val());

            complete = true;

        }).fail(function(res) {
            console.log("start err")
        });


    }

    var sendtime = setInterval(function () {
        // body...
        console.log(complete);
        if (complete) {
            console.log(complete);
            complete = false;
            submimTo("xuanhuan");
        }
    }, 1000);
</script>
</body>
</html>

<?php

//    scanCurDir(".");
//    echo count($arr)."<br>";
//    changeHtml();
}else if($type == "taglist"){

    $sparr = array();

    for ($i=0; $i < count($pathArr); $i++) {
        if(count($sparr)>=10)break;
        if(strpos($pathArr[$i],$term) !== false){
            $tmp['id'] = $i;
            $tmp['label'] = $pathArr[$i];
            $tmp['value'] = $pathArr[$i];
            array_push($sparr,$tmp);
        }
        unset($tmp);
    }
    echo json_encode($sparr);
    exit;

}else if($type == "upload"){

    $filename = $_FILES['file']['name'];
    $rs = move_uploaded_file($_FILES['file']['tmp_name'],$filename);

    if($filename != "" && $rs){
        $_SESSION['filename'] = $filename;
        if (!file_exists($filename) ) {
            echo json_encode(array("msg"=>"上传文件不存在失败","flag"=>"err"));
            exit;
        }

        try {
            $filestr = file_get_contents($filename);
        }
        catch (Exception $e) {
            echo json_encode(array("msg"=>$e->getMessage(),"flag"=>"err"));
            exit;
        }


        if ($filestr == "" || empty($filestr)) {
            echo json_encode(array("msg"=>"上传文件为空","flag"=>"err"));
            exit;
        }

        $uploadEncode = mb_detect_encoding($filestr,array('ASCII','UTF-8','GB2312','GBK','BIG5'));

        $filestr = iconv($uploadEncode, 'GBK', $filestr);
        $arr1 = explode("\r\n", $filestr);
        foreach ($arr1 as $v) {
            # code...
            $a = explode(",", $v);
            if($a[0]){
                $tmp[$a[0]] = $a[1];
            }

        }
        $_SESSION['words_gbk'] = $tmp;
        unset($tmp);

        $filestr = iconv($uploadEncode, 'UTF-8', $filestr);
        $arr1 = explode("\r\n", $filestr);
        foreach ($arr1 as $v) {
            # code...
            $a = explode(",", $v);
            if($a[0]){
                $tmp[$a[0]] = $a[1];
            }

        }
        $_SESSION['words_utf'] = $tmp;
        unset($tmp);

    }else{
        echo json_encode(array("msg"=>"上传文件名为空！","flag"=>"err"));
        exit;
    }

    if (!empty($_SESSION['words_utf']) && !empty($_SESSION['words_gbk'])) {
        echo json_encode(array("msg"=>"上传文件成功！","flag"=>"ok"));
        exit;
    }else{
        echo json_encode(array("msg"=>"上传文件失败！","flag"=>"err"));
        exit;
    }


}else if($type == "start"){

    if(!$_SESSION['filename']){
        var_dump($_SESSION['filename']);
        echo json_encode(array("valuenow"=>0,"flag"=>"err","msg"=>"上传的文件为空！"));
        unset($_SESSION['words_gbk']);
        unset($_SESSION['words_utf']) ;
        unset($_SESSION['htmlArr'] );
        unset($_SESSION['dirArr'] );
        unset($_SESSION['filename']);
        unset($_SESSION['curPath']);
        exit;
    }

    if(empty($_SESSION['curPath'])){
        $data = explode("&",$_POST['data']);
        foreach ($data as $value) {
            # code...
            list($k,$v) = explode("=",$value);
            if($k == "tag" && $v != ""){
                $_SESSION['curPath'] = urldecode($v);
            }else{
                $_SESSION['curPath'] = ".";
            }

        }
    }
    if(!in_array($_SESSION['curPath'], $pathArr) && $_SESSION['curPath'] != "."){
        echo json_encode(array("valuenow"=>0,"flag"=>"err","msg"=>"没有".$_SESSION['curPath']."路径"));
        unset($_SESSION['words_gbk']);
        unset($_SESSION['words_utf']) ;
        unset($_SESSION['htmlArr'] );
        unset($_SESSION['dirArr'] );
        unset($_SESSION['filename']);
        unset($_SESSION['curPath']);
        exit;
    }

    $startnum = empty($_GET['startnum'])? 0 : $_GET['startnum'];
    $tmpnum = $startnum;
    $endnum =  $startnum+100;
    $tmpstr = "";

    //$startTime = microtime_float();
    for ($i=$startnum; $i < $endnum; $i++) {
        //$endTime = microtime_float();

        if($i>=count($arr)){
            @unlink($_SESSION['filename']);
            unset($_SESSION['words_gbk']);
            unset($_SESSION['words_utf']) ;
            unset($_SESSION['htmlArr'] );
            unset($_SESSION['dirArr'] );
            unset($_SESSION['filename']);
            unset($_SESSION['curPath']);

            echo json_encode(array("valuenow"=>count($arr),"flag"=>"ok"));
            exit;
        }
        # code...
        if (file_exists($arr[$i])) {
            # code...
            $tmpstr = @file_get_contents($arr[$i]);
            $tmpEncode = mb_detect_encoding($tmpstr,array('ASCII','UTF-8','GB2312','GBK','BIG5'));
            $words = $tmpEncode == 'UTF-8'  ? $_SESSION['words_utf'] : $_SESSION['words_gbk'];
            $tmpnewstr = strtr($tmpstr,$words);
            $rs = @file_put_contents($arr[$i], $tmpnewstr);
        }
        $tmpnum = $i;
    }
    $startnum = $tmpnum;
    echo json_encode(array("valuenow"=>$startnum,"flag"=>"ok"));
    exit;
}else{
    echo $type;
}

?>

