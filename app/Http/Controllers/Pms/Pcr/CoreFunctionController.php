<?php

namespace App\Http\Controllers\Pms\Pcr;

use App\Http\Controllers\Controller;
use App\Models\Pms\Pcr\PmsPcrCoreFunctionData;
use App\Models\Pms\Pcr\PmsPcrStrategicFunctionData;
use App\Models\Pms\Pcr\PmsPcrStatus;
use App\Models\PmsPeriod;
use App\Models\Pms\Rsm\PmsRsm;
use App\Models\Pms\Rsm\PmsRsmAssignment;
use App\Models\Pms\Rsm\PmsRsmSuccessIndicator;
use App\Models\Pms\Pcr\PmsPcrCoreFunctionDataCorrection;
use App\Models\SysEmployee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class CoreFunctionController extends Controller
{
    public function show($period_id, $id)
    {
        $sys_employee_id = auth()->user()->sys_employee_id;
        $data = $this->get_row_data($period_id, $id, $sys_employee_id);
        // return $data["rows"];
        return Inertia::render("Pms/Pcr/CoreFunctions", ["period" => $data["period"], "form_status" => $data["form_status"], "rows" => $data["rows"], "total_percentage_weight" => $data["total_percentage_weight"], "total_average_rating" => $data["total_average_rating"]]);
    }

    public function get_row_data($period_id, $pms_pcr_status_id, $sys_employee_id)
    {
        $period = PmsPeriod::find($period_id);

        $assignments = PmsRsmAssignment::where("period_id", $period_id)->where("sys_employee_id", $sys_employee_id)->get();
        # get the success indicator ids
        $success_indicator_ids = [];
        foreach ($assignments as $assignment) {
            $success_indicator_ids[] = $assignment->pms_rsm_success_indicator_id;
        }
        # get mfo ids
        $mfo_ids = [];
        // return $success_indicator_ids;
        foreach ($success_indicator_ids as $success_indicator_id) {
            $mfo = PmsRsmSuccessIndicator::find($success_indicator_id);
            $mfo_id = $mfo->pms_rsm_id;
            if (!in_array($mfo_id, $mfo_ids)) {
                $mfo_ids[] = $mfo_id;
            }
        }
        # get mfo data and parents as well
        $mfos = [];

        foreach ($mfo_ids as $key => $mfo_id) {
            $mfo = PmsRsm::find($mfo_id);
            // if (!in_array($mfo->id, $mfo_ids)) {
            $mfos[] = $mfo;
            $mfos = get_parent($mfos, $mfo->parent_id);
            // }
        }

        // return $mfos;

        # get oredered rows
        $rows = [];
        # sort parents first start -- according to the alphanumeric code

        $sorted_codes = [];
        foreach ($mfos as $key => $mfo) {
            // if (!in_array($mfo["code"], $sorted_codes)) {
            $sorted_codes[] = $mfo["code"];
            // }
        }


        natsort($sorted_codes);

        // return $sorted_codes;

        $sorted_pms_rating_scale_matrices = [];
        foreach ($sorted_codes as $code) {
            foreach ($mfos as $mfo) {
                // if (isset($mfo["code"])) {
                if ($mfo["code"] == $code) {
                    $sorted_pms_rating_scale_matrices[] = $mfo;
                    continue;
                }
                // }
            }
        }
        # sort end

        foreach ($sorted_pms_rating_scale_matrices as $row) {
            $level = get_level(0, $row["parent_id"]);
            $rowspan = 0;
            $si_only = false;
            $success_indicators = get_success_indicators($row["id"]);
            $success_indicators_count = count($success_indicators);
            $rowspan = $success_indicators_count > 1 ? $success_indicators_count : 0;
            $datum = [
                "pms_rsm_id" => $row["id"],
                "parent_id" => $row["parent_id"],
                "level" => $level,
                "rowspan" => $rowspan,
                "mfo_only" => true,
                "si_only" => $si_only,
                "code" => $row["code"],
                "title" => $row["title"],
            ];

            # if no success indicators
            if ($success_indicators_count < 1) {
                $rows[] = $datum;
            } else {
                # if there is/are success indicators
                $datum["mfo_only"] = false;
                foreach ($success_indicators as $key => $success_indicator) {
                    if ($key > 0) {
                        $datum["si_only"] = true;
                    }

                    $quality = $success_indicator["quality"];
                    $efficiency = $success_indicator["efficiency"];
                    $timeliness = $success_indicator["timeliness"];

                    $performance_measures = [];

                    if ($quality) {
                        $performance_measures[] = "Quality";
                    }
                    if ($efficiency) {
                        $performance_measures[] = "Efficiency";
                    }
                    if ($timeliness) {
                        $performance_measures[] = "Timeliness";
                    }

                    $in_charges = PmsRsmAssignment::where("pms_rsm_success_indicator_id", $success_indicator["id"])->get();

                    $success_indicator_datum = [
                        "pms_rsm_success_indicator_id" => $success_indicator["id"],
                        "success_indicator" => $success_indicator["success_indicator"],
                        "performance_measures" => $performance_measures,
                        "quality" => $quality,
                        "efficiency" => $efficiency,
                        "timeliness" => $timeliness,
                        "pms_pcr_core_function_data" => get_pms_pcr_core_function_data($success_indicator["id"])
                    ];
                    $rows[] = array_merge($datum, $success_indicator_datum);
                }
            }
        }

        $period = PmsPeriod::find($period_id);
        $form_status = PmsPcrStatus::find($pms_pcr_status_id);
        $total_percentage_weight = get_total_percentage_weight($rows);
        $total_average_rating = get_total_average_rating($rows);
        $strat = get_strat_data($period_id, $sys_employee_id);

        return ["period" => $period, "form_status" => $form_status, "rows" => $rows, "total_percentage_weight" => $total_percentage_weight, "total_average_rating" => $total_average_rating, "strat_total_percentage_weight" => $strat["total_percentage_weight"], "strat_total_average_rating" => $strat["total_average_rating"]];
    }

    public function create_update($period_id, $id = 0, Request $request)
    {
        $pms_pcr_status = $request->form_status;
        // return  $request;
        $sys_employee_id = $pms_pcr_status["sys_employee_id"];
        // return $sys_employee_id;
        $auth_sys_employees_id = auth()->user()->sys_employee_id;
        // return $auth_sys_employees_id; //auth()->user();
        if (!$request->id) {
            $accomplishment_data = new PmsPcrCoreFunctionData();
            $accomplishment_data->pms_rsm_success_indicator_id = $request->pms_rsm_success_indicator_id;
            $accomplishment_data->pms_period_id = $period_id;
            $accomplishment_data->sys_employee_id = $sys_employee_id;
            $accomplishment_data->actual = $request->actual;
            $accomplishment_data->quality = $request->quality;
            $accomplishment_data->efficiency = $request->efficiency;
            $accomplishment_data->timeliness = $request->timeliness;
            $accomplishment_data->percent = $request->percent;
            $accomplishment_data->remarks = $request->remarks;
            $accomplishment_data->not_applicable = $request->not_applicable ? true : false;
        }
        # else do edit
        else {
            # check if user is supervisor
            # check if user is department head
            # check if user is pmt

            # check if user is employee        
            if ($sys_employee_id == $auth_sys_employees_id) {
                $accomplishment_data = PmsPcrCoreFunctionData::find($request->id);
                $accomplishment_data->pms_rsm_success_indicator_id = $request->pms_rsm_success_indicator_id;
                $accomplishment_data->pms_period_id = $period_id;
                $accomplishment_data->sys_employee_id = $sys_employee_id;
                $accomplishment_data->actual = $request->actual;
                $accomplishment_data->quality = $request->quality;
                $accomplishment_data->efficiency = $request->efficiency;
                $accomplishment_data->timeliness = $request->timeliness;
                $accomplishment_data->percent = $request->percent;
                $accomplishment_data->remarks = $request->remarks;
                $accomplishment_data->not_applicable = $request->not_applicable ? true : false;
            }

            # check if user is supervisor
            # check if user is department head
            # check if user is pmt

            elseif ($auth_sys_employees_id == $pms_pcr_status['immediate_supervisor']) {
                $accomplishment_data = new PmsPcrCoreFunctionDataCorrection();
                $accomplishment_data->pms_pcr_core_function_data_id = $request->id;

                $accomplishment_data->actual = $request->actual;
                $accomplishment_data->quality = $request->quality;
                $accomplishment_data->efficiency = $request->efficiency;
                $accomplishment_data->timeliness = $request->timeliness;
                $accomplishment_data->percent = $request->percent;
                $accomplishment_data->remarks = $request->remarks;
                $accomplishment_data->not_applicable = $request->not_applicable ? true : false;
                $accomplishment_data->created_by_sys_employee_id = $auth_sys_employees_id;
                $accomplishment_data->created_by_type = "supervisor";
            }
        }
        $accomplishment_data->save();
        return Redirect::back();
    }

    public function delete_accomplishment($period_id, $status_id, $accomplishment_id)
    {
        $accomplishment = PmsPcrCoreFunctionData::find($accomplishment_id);
        $accomplishment->delete();
        return Redirect::back();
    }

    /*
        Core functions edtor 
    */
    public function get_core_functions_rows($period_id, $pms_pcr_status_id, $sys_employee_id)
    {

        $period = PmsPeriod::find($period_id);

        $assignments = PmsRsmAssignment::where("period_id", $period_id)->where("sys_employee_id", $sys_employee_id)->get();
        # get the success indicator ids
        $success_indicator_ids = [];
        foreach ($assignments as $assignment) {
            $success_indicator_ids[] = $assignment->pms_rsm_success_indicator_id;
        }
        # get mfo ids
        $mfo_ids = [];
        // return $success_indicator_ids;
        foreach ($success_indicator_ids as $success_indicator_id) {
            $mfo = PmsRsmSuccessIndicator::find($success_indicator_id);
            $mfo_id = $mfo->pms_rsm_id;
            if (!in_array($mfo_id, $mfo_ids)) {
                $mfo_ids[] = $mfo_id;
            }
        }
        # get mfo data and parents as well
        $mfos = [];

        foreach ($mfo_ids as $key => $mfo_id) {
            $mfo = PmsRsm::find($mfo_id);
            // if (!in_array($mfo->id, $mfo_ids)) {
            $mfos[] = $mfo;
            $mfos = get_parent($mfos, $mfo->parent_id);
            // }
        }

        // return $mfos;

        # get oredered rows
        $rows = [];
        # sort parents first start -- according to the alphanumeric code

        $sorted_codes = [];
        foreach ($mfos as $key => $mfo) {
            // if (!in_array($mfo["code"], $sorted_codes)) {
            $sorted_codes[] = $mfo["code"];
            // }
        }


        natsort($sorted_codes);

        // return $sorted_codes;

        $sorted_pms_rating_scale_matrices = [];
        foreach ($sorted_codes as $code) {
            foreach ($mfos as $mfo) {
                // if (isset($mfo["code"])) {
                if ($mfo["code"] == $code) {
                    $sorted_pms_rating_scale_matrices[] = $mfo;
                    continue;
                }
                // }
            }
        }
        # sort end

        foreach ($sorted_pms_rating_scale_matrices as $row) {
            $level = get_level(0, $row["parent_id"]);
            $rowspan = 0;
            $si_only = false;
            $success_indicators = get_success_indicators($row["id"]);
            $success_indicators_count = count($success_indicators);
            $rowspan = $success_indicators_count > 1 ? $success_indicators_count : 0;
            $datum = [
                "pms_rsm_id" => $row["id"],
                "parent_id" => $row["parent_id"],
                "level" => $level,
                "rowspan" => $rowspan,
                "mfo_only" => true,
                "si_only" => $si_only,
                "code" => $row["code"],
                "title" => $row["title"],
            ];

            # if no success indicators
            if ($success_indicators_count < 1) {
                $rows[] = $datum;
            } else {
                # if there is/are success indicators
                $datum["mfo_only"] = false;
                foreach ($success_indicators as $key => $success_indicator) {
                    if ($key > 0) {
                        $datum["si_only"] = true;
                    }

                    $quality = $success_indicator["quality"];
                    $efficiency = $success_indicator["efficiency"];
                    $timeliness = $success_indicator["timeliness"];

                    $performance_measures = [];

                    if ($quality) {
                        $performance_measures[] = "Quality";
                    }
                    if ($efficiency) {
                        $performance_measures[] = "Efficiency";
                    }
                    if ($timeliness) {
                        $performance_measures[] = "Timeliness";
                    }

                    $in_charges = PmsRsmAssignment::where("pms_rsm_success_indicator_id", $success_indicator["id"])->get();

                    $success_indicator_datum = [
                        "pms_rsm_success_indicator_id" => $success_indicator["id"],
                        "success_indicator" => $success_indicator["success_indicator"],
                        "performance_measures" => $performance_measures,
                        "quality" => $quality,
                        "efficiency" => $efficiency,
                        "timeliness" => $timeliness,
                        "pms_pcr_core_function_data" => get_pms_pcr_core_function_data($success_indicator["id"])
                    ];
                    $rows[] = array_merge($datum, $success_indicator_datum);
                }
            }
        }

        $period = PmsPeriod::find($period_id);
        // $form_status = PmsPcrStatus::find($pms_pcr_status_id);
        $total_percentage_weight = get_total_percentage_weight($rows);
        $total_average_rating = get_total_average_rating($rows);

        return ["period" => $period, "rows" => $rows, "total_percentage_weight" => $total_percentage_weight, "total_average_rating" => $total_average_rating];
    }
}

