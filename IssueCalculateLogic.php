<?php
/**
 * Created by PhpStorm.
 * author: Harris
 * Date: 9/4/2019
 * Time: 5:47 PM
 */

namespace App\Models\Game\Lottery\Logics\SeriesLogic;

use App\Models\Game\Lottery\LotteryBasicWay;
use App\Models\Game\Lottery\LotteryIssue;
use App\Models\Game\Lottery\LotteryList;
use App\Models\Game\Lottery\LotterySeriesMethod;
use App\Models\Game\Lottery\LotterySeriesWay;
use App\Models\Game\Lottery\LotteryTraceList;
use App\Models\LotteryTrace;
use App\Models\Project;
use App\Models\User\UserCommissions;
use App\Models\User\Fund\FrontendUsersAccount;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

trait IssueCalculateLogic
{
    private static $projectInsertCompileColumn = [
        'user_id',
        'username',
        'top_id',
        'rid',
        'parent_id',
        'is_tester',
        'series_id',
        'lottery_sign',
        'method_sign',
        'method_group',
        'method_name',
        'user_prize_group',
        'bet_prize_group',
        'mode',
        'times',
        'price',
        'total_cost',
        'bet_number',
        'issue',
        'prize_set',
        'ip',
        'proxy_ip',
        'bet_from',
        'challenge_prize',
        'challenge',
    ];

    /**
     * @param  string  $lottery_id
     * @param  string  $issue_no
     * @return void
     */
    public static function calculateEncodedNumber(string $lottery_id, string $issue_no): void
    {
        $oIssue = self::where([
            ['issue', '=', $issue_no],
            ['lottery_id', '=', $lottery_id],
        ])->first();
        if (($oIssue !== null) && $oIssue->lottery()->exists()) {
            $oLottery = $oIssue->lottery;
            if ($oLottery->serie()->exists()) {
//                $oSeries = $oLottery->serie;
                //###############################
                if ($oIssue->status_calculated === LotteryIssue::ISSUE_CODE_STATUS_FINISHED) {
                    Log::channel('issues')->info('Finished Calculating');
                } elseif ($oIssue->status_calculated === LotteryIssue::ISSUE_CODE_STATUS_CANCELED) {
                    Log::channel('issues')->info('Winning Number Canceled, Set To Finished');
                } else {
                    self::handleUnencodedProjects($oIssue, $lottery_id, $oLottery);
                    self::handleTraceWithCurrentIssue($oLottery); //??????????????????????????????????????????project??????
                }
            }
        } else {
            Log::channel('issues')->info('Issue Missing');
        }
    }

    /**
     * @param  LotteryIssue  $oIssue
     * @param  string  $lottery_id
     * @param  LotteryList  $oLottery
     */
    private static function handleUnencodedProjects(LotteryIssue $oIssue, $lottery_id, LotteryList $oLottery): void
    {
        if ($oIssue->projects()->exists()) {
            self::handleWithOfficialCode($oIssue, $lottery_id, $oLottery);
        } else {
            Log::channel('issues')->info('no Project');
        }
    }

    /**
     * @param  LotteryIssue  $oIssue
     * @param $lottery_id
     * @param  LotteryList  $oLottery
     */
    private static function handleWithOfficialCode(LotteryIssue $oIssue, $lottery_id, LotteryList $oLottery): void
    {
        if ($oIssue->official_code !== null) {
            $oProjects = $oIssue->projects->where('lottery_sign', $lottery_id)->fresh();
            $aWnNumberOfMethods = [];
            try {
                $aWnNumberOfMethods = self::getWnNumberOfSeriesMethods(
                    $oLottery,
                    $oIssue->official_code
                ); //wn_number
            } catch (Exception $e) {
                Log::error('Winning Number Calculation on error');
                Log::error($e->getMessage().$e->getTraceAsString());
            }
            self::handleWithBasicWays($oLottery, $aWnNumberOfMethods, $oProjects, $oIssue);
        } else {
            Log::channel('issues')->info('there has no issue code');
        }
    }

    /**
     * @param  LotteryList  $oLottery
     * @param  array  $aWnNumberOfMethods
     * @param  Collection  $oProjects
     * @param  LotteryIssue  $oIssue
     */
    private static function handleWithBasicWays(
        LotteryList $oLottery,
        array $aWnNumberOfMethods,
        Collection $oProjects,
        LotteryIssue $oIssue
    ): void {
        if ($oLottery->basicways()->exists()) {
            $oBasicWays = $oLottery->basicways;
            foreach ($oBasicWays as $oBasicWay) {
                self::handleWithSeriesWays($oBasicWay, $oLottery, $aWnNumberOfMethods, $oProjects, $oIssue);
            }
        }
    }

