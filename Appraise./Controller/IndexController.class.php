<?php


namespace Appraise\Controller;

use Think\Controller;

class IndexController extends Controller
{
    public function index($page = 1,$keyword='')
    {
        $teacheresUid=M('UserRole')->where(array('status'=>1,'role_id'=>3))->getField('uid',true);
        $teacher=M('UcenterMember');
        $teacheres=$teacher->where(array('username'=>array('like','%'.$keyword.'%'),'id'=>array('in',$teacheresUid)))->order ('id asc')->page ( $page, 15 )->select();
        $totalCount = $teacher->where(array('username'=>array('like','%'.$keyword.'%'),'id'=>array('in',$teacheresUid)))->count ();
        $uid=is_login();
        $roleId=M('UserRole')->where(array('uid'=>$uid))->getField('role_id');
        $studentInfo=M('AppraiseStudentLesson')->where(array('uid'=>$uid))->select();
        $lesson=array();
        $t=0;
        $n=0;
        $teachereId=array();
        $i=0;
        foreach ($teacheres as $v)
        {
            $teachereId[$i]=$v['id'];
            $i++;
        }
        unset($v);
        $teacheresUid=$teachereId;
        foreach ($teacheresUid as $v)
        {
            $teacheres[$n]['appraise']=0;
            $point=M('Appraise')->where(array('teacherId'=>$v))->getField('point',true);
            $teacheres[$n]['point']=0;
            if($point)
            {
                $count=M('Appraise')->where(array('teacherId'=>$v))->count();
                foreach ($point as $m)
                {
                    $teacheres[$n]['point']+=$m;
                }
                unset($m);
                $teacheres[$n]['point']=$teacheres[$n]['point']/$count;
            }
            $t=0;
            foreach ($studentInfo as $k)
            {
                if(!M('AppraiseTeacherLesson')->where(array('uid'=>$k['teacherId'],'lessonId'=>$k['lessonId']))->find())
                {
                    continue;
                }
                if($v!=$k['teacherId'])
                {
                    continue;
                }
                $session=M('AppraiseSession')->where(array('lessonId'=>$k['lessonId']))->select();
                $o=0;
                foreach ($session as $p)
                {
                    if($p['sTime']<=NOW_TIME && NOW_TIME<=$p['eTime'])
                    {
                        $appraise=M('Appraise')->where(array('sessionId'=>$p['id'],'teacherId'=>$v,'studentId'=>$uid))->find();
                        if(!$appraise)
                        {
                            $o=1;
                            $t=1;
                            break;
                        }
                    }
                }
                unset($p);
                if($o)
                {
                    break;
                }
            }
            unset($k);
            if($t)
            {
                $teacheres[$n]['appraise']=1;
            }
            $n++;
        }
        unset($v);
        $this->assign ( 'keyword', $keyword );
        $this->assign('totalPageCount',$totalCount);
        $this->assign('teacheres',$teacheres);
        $this->assign('roleId',$roleId);
        $this->display();
    }

    public function view($id=0,$page=1)
    {
        $uid=is_login();
        if(!$uid)
        {
            $this->error(L('请先登录！'));
        }
        $id=intval($id);
        if(!$id)
        {
            $this->error(L('该老师不存在！'));
        }
        $appraise=M('Appraise');
        $teacher=M('UcenterMember')->where(array('id'=>$id))->getField('username');
        $appraises=$appraise->where(array('teacherId'=>$id))->order('createTime desc')->page ( $page, 15 )->select();
        $totalCount = $appraise->where ( array('teacherId'=>$id))->count ();
        $i=0;
        foreach ($appraises as $v)
        {
            $appraises[$i]['studentName']=M('UcenterMember')->where(array('id'=>$appraises[$i]['studentId']))->getField('username');
            $lessonId=M('AppraiseSession')->where(array('id'=>$appraises[$i]['sessionId']))->getField('lessonId');
            $appraises[$i]['title']=M('AppraiseLesson')->where(array('id'=>$lessonId))->getField('title');
            if($appraises[$i]['anonymous'] && !is_administrator ($uid))
            {
                $appraises[$i]['studentName']=$appraises[$i]['studentName'][0]."**";
            }
            $i++;
        }
        unset($v);
        $this->assign('teacher',$teacher);
        $this->assign('appraises',$appraises);
        $this->assign('totalPageCount',$totalCount);
        $this->display();
    }
    
    public function chooseLesson($teacherId=0)
    {
        $uid=is_login();
        if(!uid)
        {
            $this->error(L('请先登录'));
        }
        if(!$teacherId)
        {
            $this->error(L('该老师不存在'));
        }
        $lessonIds=M('AppraiseStudentLesson')->where(array('uid'=>$uid,'teacherId'=>$teacherId))->getField('lessonId',true);
        if(!$lessonIds)
        {
            $this->error(L('没有课程需要评价'));
        }
        $lesson=M('AppraiseLesson')->where(array('id'=>array('in',$lessonIds)))->select();
        $i=0;
        $lessons=array();
        foreach ($lesson as $v)
        {
            $session=M('AppraiseSession')->where(array('lessonId'=>$v['id']))->select();
            foreach ($session as $k)
            {
                if($k['sTime']<=NOW_TIME && NOW_TIME<=$k['eTime'])
                {
                    $appraise=M('Appraise')->where(array('sessionId'=>$k['id'],'teacherId'=>$teacherId,'studentId'=>$uid))->find();
                    if(!$appraise)
                    {
                        $lessons[$i]['lessonId']=$v['id'];
                        $lessons[$i]['title']=$v['title'];
                        $lessons[$i]['sessionId']=$k['id'];
                        $lessons[$i]['sTime']=$k['sTime'];
                        $lessons[$i]['eTime']=$k['eTime'];
                        $i++;
                        break;
                    }
                }
            }
            unset($k);
        }
        unset($v);
        $this->assign('lessons',$lessons);
        $this->assign('teacherId',$teacherId);
        $this->display();
    }
    
    public function doPost($teacherId = 0,  $sessionId = 0, $content = '', $point = 0, $anonymous = 0)
    {
        $uid=is_login();
        if (!$uid) {
            $this->error(L('请先登录！'));
        }
        if (!$teacherId) {
            $this->error(L('请选择老师！'));
        }
        if (!$sessionId) {
            $this->error(L('请选择课程！'));
        }
        if (!$point) {
            $this->error(L('请选择星级！'));
        }
    
        $con = M('Appraise')->create();
        $con['studentId'] = $uid;
        $con['teacherId'] = intval($teacherId);
        $con['sessionId'] =  intval($sessionId);
        $con['point'] = $point;
        $con['content'] = op_t($content);
        $con['anonymous'] = $anonymous;
        $con['createTime'] = NOW_TIME;
    
        $rs = M('Appraise')->add($con);
        if ($rs) {
            $this->success(L('评价成功').L('_EXCLAMATION_'), U('index'));
        } else {
            $this->error(L('_ERROR_OPERATION_FAIL_').L('_EXCLAMATION_'),'');
        }
    
    }
    
    public function appraise($teacherId,$sessionId)
    {
        $this->assign('teacherId', $teacherId);
        $this->assign('sessionId', $sessionId);
        $this->display();
    }
}