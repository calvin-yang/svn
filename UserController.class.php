<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Home\Controller;

use User\Api\UserApi as UserApi;
use ORG\Net;

/**
 * 用户控制器
 * 包括用户中心，用户登录及注册
 */
class UserController extends HomeController
{

    /* 用户中心首页 */
    public function index()
    {
        //$login = A('User/User', 'Api')->login('麦当苗儿ss', 'aoiujz');
        //$login = A('User/User', 'Api')->register('麦当苗儿ss', 'aoiujz', 'xiaoxiaoxiao@qq.com');
        //$login = A('User/User', 'Api')->checkEmail('zuojiazi@vip.qq.com');
        //dump($login);
    }

    /*
     * 找回密码 - 手机验证
     * url:dev.hileyou.com/index.php?s=/home/user/forgetVerify/mobile/18588613273/verify_code/asda
     */
    public function forgetVerify($mobile, $verify_code)
    {
        #验证验证码
        $result = Net\Sms::mobileVerify($mobile, 'forget_pwd', $verify_code);
        if (!$result)
            $this->returnJson(false, Net\Sms::getError());

        $this->returnJson(true, '验证成功');
    }

    /*
     * 找回密码
     * url:dev.hileyou.com/index.php?s=/home/user/forgetPwd/mobile/18588613273/newPwd/asdasd/reNewPwd/asdasd
     */
    public function forgetPwd($mobile = '', $newPwd = '', $reNewPwd = '')
    {
        if (IS_POST && is_numeric($mobile) && strlen($mobile) == 11) {
            $UcenterMemberModel = D('UcenterMember');
            $result = $UcenterMemberModel->forgetPwd($mobile, $newPwd, $reNewPwd);
            if (!$result)
                $this->returnJson(false, $UcenterMemberModel->error);

            $this->returnJson(true, '修改成功！');
        }
        $this->getError(1);
    }

    /*
     * 修改密码
     * url:dev.hileyou.com/index.php?s=/home/user/editPwd/oldPwd/asdasd/newPwd/asdasd/reNewPwd/asdasd
     */
    public function editPwd($oldPwd = '', $newPwd = '', $reNewPwd = '')
    {
        is_login() || $this->returnJson(false, '您还没有登录，请先登录！');


        $uid = $_SESSION['onethink_home']['user_auth']['uid'];
        if (IS_POST && $uid) {
            $UcenterMemberModel = D('UcenterMember');
            $member_obj = $UcenterMemberModel->getById($uid);
            if (think_ucenter_md5($oldPwd, UC_AUTH_KEY) != $member_obj['password'])
                $this->returnJson(false, '原始密码不正确！');
            if ($oldPwd == $newPwd)
                $this->returnJson(false, '新密码不能是原始密码！');
            $result = $UcenterMemberModel->editPwd($uid, $newPwd, $reNewPwd);
            if (!$result)
                $this->returnJson(false, $UcenterMemberModel->error);

            $this->returnJson(false, '修改成功！');
        }
        $this->getError(1);
    }


    /*
     * 修改手机 第一步
     * url:dev.hileyou.com/index.php?s=/home/user/editMobileOne/mobile/18588613273/verify_code/123
     */
    public function editMobileOne($mobile = '', $verify_code = '')
    {
        if (IS_POST && is_numeric($mobile) && $verify_code) {
            #验证验证码
            $result = Net\Sms::mobileVerify($mobile, 'edit_mobile_one', $verify_code);
            if (!$result)
                $this->returnJson(false, Net\Sms::getError());

            $this->returnJson(true, '验证成功');
        }
        $this->getError(1);
    }