function get_pms_pcr_core_function_data($pms_rsm_success_indicator_id)
{
    $pms_pcr_core_function_data = PmsPcrCoreFunctionData::where("pms_rsm_success_indicator_id", $pms_rsm_success_indicator_id)->first();
    if (!$pms_pcr_core_function_data) return null;
    $scores = [
        $pms_pcr_core_function_data["quality"],
        $pms_pcr_core_function_data["efficiency"],
        $pms_pcr_core_function_data["timeliness"],
    ];
    $count = 0;
    $total = 0;
    foreach ($scores as $value) {
        if ($value && $value != 0) {
            $total += $value;
            $count++;
        }
    }
    // to avoid division by zero
    if ($count > 0) {
        $average = $total / $count;
        $percent = $pms_pcr_core_function_data["percent"] / 100;
        $average = $average * $percent;
        $average = number_format($average, 2);
        $pms_pcr_core_function_data["average"] = $average;
    }
    return $pms_pcr_core_function_data;
}


function get_parent($mfos, $parent_id)
{
    $mfo = PmsRsm::find($parent_id);

    // check if parent already exists
    if (!$mfo) return $mfos;

    foreach ($mfos as $value) {
        if ($value["id"] === $mfo->id) {
            return $mfos;
        }
    }

    $mfos[] = $mfo;
    if ($mfo->parent_id) {
        $mfos[] = get_parent($mfos, $mfo->parent_id);
    }

    return $mfos;
}