    /**
     * @param  LotteryBasicWay  $oBasicWay
     * @param  LotteryList  $oLottery
     * @param  array  $aWnNumberOfMethods
     * @param  Collection  $oProjects
     * @param  LotteryIssue  $oIssue
     */
    private static function handleWithSeriesWays(
        LotteryBasicWay $oBasicWay,
        LotteryList $oLottery,
        array $aWnNumberOfMethods,
        Collection $oProjects,
        LotteryIssue $oIssue
    ): void {
        $oSeriesWays = $oBasicWay->seriesWays()->where(
            'series_code',
            $oLottery->series_id
        )
            ->where(
                'lottery_method_id',
                '!=',
                null
            )->get();
        foreach ($oSeriesWays as $oSeriesWay) {
            $oSeriesWay->setWinningNumber($aWnNumberOfMethods);
            self::handleByProjectFiltered($oProjects, $oSeriesWay, $oIssue);
            //self::handleByProjectFiltered($oProjects, $oSeriesWay, $oIssue, $oLottery);
        }
    }

    /**
     * @param  Collection  $oProjects
     * @param  LotterySeriesWay  $oSeriesWay
     * @param  LotteryIssue  $oIssue
     */
    private static function handleByProjectFiltered(
        Collection $oProjects,
        LotterySeriesWay $oSeriesWay,
        LotteryIssue $oIssue
        //LotteryList $oLottery
    ): void {
        $oProjectsToCalculate = $oProjects->where('status',Project::STATUS_NORMAL)
            ->where('method_sign',$oSeriesWay->lottery_method_id);

        if ($oProjectsToCalculate->count() >= 1) {
            //??????????????????
            if ($oSeriesWay->WinningNumber === false) {
                foreach ($oProjectsToCalculate as $project) {
                    $project->setFail($oIssue->official_code);
                    self::startTrace($project); //self::startTrace($oLottery, $project);
                    UserCommissions::sendCommissions($project->id);
                }
            } else {
                //???????????????
                self::calculateWinningNumber($oSeriesWay, $oProjectsToCalculate, $oIssue);
                //self::calculateWinningNumber($oSeriesWay, $oProjectsToCalculate, $oLottery, $oIssue);
            }
        } else {
            Log::channel('issues')->info('Dont have projects');
        }
    }

    /**
     * @param  LotterySeriesWay  $oSeriesWay
     * @param  Collection  $oProjectsToCalculate
     * @param  LotteryIssue  $oIssue
     */
    private static function calculateWinningNumber(
        LotterySeriesWay $oSeriesWay,
        Collection $oProjectsToCalculate,
        //LotteryList $oLottery,
        LotteryIssue $oIssue
    ): void {
        //???????????????
        $sWnNumber = current($oSeriesWay->WinningNumber);
        if ($oSeriesWay->basicWay()->exists()) {
            $oBasicWay = $oSeriesWay->basicWay;
            foreach ($oProjectsToCalculate as $project) {
                self::handleBySeriesHasWinningNumber($oBasicWay, $oSeriesWay, $project, $oIssue, $sWnNumber);
                self::startTrace($project); //self::startTrace($oLottery, $project);
                UserCommissions::sendCommissions($project->id);
            }
        } else {
            Log::channel('issues')->info('no basic way');
        }
    }

    /**
     * @param  LotteryBasicWay  $oBasicWay
     * @param  LotterySeriesWay  $oSeriesWay
     * @param  Project  $project
     * @param  LotteryIssue  $oIssue
     * @param  mixed  $sWnNumber
     */
    private static function handleBySeriesHasWinningNumber(
        LotteryBasicWay $oBasicWay,
        LotterySeriesWay $oSeriesWay,
        Project $project,
        LotteryIssue $oIssue,
        $sWnNumber
    ): void {
        try {
            $aPrized = $oBasicWay->checkPrize(
                $oSeriesWay,
                $project,
                $sPostion = null
            );
        } catch (Exception $e) {
            $aPrized = [];
            Log::error('Prize Checking on error');
            Log::error($e->getMessage().$e->getTraceAsString());
        }
        $strlog = 'aPrized is '.json_encode($aPrized, JSON_PRETTY_PRINT);
        Log::channel('issues')->info($strlog);
        $result = false;
        try {
            $result = $project->setWon(
                $oIssue->official_code,
                $sWnNumber,
                $aPrized
            ); //@todo Trace
        } catch (Exception $e) {
            Log::error('Set Won on error');
            Log::error($e->getMessage().$e->getTraceAsString());
        }
        if ($result !== true) {
            Log::channel('issues')->info($result);
        }
    }

