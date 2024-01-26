<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    use HasFactory;

    /**
     * 获取该患者的所有医疗记录编号。
     */
    public function medicalRecordNumbers()
    {
        return $this->hasMany(MedicalRecordNumber::class);
    }

}
