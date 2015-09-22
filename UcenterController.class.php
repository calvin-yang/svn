<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Home\Controller;
use ORG\Net;
use Common\Model\Api\SendEailApi;

class UcenterController extends HomeController
{
	
	protected function _initialize(){
		/* 读取站点配置 */
		$config = api('Config/lists');
		C($config); //添加配置
        /* 判断是否登录 */
     //is_login() || $this->ajaxReturn(array('msg' => '您还没有登录，请先登录！','status' => false));
    }
    
    /**
     * 用户中心--首页
     */
	public function UserCenter(){
		if(!IS_POST){
			$this->error('操作非法');
		}
		$uid=is_login();
		if(!$uid){
			$this->ajaxReturn(array('msg'=>'请重新登陆','status'=>false));
		}
		$model=M('UcenterMember');
		$sql="SELECT a.mobile,a.last_login_time,d.id as vip,COUNT(DISTINCT(b.id)) AS comment,COUNT(DISTINCT(c.id)) AS upvote FROM tv_ucenter_member a LEFT JOIN tv_comment b ON a.id=b.uid LEFT JOIN tv_upvote c ON a.id=c.uid left join tv_member_vip d on a.id=d.uid where a.id={$uid} limit 1";
		$result=$model->query($sql);
		if(!$result){
			$this->ajaxReturn(array('msg'=>'您访问的页面超时了','status'=>false));
		}
		$result['0']['mobile']=preg_replace("/(1\d{1,2})\d\d(\d{0,3})/","\$1*****$3",$result['0']['mobile']);
		$result['0']['last_login_time']=date('Y年m月d日 H:i',$result['0']['last_login_time']);
		foreach($result as $val){
			foreach ($val as $key=>$value){
				$data[$key]=$value;
			}
		}
		$this->ajaxReturn(array('data'=>$data,'status'=>true));
	}
	
	/**
	 * 用户中心--我点的赞
	 */
	public function ZambiaList(){
		if(!IS_POST){
			$this->error('操作非法');
		}
		$uid=is_login();
		$type=I('request.type');
		if(!$uid){
			$this->ajaxReturn(array('msg'=>'请重新登陆','status'=>false));
		}
		if(!is_numeric($type)){
			$this->error('参数非法');
		}
		/************获取数据start**********/
		$arr=array();
		if($type=='1' || $type=='5'){
			if(S('hel')){
				$hel=S('hel');
			}else{
				//已赞房源数据
				$hel=M('hel')->alias('a')->field('a.praise_number,a.id,a.look_number,a.hel_name as name,a.citys,a.address,a.logo_img_url,c.face_max_url')->join('tv_upvote b on a.id=b.pro_id and b.pro_type=1')->join('tv_member c on a.uid=c.uid')->where('b.uid='.$uid)->select();
				getReg($hel,array('citys'));
				foreach($hel as $key=>$val){
					$hel[$key]['destination']=$val['citys'].' | '.$val['address'];
					unset($hel[$key]['citys']);
					unset($hel[$key]['address']);
				}
				S('hel',$hel,60);
			}
			$arr=array_merge($arr,$hel);
		}
		if($type=='2' || $type=='5'){
			if(S('restaurant')){
				$restaurant=S('restaurant');
			}else{
				//已赞饮食数据
				$restaurant=M('restaurant')->alias('a')->field('a.praise_number,a.id,a.look_number,a.foot_name as name,a.type,a.address,a.logo_img_url,c.face_max_url')->join('tv_upvote b on a.id=b.pro_id and b.pro_type=2')->join('tv_member c on a.uid=c.uid')->where('b.uid='.$uid)->select();
				foreach($restaurant as $key=>$val){
					$restaurant[$key]['destination']=$val['type'].' | '.$val['address'];
					unset($restaurant[$key]['type']);
					unset($restaurant[$key]['address']);
				}
				S('restaurant',$restaurant,60);
			}
			$arr=array_merge($arr,$restaurant);
		}
		if($type=='3' || $type=='5'){
			if(S('play')){
				$play=S('play');
			}else{
				//已赞游玩数据
				$play=M('play')->alias('a')->field('a.praise_number,a.id,a.look_number,a.title as name,a.type,a.name as names,a.logo_img_url,c.face_max_url')->join('tv_upvote b on a.id=b.pro_id and b.pro_type=3')->join('tv_member c on a.uid=c.uid')->where('b.uid='.$uid)->select();
				foreach($play as $key=>$val){
					$play[$key]['destination']=$val['type'].' | '.$val['names'];
					unset($play[$key]['type']);
					unset($play[$key]['names']);
				}
				S('play',$play,60);
			}
			$arr=array_merge($arr,$play);
		}
		if($type=='4' || $type=='5'){
			if(S('route')){
				$route=S('route');
			}else{
				//已赞路线数据
				$route=M('route')->alias('a')->field('a.praise_number,a.id,a.comment_number as look_number,a.title as name,a.destination,a.logo_img_url,c.face_max_url')->join('tv_upvote b on a.id=b.pro_id and b.pro_type=4')->join('tv_member c on a.uid=c.uid')->where('b.uid='.$uid)->select();
				S('route',$route,60);
			}
			$arr=array_merge($arr,$route);
		}
		/************获取数据end************/
		getImg($arr,array('logo_img_url','face_max_url'));
		/************分页 start************/
		$total=count($arr);										//总条数
		$page=I('request.page')?I('request.page'):1;			//当前页
		$epage=I('request.epage')?I('request.epage'):3;			//一页加载几条
		$start=($page-1)*$epage;								//开始位置
		$end=$start+$epage;										//结束位置
		$totalPage=ceil($total/$epage);							//总页数
		if($page>$totalPage){
			$this->ajaxReturn(array('msg'=>'暂无数据','status'=>false));
		}
		$j=0;
		for($i=$start;$i<$end;$i++){
			if($i>=$total){
				break;
			}
			$data[$j]=$arr[$start+$j];
			$j++;
		}
		/************分页 end   ************/
		$this->ajaxReturn(array('data'=>$data,'status'=>true));
	}
	
	
	/**
	 * 用户中心--我的评论
	 */
	public function CommentList(){
		if(!IS_POST){
			$this->error('操作非法');
		}
		$uid=is_login();
		$type=I('request.type');
		if(!$uid){
			$this->ajaxReturn(array('msg'=>'请重新登陆','status'=>false));
		}
		if(!is_numeric($type)){
			$this->error('参数非法');
		}
		/************获取数据start**********/
		$arr=array();
		if($type=='1' || $type=='5'){
			if(S('helcom')){
				$hel=S('helcom');
			}else{
				//房源评论数据
				$hel=M('hel')->alias('a')->field('a.star,a.hel_name as name,b.insert_time as time,b.content,c.face_max_url')->join('tv_comment b on a.id=b.pro_id and b.pro_type=1')->join('tv_member c on a.uid=c.uid')->where('b.uid='.$uid)->select();
				foreach ($hel as $key=>$val){
					$hel[$key]['time']=date('y-m-d H:i',$val['time']);
				}
				S('helcom',$hel,60);
			}
			$arr=array_merge($arr,$hel);
		}
		if($type=='2' || $type=='5'){
			if(S('restaurantcom')){
				$restaurant=S('restaurantcom');
			}else{
				//饮食评论数据
				$restaurant=M('restaurant')->alias('a')->field('a.star,a.foot_name as name,b.insert_time as time,b.content,c.face_max_url')->join('tv_comment b on a.id=b.pro_id and b.pro_type=2')->join('tv_member c on a.uid=c.uid')->where('b.uid='.$uid)->select();
				foreach ($restaurant as $key=>$val){
					$restaurant[$key]['time']=date('y-m-d H:i',$val['time']);
				}
				S('restaurantcom',$restaurant,60);
			}
			$arr=array_merge($arr,$restaurant);
		}
		if($type=='3' || $type=='5'){
			if(S('playcom')){
				$play=S('playcom');
			}else{
				//游玩评论数据
				$play=M('play')->alias('a')->field('a.star,a.name,b.insert_time as time,b.content,c.face_max_url')->join('tv_comment b on a.id=b.pro_id and b.pro_type=3')->join('tv_member c on a.uid=c.uid')->where('b.uid='.$uid)->select();
				foreach ($play as $key=>$val){
					$play[$key]['time']=date('y-m-d H:i',$val['time']);
				}
				S('playcom',$play,60);
			}
			$arr=array_merge($arr,$play);
		}
		if($type=='4' || $type=='5'){
			if(S('routecom')){
				$route=S('routecom');
			}else{
				//路线评论数据
				$route=M('route')->alias('a')->field('a.star,a.title as name,b.insert_time as time,b.content,c.face_max_url')->join('tv_comment b on a.id=b.pro_id and b.pro_type=4')->join('tv_member c on a.uid=c.uid')->where('b.uid='.$uid)->select();
				foreach ($route as $key=>$val){
					$route[$key]['time']=date('y-m-d H:i',$val['time']);
				}
				S('routecom',$route,60);
			}
			$arr=array_merge($arr,$route);
		}
		/************获取数据end************/
		getImg($arr,array('face_max_url'));
		/************分页 start************/
		$total=count($arr);										//总条数
		$page=I('request.page')?I('request.page'):1;			//当前页
		$epage=I('request.epage')?I('request.epage'):3;			//一页加载几条
		$start=($page-1)*$epage;								//开始位置
		$end=$start+$epage;										//结束位置
		$totalPage=ceil($total/$epage);							//总页数
		if($page>$totalPage){
			$this->ajaxReturn(array('msg'=>'暂无数据','status'=>false));
		}
		$j=0;
		for($i=$start;$i<$end;$i++){
			if($i>=$total){
				break;
			}
			$data[$j]=$arr[$start+$j];
			$j++;
		}
		/************分页 end   ************/
		$this->ajaxReturn(array('data'=>$data,'status'=>true));
	}
	
