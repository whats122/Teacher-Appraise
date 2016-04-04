<?php


namespace Appraise\Controller;

use Think\Controller;

class IndexController extends Controller
{
    public function index($page = 1,$keyword='')
    {
        $teacheresUid=M('UserRole')->where(array('status'=>1,'role_id'=>3))->getField('uid',true);
        $teacher=M('UcenterMember');
        $teacheres=$teacher->where(array('id'=>array('in',$teacheresUid),'username'=>array('like','%'.$keyword.'%')))->order ('id asc')->page ( $page, 15 )->select();
        $totalCount = $teacher->where ( array('id'=>array('in',$teacheresUid),'username'=>array('like','%'.$keyword.'%')) )->count ();
        $uid=is_login();
        $roleId=M('UserRole')->where(array('uid'=>$uid))->getField('role_id');
        $studentInfo=M('AppraiseStudentLesson')->where(array('uid'=>$uid))->select();
        $lesson=array();
        $t=0;
        $n=0;
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

}