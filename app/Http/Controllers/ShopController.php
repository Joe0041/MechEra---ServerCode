<?php

namespace App\Http\Controllers;
use App\Http\Requests;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\UserResourcePurchaseHistoryModel;
use App\ResourceMstModel;
use App\UserModel;
use App\UserBaggageResModel;
use App\StoreGemRefreashMstModel;
use App\StoreGemToCoinMstModel;
use Exception;
use App\Exceptions\Handler;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Redis;
use Carbon\Carbon;
use DateTime;

class ShopController extends Controller
{
	public function shop(Request $request)
	{
		$req=$request->getContent();
		$data=json_decode($req,TRUE);
		$now=new DateTime;
		$datetime=$now->format( 'Y-m-d h:m:s' );
		$dmy=$now->format( 'Ymd' );
		$redis_shop=Redis::connection('default');

		$UserResHistory=new UserResourcePurchaseHistoryModel;
		$ResourceMstModel=new ResourceMstModel;
		$StoreGemRefreashMstModel=new StoreGemRefreashMstModel;
		$UserModel=new UserModel;
		$resource=[];
		$resourceList=[];

		$u_id=$data['u_id'];
		$shopkey='shop'.$u_id.$dmy;
		$resStoreInfo=$UserResHistory->where('u_id',$u_id)->get();
		$UserInfo=$UserModel->where('u_id',$u_id)->first();
		$ref_times=$redis_shop->LRANGE($shopkey,0,0);
		$time=$ref_times['0'];
		if($time<=6)
		{
			$gem=$StoreGemRefreashMstModel->where('id_ref',$time)->first();
			$need_gem=$gem['gem'];
		}else{
			$need_gem=100;
		}

		$resourceList['need_gem']=$need_gem;

		if(empty($resStoreInfo))
		{
			for($x=1;$x<=5;$x++)
			{
				$r_id = rand(1,5);
				$order_id = $x;
				$UserResHistory->insert(['u_id'=>$u_id,'r_id'=>$r_id,'order_id'=>$order_id,'order_status'=>0,'updated_at'=>$datetime,'created_at'=>$datetime]);
			}
		}

		$UserResInfo=$UserResHistory->where('u_id',$u_id)->whereBetween('order_status',array(0,1))->get();

		foreach($UserResInfo as $obj)
		{
			$r_id=$obj['r_id'];
			$resInfo=$ResourceMstModel->where('r_id',$r_id)->first();
			$resource['r_id']=$r_id;
			$resource['r_name']=$resInfo['r_name'];
			$resource['r_price']=$resInfo['r_price'];
			$resource['r_img_path']=$resInfo['r_img_path'];
			$resource['r_position']=$obj['order_id'];
			$resourceList[]=$resource;
		}
		return $time;
	}

	public function buyResource(Request $request)
	{	
		$req=$request->getContent();
		$data=json_decode($req,TRUE);
		$now=new DateTime;
		$datetime=$now->format( 'Y-m-d h:m:s' );
		$dmy=$now->format( 'Ymd' );

		$UserModel=new UserModel;
		$UserResHistory=new UserResourcePurchaseHistoryModel;
		$ResourceMstModel=new ResourceMstModel;
		$UserBaggageResModel=new UserBaggageResModel;

		$u_id=$data['u_id'];
		$r_id=$data['r_id'];
		$quantity=$data['quantity'];
		$resInfo=$ResourceMstModel->where('r_id',$r_id)->first();
		$UserInfo=$UserModel->where('u_id',$u_id)->first();
		$userGem=$UserInfo['u_gem'];

		if($r_id<=5)
		{
			$currency=$data['currency'];
			if($currency == 1)
			{
				$usedGem=$resInfo['r_gem_price']*$quantity;
				$updateGem=$userGem-$usedGem;
				if($updateGem>=0)
				{
					$UserModel->update(['u_gem'=>$updateGem,'updated_at'=>$datetime]);
				}else{
					throw new Exception("Don't have enough Gem!");
				}
			}else if($currency == 2)
			{
				$userCoin=$UserModel->where('u_id',$u_id)->pluck('u_coin');
				$usedCoin=$resInfo['r_coin_price']*$quantity;
				$updateCoin=$userCoin-$usedCoin;
				if($updateCoin>=0)
				{
					$UserModel->update(['u_coin'=>$updateCoin,'updated_at'=>$datetime]);
				}else{
					throw new Exception("Don't have enough Coin!");
				}
			}
		}else if($r_id>=6)
		{
			$order_id=$data['order_id'];
			$resStoreInfo=$UserResHistory->where('u_id',$u_id)->where('order_id',$order_id)->where('order_status',0)->first();

			if (isset($resStoreInfo)) 
			{
				$r_id=$resStoreInfo['r_id'];
				$updateGem=$userGem-$resInfo['r_gem_price'];
				if($updateGem>=0)
				{
					$UserModel->update(['u_gem'=>$updateGem,'updated_at'=>$datetime]);
					$UserResHistory->where('u_id',$u_id)->where('order_id',$order_id)->where('order_status',0)->update(['order_status'=>1,'updated_at'=>$datetime]);
				}else{
					throw new Exception("Don't have enough Gem!");
				}
			}else{
				throw new Exception("Already sold out!");
			}
		}

		$userBagRes=$UserBaggageResModel->where('u_id',$u_id)->where('br_id',$r_id)->first();
		if(isset($userBagRes))
		{
			if($userBagRes['status']==0)
			{
				$updateQuantity=$userBagRes['br_quantity']+$quantity;
				$UserBaggageResModel->where('u_id',$u_id)->where('br_id',$r_id)->update(['br_quantity'=>$updateQuantity,'updated_at'=>$datetime]);
			}else if($userBagRes['status']==1)
			{
				$UserBaggageResModel->where('u_id',$u_id)->where('br_id',$r_id)->update(['br_quantity'=>$quantity,'status'=>0,'updated_at'=>$datetime]);
			}
		}else{
			$UserBaggageResModel->insert(['u_id'=>$u_id,'br_id'=>$r_id,'br_icon'=>$resInfo['r_img_path'],'br_rarity'=>$resInfo['r_rarity'],'br_type'=>$resInfo['r_type'],'br_quantity'=>$quantity,'status'=>0,'updated_at'=>$datetime,'created_at'=>$datetime]);
		}
		$response='Buy successfully';
		return $response;
	}