	/**
	 * 用户中心---私信列表
	 */
	public function letter(){
		if(!IS_POST){
			$this->error('非法操作');
		}
		$model=M('Message');
		$uid=is_login();
		//先排序，后分组
		$sql="select a.read_flag,a.sender_id,b.id,c.mobile,a.send_time,b.message_text from tv_message a inner join tv_message_text b on a.message_text_id=b.id inner join tv_ucenter_member c on a.sender_id=c.id where a.receiver_id={$uid} and b.type=1 order by a.send_time desc";
		//分组每个用户发的消息
		$query="select t.sender_id,t.read_flag,t.mobile,t.send_time,t.message_text,count(t.id) as number from ($sql) as t where 1=1 group by t.sender_id";
		//私信列表数据
		$result=$model->query($query);
		//系统未读消息
		$list=$model->field('count(a.id)')->alias('a')->join('tv_message_text b on a.message_text_id=b.id')->where('a.read_flag=1 and b.type=2 and a.receiver_id='.$uid)->count();
		if(!$result){
			$this->ajaxReturn(array('status'=>false,'count'=>$list));
		}
		foreach($result as $key=>$val){
			$result[$key]['mobile']=preg_replace("/(1\d{1,2})\d\d(\d{0,3})/","\$1*****$3",$val['mobile']);
			$result[$key]['time']=date('H:i',$val['send_time']);
		}
		$this->ajaxReturn(array('data'=>$result,'count'=>$list,'status'=>true));
	}
	
