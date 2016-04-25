<?php
/**
 * Created by PhpStorm.
 * User: caipeichao
 * Date: 14-3-11
 * Time: PM5:41
 */

namespace Admin\Controller;

use Admin\Builder\AdminConfigBuilder;
use Admin\Builder\AdminListBuilder;
use Admin\Builder\AdminTreeListBuilder;


class AppraiseController extends AdminController
{
    //课程管理
    public function index($title='',$page=1,$r=15)
    {
        $lesson=M('AppraiseLesson');
        $lessonInfo=$lesson->where(array('title'=>array('like', '%' . $title . '%')))->page($page, $r)->select();
        $totalCount = $lesson->where(array('title'=>array('like', '%' . $title . '%')))->count();
        //显示页面
        $builder = new AdminListBuilder();
        $builder->title(L('课程管理'))
            ->button(L('全部'), array('href'=>U('index')))
            ->button(L('添加'),array( 'href'=>U('lessonAdd')))
            ->button(L('删除'), array('class' => 'btn btn-danger ajax-post tox-confirm',
                'data-confirm' => L('确定要删除吗？'),
                'url' => U('doDeleteLesson'),
                'target-form' => 'ids'))
            ->keyId()
            ->keyTitle('title',L('名称'))
            ->keyDoAction('Appraise/lessonDetail?id=###', '详情')
            ->search(L('名称'), 'title')
            ->pagination($totalCount, $r)
            ->data($lessonInfo)
            ->display();
    }
    //课程相关
    public function lessonDetail($id)
    {
        $title=M('AppraiseLesson')->where(array('id'=>$id))->getField('title');
        $session=M('AppraiseSession')->where(array('lessonId'=>$id))->select();
        $builder = new AdminListBuilder();
        $builder->title($title.L('课程详情'))
            ->button(L('添加'),array( 'href'=>U('sessionAdd',array('lessonId'=>$id))))
            ->button(L('编辑'),array( 'href'=>U('lessonAdd',array('id'=>$id))))
            ->button(L('删除'), array('class' => 'btn btn-danger ajax-post tox-confirm',
                'data-confirm' => L('确定要删除吗？'),
                'url' => U('doDeleteSession'),
                'target-form' => 'ids'))
            ->button(L('返回'),array( 'href'=>U('index')))
            ->keyId()
            ->keyTime('sTime',L('开始评价时间'))
            ->keyTime('eTime',L('结束评价时间'))
            ->keyDoAction('Appraise/sessionAdd?id=###', '编辑')
            ->data($session)
            ->display();
    }
    public function doDeleteLesson($ids='')
    {
        if(!is_login())
        {
            $this->error('您尚未登录');
        }
        if(M('AppraiseLesson')->where(array('id'=>array('in',$ids)))->delete()){
            if(M('AppraiseSession')->where(array('lessonId'=>array('in',$ids)))->delete()){
                M('AppraiseStudentLesson')->where(array('lessonId'=>array('in',$ids)))->delete();
                M('AppraiseTeacherLesson')->where(array('lessonId'=>array('in',$ids)))->delete();
                $this->success(L('删除成功'));
            }
            else
            {
                $this->error(L('操作失败'));
            }
        }
        else
        {
            $this->error(L('操作失败'));
        }
    }
    public function lessonAdd($id = 0,$lessonId=0,$title='',$sTime=0,$eTime=0 )
    {
        if (IS_POST)
        {
            $time=$eTime-$sTime;
            $four=$time/4+$sTime;
            $two=$time/2+$sTime;
            $content = M('AppraiseLesson')->create();
            if($id !=0){
                //edit
                $rs = M('AppraiseLesson')->where(array('id'=>$id))->save($content);
                $this->success(L ( '编辑成功' ), U ( 'Appraise/index' ) );
            }
            else {
                //add
                $rs = M('AppraiseLesson')->add($content);
                if($rs){
                    $lessonId=M('AppraiseLesson')->where(array('title'=>$title))->getField('id');
                    $con=M('AppraiseSession')->create();
                    $con['lessonId']=$lessonId;
                    $con['sTime']=$four;
                    $con['eTime']=$four+172800;
                    $rs=M('AppraiseSession')->add($con);
                    $con=M('AppraiseSession')->create();
                    $con['lessonId']=$lessonId;
                    $con['sTime']=$two;
                    $con['eTime']=$two+172800;
                    $rs=M('AppraiseSession')->add($con);
                    $con=M('AppraiseSession')->create();
                    $con['lessonId']=$lessonId;
                    $con['sTime']=$eTime;
                    $con['eTime']=$eTime+172800;
                    $rs=M('AppraiseSession')->add($con);
                    if($rs){
                        $this->success(L ( '添加成功' ), U ( 'Appraise/index' ) );
                    }
                    else{
                        $this->error ( L ( '添加失败' ) );
                    }
                }
                else{
                    $this->error ( L ( '添加失败' ) );
                }
            }
        }
        else{
            $builder = new AdminConfigBuilder ();
            $lesson=M('AppraiseLesson');
            if($id==0){
                $title='添加';
                $builder->title ( $title.L ( '课程' ) )
                ->keyText( 'title', L ( '名称' ))
                ->keyTime( 'sTime', L ( '课程开始时间' ))
                ->keyTime( 'eTime', L ( '课程结束时间' ))
                ->buttonSubmit(U('Appraise/lessonAdd'))
                ->buttonBack()
                ->display();
            }
            else{
                $lessonInfo=$lesson->where(array('id'=>$id))->find();
                $title='编辑';
                $builder->title ( $title.L ( '课程' ) )
                ->keyText( 'title', L ( '名称' ))
                ->buttonSubmit(U('Appraise/lessonAdd',array('id'=>$id)))
                ->data($lessonInfo)
                ->buttonBack()
                ->display();
            }
        }
    }
    //时间段相关
    public function doDeleteSession($ids)
    {
        if(!is_login())
        {
            $this->error('您尚未登录');
        }
        if(M('AppraiseSession')->where(array('lessonId'=>array('in',$ids)))->delete()){

            $this->success(L('删除成功'));
        }
        else
        {
            $this->error(L('操作失败'));
        }
    }
    public function sessionAdd($id = 0,$lessonId=0,$sTime=0,$eTime=0 )
    {
        if (IS_POST)
        {
            $content = M('AppraiseSession')->create();
            if($id !=0){
                //edit
                $lessonId=M('AppraiseSession')->where(array('id'=>$id))->getField('lessonId');
                $content['lessonId']=$lessonId;
                $rs = M('AppraiseSession')->where(array('id'=>$id))->save($content);
                if($rs)
                {
                    $this->success(L ( '编辑成功' ), U ( 'Appraise/lessonDetail' ,array('id'=>$lessonId)) );
                }
                else{
                    $this->error ( L ( '编辑失败' ) );
                }
            }
            else {
                //add
                $content['lessonId']=$lessonId;
                $rs = M('AppraiseSession')->add($content);
                if($rs){
                    $this->success(L ( '添加成功' ), U ( 'Appraise/lessonDetail' ,array('id'=>$lessonId) ) );
                }
                else{
                    $this->error ( L ( '添加失败' ) );
                }
            }
        }
        else{
            $builder = new AdminConfigBuilder ();
            $session=M('AppraiseSession');
            if($id==0){
                $title='添加';
                $builder->title ( $title.L ( '时间段' ) )
                ->keyTime( 'sTime', L ( '开始评价时间' ))
                ->keyTime( 'eTime', L ( '结束评价时间' ))
                ->buttonSubmit(U('Appraise/sessionAdd',array('lessonId'=>$lessonId)))
                ->buttonBack()
                ->display();
            }
            else{
                $sessionInfo=$session->where(array('id'=>$id))->find();
                $title='编辑';
                $builder->title ( $title.L ( '时间段' ) )
                ->keyTime( 'sTime', L ( '开始评价时间' ))
                ->keyTime( 'eTime', L ( '结束评价时间' ))
                ->buttonSubmit(U('Appraise/sessionAdd',array('id'=>$id)))
                ->data($sessionInfo)
                ->buttonBack()
                ->display();
            }
        }
    }
    //教师管理
    public function teacherManager($username='',$page=1,$r=15)
    {
        $teacher=M('UcenterMember');
        $teacherUid=M('UserRole')->where(array('role_id'=>'3','status'=>'1'))->getField('uid',true);
        $teacherInfo=$teacher->where(array('username'=>array('like', '%' . $username . '%'),'id'=>array('in',$teacherUid)))->page($page, $r)->select();
        $totalCount = $teacher->where(array('username'=>array('like', '%' . $username . '%'),'id'=>array('in',$teacherUid)))->count();
        //显示页面
        $builder = new AdminListBuilder();
        $builder->title(L('教师管理'))
        ->button(L('全部'), array('href'=>U('teacherManager')))
            ->keyId()
            ->keyTitle('username',L('教师姓名'))
            ->keyDoAction('Appraise/teacherLessonDetail?id=###', '详情')
            ->search(L('名称'), 'username')
            ->pagination($totalCount, $r)
            ->data($teacherInfo)
            ->display();
    }
    public function teacherLessonDetail($id=0)
    {
        $username=M('UcenterMember')->where(array('id'=>$id))->getField('username');
        $lessonIds=M('AppraiseTeacherLesson')->where(array('uid'=>$id))->getField('lessonId',true);
        $lessonInfo=M('AppraiseLesson')->where(array('id'=>array('in',$lessonIds)))->select();
        $builder = new AdminListBuilder();
        $builder->title($username.L('教师详情'))
        ->button(L('添加课程'),array( 'href'=>U('teacherLessonAdd',array('uid'=>$id))))
        ->button(L('删除'), array('class' => 'btn btn-danger ajax-post tox-confirm',
            'data-confirm' => L('确定要删除吗？'),
            'url' => U('doDeleteTeahcerLesson'),
            'target-form' => 'ids'))
            ->button(L('返回'),array( 'href'=>U('teacherManager')))
            ->keyId()
            ->keyTitle('title',L('课程名称'))
            ->keyDoAction('Appraise/studentLessonDetail?id=###&&teacherId='.$id, '学生详情')
            ->data($lessonInfo)
            ->display();
    }
    public function teacherLessonAdd($uid=0,$ids='',$title='',$page=1,$r=15)
    {
        if (IS_POST)
        {
            $content=M('AppraiseTeacherLesson')->create();
            $content['uid']=$uid;
            $lessonId=M('AppraiseLesson')->where(array('id'=>array('in',$ids)))->getField('id',true);
            foreach ($lessonId as $v)
            {
                $content['lessonId']=$v;
                $res=M('AppraiseTeacherLesson')->add($content);
                if($res)
                {
                    continue;
                }
                else
                {
                    $this->error(L ( '添加失败' ), U ( 'Appraise/teacherLessonAdd' ,array('uid'=>$uid) ) );
                }
            }
            unset($v);
            if($res)
            {
                $this->success(L ( '添加成功' ), U ( 'Appraise/teacherLessonDetail' ,array('id'=>$uid) ) );
            }
            else
            {
                $this->error(L ( '添加失败' ), U ( 'Appraise/teacherLessonAdd' ,array('uid'=>$uid) ) );
            }
        }
        else{
            $teacherLessonIds=M('AppraiseTeacherLesson')->where(array('uid'=>$uid))->getField('lessonId',true);
            $lesson=M('AppraiseLesson');
			if(!$teacherLessonIds)
			{
				$teacherLessonIds='0';
			}
            $lessonInfo=$lesson->where(array('title'=>array('like', '%' . $title . '%'),'id'=>array('not in',$teacherLessonIds)))->page($page, $r)->select();
            $totalCount = $lesson->where(array('title'=>array('like', '%' . $title . '%'),'id'=>array('not in',$teacherLessonIds)))->count();
            //显示页面
            $builder = new AdminListBuilder();
            $builder->title(L('为教师添加课程'))
                ->button(L('全部'), array('href'=>U('teacherLessonAdd')))
                ->button(L('添加'),array('class' => 'ajax-post',
                'url' => U('teacherLessonAdd',array('uid'=>$uid)),
                'target-form' => 'ids'))
                ->button(L('返回'), array('href'=>U('teacherLessonDetail',array('id'=>$uid))))
                ->keyId()
                ->keyTitle('title',L('课程名称'))
                ->search(L('课程名称'), 'title')
                ->pagination($totalCount, $r)
                ->data($lessonInfo)
                ->display();
        }
    }
    public function doDeleteTeahcerLesson($ids='')
    {
        if(!is_login())
        {
            $this->error('您尚未登录');
        }
        if(M('AppraiseTeacherLesson')->where(array('lessonId'=>array('in',$ids)))->delete()){
            //M('AppraiseStudentLesson')->where(array('lessonId'=>array('in',$ids)))->delete();
            $this->success(L('删除成功'));
        }
        else
        {
            $this->error(L('操作失败'));
        }
    }
    public function studentLessonDetail($id=0,$teacherId=0,$username='',$page=1,$r=15)
    {
        $title=M('AppraiseLesson')->where(array('id'=>$id))->getField('title');
        $teacherName=M('UcenterMember')->where(array('id'=>$teacherId))->getField('username');
        $haveStudent=M('AppraiseStudentLesson')->where(array('teacherId'=>$teacherId,'lessonId'=>$id))->getField('uid',true);
        $studentInfo=M('UcenterMember')->where(array('title'=>array('like', '%' . $username . '%'),'id'=>array('in',$haveStudent)))->page($page, $r)->select();
        $totalCount = M('UcenterMember')->where(array('title'=>array('like', '%' . $username . '%'),'id'=>array('in',$haveStudent)))->count();
        $builder = new AdminListBuilder();
        $builder->title($teacherName.L('教师的').$title.L('课程学生详情'))
        ->button(L('添加'),array( 'href'=>U('studentLessonAdd',array('lessonId'=>$id,'teacherId'=>$teacherId))))
        ->button(L('删除'), array('class' => 'btn btn-danger ajax-post tox-confirm',
            'data-confirm' => L('确定要删除吗？'),
            'url' => U('doDeleteStudentLesson',array('lessonId'=>$id,'teacherId'=>$teacherId)),
            'target-form' => 'ids'))
            ->button(L('返回'),array( 'href'=>U('teacherLessonDetail',array('id'=>$teacherId))))
            ->keyId()
            ->keyTitle('username',L('学生名称'))
            ->search(L('学生名称'), 'username')
            ->pagination($totalCount, $r)
            ->data($studentInfo)
            ->display();
    }
    public function studentLessonAdd($lessonId=0,$teacherId=0,$ids='')
    {
        if (IS_POST)
        {
            $content=M('AppraiseStudentLesson')->create();
            $content['lessonId']=$lessonId;
            $content['teacherId']=$teacherId;
            $StudentId=M('UcenterMember')->where(array('id'=>array('in',$ids)))->getField('id',true);
            foreach ($StudentId as $v)
            {
                $content['uid']=$v;
                $res=M('AppraiseStudentLesson')->add($content);
                if($res)
                {
                    continue;
                }
                else
                {
                    $this->error(L ( '添加失败' ), U ( 'Appraise/studentLessonAdd' ,array('id'=>$lessonId,'teacherId'=>$teacherId) ) );
                }
            }
            unset($v);
            if($res)
            {
                $this->success(L ( '添加成功' ), U ( 'Appraise/studentLessonDetail' ,array('id'=>$lessonId,'teacherId'=>$teacherId) ) );
            }
            else
            {
                $this->error(L ( '添加失败' ), U ( 'Appraise/studentLessonAdd' ,array('id'=>$lessonId,'teacherId'=>$teacherId) ) );
            }
        }
        else{
            $title=M('AppraiseLesson')->where(array('id'=>$lessonId))->getField('title');
            $teacherName=M('UcenterMember')->where(array('id'=>$teacherId))->getField('username');
            $haveStudent=M('AppraiseStudentLesson')->where(array('teacherId'=>$teacherId,'lessonId'=>$lessonId))->getField('uid',true);
            if($haveStudent)
            {
                $studentUid=M('UserRole')->where(array('role_id'=>'2','status'=>'1','uid'=>array('not in',$haveStudent)))->getField('uid',true);
            }
            else
            {
                $studentUid=M('UserRole')->where(array('role_id'=>'2','status'=>'1'))->getField('uid',true);
            }
            $studentInfo=M('UcenterMember')->where(array('id'=>array('in',$studentUid)))->select();
            $builder = new AdminListBuilder();
            $builder->title($teacherName.L('教师的').$title.L('课程学生添加'))
                    ->button(L('添加'), array('class' => 'ajax-post',
                        'url' => U('studentLessonAdd',array('lessonId'=>$lessonId,'teacherId'=>$teacherId)),
                        'target-form' => 'ids'))
                    ->button(L('返回'),array( 'href'=>U('studentLessonDetail',array('id'=>$lessonId,'teacherId'=>$teacherId))))
                    ->keyId()
                    ->keyTitle('username',L('学生名称'))
                    ->data($studentInfo)
                    ->display();
        }
    }
    public function doDeleteStudentLesson($lessonId=0,$teacherId=0,$ids='')
    {
        if(!is_login())
        {
            $this->error('您尚未登录');
        }
        if(M('AppraiseStudentLesson')->where(array('lessonId'=>$lessonId,'teacherId'=>$teacherId,'uid'=>array('in',$ids)))->delete()){
            $this->success(L('删除成功'));
        }
        else
        {
            $this->error(L('操作失败'));
        }
    }
}