    /**
     * @param  LotteryList  $oLottery
     * @param  string  $sFullWnNumber
     * @param  bool  $bNameKey
     * @return array
     */
    public static function getWnNumberOfSeriesMethods(
        LotteryList $oLottery,
        string $sFullWnNumber,
        $bNameKey = false
    ): array {
        $oSeriesMethods = LotterySeriesMethod::where('series_code', '=', $oLottery->series_id)->get();
        $aWnNumbers = [];
        $sKeyColumn = $bNameKey ? 'name' : 'id';
        foreach ($oSeriesMethods as $oSeriesMethod) {
            $aWnNumbers[$oSeriesMethod->{$sKeyColumn}] = $oSeriesMethod->getWinningNumber($sFullWnNumber);
        }
        return $aWnNumbers;
    }

    /**
     * public static function startTrace(LotteryList $oLottery, Project $project): void
     * $oLottery??????????????????????????? ????????????
     * @param  Project  $project
     */
    public static function startTrace(Project $project): void
    {
        $oProject = $project->fresh();
        $oTrace = self::getTraceItems($oProject);
        if ($oTrace !== null) {
            ++$oTrace->finished_issues; //???????????????+1
            $oTrace->finished_amount += $oProject->total_cost; //???????????????
            $oTrace->finished_bonus += $oProject->bonus; //?????????????????????
            if ($oProject->status >= Project::STATUS_WON && $oTrace->win_stop === 1) {
                self::traceWinStop($oTrace, $oProject);//????????????
            //} elseif ($oProject->status > Project::STATUS_NORMAL && $first < 1) {//????????????????????????
            } elseif ($oProject->status > Project::STATUS_NORMAL) { //??????????????????   ??????????????????????????????????????????????????? $first??????
                self::traceNormalConditionUpdate($oTrace, $oProject);
            }
        }
        //self::handleTraceWithCurrentIssue($oLottery, $oProject);?????????????????????????????????????????????project?????????????????????????????????
    }

    /**
     * private static function handleTraceWithCurrentIssue(LotteryList $oLottery, Project $oProject): void
     * ??????????????????????????????   ???????????????$oProject->user_id
     * @param  LotteryList  $oLottery
     * @param  Project  $oProject
     */
    public static function handleTraceWithCurrentIssue(LotteryList $oLottery)
    {
        //get current issues
        $currentIssue = LotteryIssue::getCurrentIssue($oLottery->en_name);
        //then check if there have tracelists or not
        if ($currentIssue !== null && $currentIssue->tracelists()->exists()) {
            //select with criterias
            $oTraceListEloq = $currentIssue->tracelists()->where([
                ['lottery_sign', '=', $oLottery->en_name],
                ['status', '=', LotteryTraceList::STATUS_WAITING],
                //['user_id', '=', $oProject->user_id],
            ])
                ->get();
            Log::channel('trace')->info($oTraceListEloq->toJson());
            //check if it is not empty then do other logics
            if (!$oTraceListEloq->isEmpty()) {
                return self::addProjectDataByTrace($oTraceListEloq);
            }
        } else {
            Log::channel('issues')->info('no issue or no tracelists');
        }
    }

    /**
     * @param  Collection  $oTraceListEloq
     */
    private static function addProjectDataByTrace(Collection $oTraceListEloq)
    {
        //loop ,select and then insert to project table and update the trace detail table
        foreach ($oTraceListEloq as $oTraceList) {
            if ($oTraceList->trace()->exists()) {
                $oTrace = $oTraceList->trace;
                if ($oTraceList->status === LotteryTraceList::STATUS_WAITING) {
//???????????????????????????
                    FrontendUsersAccount::traceThaw($oTraceList); //?????????????????????
                     //????????? project ???
                    $projectData = self::getProjectData($oTraceList);
                    $project = new Project();
                    $project->fill($projectData);
                    $project->save();
                    $oTraceList->project_id = $project->id;
                    $oTraceList->project_serial_number = $project->serial_number;
                    $oTraceList->status = LotteryTraceList::STATUS_RUNNING;
                    $oTraceList->save(); // TraceList
                    $TraceDetailUpdateData = ['now_issue' => $oTraceList->issue,];
                    $oTrace->update($TraceDetailUpdateData); // Trace
                    return FrontendUsersAccount::betDeduction($project); //????????????
                }
            } else {
                Log::channel('issues')->info('??????????????????????????????');
            }
        }
    }