	/**
	 * 用户中心---系统消息列表
	 */
	public function sysem(){
		if(!IS_POST){
			$this->error('非法操作');
		}
		$model=M('Message');
		$uid=is_login();
		//系统消息列表
		$result=$model->field('a.id,a.read_flag,a.send_time,b.message_text')->alias('a')->join('tv_message_text b on a.message_text_id=b.id')->where('b.type=2 and a.receiver_id='.$uid)->select();
		//私信未读消息
		$list=$model->field('count(a.id)')->alias('a')->join('tv_message_text b on a.message_text_id=b.id')->where('a.read_flag=1 and b.type=1 and a.receiver_id='.$uid)->count();
		if(!$result){
			$this->ajaxReturn(array('status'=>false,'count'=>$list));
		}
		foreach($result as $key=>$val){
			$result[$key]['time']=date('m月d日',$val['send_time']);
		}
		$this->ajaxReturn(array('data'=>$result,'count'=>$list,'status'=>true));
	}
	/**
	 * 现金券-----calvin
	 * @return [type] [description]
	 */
	public function voucher(){
		$uid = UID;
		$status = I('get.status');//(状态(1:未使用,2:已使用,3:已过期))
		$map['a.uid'] = array('eq',$uid);
		switch ($status) {
			case '1':
				$model = M('voucher');
				$map['b.status'] = array('eq',1);
				$map['b.effective_time'] = array('egt',time());
				$list = $model->field('a.number,a.pro_id,b.foot_name name,b.voucher,FROM_UNIXTIME(b.effective_time, "%Y年%m月%d") effective_time')->alias('a')->join(C('DB_PREFIX').'restaurant b on a.pro_id = b.id')->where($map)->select();
				break;
			case '2':
				$model = M('pro_order');
				$map['c.status'] = array('eq',1);
				$map['a.voucher_number'] = array('neq',0);
				$list = $model->field('a.voucher_number number,b.voucher,FROM_UNIXTIME(b.effective_time, "%Y年%m月%d") effective_time,b.foot_name name')->alias('a')->join(C('DB_PREFIX').'restaurant b on a.pro_id = b.id')->join(C('DB_PREFIX').'order c on a.id = c.pro_order_id')->where($map)->select();

				break;
			case '3':
				$model = M('voucher');
				$map['b.status'] = array('eq',1);
				$map['b.effective_time'] = array('lt',time());
				$list = $model->field('a.number,a.pro_id,b.foot_name name,b.voucher,FROM_UNIXTIME(b.effective_time, "%Y年%m月%d") effective_time')->alias('a')->join(C('DB_PREFIX').'restaurant b on a.pro_id = b.id')->where($map)->select();
				break;
		}
		if(!$list)
			$this->ajaxReturn(array('msg' => '暂无数据','status' => false));
		$data['list'] = $list;
		$this->ajaxReturn(array('msg' => '200 ok','data' => $data,'status' => true));
	}
	/**
	 * 服务商申请
	 * @return [type] [description]
	 */
	public function facilitator(){
		$uid = UID;
		if(IS_POST){
			/**
			 * type 服务商类型
			 * realname    服务商姓名
			 * IDcard    省份证号
			 * business_licence    营业执照号码
			 * IDcard_url   省份证url(正面,反面)
			 * business_licence_url    营业执照图片url
			 * tel  公司电话
			 * operations_type  公司运营类型
			 * address   运营所在地址
			 * 
			 * @var [type]
			 */
			$data = I('post.');
			$reg = "/(^\d{15}$)|(^\d{18}$)|(^\d{17}(\d|X|x)$)/";
			$isMob="/^1[3-5,8]{1}[0-9]{9}$/";
 			$isTel="/^([0-9]{3,4}-)?[0-9]{7,8}$/";
			if(!I('post.IDcard') || !preg_match($reg, I('post.IDcard')))
				$this->ajaxReturn(array('msg' => '身份证未填或格式不正确','status' => false));
			if(!I('post.tel') || !preg_match($isTel,I('post.tel')))
				$this->ajaxReturn(array('msg' => '电话号码未填或格式不正确','status' => false));
			if(!I('post.address') || !I('post.operations_type'))
				$this->ajaxReturn(array('msg' => '请填写运营所在地址或公司运营类型','status' => false));
			if(!I('post.id')){
				$data['insert_time'] = time();
				$data['status'] = 3;
				$result = M('facilitator_data')->add($data);
			}else{
				$result = M('facilitator_data')->save($data);
			}
			if($result)
				$this->ajaxReturn(array('msg' => '申请已被提交，请耐心等候审核...','status' => true));
			else
				$this->ajaxReturn(array('msg' => '申请未被提交，请重新申请...','status' => false));
			

		}
		#当前登录用户的真实姓名、身份证号
		$user_info = M('member')->field('realname,IDcard')->where('uid = '.$uid)->find();

		$this->ajaxReturn(array('msg' => '用户姓名、身份证号','status' => true));


	}
	/**
	 * 我的订单
	 * @return [type] [description]
	 */
	public function myorder(){
		if(I('request.type')){
			switch (I('request.type')) {
				case '1':
					$map['b.title'] = array('eq','住宿');
					break;
				case '2':
					$map['b.title'] = array('eq','美食');
					break;
				case '3':
					$map['b.title'] = array('eq','景点');
					break;
				case '4':
					$map['b.title'] = array('eq','路线');
					break;
				case '5':
					$map['b.title'] = array('eq','现金券');
					break;
				
			}
		}
		$map['b.status'] = array('not in','2,3,8,9');
		if(I('request.order_type') == 'refund')
			$map['b.status'] = array('in','5,6');

		if(I('request.status'))
			$map['b.status'] = array('eq',I('request.status'));
			

		
		$model = M('pro_order');
		$uid = 44;//is_login();
		
		$map['a.uid'] = array('eq',$uid);
		
		$list = $model->alias('a')->field('a.id,a.pro_type,a.unit_price,a.pro_img,a.pro_name,a.number,a.discount_price,a.total_price,b.number order_number,b.title,b.status')->join(C('DB_PREFIX').'order b on a.id = b.pro_order_id')->where($map)->select();
		$data['list'] = array();
		if(!$list)
			$this->ajaxReturn(array('data' => $data,'status' => true));
		order_int_to_string($list);
		getImg($list,array('pro_img'));
			
		#制作分页
		if(I('request.page') != ""){
            $page = I('request.page'); //第几页
        }else{
            $page = '1'; //第几页
        }
        $page_size = '6'; //每页五条数据
        $page_count = ceil(count($list)/$page_size);//几页
        $page_number = ($page-1)*$page_size;
        if($page_count < $page){
            $this->ajaxReturn(array('msg'=>'暂无数据','status' => false));
            exit;
        }
        $j = 1;
        $lists = array();
        for($i = $page_number; $i<count($list); $i++){
            if($j > 6){
                #跳出循环
                break;
            }
            #尾部添加数组
            array_push($lists,$list[$i]);
            $j++;
        }
        $list = $lists;
		$data['list'] = $list;
		$this->ajaxReturn(array('msg' => '200 ok','data' => $data,'status' => true));


	}
	/**
	 * 退费订单
	 * @return [type] [description]
	 */
	public function order_refund(){
		$model = M('pro_order');
		$uid = 44;//UID;
		$map['b.status'] = array('in','5,6');
		$map['a.uid'] = array('eq',$uid);
		#检测缓存
		if(S('order_refund_list')){
			$list = S('order_list');
		}else{
			$list = $model->alias('a')->field('a.id,a.unit_price,a.pro_img,a.pro_name,a.number,a.discount_price,a.total_price,b.number order_number,b.title,b.status')->join(C('DB_PREFIX').'order b on a.id = b.pro_order_id')->where($map)->select();

			if(!$list)
				$this->ajaxReturn(array('msg' => '暂无订单数据','status' => false));
			order_int_to_string($list);
			getImg($list,array('pro_img'));
			S('order_refund_list',$list,30);
		}
		#制作分页
		if(I('request.page') != ""){
            $page = I('request.page'); //第几页
        }else{
            $page = '1'; //第几页
        }
        $page_size = '6'; //每页五条数据
        $page_count = ceil(count($list)/$page_size);//几页
        $page_number = ($page-1)*$page_size;
        if($page_count < $page){
            $this->ajaxReturn(array('msg'=>'暂无数据','status' => false));
            exit;
        }
        $j = 1;
        $lists = array();
        for($i = $page_number; $i<=count($list); $i++){
            if($j > 6){
                #跳出循环
                break;
            }
            #尾部添加数组
            array_push($lists,$list[$i]);
            $j++;
        }
        $list = $lists;
		$data['list'] = $list;
		$this->ajaxReturn(array('msg' => '200 ok','data' => $data,'status' => true));
	}