    /*
     * 修改手机 第二步
     * url:dev.hileyou.com/index.php?s=/home/user/editMobileTwo/oldMobile/newMobile/18588613273/verify_code/123
     */
    public function editMobileTwo($oldMobile = '', $newMobile = '', $verify_code = '')
    {
        if (IS_POST && is_numeric($newMobile) && $verify_code && is_numeric($oldMobile) && strlen($oldMobile) == 11) {
            #验证验证码
            $result = Net\Sms::mobileVerify($newMobile, 'edit_mobile_two', $verify_code);
            if (!$result)
                $this->returnJson(false, Net\Sms::getError());

            $UcenterMemberModel = D('UcenterMember');
            $map['mobile'] = $oldMobile;
            $data['mobile'] = $newMobile;
            $result = $UcenterMemberModel->saveData($map, $data);
            if ($result)
                $this->returnJson(true, '手机修改成功！');
            else
                $this->returnJson(false, '手机修改失败！');

        }
        $this->getError(1);
    }


    /* 注册页面
     * url:dev.hileyou.com/index.php?s=/home/user/register
     */
    public function register($mobile = '', $password = '', $repassword = '', $verify_code = '', $imei = '', $mobile_model = '')
    {
        if (IS_POST) { //注册用户

            #判断手机号码是否存在
            if ($mobile && is_numeric($mobile)) {
                $MemberModel = D('Member');
                $map['mobile'] = $mobile;
                $member_obj = M('ucenter_member')->where($map)->find();

                if ($member_obj['id'] > 0)
                    $this->ajaxReturn(array('msg' => '注册手机号码已存在！', 'status' => false));
            }
            #表单验证
            if (strlen($password) < 6 || strlen($password) > 15) {
                $this->ajaxReturn(array('msg' => '确认长度在 6-15个字节！', 'status' => false));
            }

            if ($password != $repassword) {
                $this->ajaxReturn(array('msg' => '确认密码不一致！', 'status' => false));
            }

            #验证验证码
            $result = Net\Sms::mobileVerify($mobile, 'reg', $verify_code);
            if (!$result)
                $this->ajaxReturn(array('msg' => Net\Sms::getError(), 'status' => false));


            #注册
            $User = new UserApi;
            $uid = $User->register($mobile, $password);
            if (0 < $uid) { //注册成功
                $user = array('uid' => $uid, 'reg_time' => NOW_TIME, 'reg_ip' => get_client_ip(1), 'mobile' => $mobile, 'status' => 1, 'imei' => $imei, 'mobile_model' => $mobile_model);
                $is_reg = $MemberModel->add($user);

                //用户登陆
                $MemberModel->login($uid);
                if ($is_reg)
                    $this->ajaxReturn(array('msg' => '注册成功！', 'status' => true));
            } else { //注册失败，显示错误信息
                $this->ajaxReturn(array('msg' => '注册失败！', 'status' => false));
            }
        }
        $this->getError(1);
    }

    /*
     *   请求发送验证码
     *  url:dev.hileyou.com/index.php?s=/home/user/sendVerify/mobile/185886132777/type/forget_pwd
     */
    public function sendVerify($mobile = '', $type = 'reg')
    {
        if ($_SESSION['send_verify_time'])
            if (time() - $_SESSION['send_verify_time'] < 60)
                $this->returnJson(false, '60秒内只能发送一次验证码!');

        if (!preg_match("/^13[0-9]{1}[0-9]{8}$|15[0189]{1}[0-9]{8}$|18[0-9]{9}$/", $mobile))
            $this->returnJson(true, '手机号码格式不正确！');

        $UcenterMemberModel = D('UcenterMember');
        $ucenterMemeber_obj = $UcenterMemberModel->getByMobile($mobile);
        switch ($type) {
            case 'edit_mobile_one'://修改手机第一步 判断是否有这个手机号码用户
                #判断手机号码是否存在
                if (!$ucenterMemeber_obj['id'])
                    $this->returnJson(false, '手机号码不存在！');
                break;
            case 'edit_mobile_two'://修改手机第二步
                #判断手机号码是否存在
                if ($ucenterMemeber_obj['id'] > 0)
                    $this->returnJson(false, '手机号码已被使用！');
                break;
            case 'reg'://注册
                #判断手机号码是否存在
                if ($ucenterMemeber_obj['id'] > 0)
                    $this->returnJson(false, '手机号码已被使用！');
                break;
            case 'forget_pwd'://找回密码
                if (!$ucenterMemeber_obj[id])
                    $this->returnJson(false, '该手机号码未注册！');
                break;
            case 'reservation'://产品预定
                
                break;
        }

        $result = Net\Sms::sendVerify($mobile, $type);

        if (!$result) {
            $data['msg'] = Net\Sms::getError();
            $data['status'] = false;
        } else {
            $_SESSION['send_verify_time'] = time();
            $data['status'] = true;
        }
        $this->ajaxReturn($data);
    }

