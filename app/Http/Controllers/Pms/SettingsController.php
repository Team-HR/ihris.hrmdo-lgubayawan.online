<?php

namespace App\Http\Controllers\Pms;

use App\Http\Controllers\Controller;
use App\Models\Pms\Pcr\PmsPcrStatus;
use App\Models\Pms\Pcr\PmsPcrSupportFunction;
use App\Models\PmsPeriod;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Redirect;

class SettingsController extends Controller
{
    public function index()
    {
        return Inertia::render('Pms/Settings/Index');
    }

    public function periods()
    {
        $periods = PmsPeriod::all();
        $years = [];
        foreach ($periods as $period) {
            if (!in_array($period["year"], $years)) {
                $years[] = $period["year"];
            }
        }

        $_years = [];

        foreach ($years as $year) {
            $_years[] = [
                "year" => $year,
                "firstPeriod" => "January - June",
                "secondPeriod" => "July - December"
            ];
        }

        rsort($_years);

        return Inertia::render('Pms/Settings/Periods', ['years' => $_years]);
    }


    public function support_functions()
    {
        return Inertia::render('Pms/Settings/SupportFunctions');
    }

    public function create_period(Request $request)
    {
        $year = $request->year;

        // check first if year  already exist
        $res = PmsPeriod::where("year", $year)->get();
        $exists = count($res);
        if ($exists < 1) {
            // return "create";
            // PmsPeriod::create([
            //     "period" => "January - June",
            //     "year" => $year,
            // ]);

            $pmsPeriod = new PmsPeriod;
            $pmsPeriod->period = "January - June";
            $pmsPeriod->year = $year;
            $pmsPeriod->save();

            $pmsPeriod = new PmsPeriod;
            $pmsPeriod->period = "July - December";
            $pmsPeriod->year = $year;
            $pmsPeriod->save();

            // PmsPeriod::create([
            //     "period" => "July - December",
            //     "year" => $year,
            // ]);
            return Redirect::back();
        }
        // return "exists";
        return Redirect::back();
    }

    public function support_functions_setup($period_id)
    {

        $period = PmsPeriod::find($period_id);
        return Inertia::render("Pms/Settings/SupportFunctionsPeriod", ["period" => $period]);
    }

    public function support_functions_setup_create_update($period_id, Request $request)
    {
        // return Inertia::render("Pms/Settings/SupportFunctionsPeriodEditor");

        $id = $request->id;
        $support_function = $request->support_function;
        $success_indicator = $request->success_indicator;
        $quality = $request->quality;
        $efficiency = $request->efficiency;
        $timeliness = $request->timeliness;
        $percent = $request->percent;
        $form_type = $request->form_type;

        // check measures if empty
        // $countEmpty = 0;
        // foreach ($request->timeliness as $measure) {
        //     if (!$measure) {
        //         $countEmpty++;
        //     }
        // }
        // return $countEmpty;

        $quality = measureIsNotEmpty($quality) ? $quality : [];
        $efficiency = measureIsNotEmpty($efficiency) ? $efficiency : [];
        $timeliness = measureIsNotEmpty($timeliness) ? $timeliness : [];

        if (!$id) {
            // pms_pcr_support_functions
            $supportFunction = new PmsPcrSupportFunction();
            $supportFunction->pms_period_id = $period_id;
            $supportFunction->support_function = $support_function;
            $supportFunction->success_indicator = $success_indicator;
            $supportFunction->quality = $quality;
            $supportFunction->efficiency = $efficiency;
            $supportFunction->timeliness = $timeliness;
            $supportFunction->percent = $percent;
            $supportFunction->form_type = $form_type["code"];
            $supportFunction->save();
        } else {
            $supportFunction = PmsPcrSupportFunction::find($id);
            $supportFunction->support_function = $support_function;
            $supportFunction->success_indicator = $success_indicator;
            $supportFunction->quality = $quality;
            $supportFunction->efficiency = $efficiency;
            $supportFunction->timeliness = $timeliness;
            $supportFunction->percent = $percent;
            $supportFunction->save();
        }

        return Redirect::back();
    }
    public function get_support_functions($period_id, Request $request)
    {
        $supportFunctions = PmsPcrSupportFunction::where("pms_period_id", $period_id)->where("form_type", $request->form_type["code"])->get();
        return $supportFunctions;
    }
}


function measureIsNotEmpty($arr)
{
    foreach ($arr as $measure) {
        if ($measure) {
            return true;
        }
    }
    return false;
}
