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
}