    /* 登录页面
    * mobile 手机号码
    * password 密码
    *ur l: dev.hileyou.com/index.php?s=/home/user/login
    */
    public function login($mobile = '', $password = '')
    {
        if (IS_POST) { //登录验证
            /* 调用UC登录接口登录 */
            $user = new UserApi;
            $uid = $user->login($mobile, $password);
            if (0 < $uid) { //UC登录成功
                /* 登录用户 */
                $MemberModel = D('Member');
                if ($MemberModel->login($uid)) { //登录用户
                    //TODO:跳转到登录前页面
                    $this->ajaxReturn(array('msg' => '登陆成功！', 'status' => true));
                } else {
                    $msg = $MemberModel->getError();
                    $this->ajaxReturn(array('msg' => $msg, 'status' => false));
                }
            } else { //登录失败
                switch ($uid) {
                    case -1:
                        $msg = '用户不存在或被禁用！';
                        break; //系统级别禁用
                    case -2:
                        $msg = '密码错误！';
                        break;
                    default:
                        $msg = '未知错误！';
                        break; // 0-接口参数错误（调试阶段使用）
                }
                $this->ajaxReturn(array('msg' => $msg, 'status' => false));
            }

        }
    }

    /* 第一次登录设置密码 */
    public function firstpwd()
    {
        // 判断是否第一次登录
        if (session('mobile')) {
            $mobile = session('mobile');
            $map['mobile'] = $mobile;
            $result = M('ucenter_member')->field('last_login_time')->where($map)->find();
            if ($result) {
                $last_login_time = $result['last_login_time'];
                if ($last_login_time != '0') {
                    $this->error('非法操作');
                    exit;
                } else {
                    if (IS_POST) {
                        $user = new UserApi;
                        $result = $user->insertpwd(I('post.'));
                        if ($result['status'] == '0') {
                            $this->error($result['info']);
                        } else {
                            $this->login($mobile, I('post.password'));
                        }
                    }
                    $this->display();
                }
            }
        } else {
            $this->error('非法操作');
        }
    }

    /* 退出登录 */
    public function logout()
    {
        if (is_login()) {
            D('Member')->logout();
            $this->success('退出成功！', U('User/login'));
        } else {
            $this->redirect('User/login');
        }
    }

    /* 验证码，用于登录和注册 */
    public function verify()
    {
        $verify = new \COM\Verify();
        $verify->entry(1);
    }

    /**
     * 获取用户注册错误信息
     * @param  integer $code 错误编码
     * @return string        错误信息
     */
    private function showRegError($code = 0)
    {
        switch ($code) {
            case -1:
                $error = '用户名长度必须在16个字符以内！';
                break;
            case -2:
                $error = '用户名被禁止注册！';
                break;
            case -3:
                $error = '用户名被占用！';
                break;
            case -4:
                $error = '密码长度必须在6-30个字符之间！';
                break;
            case -5:
                $error = '邮箱格式不正确！';
                break;
            case -6:
                $error = '邮箱长度必须在1-32个字符之间！';
                break;
            case -7:
                $error = '邮箱被禁止注册！';
                break;
            case -8:
                $error = '邮箱被占用！';
                break;
            case -9:
                $error = '手机格式不正确！';
                break;
            case -10:
                $error = '手机被禁止注册！';
                break;
            case -11:
                $error = '手机号被占用！';
                break;
            default:
                $error = '未知错误';
        }
        return $error;
    }


