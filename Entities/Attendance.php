<?php

namespace Modules\Attendance\Entities;

use App\Models\User;
use App\Models\Branch;
use App\Models\SClass;
use App\Models\Section;
use App\Models\Student;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'student_id', 'branch_id', 'status', 'remark', 'employee_id', 'date', 's_class_id', 'section_id'];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }


    public function class ()
    {
        return $this->belongsTo(SClass::class, 's_class_id');
    }


    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }


    public function user()
    {
        return $this->belongsTo(User::class);
    }


    protected static function newFactory()
    {
        return \Modules\Attendance\Database\factories\AttendanceFactory::new ();
    }
}