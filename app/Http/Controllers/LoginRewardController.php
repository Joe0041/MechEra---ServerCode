<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\UserModel;
use App\CharacterModel;
use App\Login_rewardsModel;
use App\DefindMstModel;
use App\EquipmentMstModel;
use App\ResourceMstModel;
use App\ScrollMstModel;
use Exception;
use DateTime;
use Illuminate\Support\Facades\Redis;
class LoginRewardController extends Controller
{
	public function getLoginReward(Request $request){
		$req=$request->getContent();
		$json=base64_decode($req);
		$data=json_decode($json,TRUE);
		$now   = new DateTime;
		$datetime=$now->format( 'Y-m-d h:m:s' );
		$uid=$data['u_id'];
		$userModel=new UserModel();
		$loginRewardsModel= new Login_rewardsModel();
		$defindMstModel=new DefindMstModel();

		$userData=$userModel->where('u_id',$uid)->first();
		$maxDays=$defindMstModel->where('defind_id',5)->first();
		$loginRewards=$loginRewardsModel->where('days','<=',$maxDays['value1'])->where('start_date','<=',$datetime)->where('end_date','>=',$datetime)->get();
		$loginCount=$userData['u_login_count'];
		$result=[];
		foreach($loginRewards as $reward){
			
			$getReward=$this->chooseReward($reward,$loginCount);

			$result['login_rewards'][]=$getReward;
		}

		$response=json_encode($result,TRUE);

		return $response;

 	}
 	public function getToday(Request $request){
 		$req=$request->getContent();
		$json=base64_decode($req);
		$data=json_decode($json,TRUE);
		$now   = new DateTime;
		$datetime=$now->format( 'Y-m-d h:m:s' );
		$uid=$data['u_id'];
		$userModel=new UserModel();
		$loginRewardsModel= new Login_rewardsModel();
		$defindMstModel=new DefindMstModel();
		$userData=$userModel->where('u_id',$uid)->first();
		$maxDays=$defindMstModel->where('defind_id',5)->first();
		$todayRewards=$loginRewardsModel->where('days',$userData['u_login_count'])->where('start_date','<=',$datetime)->where('end_date','>=',$datetime)->first();
		$loginCount=$userData['u_login_count'];
		$getReward=$this->chooseReward($todayRewards,$loginCount);
		
		if(($loginCount+1)==$maxDays){
			$loginCount=1;
		}
		else{
			$loginCount=$loginCount+1;

		}
		$userModel->where('u_id',$uid)->update(['u_login_count',$loginCount]);
		$getReward['createtime']=time();
		$todayreward=json_encode($getReward,TRUE);
		Redis::LPUSH('reward_history',$todayreward);
		return $todayreward;
 	}

 	private function chooseReward($reward,$loginCount){
 		$resourceModel=new ResourceMstModel();
		$equipmentMstModel=new EquipmentMstModel();
		$scrollMstModel=new ScrollMstModel();
		$getReward=[];
		if($reward['item_type']==1){
				$resourceData=$resourceModel->where('r_id',$reward['item_org_id'])->first();
				$getReward['days']=$reward['days'];
				$getReward['item_type']=$reward['item_type'];
				$getReward['item_org_id']=$reward['item_org_id'];
				$getReward['item_quantity']=$reward['item_quantity'];
				$getReward['description']=$reward['description'];
				$getReward['img_path']=$resourceData['r_img_path'];
			}
			else  if($reward['item_type']==2){
				$eqData=$equipmentMstModel->where('equ_id',$reward['item_org_id'])->first();
				$getReward['days']=$reward['days'];
				$getReward['item_type']=$reward['item_type'];
				$getReward['item_org_id']=$reward['item_org_id'];
				$getReward['item_quantity']=$reward['item_quantity'];
				$getReward['description']=$reward['description'];
				$getReward['img_path']=$eqData['icon_path'];

			}
			else if ($reward['item_type']==3){
				$scData=$scrollMstModel->where('sc_id',$reward['item_org_id'])->first();
				$getReward['days']=$reward['days'];
				$getReward['item_type']=$reward['item_type'];
				$getReward['item_org_id']=$reward['item_org_id'];
				$getReward['item_quantity']=$reward['item_quantity'];
				$getReward['description']=$reward['description'];
				$getReward['img_path']=$scData['sc_img_path'];
			}

			if(($loginCount+1)==$reward['days']){
				$getReward['today']=1;
			}
			else{
				$getReward['today']=0;
			}
 	
 		return $getReward;
 	}
}