    /**
     * 修改密码提交
     * @author huajie <banhuajie@163.com>
     */
    public function profile()
    {
        if (IS_POST) {
            //获取参数
            $uid = is_login();
            $password = I('post.old');
            $repassword = I('post.repassword');
            $data['password'] = I('post.password');
            empty($password) && $this->error('请输入原密码');
            empty($data['password']) && $this->error('请输入新密码');
            empty($repassword) && $this->error('请输入确认密码');

            if ($data['password'] !== $repassword) {
                $this->error('您输入的新密码与确认密码不一致');
            }

            $Api = new UserApi();
            $res = $Api->updateInfo($uid, $password, $data);
            if ($res['status']) {
                $this->success('修改密码成功！');
            } else {
                $this->error($res['info']);
            }
        } else {
            $this->display();
        }
    }
    /**
     * 修改账户信息
     * face_max_url  用户头
     * realname   真实姓名
     * IDcard 身份证号码
     * sex  性别(0:保密；1:男；2:女)
     * birthday   生日
     * 
     * @author huajie <banhuajie@163.com>
     */
    public function upmember(){
        //is_login() || $this->returnJson(false, '您还没有登录，请先登录！');
        $uid = 1;
        $model = M('member');
        if(IS_POST){
            
            
            $data = I('post.');
            $data['uid'] = $uid;
            $data['update_time'] = time();
            $result = $model->save($data);
            //echo $model->getLastSql();
            if($result){
                if($data['email'] != ""){
                    $data['id'] = $uid;
                    M('ucenter_member')->save($data);
                }
                $this->ajaxReturn(array('msg' => '信息修改成功', 'status' => true));
            }else{
                $this->ajaxReturn(array('msg' => '信息修改失败','status' => false));
            }
            exit;
        }else{
            $info = $model->field('a.face_max_url,a.realname,a.IDcard,a.sex,a.birthday,b.email')->alias('a')->join(C('DB_PREFIX').'ucenter_member b on a.uid = b.id')->where('a.uid = '.$uid)->find();
            $data['info'] = $info;
            $this->ajaxReturn(array('msg' =>$data ,'status' => true));
        }
    }
    
    // 上传用户头像接口
    Public function imgUplode() {
        $upload = new Util\ImgUpload();
        $upload->annexFolder = "./Uploads/user";//$annexFolder;   //附件存放路径
        $upload->smallFolder =  "./Uploads/user/smallimg";//$smallFolder;   //缩略图存放路径
        $upload->markFolder = "./Uploads/user/mark";//$markFolder;     //水印图片存放处
        
        
        $upload->upFileType = C('IMG_PIC_SUFFIX');
        $upload->upFileMax = C('IMG_MAX_SIZE') * 1024 * 1024;
        $upload->maxWidth = C('MAX_WIDTH');//$maxWidth;         //图片最大宽度 
        $upload->maxHeight = C('MAX_HEIGHT'); //$maxHeight;       //图片最大高度

        $result = $upload->upLoad("img");
        if($result['status'] == '1'){
            if(C('SWITCHIMG_SWITCH') == 1){
                
                $newSmallImg = $upload->smallImg($result['info'],C('MAX_WIDTH'),C('MAX_HEIGHT'));
            }
            if(C('SWITCH_MARK') == '1'){
                $upload->maxWidth = C('WATERMARK_WIDTH');
                $upload->maxHeight = C('WATERMARK_HEIGHT');//设置生成水印图像值 
                $text = array(C('WATERMARK'));
                $upload->toFile = true; 
                $newMark = $upload->waterMark($result['info'],$text);
            }
            $model = M('img');
            $data['url'] = $result['info'];
            $data['insert_time'] = time();
            $img_id = $model->add($data);
            $result['img_id'] = $img_id;
            $this->ajaxReturn(array('msg' => $result, 'status' => true));
        }else{
            $this->ajaxReturn(array('msg' => '图片上传失败', 'status' => false));
        }
        
    
    }

}