# get level iterator
function get_level($level = 0, $parent_id)
{
    $rsm = PmsRsm::find($parent_id);
    if ($rsm) {
        $level = $level + 1;
        $level = get_level($level, $rsm["parent_id"]);
    }
    return $level;
}

# count rowspan 
function get_rowspan($id)
{
    $count = PmsRsmSuccessIndicator::where("pms_rsm_id", $id)->count();
    return $count;
}

# get success indicators of the mfo/pap
function get_success_indicators($id)
{
    $pms_rating_scale_matrix_success_indicators = PmsRsmSuccessIndicator::where("pms_rsm_id", $id)->get();
    return $pms_rating_scale_matrix_success_indicators;
}

function get_total_percentage_weight($rows)
{
    $total_percentage_weight = 0;

    foreach ($rows as $row) {
        if (isset($row["pms_pcr_core_function_data"])) {
            $total_percentage_weight += $row["pms_pcr_core_function_data"]["percent"];
        }
    }

    return $total_percentage_weight;
}

function get_total_average_rating($rows)
{
    $total_average_rating = 0;

    foreach ($rows as $row) {
        if (isset($row["pms_pcr_core_function_data"])) {
            $total_average_rating += $row["pms_pcr_core_function_data"]["average"];
        }
    }

    return number_format($total_average_rating, 2);
}

function get_strat_data($period_id, $sys_employee_id)
{
    $strat = PmsPcrStrategicFunctionData::where("pms_period_id", $period_id)->where("sys_employee_id", $sys_employee_id)->first();

    if (isset($strat->percent) && isset($strat->final_numerical_rating)) {
        $data = [
            "total_percentage_weight" => $strat->percent,
            "total_average_rating" => number_format($strat->final_numerical_rating * ($strat->percent / 100), 2)
        ];
    } else {
        $data = [
            "total_percentage_weight" => null,
            "total_average_rating" => null
        ];
    }

    return $data;
}
