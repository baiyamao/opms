<?php


namespace App\Http\Controllers;

use App\Models\BoyWhoGrowthStandardsHeight;
use App\Models\BoyWhoGrowthStandardsLength;
use App\Models\BoyWhoHeightWeight;
use App\Models\BoyWhoLengthWeight;
use App\Models\GirlWhoGrowthStandardsHeight;
use App\Models\GirlWhoGrowthStandardsLength;
use App\Models\GirlWhoHeightWeight;
use App\Models\GirlWhoLengthWeight;
use Illuminate\Http\Request;


class GrowthEvaluationController extends Controller
{
    public function evaluate(Request $request)
    {
        // 获取请求数据
        $data = $request->all();
        $ageInMonths = $this->calculateAgeInMonths($data['birthday']);
        $gender = $data['gender'];
        $heightType = $data['height_type'];
        $height = $data['height'];
        $weight = $data['weight'];
        // 根据性别，年龄和性别查询标准数据
        if($gender=="boy"){
            if($ageInMonths>24){
                $standards = BoyWhoGrowthStandardsHeight::where('age_month', $ageInMonths)->first();
                $heightWeightStandards = BoyWhoHeightWeight::where('height', $height)->first();
            }elseif($ageInMonths<24){
                $standards = BoyWhoGrowthStandardsLength::where('age_month', $ageInMonths)->first();
                $heightWeightStandards = BoyWhoLengthWeight::where('height', $height)->first();
            }else{
                if($heightType=="height"){
                    $standards = BoyWhoGrowthStandardsHeight::where('age_month', $ageInMonths)->first();
                    $heightWeightStandards = BoyWhoHeightWeight::where('height', $height)->first();
                }else{
                    $standards = BoyWhoGrowthStandardsLength::where('age_month', $ageInMonths)->first();
                    $heightWeightStandards = BoyWhoLengthWeight::where('height', $height)->first();
                }
            }
        }elseif($gender=="girl"){
            if($ageInMonths>24){
                $standards = GirlWhoGrowthStandardsHeight::where('age_month', $ageInMonths)->first();
                $heightWeightStandards = GirlWhoHeightWeight::where('height', $height)->first();
            }elseif($ageInMonths<24){
                $standards = GirlWhoGrowthStandardsLength::where('age_month', $ageInMonths)->first();
                $heightWeightStandards = GirlWhoLengthWeight::where('height', $height)->first();
            }else{
                if($heightType=="height"){
                    $standards = GirlWhoGrowthStandardsHeight::where('age_month', $ageInMonths)->first();
                    $heightWeightStandards = GirlWhoHeightWeight::where('height', $height)->first();
                }else{
                    $standards = GirlWhoGrowthStandardsLength::where('age_month', $ageInMonths)->first();
                    $heightWeightStandards = GirlWhoLengthWeight::where('height', $height)->first();
                }
            }
        }else{
            return response()->json(['error' => '未指定性别'], 500);
        }



        if (!$standards) {
            return response()->json(['error' => 'No standards found for the given age.'], 404);
        }

        // 根据标准数据评估生长发育水平
        $evaluation = $this->evaluateGrowth($ageInMonths,$height, $weight, $standards,$heightWeightStandards);

        return response()->json($evaluation);
    }

    private function calculateAgeInMonths($birthday)
    {
        $birthDate = new \DateTime($birthday);
        $currentDate = new \DateTime();
        $interval = $birthDate->diff($currentDate);
        return $interval->y * 12 + $interval->m;
    }

    private function evaluateGrowth($ageInMonths,$height, $weight, $standards,$heightWeightStandards)
    {
//        "height_minus_3sd": "79.30",
//        "height_minus_2sd": "82.50",
//        "height_minus_1sd": "85.60",
//        "height_0sd": "88.80",
//        "height_plus_1sd": "92.00",
//        "height_plus_2sd": "95.20",
//        "height_plus_3sd
        $height_evaluation="";
        $weight_evaluation="";
        if ($height < $standards->height_minus_2sd){
            $height_evaluation="下";
        }elseif($height<$standards->height_minus_1sd){
            $height_evaluation="中下";
        }elseif($height<$standards->height_0sd){
            $height_evaluation="中-";
        }elseif($height<$standards->height_plus_1sd){
            $height_evaluation="中+";
        }elseif($height<$standards->height_plus_2sd){
            $height_evaluation="中上";
        }else{
            $height_evaluation="上";
        }

        if($standards->weight_0sd==null){
            $weight_evaluation=null;
        }else{
            if ($weight < $standards->weight_minus_2sd){
                $weight_evaluation="下";
            }elseif($weight<$standards->weight_minus_1sd){
                $weight_evaluation="中下";
            }elseif($weight<$standards->weight_0sd){
                $weight_evaluation="中-";
            }elseif($weight<$standards->weight_plus_1sd){
                $weight_evaluation="中+";
            }elseif($weight<$standards->weight_plus_2sd){
                $weight_evaluation="中上";
            }else{
                $weight_evaluation="上";
            }
        }


        return [
            'age_in_months' => $ageInMonths,
            'height' => $height,
            'height_evaluation' => $height_evaluation,
            'weight' => $weight,
            'weight_evaluation' => $weight_evaluation,
            'standards' => $standards,
            'height_weight_standards' => $heightWeightStandards
        ];
    }
}

