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
     * prizeBigSmallOddEvenBsde 前二大小单双
     * prizeConstitutedCombin 前三组三复式
     * prizeConstitutedContain 前三一码不定位
     * prizeConstitutedDoubleAreaCombin 四星组选4
     * prizeConstitutedDoubleAreaOptionalcombin 任四组12
     * prizeConstitutedForCombin30Combin 五星组选30
     * prizeConstitutedOptionalcombin 任三组三复式
     * prizeEnumCombin 前三组三单式
     * prizeEnumEqual 前三直选单式
     * prizeEnumOptionalcombin 任三组三单式
     * prizeEnumOptionalequal 任二直选单式
     * prizeFunSeparatedConstitutedInterest 五码趣味三星
     * prizeMixCombinCombin 前三混合组选
     * prizeMixCombinOptionalcombin 任三混合组选
     * prizeMultiOneEqual 定位胆
     * prizeMultiSequencingEqual 前三直选组合
     * prizeNecessaryCombin 前三组选包胆
     * prizeSectionalizedSeparatedConstitutedArea 五码区间三星
     * prizeSeparatedConstitutedEqual 前三直选复式
     * prizeSeparatedConstitutedOptionalequal 任二直选复式
     * prizeSpanEqual 前三直选跨度
     * prizeSpecialConstitutedSpecial 前三特殊
     * prizeSumCombin 前三组选和值
     * prizeSumEqual 前三直选和值
     * prizeSumOptionalcombin 任三组选和值
     * prizeSumOptionalequal 任二直选和值
     * prizeSumTailSum_tail 前三和尾
     * prizeTwoStarBigSmallTsbs 万千
     * prizeTwoStarBigSmallTsEqual 万百
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
                    $aDiffMin0 = array_diff($aWnMins, str_split($aBetNumber[0]));
                    $aDiffMin1 = array_diff($aWnMins, str_split($aBetNumber[1]));
                    $aDiffMin = isset($aBetNumber[1]) ? $aDiffMin1 : $aDiffMin0;
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
                $aDiffMin1 = array_diff($aWnMins, str_split($aBetNumber[1]));
                $aDiffMin0 = array_diff($aWnMins, str_split($aBetNumber[0]));
                $aDiffMin = isset($aBetNumber[1]) ? $aDiffMin1 : $aDiffMin0;
                $result = (int)(empty($aDiffMax) && empty($aDiffMin));
                break;
            case 'prizeSumTailSumTail'://prizeSumTailSum_tail 返回和尾的中奖注数
                $iSumTail = DigitalNumber::getSumTail($sWnNumber);
                $aBetNumbers = str_split($sBetNumber);
                $result = (int)in_array((string)$iSumTail, $aBetNumbers, true);
                break;
            case 'prizeSpecialConstitutedSpecial'://返回三星特殊的中奖注数
                $result = (int)preg_match('/'.$sWnNumber.'/', $sBetNumber);
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
                $result = (int)preg_match('/'.$sWnNumber.'/', $sBetNumber);
                break;
            case 'prizeTwoStarBigSmallTsbs':
            case 'prizeTwoStarBigSmallTsEqual':
            case 'commonPrizeTwoStar': //龙虎和 共用函数
                $arrToIntersectWith = $this->wn_function === 'tsEqual' ? [2] : [0, 1];
                $aBetNumber = str_split($sBetNumber);
                $intersect = array_intersect($arrToIntersectWith, $aBetNumber);
                $aBetNumber = array_unique($intersect);
                $iWnDigital = $this->getTsbslWinNumberSsc($oSeriesWay->area_position, $sWnNumber);
                $result = (int)in_array($iWnDigital, $aBetNumber, true);
                break;
            case 'prizeConstitutedDoubleAreaOptionalcombin': //任选组选选重号与不重号的复式注数计算
                $aBetNumber = explode($this->splitChar, $sBetNumber);
                $sWnNumber = $this->getOptionalWinNumber($sWnNumber);
                $aDoubleNumber = array_intersect($sWnNumber, str_split($aBetNumber[0]));
                $aSingleNumber = array_intersect($sWnNumber, str_split($aBetNumber[1]));
                $aDoubleDigitals = array_count_values($aDoubleNumber);
                $aSingleDigitals = array_count_values($aSingleNumber);
                $uniqueCount = ($this->choose_count - $this->max_repeat_time) / $this->min_repeat_time;
                $aMinCombin = Math::getCombinationToString(array_keys($aSingleDigitals), $uniqueCount);
                $aMinCombins = [];
                foreach ($aMinCombin as $sMinCombin) {
                    $aMinCombins[] = explode(',', $sMinCombin);
                }
                $iSumHits = 0;
                foreach ($aDoubleDigitals as $digital => $count) {
                    if ($count < $this->max_repeat_time) {
                        continue;
                    }
                    $combinCount = Math::combin($count, $this->max_repeat_time);
                    $iMinCombinCount = 0;
                    foreach ($aMinCombins as $aDigtal) {
                        if (in_array($digital, $aDigtal, false)) {
                            continue;
                        }
                        $iMinCombinCount += $uniqueCount === 1 ? $aSingleDigitals[$aDigtal[0]] : $aSingleDigitals[$aDigtal[0]] * $aSingleDigitals[$aDigtal[1]];
                    }
                    $iSumHits += $combinCount * $iMinCombinCount;
                }
                $result = $iSumHits;
                break;
            case 'prizeConstitutedOptionalcombin': //返回任选组选复式的中奖注数
                $aBetNumber = str_split($sBetNumber);
                $sWnNumber = array_intersect($this->getOptionalWinNumber($sWnNumber), $aBetNumber);
                $prizeCount = 0;
                $filterDigital = [];
                $aWinDigital = array_count_values($sWnNumber);
                $sWnNumber = [];
                foreach ($aWinDigital as $digital => $count) {
                    if ($count < $this->min_repeat_time) {
                        unset($aWinDigital[$digital]);
                    }
                    if ($count >= $this->min_repeat_time) {
                        $sWnNumber = array_merge(array_fill(0, $count, $digital), $sWnNumber);
                    }
                }
                foreach ($aWinDigital as $digital => $count) {
                    if ($count >= $this->max_repeat_time) {
                        if ($this->max_repeat_time === $this->min_repeat_time) {
                            $filterDigital[] = $digital;
                            $aWnMins = array_diff($sWnNumber, $filterDigital);
                            $iDiffCount = count($aWnMins);
                            foreach (array_count_values($aWnMins) as $minCount) {
                                if ($minCount > $this->min_repeat_time) {
                                    $iDiffCount -= $minCount - $this->min_repeat_time;
                                }
                            }
                        } else {
                            $iDiffCount = count(array_diff($sWnNumber, [$digital]));
                        }
                        $mathOnlyCombinCount = Math::combin($count, $this->max_repeat_time);
                        $mathWithChouseCountCombin = Math::combin($iDiffCount, $this->choose_count - $this->max_repeat_time);
                        $mathCombinCount = $mathOnlyCombinCount * $mathWithChouseCountCombin;
                        $prizeCount += $mathCombinCount;
                    }
                }
                $result = $prizeCount;
                break;
            case 'prizeEnumOptionalcombin'://组选单式计奖
                $aWnNumber = str_split($sWnNumber);
                $aBetNumbers = explode($this->splitChar, $sBetNumber);
                $aPosition = str_split($this->sPosition);
                $aSelectWnNumber = [];
                foreach ($aPosition as $p) {
                    if (!isset($aWnNumber[$p])) {
                        return 0;
                    }
                    $aSelectWnNumber[] = $aWnNumber[$p];
                }
                $iHitCounts = 0;
                foreach ($aBetNumbers as $sNumber) {
                    if (strlen($sNumber) !== $this->choose_count) {
                        continue;
                    }
                    $aBetDigits = str_split($sNumber);
                    $aCountValueBetDigits = array_count_values($aBetDigits);
                    if (!$this->_verifyRepeatTimes($aBetDigits)) {
                        continue;
                    }
                    $aBoth = array_intersect($aSelectWnNumber, array_unique(str_split($sNumber)));
                    if (count(array_unique($aBoth)) !== $this->unique_count) {
                        continue;
                    }
                    if ($this->max_repeat_time === $this->min_repeat_time) { //AB  ABC ABCD AABB
                        $iHits = 1;
                        foreach ($aCountValueBetDigits as $digit => $count) {
                            $iHitInWn = count(array_intersect($aSelectWnNumber, [$digit]));//当前数字在开奖号里出现几次
                            $iHits *= Math::combin($iHitInWn, $this->max_repeat_time);
                        }
                        $iHitCounts += $iHits;
                    } else {//AAB
                        $iHits = 1;
                        foreach ($aCountValueBetDigits as $digit => $count) {
                            if (!in_array($count, [1, 2], false)) {
                                continue 2;
                            }
                            $iHitInWn = count(array_intersect($aSelectWnNumber, [$digit]));
                            $chooseCount = $count === $this->max_repeat_time ? $this->max_repeat_time : $this->min_repeat_time;
                            $iHits *= Math::combin($iHitInWn, $chooseCount);
                        }
                        $iHitCounts += $iHits;
                    }
                }
                $result = $iHitCounts;
                break;
            case 'prizeEnumOptionalequal'://返回任选直选单式的中奖注数
                $nWnCount = 0;
                $aWnNumber = str_split($sWnNumber);
                $aBetDigitals = explode($this->splitChar, $sBetNumber);
                $aPostions = str_split($this->sPosition);
                $allPositions = Math::getCombinationToString($aPostions, $this->choose_count);
                foreach ($aBetDigitals as $sDigits) {
                    $aDigits = str_split($sDigits);
                    foreach ($allPositions as $sP) {
                        $current_index = 0;
                        $aSingleBet = [];
                        $aP = explode(',', $sP);
                        for ($i = 0; $i < 5; $i++) {
                            if (!in_array($i, $aP, false)) {
                                $aSingleBet[$i] = '';
                            } else {
                                $aSingleBet[$i] = $aDigits[$current_index] ?? '';
                                $current_index++;
                            }
                        }
                        $aBoth = array_intersect_assoc($aWnNumber, $aSingleBet);
                        if (count($aBoth) === $this->choose_count) {
                            $nWnCount++;
                        }
                    }
                }
                $result = $nWnCount;
                break;
            case 'prizeMixCombinOptionalcombin':
                $aWnNumber = str_split($sWnNumber);
                $aBetNumbers = explode($this->splitChar, $sBetNumber);
                $aPosition = str_split($this->sPosition);
                $aSelectWnNumber = [];
                foreach ($aPosition as $p) {
                    if (!isset($aWnNumber[$p])) {
                        return 0;
                    }
                    $aSelectWnNumber[] = $aWnNumber[$p];
                }
                $iHitCounts = 0;
                foreach ($aBetNumbers as $sNumber) {
                    if (strlen($sNumber) !== $this->choose_count) {
                        continue;
                    }
                    $aBetDigits = str_split($sNumber);
                    $aCountValueBetDigits = array_count_values($aBetDigits);
                    if (!$this->_verifyRepeatTimes($aBetDigits)) {
                        continue;
                    }
                    $aBoth = array_intersect($aSelectWnNumber, array_unique(str_split($sNumber)));
                    if (count(array_unique($aBoth)) !== $this->unique_count) {
                        continue;
                    }
                    if ($this->max_repeat_time === $this->min_repeat_time) { //AB  ABC ABCD AABB
                        $iHits = 1;
                        foreach ($aCountValueBetDigits as $digit => $count) {
                            $iHitInWn = count(array_intersect($aSelectWnNumber, [$digit]));//当前数字在开奖号里出现几次
                            $iHits *= Math::combin($iHitInWn, $this->max_repeat_time);
                        }
                        $iHitCounts += $iHits;
                    } else {//AAB
                        $iHits = 1;
                        foreach ($aCountValueBetDigits as $digit => $count) {
                            if (!in_array($count, [1, 2], false)) {
                                continue 2;
                            }
                            $iHitInWn = count(array_intersect($aSelectWnNumber, [$digit]));
                            $chooseCount = $count === $this->max_repeat_time ? $this->max_repeat_time : $this->min_repeat_time;
                            $iHits *= Math::combin($iHitInWn, $chooseCount);
                        }
                        $iHitCounts += $iHits;
                    }
                }
                $result = $iHitCounts;
                break;
            case 'prizeSeparatedConstitutedOptionalequal': //返回任选直选复式的中奖注数
                $aWnNumber = str_split($sWnNumber);
                $aBetDigitals = explode($this->splitChar, $sBetNumber);
                $aBetDigitsByPostion = [];
                foreach ($aBetDigitals as $i => $sBetDigits) {
                    $aBetDigitsByPostion[$i] = str_split($sBetDigits);
                }
                $iHitCount = 0;
                for ($i = 0, $iMax = count($aBetDigitsByPostion); $i < $iMax; $i++) {
                    if ($aWnNumber[$i] === 0) {
                        $aWnNumber[$i] = '0';
                    }
                    if (in_array($aWnNumber[$i], $aBetDigitsByPostion[$i], false)) {
                        $iHitCount++;
                    }
                }
                $result = $iHitCount >= $this->choose_count ? Math::combin($iHitCount, $this->choose_count) : 0;
                break;
            case 'prizeSumOptionalcombin'://任三组选和值
                $aWnNumber = str_split($sWnNumber);
                $aBetNumbers = explode($this->splitChar, $sBetNumber);
                $aPosition = str_split($this->sPosition);
                $aSelectWnNumber = [];
                foreach ($aPosition as $p) {
                    if (!isset($aWnNumber[$p])) {
                        return 0;
                    }
                    $aSelectWnNumber[] = $aWnNumber[$p];
                }
                $digitsCounts = count($aSelectWnNumber);
                if ($digitsCounts < $this->choose_count) {
                    return 0;
                }
                $aCombins = Math::getCombinationToString($aSelectWnNumber, $this->choose_count);
                $aSumValues = [];
                foreach ($aCombins as $sComb) {
                    $asComb = explode(',', $sComb);
                    if (($this->choose_count === 2) && count(array_unique($asComb)) === 1) {
                        continue;
                    }
                    if (count($asComb) !== $this->choose_count) {
                        continue;
                    }
                    if (count(array_unique($asComb)) !== $this->unique_count) {
                        continue;
                    }
                    $aCountComb = array_flip(array_count_values($asComb));
                    if (!isset($aCountComb[$this->max_repeat_time])) {
                        continue;
                    }
                    if (!isset($aCountComb[$this->min_repeat_time])) {
                        continue;
                    }
                    $iSum = DigitalNumber::getSum($sComb);
                    array_push($aSumValues, $iSum);
                }
                $aBoth = array_intersect($aSumValues, $aBetNumbers);
                $result = count($aBoth);
                break;
            case 'prizeSumOptionalequal'://任选和值计奖
                $aWnNumber = str_split($sWnNumber);
                $aBetNumbers = explode($this->splitChar, $sBetNumber);
                $aPosition = str_split($this->sPosition);
                $aSelectWnNumber = [];
                foreach ($aPosition as $p) {
                    if (!isset($aWnNumber[$p])) {
                        return 0;
                    }
                    $aSelectWnNumber[] = $aWnNumber[$p];
                }
                $digitsCounts = count($aSelectWnNumber);
                if ($digitsCounts < $this->choose_count) {
                    return 0;
                }
                $aCombins = Math::getCombinationToString($aSelectWnNumber, $this->choose_count);
                $aSumValues = [];
                foreach ($aCombins as $sComb) {
                    $asComb = explode(',', $sComb);
                    if (count($asComb) !== $this->choose_count) {
                        continue;
                    }
                    $iSum = DigitalNumber::getSum($sComb);
                    array_push($aSumValues, $iSum);
                }
                $aBoth = array_intersect($aSumValues, $aBetNumbers);
                $result = count($aBoth);
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

    /**
     * @param $sWnNumber
     * @return array
     */
    private function getOptionalWinNumber($sWnNumber): array
    {
        $aWnNumber = str_split($sWnNumber);
        $aPosition = str_split($this->sPosition);
        $aWnDigitals = [];
        foreach ($aPosition as $iPosition) {
            $aWnDigitals[] = $aWnNumber[$iPosition];
        }
        return $aWnDigitals;
    }
}
