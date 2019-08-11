<?php
/**
 * Created by PhpStorm.
 * author: Harris
 * Date: 8/10/2019
 * Time: 4:43 PM
 */

namespace App\models\Game\Lottery\Logics\SeriesLogic\Prizes;


use App\Lib\Game\DigitalNumber;
use App\Lib\Game\Math;
use App\Models\Game\Lottery\LotterySeriesWay;
use Illuminate\Support\Facades\Log;

trait SdPrize
{
//##########################################################[福彩3d系列 prize 计算]#########################################

    /**
     * 福彩3d系列计算中奖
     * prizeBigSmallOddEvenBsde
     * prizeConstitutedCombin
     * prizeConstitutedContain
     * prizeEnumCombin
     * prizeEnumEqual
     * prizeMixCombinCombin
     * prizeMultiOneEqual
     * prizeNecessaryCombin
     * prizeSeparatedConstitutedEqual
     * prizeSumCombin
     * prizeSumEqual
     * @param $sFunction
     * @param $sBetNumber
     * @param $sWnNumber
     * @param  LotterySeriesWay  $oSeriesWay
     * @return float|int
     */
    private function getPrizeSd($sFunction, $sBetNumber, $sWnNumber, LotterySeriesWay $oSeriesWay)
    {
        switch ($sFunction) {
            case 'prizeBigSmallOddEvenBsde'://返回大小单双的中奖注数
                $aWnDigitals = explode($this->splitChar, $sWnNumber);
                $aBetDigitals = explode($this->splitChar, $sBetNumber);
                $iWonCount = 1;
                foreach ($aWnDigitals as $i => $sWnDigitals) {
                    $aWnDigitalsOfWei = str_split($sWnDigitals);
                    $aBetDigitalsOfWei = str_split($aBetDigitals[$i]);
                    $aBoth = array_intersect($aWnDigitalsOfWei, $aBetDigitalsOfWei);
                    if (!$iWonCount *= count($aBoth)) {
                        break;
                    }
                }
                $result = $iWonCount;
                break;
            case 'prizeConstitutedCombin'://计算单区型组选复式的中奖注数
                if ($this->max_repeat_time === 1) {
                    $aBetDigitals = str_split($sBetNumber);
                    $aWnDigitals = str_split($sWnNumber);
                    $aDiff = array_diff($aWnDigitals, $aBetDigitals);
                    $result = (int)empty($aDiff);
                } else {
                    $aBetNumber = explode($this->splitChar, $sBetNumber);
                    $aWnDigitals = array_count_values(str_split($sWnNumber));
                    $aWnMaxs = array_keys($aWnDigitals, $this->max_repeat_time);
                    $aWnMins = array_keys($aWnDigitals, $this->min_repeat_time);
                    $aDiffMax = array_diff($aWnMaxs, str_split($aBetNumber[0]));
                    $aDiffMin = isset($aBetNumber[1]) ? array_diff($aWnMins,
                        str_split($aBetNumber[1])) : array_diff($aWnMins, str_split($aBetNumber[0]));
                    $result = (int)(empty($aDiffMax) && empty($aDiffMin));
                }
                break;
            case 'prizeConstitutedContain'://返回不定位的中奖注数
                $aBetDigitals = array_unique(str_split($sBetNumber));
                $aBoth = array_intersect($sWnNumber, $aBetDigitals);
                $iHitCount = count($aBoth);
                $result = $iHitCount >= $this->choose_count ? Math::combin($iHitCount, $this->choose_count) : 0;
                break;
            case 'prizeEnumCombin'://返回组选单式的中奖注数
            case 'prizeEnumEqual'://返回直选单式的中奖注数
            case 'prizeMixCombinCombin'://返回混合组选的中奖注数
                $aBetNumbers = explode($this->splitChar, $sBetNumber);
                $aKeys = array_keys($aBetNumbers, $sWnNumber);
                $result = count($aKeys);
                break;
            case 'prizeMultiOneEqual'://返回定位胆的中奖注数
                $result = (int)preg_match("/$sWnNumber/", $sBetNumber);
                break;
            case 'prizeNecessaryCombin'://返回组选包胆的中奖注数
                $aWnDigitals = array_unique(str_split($sWnNumber));
                $result = (int)in_array($sBetNumber, $aWnDigitals, true);
                break;
            case 'prizeSeparatedConstitutedEqual'://返回直选复式的中奖注数
                $aWnDigitals = str_split($sWnNumber);
                $p = [];
                foreach ($aWnDigitals as $iDigital) {
                    $p[] = '[\d]*'.$iDigital.'[\d]*';
                }
                $pattern = '/^'.implode('\|', $p).'$/';
                $result = (int)preg_match($pattern, $sBetNumber);
                break;
            case 'prizeSumEqual'://返回直选和值的中奖注数
            case 'prizeSumCombin'://返回组选和值的中奖注数
                $iSum = DigitalNumber::getSum($sWnNumber);
                $aBetNumbers = explode($this->splitChar, $sBetNumber);
                $result = (int)in_array((string)$iSum, $aBetNumbers, true);
                break;
            default:
                Log::channel('issues')->info('需要添加福彩sd系列方法:'.$sFunction.$oSeriesWay->toJson());
                $result = 0;
        }
        return $result;
    }
}