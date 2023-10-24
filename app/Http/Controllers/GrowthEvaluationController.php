<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{BoyWhoGrowthStandardsHeight, BoyWhoGrowthStandardsLength, BoyWhoHeightWeight, BoyWhoLengthWeight,
    GirlWhoGrowthStandardsHeight, GirlWhoGrowthStandardsLength, GirlWhoHeightWeight, GirlWhoLengthWeight
};

class GrowthEvaluationController extends Controller
{
    public function evaluate(Request $request)
    {
        // 获取请求数据并进行验证
        $data = $request->validate([
            'birthday' => 'required|date',
            'gender' => 'required|in:boy,girl',
            'height_type' => 'required',
            'height' => 'required|numeric',
            'weight' => 'required|numeric',
        ]);

        $ageInMonths = $this->calculateAgeInMonths($data['birthday']);
        $gender = $data['gender'];
        $heightType = $data['height_type'];
        $height = $data['height'];
        $weight = $data['weight'];

        // 根据性别，年龄和性别查询标准数据
        $standards = $this->getGrowthStandards($gender, $ageInMonths, $heightType, $height);

        //根据身高查询体重标准
        $standardHeight = $this->getClosestNumber($height);
        $heightWeightStandards = $this->getHeightWeightStandards($gender, $ageInMonths, $heightType, $standardHeight);

        if (!$standards) {
            return response()->json(['error' => '月龄超过发育标准数据范围！'], 404);
        }


//        if (!$heightWeightStandards) {
//            return response()->json(['error' => '提供的身长身高值超出发育标准数据范围！'], 404);
//        }

        // 根据标准数据评估生长发育水平
        $evaluation = $this->evaluateGrowth($ageInMonths, $height, $weight, $standards,$heightWeightStandards);

        $evaluation['standards']['gender'] = $gender;
        $evaluation['standards']['height_type'] = $heightType;

        return response()->json($evaluation);
    }

    private function calculateAgeInMonths($birthday)
    {
        $birthDate = new \DateTime($birthday);
        $currentDate = new \DateTime();
        $interval = $birthDate->diff($currentDate);
        return $interval->y * 12 + $interval->m;
    }

    private function getGrowthStandards($gender, $ageInMonths, $heightType, $height)
    {
        $standardsModel = null;
        if($ageInMonths == 24){
            if ($gender == "boy") {
                $standardsModel = ($heightType == "height") ? BoyWhoGrowthStandardsHeight::class : BoyWhoGrowthStandardsLength::class;
            } elseif ($gender == "girl") {
                $standardsModel = ($heightType == "height") ? GirlWhoGrowthStandardsHeight::class : GirlWhoGrowthStandardsLength::class;
            } else {
                return null; // 未指定性别
            }
        }else{
            if ($gender == "boy") {
                $standardsModel = ($ageInMonths > 24) ? BoyWhoGrowthStandardsHeight::class : BoyWhoGrowthStandardsLength::class;
            } elseif ($gender == "girl") {
                $standardsModel = ($ageInMonths > 24) ? GirlWhoGrowthStandardsHeight::class : GirlWhoGrowthStandardsLength::class;
            } else {
                return null; // 未指定性别
            }
        }

        return $standardsModel::where('age_month', $ageInMonths)->first();
    }