	/**
	 * 订单详情
	 * @return [type] [description]
	 */
	public function orderdetailes(){
		if(!I('request.protype'))
			$this->ajaxReturn(array('msg' => '非法操作','status' => false));
		$model = M('pro_order');
		$uid = 44;//UID;
		$id = I('request.order_id');
		$pro_type = I('request.protype');
		$map['a.id'] = array('eq',$id);
		$map['b.status'] = array('not in','2,3');
		//$map['a.uid'] = array('eq',$uid);
		switch ($pro_type) {
			#房源
			case '1':
				$info = $model->field('a.id,a.pro_id,a.pro_name,a.pro_img,a.unit_price,a.number,a.adult,a.total_price,a.discount_price,a.discount_way,FROM_UNIXTIME(a.start_time, "%Y-%m-%d") start_time,FROM_UNIXTIME(a.end_time, "%Y-%m-%d") end_time,FROM_UNIXTIME(a.insert_time, "%Y-%m-%d") insert_time,a.pro_type,b.number order_number,b.title,b.pay_type,b.status,c.hel_tel tel')->alias('a');
				$info = $info->join(C('DB_PREFIX').'order b on a.id = b.pro_order_id');
				$info = $info->join(C('DB_PREFIX').'hel c on a.pro_id = c.id');
				$info = $info->where($map)->find();

				break;	
			#餐饮
			case '2':
				$info = $model->field('a.id,a.pro_id,a.voucher_number,a.pro_name,a.pro_img,a.unit_price,a.number,a.total_price,a.discount_price,a.discount_way,FROM_UNIXTIME(a.start_time, "%Y-%m-%d") start_time,FROM_UNIXTIME(a.insert_time, "%Y-%m-%d") insert_time,a.pro_type,b.number order_number,b.title,b.pay_type,b.status,c.foot_tel tel,c.provinces,c.citys,c.countys,c.address')->alias('a');
				$info = $info->join(C('DB_PREFIX').'order b on a.id = b.pro_order_id');
				$info = $info->join(C('DB_PREFIX').'restaurant c on a.pro_id = c.id');
				$info = $info->where($map)->find();
				break;
			#景点
			case '3':
				$info = $model->field('a.id,a.pro_id,a.pro_name,a.pro_img,a.unit_price,a.number,a.total_price,a.discount_price,a.discount_way,a.pro_type,FROM_UNIXTIME(a.start_time, "%Y-%m-%d") start_time,FROM_UNIXTIME(a.insert_time, "%Y-%m-%d") insert_time,b.number order_number,b.title,b.pay_type,b.status,c.tel,c.provinces,c.citys,c.countys,c.address')->alias('a');
				$info = $info->join(C('DB_PREFIX').'order b on a.id = b.pro_order_id');
				$info = $info->join(C('DB_PREFIX').'play c on a.pro_id = c.id');
				$info = $info->where($map)->find();
				break;
			#景点
			case '4':
				$info = $model->field('a.id,a.pro_id,a.pro_name,a.pro_img,a.unit_price,a.number,a.total_price,a.discount_price,a.discount_way,FROM_UNIXTIME(a.start_time, "%Y-%m-%d") start_time,FROM_UNIXTIME(a.insert_time, "%Y-%m-%d") insert_time,a.pro_type,b.number order_number,b.title,b.pay_type,b.status,c.tel,c.provinces,c.citys,c.countys,c.starting_city')->alias('a');
				$info = $info->join(C('DB_PREFIX').'order b on a.id = b.pro_order_id');
				$info = $info->join(C('DB_PREFIX').'route c on a.pro_id = c.id');
				$info = $info->where($map)->find();
				break;

		}
		if(!$info)
			$this->ajaxReturn(array('msg' => '非法操作','status' => false));
		$list['0'] = $info;
		order_int_to_string($list);
		$info = $list['0'];
		#自动省略浮点数后面无意义的零
		$info['total_price'] = sprintf("%s",$info['total_price']*1);
		$info['discount_price'] = sprintf("%s",$info['discount_price']*1);
		$info['unit_price'] = sprintf("%s",$info['unit_price']*1);
		#计算住宿日期的星期
		$weekarray=array("星期日","星期一","星期二","星期三","星期四","星期五","星期六");
		$start_Week =  date('w',strtotime($info['start_time']));
		$end_Week =  date('w',strtotime($info['end_time']));
		$info['start_Week'] = $weekarray[$start_Week];
		$info['end_Week'] = $weekarray[$end_Week];
		#积分折扣价钱
		$info['sore_price'] = ($info['total_price'] - $info['discount_price']);
		getImg($info,array('pro_img'));
		$data['info'] = $info;
		$this->ajaxReturn(array('msg' => '200 ok','data' => $data,'status' => true));
	}
	/**
	 * [order_changeStatus 用户订单点击确认已交易]
	 * @return [type] [description]
	 */
	public function order_changeStatus(){
		if(!IS_POST || !I('post.order_id'))
			$this->ajaxReturn(array('msg' => '非法操作','status' => false));
		$uid = 44;//is_login();
		$model = M('pro_order');
		$map['a.uid'] = array('eq',$uid);
		$map['b.status'] = array('eq',7);
		$orderInfo = $model->alias('a')->field('a.pro_id,a.pro_type,a.discount_price,a.voucher_number,a.voucher_price,b.id,b.status')->join(C('DB_PREFIX').'order b on a.id = b.pro_order_id')->where($map)->find();
		if(!$orderInfo)
			$this->ajaxReturn(array('msg' => '非法操作','status' => false));
		#获取商家id
		switch ($orderInfo['pro_type']) {
			case '1':
				$fa_uid = M('hel')->where('id = '.$orderInfo['pro_id'])->find('uid');
				break;
			case '2':
				$fa_uid = M('restaurant')->where('id = '.$orderInfo['pro_id'])->find('uid');
				break;
			case '3':
				$fa_uid = M('play')->where('id = '.$orderInfo['pro_id'])->find('uid');
				break;
			case '4':
				$fa_uid = M('route')->where('id = '.$orderInfo['pro_id'])->find('uid');
				break;
			case '5':
				$fa_uid = M('restaurant')->where('id = '.$orderInfo['pro_id'])->find('uid');
				break;

		}
		#检测该订单是否有使用优惠券
		if(empty($order_info['voucher_number'])){
            $price = $order_info['discount_price'];
        }else{
            $price = $order_info['discount_price'] + $order_info['voucher_price'] * $order_info['voucher_number'];
        }
        $f_price = M('financial')->where('uid = '.$fa_uid)->getField('price');
        #检测该商家用户是否含有资金
        if($f_price){
            $datas['price'] = $f_price + $price;
            $datas['last_time'] = time();
            $datas['last_price'] = $price;
            $fl_result = M('financial')->where('uid = '.$fa_uid)->save($datas);
        }else{
            $datas['price'] = $price;
            $datas['uid'] = $fa_uid;
            $datas['last_time'] = time();
            $datas['last_price'] = $price;
            $fl_result = M('financial')->add($datas);
        }
        if(!$fl_result)
        	$this->ajaxReturn(array('msg' => '结账失败','status' => false));
        $result = M('order')->where('id = '.$orderInfo['id'])->save(array('status' => '4'));
        
        if($result)
        	$this->ajaxReturn(array('msg' => '结账成功','status' => true));
        else
        	$this->ajaxReturn(array('msg' => '非法操作','status' => false));

	}
	/**
	 * [order_refund_apply 退费申请]
	 * @return [type] [description]
	 */
	public function order_refund_apply(){
		if(IS_POST){
			$model = M('order_refund');
			$uid = 44;//UID;
			if(!I('post.order_id'))
				$this->ajaxReturn(array('msg' => '非法操作','status' => false));
			if(!I('post.reason'))
				$this->ajaxReturn(array('msg' => '请填写退款理由','status' => false));
			$map['a.id'] = array('eq',I('post.order_id'));
			$map['b.status'] = array('eq',1);
			$map['a.uid'] = array('eq',$uid);
			$order_id = M('pro_order')->alias('a')->join(C('DB_PREFIX').'order b on a.id = b.pro_order_id')->where($map)->getField('b.id');
			if(!$order_id)
				$this->ajaxReturn(array('msg' => '非法操作','status' => false));
			$data['insert_time'] = time();
            $data['reason'] = I('post.reason');
            $data['order_id'] = $order_id;
            $data['uid'] = $uid;
            $res = $model->field('id')->where('uid = '.$data['uid'].' and order_id = '.$data['order_id'])->find();
            if($res)
            	$this->ajaxReturn(array('msg' => '您的退款申请已被提交，请耐心等候','status' => false));
            $result = $model->add($data);
            $result = M('order')->where('id = '.$order_id)->save(array('status'=>5));
            if($result)
            	$this->ajaxReturn(array('msg' => '审核已提交,请耐心等候','status' => true));
            else
            	$this->ajaxReturn(array('msg' => '审核提交失败,请重新提交','status' => false));

		}
		if(!I('get.order_id'))
			$this->ajaxReturn(array('msg' => '非法操作','status' => false));
		$model = M('pro_order');
		$pro_order_id = I('request.order_id');
		$uid = 44;//UID;
		$map['b.pro_order_id'] = array('eq',$pro_order_id);
		$map['a.uid'] = array('eq',$uid);
		$map['b.status'] = array('eq',1);
		$info = $model->alias('a')->field('a.pro_name,a.pro_img,a.unit_price,a.number,a.discount_price,a.total_price,b.id,b.number order_number')->join(C('DB_PREFIX').'order b on a.id = b.pro_order_id')->where($map)->find();
		if(!$info)
			$this->ajaxReturn(array('msg' => '非法操作','status' => false));
		getImg($info,array('pro_img'));
		$data['info'] = $info;
		$this->ajaxReturn(array('msg' => '200 ok','data' => $data,'status' => true));

	}
	/**
	 * [order_fsrefund 撤销退款]
	 * @return [type] [description]
	 */
	public function order_fsrefund(){
		if(!IS_POST)
			$this->ajaxReturn(array('msg' => '非法操作','status' => false));
		$pro_order_id = I('post.order_id');
		$uid = 44;//UID;
		$map['a.id'] = array('eq',$pro_order_id);
		$map['a.uid'] = array('eq',$uid);
		$map['b.status'] = array('eq','5');
		$order_id = M('pro_order')->alias('a')->join(C('DB_PREFIX').'order b on a.id = b.pro_order_id')->where($map)->getField('b.id');

		if(!$order_id)
			$this->ajaxReturn(array('msg' => '非法操作','status' => false));
		$de_or_re = M('order_refund')->where('order_id = '.$order_id.' and uid = '.$uid)->delete();
		
		if($de_or_re)
			$result = M('order')->where('id = '.$order_id)->save(array('status'=>'1'));
		else
			$result = false;
		if($result)
			$this->ajaxReturn(array('msg' => '撤销成功','status' => true));
		else
			$this->ajaxReturn(array('msg' => '撤销失败','status' => false));

	}
	/**
	 * 订单评论
	 * pro_type    产品类型
	 * pro_id      产品id
	 * content     评论内容
	 * score       评论星级
	 * 
	 * @return [type] [description]
	 */
	public function ordercomment(){
		if(IS_POST){
			if(!I('post.pro_id') || !I('post.pro_type') || !I('post.order_id'))
				$this->ajaxReturn(array('msg' => '非法操作','status' => false));
			$data = I('post.');
			$uid = 44;//UID;
			$data['uid'] = $uid;
			$data['insert_time'] = time();
			$comInfo = M('comment')->where('order_id = '.I('post.order_id'))->find();
			if($comInfo)
				$this->ajaxReturn(array('msg' => '您已经评论过该订单了','status' => false));

			$result = M('comment')->add($data);
			if($result)
				$this->ajaxReturn(array('msg' => '评论成功','status' => true));
			else
				$this->ajaxReturn(array('msg' => '评论失败','status' => false));

		}
		if(!I('request.order_id'))
			$this->ajaxReturn(array('msg' => '非法操作','status' => false));
		$order_id = I('request.order_id');
		$uid = 44;//UID;
		$map['id'] = array('eq',$order_id);
		$map['uid'] = array('eq',$uid);
		$info = M('pro_order')->field('id order_id,pro_id,pro_type,pro_name,pro_img')->where($map)->find();
		
		if(!$info)
			$this->ajaxReturn(array('msg' => '非法操作','status' => false));
		getImg($info,array('pro_img'));
		$data['info'] = $info;
		$this->ajaxReturn(array('msg' => '200 ok','data' => $data,'status' => true));

	}
	/**
	 * 我的购物车
	 * @return [type] [description]
	 */
	public function mycart(){
		$model = M('cart');
		$pro_type = I('request.pro_type','');
		$uid = is_login();
		switch ($pro_type) {
			case '1':
				$list = $model->alias('a')->field('a.pro_id,a.pro_type,b.logo_img_url,b.title,b.day_price price,b.favorable_price')->join(C('DB_PREFIX').'hel b on a.pro_id = b.id')->where('a.uid = '.$uid.' and a.pro_type = 1 and b.status = 1')->select();
				echo $model->getLastSql();exit;
				break;
			case '2':
				$list = $model->alias('a')->field('a.pro_id,a.pro_type,b.logo_img_url,b.foot_name title,b.per_capita price,b.favorable_price')->join(C('DB_PREFIX').'restaurant b on a.pro_id = b.id')->where('a.uid = '.$uid.' and a.pro_type = 2 and b.status = 1')->select();
				
				break;
			case '3':
				$list = $model->alias('a')->field('a.pro_id,a.pro_type,b.logo_img_url,b.title,b.price,b.favorable_price')->join(C('DB_PREFIX').'play b on a.pro_id = b.id')->where('a.uid = '.$uid.' and a.pro_type = 3 and b.status = 1')->select();
				
				break;
			case '4':
				$list = $model->alias('a')->field('a.pro_id,a.pro_type,b.logo_img_url,b.title,b.price,b.favorable_price')->join(C('DB_PREFIX').'route b on a.pro_id = b.id')->where('a.uid = '.$uid.' and a.pro_type = 4 and b.status = 1')->select();
				
				break;
			
			default:
				$list = array();
            	#缓存
            	if(S('list')){
            		$list = S('cart_list');
            	}else{
            		#路线列表数据
	                $routelist = $model->alias('a')->field('a.pro_id,a.pro_type,b.logo_img_url,b.title,b.price,b.favorable_price')->join(C('DB_PREFIX').'route b on a.pro_id = b.id')->where('a.uid = '.$uid.' and a.pro_type = 4 and b.status = 1')->select();
	                
	                if($routelist)
	                	$list = array_merge($list,$routelist);
	                #游玩列表数据
	                $playlist = $model->alias('a')->field('a.pro_id,a.pro_type,b.logo_img_url,b.title,b.price,b.favorable_price')->join(C('DB_PREFIX').'play b on a.pro_id = b.id')->where('a.uid = '.$uid.' and a.pro_type = 3 and b.status = 1')->select();
	                if($playlist)
	                	$list = array_merge($list,$playlist);
	                #餐饮列表数据
	                $restaurantlist = $model->alias('a')->field('a.pro_id,a.pro_type,b.logo_img_url,b.foot_name title,b.per_capita price,b.favorable_price')->join(C('DB_PREFIX').'restaurant b on a.pro_id = b.id')->where('a.uid = '.$uid.' and a.pro_type = 2 and b.status = 1')->select();
	                if($restaurantlist)
	                	$list = array_merge($list,$restaurantlist);
	                #房源列表数据
	                $hellist = $model->alias('a')->field('a.pro_id,a.pro_type,b.logo_img_url,b.title,b.day_price price,b.favorable_price')->join(C('DB_PREFIX').'hel b on a.pro_id = b.id')->where('a.uid = '.$uid.' and a.pro_type = 1 and b.status = 1')->select();
	                if($hellist)
	                	$list = array_merge($list,$hellist);
	                S('cart_list',$list,10);
            	}
				break;
		}
		if(I('request.page') != ""){
        	$page = I('request.page'); //第几页
        }else{
        	$page = '1'; //第几页
        }
        $page_size = '5'; //每页五条数据
        $page_count = ceil(count($list)/$page_size);//几页
        $page_number = ($page-1)*$page_size;
        if($page_count < $page){
            $this->ajaxReturn(array('msg'=>'暂无数据','status' => false));
            exit;
        }
        $j = 1;
        $lists = array();
        for($i = $page_number; $i<count($list); $i++){
        	if($j > 5){
        		#跳出循环
        		break;
        	}
        	#尾部添加数组
        	array_push($lists,$list[$i]);
        	$j++;
        }
        $list = $lists;
        if (!$list) {
        	$this->ajaxReturn(array('msg' => '暂无数据', 'status' => false));
        	exit;
        }
        getImg($list,array('logo_img_url'));
        $data['list'] = $list;
        $this->ajaxReturn(array('msg' => $data, 'status' => true));
	}
	/**
	 * 购物车数量
	 * @return [type] [description]
	 */
	public function mycartnumber(){
		$model = M('cart');
		$uid = UID;
		$number = $model->where('uid = '.$uid)->count();
		$data['number'] = $number;
		$this->ajaxReturn(array('msg' => '购物车数量','data' => $data,'status' => true));

	}
	/**
	 * 美景贴图
	 * @return [type] [description]
	 */
	public function travel(){
		if(IS_POST){
			$model = M('news');
			if(!is_numeric(I('post.logo_img_url')))
				$this->ajaxReturn(array('msg' => '图片不合法','status' => false));
			if(!I('post.title'))
				$this->ajaxReturn(array('msg' => '请填写标题','status' => false));
			if(!I('post.content'))
				$this->ajaxReturn(array('msg' => '请填写内容','status' => false));
			$data = I('post.');
			$data['insert_time'] = time();
			$data['uid'] = UID;
			$data['publisher'] = get_username($data['uid']);
			$result = $model->add($data);
			if($result)
				$this->ajaxReturn(array('msg' => '发布成功','status' => true));
			else
				$this->ajaxReturn(array('msg' => '发布失败','status' => false));

		}
	}
	// 上传用户头像接口
    Public function imgUplode() {
        $upload = new Util\ImgUpload();
        $upload->annexFolder = "./Uploads/Facilitator";//$annexFolder;   //附件存放路径
        $upload->smallFolder =  "./Uploads/Facilitator/smallimg";//$smallFolder;   //缩略图存放路径
        $upload->markFolder = "./Uploads/Facilitator/mark";//$markFolder;     //水印图片存放处
        
        
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

	
	/**
	 * 用户中心---私信内容页
	 */
	public function letterCon(){
		if(!IS_POST){
			$this->error('非法操作');
		}
		$model=M('Message');
		$uid=is_login();
		$uid=49;//调试后删除
		$id=I('post.id');
		$id=41;//调试后删除
		$result=$model->field('a.sender_id,c.mobile,a.send_time,b.message_text')->alias('a')->join('tv_message_text b on a.message_text_id=b.id')->join('tv_ucenter_member c on a.sender_id=c.id')->where("(a.sender_id=$id and a.receiver_id=$uid) or (a.sender_id=$uid and a.receiver_id=$id)")->order('a.send_time asc')->select();
		foreach ($result as $key=>$val){
			$result[$key]['mobile']=preg_replace("/(1\d{1,2})\d\d(\d{0,3})/","\$1*****$3",$val['mobile']);
			$result[$key]['time']=date('m月d日 H:i',$val['send_time']);
		}
		$this->ajaxReturn(array('status'=>true,'data'=>$result));
	}
	
	/**
	 * 用户中心---系统内容页
	 */
	public function sysemCon(){
		if(!IS_POST){
			$this->error('非法操作');
		}
		$model=M('Message');
		$id=I('post.id');
		$id=8;//调试后删除
		$result=$model->field('a.send_time,b.message_text')->alias('a')->join('tv_message_text b on a.message_text_id=b.id')->where("a.id=".$id)->find();
		foreach ($result as $key=>$val){
			$result[$key]['time']=date('m月d日 H:i',$val['send_time']);
		}
		$this->ajaxReturn(array('status'=>true,'data'=>$result));
	}
	
	/**
	 * 用户中心---个人信息
	 */
	public function Users(){
		if(!IS_POST){
			$this->error('非法操作');
		}
		$uid=is_login();
		$uid=49;//调试后删除
		$result=M('Member')->field('a.face_max_url,a.nickname,a.realname,b.mobile')->alias('a')->join('tv_ucenter_member b on a.uid=b.id')->where('a.uid='.$uid)->find();
		if(!$result){
			$this->ajaxReturn(array('msg'=>'该网页正在维护中，请稍后再试！','status'=>false));
		}
		getImg($result,array('face_max_url'));
		$this->ajaxReturn(array('status'=>true,'data'=>$result));
	}
	
	/**
	 * 用户中心---修改身份证
	 */
	public function IdcardUp(){
		if(!IS_POST){
			$this->error('非法操作');
		}
		$uid=is_login();
		$uid=49;//调试后删除
		$data['IDcard']=I('post.IDcard');
		if(!$data['IDcard']){
			$this->error('身份证不能为空！');
		}
		if(!$uid){
			$this->ajaxReturn(array('msg'=>'该网页正在维护中，请稍后再试！','status'=>false));
		}
		$result=M('Member')->where('uid='.$uid)->save($data);
		if(!$result){
			$this->ajaxReturn(array('msg'=>'修改失败，请稍后再试！','status'=>false));
		}
		$this->ajaxReturn(array('msg'=>'修改成功','status'=>true));
	}
	
	/**
	 * 用户中心---修改昵称
	 */
	public function NicknameUp(){
		if(!IS_POST){
			$this->error('非法操作');
		}
		$uid=is_login();
		$uid=49;//调试后删除
		$data['nickname']=I('post.nickname');
		if(!$data['nickname']){
			$this->error('昵称不能为空！');
		}
		if(!$uid){
			$this->ajaxReturn(array('msg'=>'该网页正在维护中，请稍后再试！','status'=>false));
		}
		$result=M('Member')->where('uid='.$uid)->save($data);
		if(!$result){
			$this->ajaxReturn(array('msg'=>'修改失败，请稍后再试！','status'=>false));
		}
		$this->ajaxReturn(array('msg'=>'修改成功','status'=>true));
	}
	
	/**
	 * 用户中心---修改性别
	 */
	public function SexUp(){
		if(!IS_POST){
			$this->error('非法操作');
		}
		$uid=is_login();
		$uid=49;//调试后删除
		$data['sex']=I('post.sex');
		if(!$data['sex']){
			$this->error('性别不能为空！');
		}
		if(!$uid){
			$this->ajaxReturn(array('msg'=>'该网页正在维护中，请稍后再试！','status'=>false));
		}
		$result=M('Member')->where('uid='.$uid)->save($data);
		if(!$result){
			$this->ajaxReturn(array('msg'=>'修改失败，请稍后再试！','status'=>false));
		}
		$this->ajaxReturn(array('msg'=>'修改成功','status'=>true));
	}
	
	/**
	 * 用户中心---修改真实姓名
	 */
	public function RealnameUp(){
		if(!IS_POST){
			$this->error('非法操作');
		}
		$uid=is_login();
		$uid=49;//调试后删除
		$data['realname']=I('post.realname');
		if(!$data['realname']){
			$this->error('真实姓名不能为空！');
		}
		if(!$uid){
			$this->ajaxReturn(array('msg'=>'该网页正在维护中，请稍后再试！','status'=>false));
		}
		$result=M('Member')->where('uid='.$uid)->save($data);
		if(!$result){
			$this->ajaxReturn(array('msg'=>'修改失败，请稍后再试！','status'=>false));
		}
		$this->ajaxReturn(array('msg'=>'修改成功','status'=>true));
	}
	
	
	/**
	 * 用户中心---修改邮箱
	 */
	public function EmailUp(){
		if(!IS_POST){
			$this->error('非法操作');
		}
		$uid=is_login();
		$uid=49;//调试后删除
		$data['email']=I('request.email');
		$emailcode=I('request.code');
		if($data['email']!=session('emailstr')){
			$this->ajaxReturn(array('msg'=>'该邮箱与验证邮箱不匹配','status'=>false));
		}
		if($emailcode!=session('emailcode')){
			$this->ajaxReturn(array('msg'=>'验证码不正确','status'=>false));
		}
		$model=M('UcenterMember');
		$result=$model->where('id='.$uid)->save($data);
		if(!$result){
			$this->ajaxReturn(array('msg'=>'更新失败，请稍后再试！','status'=>false));
		}
		$this->ajaxReturn(array('msg'=>'验证成功','status'=>true));
	}
	
	/**
	 * 用户中心---系统发送邮件验证码
	 */
	public function EmailSend(){
		if(!IS_POST){
			$this->error('非法操作');
		}
		$email=I('request.email');
		$str=rand(1000,9999);
		$mail = new SendEailApi();
		if(!$mail->SendMail($email,'海乐游系统验证',$str)){
			$this->ajaxReturn(array('msg'=>'邮箱格式不正确！','status'=>false));
		}
		session('emailcode',$str);
		Session('emailstr',$email);
		$this->ajaxReturn(array('msg'=>'发送成功！','status'=>true));
	}
	
	/**
	 * 用户中心---申请vip数据提供
	 */
	public function VipList(){
		if(!IS_POST){
			$this->error('非法操作');
		}
		$result=M('Vip')->field('id,name,monthly_price as price')->where('status=1')->select();
		$this->ajaxReturn(array('data'=>$result,'status'=>true));
	}
	/**
	 * 用户中心---申请vip
	 */
	public function VipApply(){
		if(!IS_POST){
			$this->error('非法操作');
		}
		$uid=is_login();
		$uid=49;
		$id=I('post.id');
		$num=I('post.num');
		if(!is_numeric($id) || !is_numeric($num)){
			$this->error('参数非法');
		}
		$result=M('Vip')->field('name,monthly_price as price')->where('status=1 and id='.$id)->find();
		$data['pro_name']=$result['name'];					//产品名
		$data['pro_id']=$id;								//产品id
		$data['unit_price']=$result['price'];				//产品单价
		$data['number']=$num;								//数量
		$data['total_price']=$result['price'] * $num;		//总价
		$list=M('ProOrder')->add($data);
		if(!$list){
			$this->ajaxReturn(array('status'=>false,'msg'=>'申请失败'));
		}
		$this->ajaxReturn(array('status'=>true,'msg'=>'申请成功'));
	}
	
	
	
}