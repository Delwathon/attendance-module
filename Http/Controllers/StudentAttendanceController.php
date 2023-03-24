<?php

namespace Modules\Attendance\Http\Controllers;

use Carbon\Carbon;
use App\Models\Branch;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Admission\Entities\Enrol;
use Modules\Student\Entities\Student;
use Modules\Academics\Entities\SClass;
use Modules\Academics\Entities\Section;
use Modules\Attendance\Entities\Attendance;
use Illuminate\Contracts\Support\Renderable;

class StudentAttendanceController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index(Request $request)
    {

        $branches = Branch::get();

        if ($request->get('branch')) {
            $request->validate([
                'branch' => 'required|numeric',
                'class' => 'required|numeric',
                'section' => 'required|numeric',
            ]);
            return redirect()->route('student-attendance.filter', [$request->branch, $request->class, $request->section, date('Y-m-d')]);
        }


        return view('attendance.today', compact(['branches']));
    }

    public function markAttendance(Request $request)
    {
        $attendance = Attendance::with(['student'])->find($request->id);
        $attendance->status = $request->status;
        $attendance->remark = $request->remark;
        $attendance->update();
        return json_encode(['success' => $attendance->student->regno . ' marked ' . $attendance->status]);

    }


    public function past(Request $request)
    {

        if ($request->get('branch')) {
            $request->validate([
                'branch' => 'required|numeric',
                'class' => 'required|numeric',
                'section' => 'required|numeric',
                'month' => 'required',
            ]);

            // return date('Y-m', strtotime($request->month));
            return redirect()->route('past-attendance-class', [$request->branch, $request->class, $request->section, date('Y-m', strtotime($request->month))]);
        }
        $branches = Branch::get();

        return view('attendance.past', compact(['branches']));
    }



    public function studentAttendanceFilter($branch, $class, $section, $date)
    {

        $branch = Branch::find($branch);
        $section = Section::find($section);
        $class = SClass::find($class);
        $enrols = Enrol::with(['branch', 'student', 'dept', 'class', 'section'])
            ->where([
                'branch_id' => $branch->id,
                'section_id' => $section->id,
                's_class_id' => $class->id
            ])->get();
        $branches = Branch::get();
        $classes = SClass::whereHas('branches', function ($query) use ($branch) {
            $query->where('branch_id', $branch->id);
        })->get();
        $sections = Section::whereHas('classes', function ($query) use ($class) {
            $query->where('s_class_id', $class->id);
        })->get();

        // get the id of the employee marking attendance
        $employee_id = 1;
        $employees = Employee::all();
        foreach ($employees as $checkEmployee) {
            if ($checkEmployee->user_id == Auth::user()->id && $checkEmployee->branch_id == $branch->id) {
                $employee_id = $checkEmployee->id;
                break;
            }
        }

        foreach ($enrols as $key => $enrol) {
            $query = [
                'student_id' => $enrol->student->id,
                'user_id' => $enrol->student->user_id,
                'branch_id' => $branch->id,
                'section_id' => $section->id,
                's_class_id' => $class->id,
                'employee_id' => $employee_id,
                'date' => date('Y-m-d'),
            ];

            // return $student;
            Attendance::updateOrCreate($query);
        }
        $attendances = Attendance::with(['user', 'section', 'branch', 'class'])->where(['s_class_id' => $class->id, 'section_id' => $section->id, 'branch_id' => $branch->id, 'date' => $date])->get();
        // return $attendances;
        return view('attendance.today', compact(['branches', 'classes', 'sections', 'attendances', 'section', 'branch', 'class']));

    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */


    //  Not use this store again
    public function store(Request $request)
    {
        //

        foreach ($request->attendance as $key => $value) {
            $query = [
                'student_id' => $key,
                'user_id' => $request->user[$key],
                'branch_id' => $request->branch_id,
                'section_id' => $request->section_id,
                's_class_id' => $request->class_id,
                'date' => date('Y-m-d'),
            ];
            $attendance = [
                'remark' => $request->remark[$key],
                'status' => $value,
                'student_id' => $key,
                'user_id' => $request->user[$key],
                'branch_id' => $request->branch_id,
                'section_id' => $request->section_id,
                's_class_id' => $request->class_id,
                'date' => date('Y-m-d')

            ];
            // return $student;
            Attendance::updateOrCreate($query, $attendance);
        }



        return redirect()->back()->with('success', 'Attendance taken successfully');
    }




    public function attendance($branch, $class, $section, $months)
    {

        $branches = Branch::get();
        $branch = Branch::find($branch);
        $section = Section::find($section);
        $class = SClass::find($class);
        $classes = SClass::whereHas('branches', function ($query) use ($branch) {
            $query->where('branch_id', $branch->id);
        })->get();
        $sections = Section::whereHas('classes', function ($query) use ($class) {
            $query->where('s_class_id', $class->id);
        })->get();

        $attendances = Attendance::where(['s_class_id' => $class->id, 'section_id' => $section->id, 'branch_id' => $branch->id])->get();
        // return $attendances;
        $date = strtotime($months);
        $year = date('Y', $date);
        $month = date('m', $date);
        // return $day;
        $days = Carbon::now()->year($year)->month($month)->daysInMonth;
        // return $days;


        $students = Student::with(['branch', 'enrol', 'attendances', 'user', 'state', 'lga', 'guardian'])
            ->whereHas('attendances', function ($query) use ($year, $month, $section, $branch, $class) {
                $query
                    ->whereMonth('date', '=', $month)
                    ->whereYear('date', '=', $year)
                    ->orderBy('id', 'desc')
                    ->where([
                        'branch_id' => $branch->id,
                        'section_id' => $section->id,
                        's_class_id' => $class->id
                    ]);
            })->get();

        // return $students;

        return view('attendance.past', compact(['branches', 'classes', 'sections', 'students', 'months', 'days', 'section', 'branch', 'class']));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //


    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}