	public function refreashRareResource(Request $request)
	{
		$req=$request->getContent();
		$data=json_decode($req,TRUE);
		$now=new DateTime;
		$datetime=$now->format( 'Y-m-d h:m:s' );
		$dmy=$now->format( 'Ymd' );
		$redis_shop=Redis::connection('default');

		$UserResHistory=new UserResourcePurchaseHistoryModel;
		$ResourceMstModel=new ResourceMstModel;
		$UserModel=new UserModel;
		$StoreGemRefreashMstModel=new StoreGemRefreashMstModel;
		$resource=[];
		$resourceList=[];

		$u_id = $data['u_id'];
		$shopkey='shop'.$u_id.$dmy;
		$UserInfo=$UserModel->where('u_id',$u_id)->first();
		$ref_times=$redis_shop->LRANGE($shopkey,0,0);
		$times=json_decode($ref_times);
		$time=(int)$times;
		if(isset($ref_times))
		{
			if($time<=5)
			{
				$gem=$StoreGemRefreashMstModel->where('id_ref',$time)->first();
				$spend_gem=$gem['gem'];
			}else{
				$spend_gem=100;
			}
			$updateRef=$time+1;
			$redis_shop->LPUSH($shopkey,$updateRef);
		}else{
			$spend_gem=0;
			$redis_shop->LPUSH($shopkey,1);
		}
		
		$updateGem=$UserInfo['u_gem']-$spend_gem;
		$UserModel->where('u_id',$u_id)->update(['u_gem'=>$updateGem,'updated_at'=>$datetime]);

		$resStoreInfo=$UserResHistory->where('u_id',$u_id)->get();

		if(isset($resStoreInfo))
		{
			foreach($resStoreInfo as $obj)
			{
				$id=$obj['r_pur_id'];
				$status=$obj['order_status'];
				if($status == 0)
				{
					$UserResHistory->where('r_pur_id',$id)->update(['order_status'=>2,'updated_at'=>$datetime]);
				}else if($status == 1)
				{
					$UserResHistory->where('r_pur_id',$id)->update(['order_status'=>3,'updated_at'=>$datetime]);
				}
			}

			for($x=1;$x<=5;$x++)
			{
				$r_id = rand(6,10);
				$order_id = $x;
				$UserResHistory->insert(['u_id'=>$u_id,'r_id'=>$r_id,'order_id'=>$order_id,'order_status'=>0,'updated_at'=>$datetime,'created_at'=>$datetime]);
				$resourceInfo=$ResourceMstModel->where('r_id',$r_id)->first();
				$resource['r_id']=$r_id;
				$resource['r_name']=$resourceInfo['r_name'];
				$resource['r_price']=$resourceInfo['r_gem_price'];
				$resource['r_img_path']=$resourceInfo['r_img_path'];
				$resource['r_position']=$order_id;
				$resourceList[]=$resource;
			}
		}else{
			throw new Exception("there have some error");
			$response=[
			'status' => 'Wrong',
			'error' => "please check UserResourcePurchaseHistory table",
			];
		}
		return $resourceList;
	}

	public function buyCoin(Request $request)
	{	
		$req=$request->getContent();
		$data=json_decode($req,TRUE);

		$u_id=$data['u_id'];
		$id=$data['id'];

		$UserModel=new UserModel;
		$StoreGemToCoinMstModel=new StoreGemToCoinMstModel;
		$buyType=$StoreGemToCoinMstModel->where('u_id',$u_id)->first();
		$UserInfo=$UserModel->where('u_id',$u_id)->first();

		$spend_gem=$buyType['gem'];
		$get_coin=$buyType['coin'];

		$updateGem=$UserInfo['u_gem']-$spend_gem;
		$updateCoin=$UserInfo['u_coin']+$get_coin;

		$UserModel->where('u_id',$u_id)->update(['u_gem'=>$updateGem,'u_coin'=>$updateCoin]);

		$response='Buy successfully';
		return $response;		
	}
}