    private function evaluateGrowth($ageInMonths, $height, $weight, $standards,$heightWeightStandards)
    {
        $height_evaluation = $this->evaluateMeasurement($height, $standards->height_minus_3sd, $standards->height_minus_2sd, $standards->height_minus_1sd, $standards->height_0sd, $standards->height_plus_1sd, $standards->height_plus_2sd, $standards->height_plus_3sd);
        $weight_evaluation = ($standards->weight_0sd == null) ? null : $this->evaluateMeasurement($weight, $standards->weight_minus_3sd,$standards->weight_minus_2sd, $standards->weight_minus_1sd, $standards->weight_0sd, $standards->weight_plus_1sd, $standards->weight_plus_2sd,$standards->weight_plus_3sd);

        //身高别体重评价
        if($heightWeightStandards){
            $height_weight_evaluation = $this->evaluateMeasurement($weight,$heightWeightStandards->weight_minus_3sd,$heightWeightStandards->weight_minus_2sd, $heightWeightStandards->weight_minus_1sd, $heightWeightStandards->weight_0sd, $heightWeightStandards->weight_plus_1sd, $heightWeightStandards->weight_plus_2sd,$heightWeightStandards->weight_plus_3sd);
        }else{
            $height_weight_evaluation =null;
        }

        $BMI=$this->calculateBMI($weight,$height);
        $BMI_evaluation = $this->evaluateMeasurement($BMI,$standards->bmi_minus_3sd,$standards->bmi_minus_2sd, $standards->bmi_minus_1sd, $standards->bmi_0sd, $standards->bmi_plus_1sd, $standards->bmi_plus_2sd,$standards->bmi_plus_3sd);

        $nutrition = [];

        switch ($weight_evaluation) {
            case "下":
                $nutrition['nutrition_weight_evaluation'] = "低体重";
                break;
            case "下下":
                $nutrition['nutrition_weight_evaluation'] = "重度低体重";
                break;
        }

        switch ($height_evaluation) {
            case "下":
                $nutrition['nutrition_height_evaluation'] = "生长迟缓";
                break;
            case "下下":
                $nutrition['nutrition_height_evaluation'] = "重度生长迟缓";
                break;
        }

        switch ($height_weight_evaluation) {
            case "上上":
                $nutrition['nutrition_height_weight_evaluation'] = "重度肥胖";
                break;
            case "上":
                $nutrition['nutrition_height_weight_evaluation'] = "肥胖";
                break;
            case "中上":
                $nutrition['nutrition_height_weight_evaluation'] = "超重";
                break;
            case "下":
                $nutrition['nutrition_height_weight_evaluation'] = "消瘦";
                break;
            case "下下":
                $nutrition['nutrition_height_weight_evaluation'] = "重度消瘦";
                break;
            case null:
                $nutrition['nutrition_height_weight_evaluation'] = null;
                break;
        }

        switch ($BMI_evaluation) {
            case "上上":
                $nutrition['nutrition_bmi_evaluation'] = "重度肥胖";
                break;
            case "上":
                $nutrition['nutrition_bmi_evaluation'] = "肥胖";
                break;
            case "中上":
                $nutrition['nutrition_bmi_evaluation'] = "超重";
                break;
            case "下":
                $nutrition['nutrition_bmi_evaluation'] = "消瘦";
                break;
            case "下下":
                $nutrition['nutrition_bmi_evaluation'] = "重度消瘦";
                break;
        }



        return [
            'age_in_months' => $ageInMonths,
            'height' => $height,
            'height_evaluation' => $height_evaluation,
            'weight' => $weight,
            'weight_evaluation' => $weight_evaluation,
            'standards' => $standards,
            'height_weight_evaluation' => $height_weight_evaluation,
            'height_weight_standards' => $heightWeightStandards,
            'bmi' => $BMI,
            'bmi_evaluation' =>$BMI_evaluation,
        ]+$nutrition;
    }

    private function evaluateMeasurement($measurement, $minus_3sd,$minus_2sd, $minus_1sd, $sd_0, $plus_1sd, $plus_2sd, $plus_3sd)
    {
        if ($measurement < $minus_3sd) {
            return "下下";
        } elseif ($measurement < $minus_2sd) {
            return "下";
        } elseif ($measurement < $minus_1sd) {
            return "中下";
        } elseif ($measurement < $sd_0) {
            return "中-";
        } elseif ($measurement < $plus_1sd) {
            return "中+";
        } elseif ($measurement < $plus_2sd) {
            return "中上";
        } elseif ($measurement < $plus_3sd) {
            return "上";
        } else{
            return "上上";
        }
    }

    private function getHeightWeightStandards($gender, $ageInMonths, $heightType, $standardHeight)
    {
        $standardsModel = null;
        if($ageInMonths == 24){
            if ($gender == "boy") {
                $standardsModel = ($heightType == "height") ? BoyWhoHeightWeight::class : BoyWhoLengthWeight::class;
            } elseif ($gender == "girl") {
                $standardsModel = ($heightType == "height") ? GirlWhoHeightWeight::class : GirlWhoLengthWeight::class;
            } else {
                return null; // 未指定性别
            }
        }else{
            if ($gender == "boy") {
                $standardsModel = ($ageInMonths > 24) ? BoyWhoHeightWeight::class : BoyWhoLengthWeight::class;
            } elseif ($gender == "girl") {
                $standardsModel = ($ageInMonths > 24) ? GirlWhoHeightWeight::class : GirlWhoLengthWeight::class;
            } else {
                return null; // 未指定性别
            }
        }

        return $standardsModel::where('height', $standardHeight)->first();

    }

    private function getClosestNumber($height): float|int
    {
        $int = intval($height);
        if($height>=$int && $height<$int+0.25){
            return $int;
        }elseif ($height<$int+0.50){
            return $int+0.50;
        }elseif($height<$int+0.75){
            return $int+0.50;
        }else{
            return $int+1;
        }
    }

    private function calculateBMI($weight,$height):float
    {
        if($weight&&$height){
            $BMI=$weight/(($height*0.01)**2);
            return round($BMI, 2);
        }
        return 0.00;
    }

}
