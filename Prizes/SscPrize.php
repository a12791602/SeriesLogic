<?php
/**
 * Created by PhpStorm.
 * author: Harris
 * Date: 8/10/2019
 * Time: 4:35 PM
 */

namespace App\models\Game\Lottery\Logics\SeriesLogic\Prizes;


use App\Lib\Game\DigitalNumber;
use App\Lib\Game\Math;
use App\Models\Game\Lottery\LotterySeriesWay;
use Illuminate\Support\Facades\Log;

trait SscPrize
{
//##########################################################[时时彩系列 prize 计算]#########################################

    /**
     * ssc 系列
     * @param $sFunction
     * @param $sBetNumber
     * @param $sWnNumber
     * @param  LotterySeriesWay  $oSeriesWay
     * @return float|int
     */
    private function getPrizeSsc($sFunction, $sBetNumber, $sWnNumber, LotterySeriesWay $oSeriesWay)
    {
        switch ($sFunction) {
            case 'prizeEnumCombin'://返回组选单式的中奖注数
            case 'prizeEnumEqual'://返回直选单式的中奖注数
            case 'prizeMixCombinCombin'://返回混合组选的中奖注数
                $aBetNumbers = explode($this->splitChar, $sBetNumber);
                $aKeys = array_keys($aBetNumbers, $sWnNumber);
                $result = count($aKeys);
                break;
            case 'prizeMultiSequencingEqual'://返回直选组合的中奖注数
            case 'prizeFunSeparatedConstitutedInterest'://返回趣味玩法的中奖注数
            case 'prizeSectionalizedSeparatedConstitutedArea'://返回区间玩法的中奖注数
            case 'prizeSeparatedConstitutedEqual'://返回直选复式的中奖注数
                $aWnDigitals = str_split($sWnNumber);
                $p = [];
                foreach ($aWnDigitals as $iDigital) {
                    $p[] = '[\d]*'.$iDigital.'[\d]*';
                }
                $pattern = '/^'.implode('\|', $p).'$/';
                $result = (int)preg_match($pattern, $sBetNumber);
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
            case 'prizeConstitutedDoubleAreaCombin'://计算双区型组选复式的中奖注数
            case 'prizeConstitutedForCombin30Combin'://计算双区型组选复式的中奖注数
                $aBetNumber = explode($this->splitChar, $sBetNumber);
                $aWnDigitals = array_count_values(str_split($sWnNumber));
                $aWnMaxs = array_keys($aWnDigitals, $this->max_repeat_time);
                $aWnMins = array_keys($aWnDigitals, $this->min_repeat_time);
                $aDiffMax = array_diff($aWnMaxs, str_split($aBetNumber[0]));
                $aDiffMin = isset($aBetNumber[1]) ? array_diff($aWnMins,
                    str_split($aBetNumber[1])) : array_diff($aWnMins, str_split($aBetNumber[0]));
                $result = (int)(empty($aDiffMax) && empty($aDiffMin));
                break;
            case 'prizeSumTailSumTail'://prizeSumTailSum_tail 返回和尾的中奖注数
                $iSumTail = DigitalNumber::getSumTail($sWnNumber);
                $aBetNumbers = str_split($sBetNumber);
                $result = (int)in_array((string)$iSumTail, $aBetNumbers, true);
                break;
            case 'prizeSpecialConstitutedSpecial'://返回三星特殊的中奖注数
                $result = (int)preg_match("/$sWnNumber/", $sBetNumber);
                break;
            case 'prizeSpanEqual'://返回直选跨度的中奖注数
                $iSpan = DigitalNumber::getSpan($sWnNumber);
                $aBetNumbers = str_split($sBetNumber);
                $result = (int)in_array((string)$iSpan, $aBetNumbers, true);
                break;
            case 'prizeNecessaryCombin'://返回组选包胆的中奖注数
                $aWnDigitals = array_unique(str_split($sWnNumber));
                $result = (int)in_array($sBetNumber, $aWnDigitals, true);
                break;
            case 'prizeSumEqual'://返回直选和值的中奖注数
            case 'prizeSumCombin'://返回组选和值的中奖注数
                $iSum = DigitalNumber::getSum($sWnNumber);
                $aBetNumbers = explode($this->splitChar, $sBetNumber);
                $result = (int)in_array((string)$iSum, $aBetNumbers, true);
                break;
            case 'prizeMultiOneEqual'://返回定位胆的中奖注数
                $result = (int)preg_match("/$sWnNumber/", $sBetNumber);
                break;
            case 'prizeTwoStarBigSmallTsbs':
            case 'prizeTwoStarBigSmallTsEqual':
            case 'commonPrizeTwoStar': //龙虎和 共用函数
                $arrToIntersectWith = $this->wn_function == 'tsEqual' ? [2] : [0, 1];
                $aBetNumber = str_split($sBetNumber);
                $intersect = array_intersect($arrToIntersectWith, $aBetNumber);
                $aBetNumber = array_unique($intersect);
                $iWnDigital = $this->getTsbslWinNumberSsc($oSeriesWay->area_position, $sWnNumber);
                $result = (int)in_array($iWnDigital, $aBetNumber, true);
                break;
            default:
                Log::channel('issues')->info('需要添加时时彩系列方法:'.$sFunction.$oSeriesWay->toJson());
                $result = 0;
        }
        return $result;
    }

    /**
     * 返回二星大小的中奖号码
     * @param $areaPosition
     * @param $sWnNumber
     * @return int
     */
    private function getTsbslWinNumberSsc($areaPosition, $sWnNumber): ?int
    {
        $aWnNumber = str_split($sWnNumber);
        $aPosition = str_split($areaPosition);
        $aWnDigital = [];
        foreach ($aPosition as $iPosition) {
            $aWnDigital[] = $aWnNumber[$iPosition];
        }
        if ($aWnDigital[0] > $aWnDigital[1]) {
            return 0; //龙
        } elseif ($aWnDigital[0] < $aWnDigital[1]) {
            return 1; //虎
        } elseif ($aWnDigital[0] === $aWnDigital[1]) {
            return 2; //和
        }
    }
}