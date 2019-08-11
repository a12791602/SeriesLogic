<?php
/**
 * Created by PhpStorm.
 * author: Harris
 * Date: 8/10/2019
 * Time: 4:38 PM
 */

namespace App\models\Game\Lottery\Logics\SeriesLogic\Prizes;


use App\Lib\Game\DigitalNumber;
use App\Lib\Game\Math;
use App\Models\Game\Lottery\LotterySeriesWay;
use Illuminate\Support\Facades\Log;

trait K3Prize
{
//##########################################################[k3系列 prize 计算]#########################################

    /**
     * 十一选五系列计算中奖
     * prizeBigSmallOddEvenK3bsde
     * prizeEnumK3combin
     * prizeEnumK3contain
     * prizeSumK3combin
     * @param $sFunction
     * @param $sBetNumber
     * @param $sWnNumber
     * @param  LotterySeriesWay  $oSeriesWay
     * @return float|int
     */
    private function getPrizeK3($sFunction, $sBetNumber, $sWnNumber, LotterySeriesWay $oSeriesWay)
    {

        switch ($sFunction) {
            case 'prizeBigSmallOddEvenK3bsde'://K3大小单双和值的中奖注数
                $aWnNumber = str_split($sWnNumber);
                $aBetNumber = str_split($sBetNumber);
                $aBoth = array_intersect($aWnNumber, $aBetNumber);
                $result = count($aBoth);
                break;
            case 'prizeEnumK3combin'://快3组选单式的中奖注数
                $aBetNumbers = explode($this->splitChar, $sBetNumber);
                $result = (int)array_keys($aBetNumbers, $sWnNumber);
                break;
            case 'prizeEnumK3contain'://返回快3任选单式的中奖注数
                $winCount = 0;
                $aBetNumbers = explode($this->splitChar, $sBetNumber);
                $aWnNumber = str_split($sWnNumber);
                $aCombinations = Math::getCombinationToString($aWnNumber, $this->choose_count);
                $aDigitals = [];
                foreach ($aCombinations as $sCombination) {
                    $aDigital = explode(',', $sCombination);
                    sort($aDigital);
                    $aDigitals[] = implode($aDigital);
                }
                foreach ($aBetNumbers as $ithemBetNumber) {
                    if (in_array($ithemBetNumber, $aDigitals, false)) {
                        $winCount++;
                    }
                }
                $result = $winCount;
                break;
            case 'prizeSumK3combin'://K3组选和值的中奖注数
                $iSum = DigitalNumber::getSum($sWnNumber);
                $aBetNumbers = explode($this->splitChar, $sBetNumber);
                $result = (int)in_array($iSum, $aBetNumbers, false);
                break;
            default:
                Log::channel('issues')->info('需要添加k3系列方法:'.$sFunction.$oSeriesWay->toJson());
                $result = 0;
        }
        return $result;
    }
}