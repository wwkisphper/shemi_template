<?php
namespace app\API\model;

use think\Model;

class Prize extends Model
{
	/**
	 * 扣除用户抽奖所用积分
	 * @param  $uid 扣除积分的用户
	 * @param  $integral 扣除的积分数
	 * @return 失败   成功条数
	 */
	public function PrizeDeductionIntegral($uid,$integral){

		$userIntegral = db('Promotion_integral') -> where("belong_uid = {$uid}") -> sum('integral');
		if($integral > $userIntegral){
			return false;
		}

		$data['belong_uid'] = $uid;
		$data['integral'] = -$integral;
		$data['source_uid'] = $uid;

		$success = db('Promotion_integral') -> insert($data);

		return $success;
	}

	/**
	 * 抽奖
	 * @param $adminId 商户id
	 * @param $pumpingId 奖品id 或 未抽中状态
	 */
	public function pumping($adminId){
		$data = db('Prize') -> field('id,probability') -> where("admin_id={$adminId} AND stock >= 1") -> select();
        $pumpingId = 0;
        $random = mt_rand()/mt_getrandmax();
        foreach ($data as $key => $value) {
            $value['probability'] = $value['probability']/100;
            if($random <= $value['probability']){
                $pumpingId = $value['id'];
                break;
            }
        }

        return $pumpingId;
	}

	/**
	 * 记录中奖
	 * @param  $uid 用户id
	 * @param  $adminId 商户id
	 * @param  $pumpingId 抽中的奖品id
	 */
	public function PumpingRecord($uid,$adminId,$pumpingId){
		$prizeData = db('Prize') -> field('name,picture') -> find($pumpingId);

		$data['uid'] = $uid;
		$data['admin_id'] = $adminId;
		$data['name'] = $prizeData['name'];
		$data['picture'] = $prizeData['picture'];
		$data['create_time'] = time();

		$success = db('Winning_record') -> insert($data);
		
		$result['success'] = $success;
		$result['prize_name'] = $prizeData['name'];

		return $result;
	}
}