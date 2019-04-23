<?php

class UsersController extends Controller
{
    public function index()
    {
        dd('这是home');
    }

    public function login()
    {
//        dd('这是login');
        return view('app/pages/login');
    }

    //检测用户名

    public function loginCheck()
    {
        //检测数据的完整性
//        dd($_POST);

        //检测数据的有效性 --- 长度，字母，
        $username = $_POST['username'];
        $password = $_POST['password'];

        if (empty($username) || empty($password)) {
//            dd('为空');
            //提供了错误提交机制
            error('用户名和密码都不能为空');
//            d(error());
//            foreach (error() as $error)
//                d($error);
            return redirect('/login');
        }
        //然后才是业务，登录检测
        dd('连接数据库查询数据！');
        //查询完之后，进入login之后的页面
        return view('app/pages/login_check');
    }

    public function logout()
    {
        if (session('user')) {
            session_destroy();
            session(null);
            return redirect('/login');
        }
    }

    public function upload()
    {
        return view('app/pages/upload');
    }

    public function uploading()
    {

        //步骤1，验证数据完整性
        d($_FILES);
        $image = $_FILES['image'];

//        id bigint(20) unsigned primary key auto_increment,        唯一id
//        savename varchar(40) not null,                            hash过的名字
//        type varchar(24) not null,                                文件类型
//        ext varchar(12) not null,                                 文件后缀
//        size bigint(20) unsigned not null,                        文件大小
//        savepath varchar(60) not null,                            保存路径
//        md5 char(32) not null,                                    md5值

        //验证上传是否为空
        if (strlen($image['tmp_name']) == 0) {
            error('未选择上传文件！');
            return redirect('/upload');
        }
        //文件类型限制
        if (!preg_match('/^image\//', $image['type'])) {
//            dd('正确的输出文件类型', $file['type']);
            error('文件类型不是图片！');
            return redirect('/upload');
        }
        //文件大小限制，验证数据是否为空
        if ($image['size'] > 2 << 10 << 10) {//大小限制在2M以内 2*1024*1024
            error('文件大小超过2M！！！');
            return redirect('/upload');
        }
        //以上限制通过之后开始业务---真正文件上传
        //首先，判断文件是否存在，如果存在，则为这个用户添加一个映射，
        //不存在则添加一个resource并添加一个映射，
        //通过md5判断，单向不可逆hash值
        d($image['tmp_name']);
        $image_md5 = md5_file($image['tmp_name']);
        d('file_md5:', $image_md5);

        $sql = 'select id from t_image_resource where `md5` = ?';
        $findParameters = [$image_md5];
        d($sql, $findParameters);
        $db = new DB();
        $result = $db->find($sql, $findParameters);

        $user_id = 1;
        $look_name = $image['name'];
        //首先判断md5找不到的情况即result = false,文件名加后缀，然后移动文件到目标文件夹
        if (!$result) {
            $image_name = md5(date("Y-m-d H:i:s") . rand() . md5($image_md5));
            $image_ext = pathinfo($image['name'])["extension"];
            $image_name .= '.' . $image_ext;
            $folder = 'saveUploadPath/' . date('Y-m-d') . '/';

            if (!file_exists($folder))
                mkdir($folder, 0777, true);
            $dest_file = $folder . $image_name;
            //移动文件
            move_uploaded_file($image['tmp_name'], $dest_file);
            $save_image_parameters = [
                'savename' => $image_name,
                'image_type' => $image['type'],
                'ext' => $image_ext,
                'size' => $image['size'],
                'savepath' => $folder,
                'md5' => $image_md5
            ];
            $save_image = $db->save('t_image_resource', $save_image_parameters);
            $image_id = $save_image->id;

            d('数据库暂无此照片，保存图片成功');

        } else {
            $image_id = $result->id;

            d('在数据库中找到了数据');
        }
        //一个use_id只用映射一张照片，如果该ues_id 在表中存在，则update，不存在则insert
        $sql = 'select id from t_image_map where use_id = ?';
        $findParameters = [$user_id];
        d($sql, $findParameters);
        $result = $db->find($sql, $findParameters);

        $save_map_parameters = [
            'use_id' => $user_id,
            'image_id' => $image_id,
            'look_name' => $look_name
        ];

        if (!$result) {
            //不存在，insert
            $save_map = $db->save('t_image_map', $save_map_parameters);
            dd($save_map, '跟用户映射成功');
        } else {
            //存在，update
            $sql = 'update t_image_map set image_id = ?, look_name = ? where use_id = ?';
            $save_map_parameters = [$image_id, $look_name, $user_id];
            $update = $db->query($sql, $save_map_parameters);
            dd($update, '之前存在现在更改');
        }

    }
}