    public static function getProjectData($oTraceList)
    {
        return [
            'serial_number' => Project::getProjectSerialNumber(),
            'user_id' => $oTraceList->user_id,
            'username' => $oTraceList->username,
            'top_id' => $oTraceList->top_id,
            'rid' => $oTraceList->rid,
            'parent_id' => $oTraceList->parent_id,
            'is_tester' => $oTraceList->is_tester,
            'series_id' => $oTraceList->series_id,
            'lottery_sign' => $oTraceList->lottery_sign,
            'method_sign' => $oTraceList->method_sign,
            'method_group' => $oTraceList->method_group,
            'method_name' => $oTraceList->method_name,
            'user_prize_group' => $oTraceList->user_prize_group,
            'bet_prize_group' => $oTraceList->bet_prize_group,
            'mode' => $oTraceList->mode,
            'times' => $oTraceList->times,
            'price' => $oTraceList->single_price,
            'total_cost' => $oTraceList->total_price,
            'bet_number' => $oTraceList->bet_number,
            'issue' => $oTraceList->issue,
            'prize_set' => $oTraceList->prize_set,
            'ip' => $oTraceList->ip,
            'proxy_ip' => $oTraceList->proxy_ip,
            'bet_from' => $oTraceList->bet_from,
            'time_bought' => time(),
            //'status_flow' => Project::STATUS_FLOW_TRACE,
        ];
    }
    /**
     * ???????????????????????? project ???
     * @param  LotteryTraceList  $oTraceList
     * @return array
     */
    private static function getProjectDataFromTraceLists(LotteryTraceList $oTraceList): array
    {
        $projectData = [];
        foreach (self::$projectInsertCompileColumn as $column) {
            $projectData[$column] = $oTraceList->$column;
        }
        return $projectData;
    }

    /**
     * @param  LotteryTrace  $oTrace
     * @param  Project  $oProject
     */
    private static function traceNormalConditionUpdate(LotteryTrace $oTrace, Project $oProject): void
    {
        $waitingNum = $oTrace->traceLists->where('status', LotteryTraceList::STATUS_WAITING)->count();
        if ($waitingNum === 0) {
            $oTrace->status = LotteryTrace::STATUS_FINISHED;//???????????????????????????????????????????????????
        }
        $oTrace->save();
        //update TraceLists with Project
        if ($oProject->tracelist()->exists()) {
            $oTraceListFromProject = $oProject->tracelist;
            $oTraceListFromProject->status = LotteryTraceList::STATUS_FINISHED;
            // $oTraceListFromProject->project_id = $oProject->id;    //??????TraceList???????????????ProjectId
            // $oTraceListFromProject->project_serial_number = $oProject->serial_number;    //??????TraceList???????????????ProjectNumber
            $oTraceListFromProject->save();
        }
    }

    /**
     * @param  LotteryTrace  $oTrace
     * @param  Project  $oProject
     */
    private static function traceWinStop(LotteryTrace $oTrace, Project $oProject): void
    {
        //Remaining TraceList to stop continuing
        $oTraceListToUpdate = $oTrace->traceRunningLists();
        $oTraceList = $oTraceListToUpdate->get();
        $traceListStopData = [
            'status' => LotteryTraceList::STATUS_WIN_STOPED,
        ];
        $oTraceListToUpdate->update($traceListStopData);
        //Update TraceDetail tables
        $oTrace->status = LotteryTrace::STATUS_WIN_STOPED;
        $oTrace->canceled_issues += $oTraceList->count();
        $oTrace->canceled_amount += $oTraceList->sum('total_price');
        $oTrace->stop_issue = $oProject->issue;
        $oTrace->stop_time = time();
        $oTrace->save();
        //update TraceLists with Project
        if ($oProject->tracelist()->exists()) {
            $oTraceListFromProject = $oProject->tracelist;
            $oTraceListFromProject->status = LotteryTraceList::STATUS_FINISHED;
            $oTraceListFromProject->save();
        }
        if ($oTraceList->count() > 0) {
            FrontendUsersAccount::traceWinStopAccount($oProject, $oTraceList->sum('total_price'), $oTrace);//??????????????????????????????
        }
    }

    /**
     * private static function getTraceItems(Project $oProject, &$first)
     * ??????????????????   ???????????????????????????????????????????????????   ??????$first????????????
     * @param  Project  $oProject
     * @return mixed
     */
    private static function getTraceItems(Project $oProject)
    {
        $oTrace = LotteryTrace::where([
            ['user_id', '=', $oProject->user_id],
            ['lottery_sign', '=', $oProject->lottery_sign],
            ['now_issue', '=', $oProject->issue],
            ['bet_number', '=', $oProject->bet_number],
        ])->first();
        if ($oTrace === null) {
            $oTrace = LotteryTrace::where([
                ['user_id', '=', $oProject->user_id],
                ['lottery_sign', '=', $oProject->lottery_sign],
                ['bet_number', '=', $oProject->bet_number],
            ])->first();
        }
        return $oTrace;
    }
}
