<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Backend\UserController;
use App\Http\Controllers\Backend\ProfileController;
use App\Http\Controllers\Backend\Setup\StudentClassController;
use App\Http\Controllers\Backend\Setup\StudentYearController;
use App\Http\Controllers\Backend\Setup\StudentGroupController;
use App\Http\Controllers\Backend\Setup\FeeCategoryController;
use App\Http\Controllers\Backend\Setup\FeeAmountControllere;
use App\Http\Controllers\Backend\Setup\SchoolSubjectController;
use App\Http\Controllers\Backend\Setup\AssignSubjectController;
use App\Http\Controllers\Backend\Setup\AssignClassTeacherController;
use App\Http\Controllers\Backend\Setup\DesignationController;
use App\Http\Controllers\Backend\Setup\SchoolSectionController;
use App\Http\Controllers\Backend\SettingController;
use App\Http\Controllers\Backend\RoleController;
use App\Http\Controllers\Backend\ParentResultLinkController;
use App\Http\Controllers\Backend\ParentController;
use App\Models\SiteSetting;
use App\Http\Controllers\Backend\EventController;
use App\Http\Controllers\Backend\SchoolTimetableController;
use App\Http\Controllers\Backend\LeaveRequestController;
use App\Http\Controllers\Backend\AIController;
use App\Http\Controllers\Backend\ChatConnectionController;

use App\Http\Controllers\Backend\Student\StudentRegController;
use App\Http\Controllers\Backend\Student\StudentRollController;
use App\Http\Controllers\Backend\Student\RegistrationFeeController;
use App\Http\Controllers\Backend\Student\MonthlyFeeController;
use App\Http\Controllers\Backend\Student\ExamFeeController;

use App\Http\Controllers\Backend\Employee\EmployeeRegController;
use App\Http\Controllers\Backend\Employee\EmployeeSalaryController;
use App\Http\Controllers\Backend\Employee\EmployeeLeaveController;
use App\Http\Controllers\Backend\Employee\EmployeeAttendanceController;
use App\Http\Controllers\Backend\Employee\MonthlySalaryController;

use App\Http\Controllers\Backend\Marks\MarksController;
use App\Http\Controllers\Backend\Marks\GradeController;

use App\Http\Controllers\Backend\DefaultController;

use App\Http\Controllers\Backend\Account\StudentFeeController;
use App\Http\Controllers\Backend\Account\AccountSalaryController;
use App\Http\Controllers\Backend\Account\OtherCostController;

use App\Http\Controllers\Backend\Report\ProfiteController;
use App\Http\Controllers\Backend\Report\MarkSheetController;
use App\Http\Controllers\Backend\Report\AttenReportController;
use App\Http\Controllers\Backend\Report\ResultReportController;
use App\Http\Controllers\Backend\Report\ReportController;
use App\Http\Controllers\Backend\Academic\AcademicSessionTermController;
use App\Http\Controllers\Backend\Academic\AcademicConfigController;
use App\Http\Controllers\Backend\Academic\StructuredMarksController;
use App\Http\Controllers\Backend\Academic\ParentDashboardController;
use App\Http\Controllers\Backend\Homework\HomeworkController;
use App\Http\Controllers\Backend\Homework\SubmissionController;
use App\Http\Controllers\FeeController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\Backend\LearnHub\LearnHubController;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the 'web' middleware group. Now create something great!
|
*/

Route::middleware(['auth'])->get('/dashboard', [AdminController::class, 'Index'])->name('dashboard');
Route::post('/wallet/fund/paystack-webhook', [\App\Http\Controllers\Backend\Wallet\WalletFundingController::class, 'paystackWebhook'])->name('wallet.fund.paystack.webhook');
Route::middleware(['auth'])->get('teacher/assignment/pdf/{teacher_id?}', [AssignSubjectController::class, 'TeacherAssignmentPdf'])->name('assign.subject.teacher.pdf');
Route::middleware(['auth', 'role:Admin'])->get('/admin', function () {
    return redirect()->route('dashboard');
})->name('admin.dashboard');

Route::group(['middleware' => 'prevent-back-history'],function(){
   
   



Route::get('/', function() {
    return redirect()->route('login');
})->name('portal');
Route::get('/home', function() { return redirect()->route('portal'); })->name('home');

Route::get('/r/{token}', [ParentResultLinkController::class, 'show'])->name('parent.result.link');
Route::get('/r/{token}/report', [ParentResultLinkController::class, 'reportCard'])->name('parent.result.link.report');

Route::middleware('auth')->get('/admin/logout', [AdminController::class, 'Logout'])->name('admin.logout');
 
 
Route::group(['middleware' => 'auth'],function(){


 // User Management All Routes 

Route::prefix('users')->middleware('permission:manage_users')->group(function(){

Route::get('/view', [UserController::class, 'UserView'])->name('users.view');

Route::get('/add', [UserController::class, 'UserAdd'])->name('users.add');

Route::post('/store', [UserController::class, 'UserStore'])->name('users.store');

Route::get('/edit/{id}', [UserController::class, 'UserEdit'])->name('users.edit');
Route::post('/update/{id}', [UserController::class, 'UserUpdate'])->name('users.update');

Route::get('/delete/{id}', [UserController::class, 'UserDelete'])->name('users.delete');

}); 


 // Role Management All Routes 

Route::prefix('role')->middleware('permission:assign_roles')->group(function(){

Route::get('/view', [RoleController::class, 'RoleView'])->name('role.view');

Route::get('/add', [RoleController::class, 'RoleAdd'])->name('role.add');

Route::post('/store', [RoleController::class, 'RoleStore'])->name('role.store');

Route::get('/edit/{id}', [RoleController::class, 'RoleEdit'])->name('role.edit');

Route::post('/update/{id}', [RoleController::class, 'RoleUpdate'])->name('role.update');

Route::get('/delete/{id}', [RoleController::class, 'RoleDelete'])->name('role.delete');

}); 


 // Parent Management All Routes 

Route::prefix('parent')->middleware('role:Admin')->group(function(){

Route::get('/view', [ParentController::class, 'ParentView'])->name('parent.view');

Route::get('/add', [ParentController::class, 'ParentAdd'])->name('parent.add');

Route::post('/store', [ParentController::class, 'ParentStore'])->name('parent.store');

Route::get('/edit/{id}', [ParentController::class, 'ParentEdit'])->name('parent.edit');

Route::post('/update/{id}', [ParentController::class, 'ParentUpdate'])->name('parent.update');

Route::get('/delete/{id}', [ParentController::class, 'ParentDelete'])->name('parent.delete');

Route::post('/result-link/{parentId}', [ParentResultLinkController::class, 'store'])->name('parent.result.link.store');
Route::post('/result-link/revoke/{id}', [ParentResultLinkController::class, 'destroy'])->name('parent.result.link.destroy');

}); 

/// User Profile and Change Password 
Route::prefix('profile')->group(function(){

Route::get('/view', [ProfileController::class, 'ProfileView'])->name('profile.view');

Route::get('/edit', [ProfileController::class, 'ProfileEdit'])->name('profile.edit');

Route::post('/store', [ProfileController::class, 'ProfileStore'])->name('profile.store');

Route::get('/password/view', [ProfileController::class, 'PasswordView'])->name('password.view');

Route::post('/password/update', [ProfileController::class, 'PasswordUpdate'])->name('profile.password.update');

}); 

// Event Management Routes
Route::prefix('events')->group(function(){
    Route::get('/view', [EventController::class, 'ViewEvent'])->name('event.view');
    Route::middleware('role:Admin')->group(function () {
        Route::get('/add', [EventController::class, 'AddEvent'])->name('event.add');
        Route::post('/store', [EventController::class, 'StoreEvent'])->name('event.store');
        Route::get('/edit/{id}', [EventController::class, 'EditEvent'])->name('event.edit');
        Route::post('/update/{id}', [EventController::class, 'UpdateEvent'])->name('event.update');
        Route::get('/delete/{id}', [EventController::class, 'DeleteEvent'])->name('event.delete');
        Route::get('/registrations/{event_id}', [EventController::class, 'ViewRegistrations'])->name('event.registrations.view');
    });
});

Route::middleware(['auth', 'role:Admin'])->prefix('timetable')->name('timetable.')->group(function () {
    Route::get('/', [SchoolTimetableController::class, 'index'])->name('index');
    Route::post('/', [SchoolTimetableController::class, 'store'])->name('store');
    Route::get('/{timetable}/edit', [SchoolTimetableController::class, 'edit'])->name('edit');
    Route::put('/{timetable}', [SchoolTimetableController::class, 'update'])->name('update');
    Route::delete('/{timetable}', [SchoolTimetableController::class, 'destroy'])->name('destroy');
});

Route::middleware('role:Admin,Teacher,Staff')->prefix('leave-requests')->name('leave.requests.')->group(function () {
    Route::get('/', [LeaveRequestController::class, 'index'])->name('index');
    Route::get('/create', [LeaveRequestController::class, 'create'])->name('create');
    Route::post('/', [LeaveRequestController::class, 'store'])->name('store');
    Route::get('/{leaveRequest}/download', [LeaveRequestController::class, 'download'])->name('download');
    Route::post('/{leaveRequest}/status', [LeaveRequestController::class, 'updateStatus'])->name('status');
    Route::post('/{leaveRequest}/cancel', [LeaveRequestController::class, 'cancel'])->name('cancel');
});




/// User Profile and Change Password 
Route::prefix('setups')->middleware('role:Admin,Accountant')->group(function(){

// Student Class Routes 
Route::get('student/class/view', [StudentClassController::class, 'ViewStudent'])->name('student.class.view');

Route::get('student/class/add', [StudentClassController::class, 'StudentClassAdd'])->name('student.class.add');

Route::post('student/class/store', [StudentClassController::class, 'StudentClassStore'])->name('store.student.class');

Route::get('student/class/edit/{id}', [StudentClassController::class, 'StudentClassEdit'])->name('student.class.edit');

Route::post('student/class/update/{id}', [StudentClassController::class, 'StudentClassUpdate'])->name('update.student.class');

Route::get('student/class/delete/{id}', [StudentClassController::class, 'StudentClassDelete'])->name('student.class.delete');

// Student Year Routes 

Route::get('student/year/view', [StudentYearController::class, 'ViewYear'])->name('student.year.view');

Route::get('student/year/add', [StudentYearController::class, 'StudentYearAdd'])->name('student.year.add');

Route::post('student/year/store', [StudentYearController::class, 'StudentYearStore'])->name('store.student.year');

Route::get('student/year/edit/{id}', [StudentYearController::class, 'StudentYearEdit'])->name('student.year.edit');

Route::post('student/year/update/{id}', [StudentYearController::class, 'StudentYearUpdate'])->name('update.student.year');

Route::get('student/year/delete/{id}', [StudentYearController::class, 'StudentYearDelete'])->name('student.year.delete');

Route::get('student/year/active/{id}', [StudentYearController::class, 'StudentYearActive'])->name('student.year.active');




// Student Group Routes 

Route::get('student/group/view', [StudentGroupController::class, 'ViewGroup'])->name('student.group.view');

Route::get('student/group/add', [StudentGroupController::class, 'StudentGroupAdd'])->name('student.group.add');

Route::post('student/group/store', [StudentGroupController::class, 'StudentGroupStore'])->name('store.student.group');

Route::get('student/group/edit/{id}', [StudentGroupController::class, 'StudentGroupEdit'])->name('student.group.edit');

Route::post('student/group/update/{id}', [StudentGroupController::class, 'StudentGroupUpdate'])->name('update.student.group');

Route::get('student/group/delete/{id}', [StudentGroupController::class, 'StudentGroupDelete'])->name('student.group.delete');

// Dynamic Fee Management System Routes
Route::prefix('fee-management')->group(function(){
    Route::get('/types', [\App\Http\Controllers\Backend\Account\FeeManagementController::class, 'feeTypes'])->name('fee.types');
    Route::post('/types', [\App\Http\Controllers\Backend\Account\FeeManagementController::class, 'storeFeeType'])->name('fee.types.store');
    Route::post('/types/{id}', [\App\Http\Controllers\Backend\Account\FeeManagementController::class, 'updateFeeType'])->name('fee.types.update');
    Route::get('/types/{id}/delete', [\App\Http\Controllers\Backend\Account\FeeManagementController::class, 'deleteFeeType'])->name('fee.types.delete');

    Route::get('/structures', [\App\Http\Controllers\Backend\Account\FeeManagementController::class, 'feeStructures'])->name('fee.structures');
    Route::post('/structures', [\App\Http\Controllers\Backend\Account\FeeManagementController::class, 'storeFeeStructure'])->name('fee.structures.store');
    Route::get('/structures/{id}/edit', [\App\Http\Controllers\Backend\Account\FeeManagementController::class, 'editFeeStructure'])->name('fee.structures.edit');
    Route::post('/structures/{id}', [\App\Http\Controllers\Backend\Account\FeeManagementController::class, 'updateFeeStructure'])->name('fee.structures.update');
    Route::get('/structures/{id}/delete', [\App\Http\Controllers\Backend\Account\FeeManagementController::class, 'deleteFeeStructure'])->name('fee.structures.delete');

    Route::get('/assign', [\App\Http\Controllers\Backend\Account\FeeManagementController::class, 'assignFees'])->name('fee.assign');
    Route::post('/assign', [\App\Http\Controllers\Backend\Account\FeeManagementController::class, 'storeAssignFee'])->name('fee.assign.store');

    Route::get('/payments', [\App\Http\Controllers\Backend\Account\FeeManagementController::class, 'payments'])->name('fee.payments');
    Route::post('/payments/record', [\App\Http\Controllers\Backend\Account\FeeManagementController::class, 'recordPayment'])->name('fee.payments.record');
    Route::get('/payments/receipt/{id}', [\App\Http\Controllers\Backend\Account\FeeManagementController::class, 'paymentReceipt'])->name('fee.payments.receipt');

    Route::get('/report', [\App\Http\Controllers\Backend\Account\FeeManagementController::class, 'feeReport'])->name('fee.report');
});


// Fee Category Routes 

Route::get('fee/category/view', [FeeCategoryController::class, 'ViewFeeCat'])->name('fee.category.view');

Route::get('fee/category/add', [FeeCategoryController::class, 'FeeCatAdd'])->name('fee.category.add');

Route::post('fee/category/store', [FeeCategoryController::class, 'FeeCatStore'])->name('store.fee.category');

Route::get('fee/category/edit/{id}', [FeeCategoryController::class, 'FeeCatEdit'])->name('fee.category.edit');

Route::post('fee/category/update/{id}', [FeeCategoryController::class, 'FeeCategoryUpdate'])->name('update.fee.category');

Route::get('fee/category/delete/{id}', [FeeCategoryController::class, 'FeeCategoryDelete'])->name('fee.category.delete');

// Fee Category Amount Routes 

Route::get('fee/amount/view', [FeeAmountControllere::class, 'ViewFeeAmount'])->name('fee.amount.view');

Route::get('fee/amount/add', [FeeAmountControllere::class, 'AddFeeAmount'])->name('fee.amount.add');

Route::post('fee/amount/store', [FeeAmountControllere::class, 'StoreFeeAmount'])->name('store.fee.amount');

Route::get('fee/amount/edit/{fee_category_id}', [FeeAmountControllere::class, 'EditFeeAmount'])->name('fee.amount.edit');

Route::post('fee/amount/update/{fee_category_id}', [FeeAmountControllere::class, 'UpdateFeeAmount'])->name('update.fee.amount');

Route::get('fee/amount/details/{fee_category_id}', [FeeAmountControllere::class, 'DetailsFeeAmount'])->name('fee.amount.details');





// School Subject All Routes 

Route::get('school/subject/view', [SchoolSubjectController::class, 'ViewSubject'])->name('school.subject.view');

Route::get('school/subject/add', [SchoolSubjectController::class, 'SubjectAdd'])->name('school.subject.add');

Route::post('school/subject/store', [SchoolSubjectController::class, 'SubjectStore'])->name('store.school.subject');

Route::get('school/subject/edit/{id}', [SchoolSubjectController::class, 'SubjectEdit'])->name('school.subject.edit');

Route::post('school/subject/update/{id}', [SchoolSubjectController::class, 'SubjectUpdate'])->name('update.school.subject');

Route::get('school/subject/delete/{id}', [SchoolSubjectController::class, 'SubjectDelete'])->name('school.subject.delete');


// Assign Subject Routes 

Route::get('assign/subject/view', [AssignSubjectController::class, 'ViewAssignSubject'])->name('assign.subject.view');

Route::get('assign/subject/add', [AssignSubjectController::class, 'AddAssignSubject'])->name('assign.subject.add');

Route::get('assign/subject/teachers', [AssignSubjectController::class, 'GetTeachersBySection'])->name('assign.subject.teachers');

Route::post('assign/subject/store', [AssignSubjectController::class, 'StoreAssignSubject'])->name('store.assign.subject');

Route::get('assign/subject/edit/{class_id}/{section_id?}', [AssignSubjectController::class, 'EditAssignSubject'])->name('assign.subject.edit');

Route::post('assign/subject/update/{class_id}', [AssignSubjectController::class, 'UpdateAssignSubject'])->name('update.assign.subject');

Route::get('assign/subject/details/{class_id}/{section_id?}', [AssignSubjectController::class, 'DetailsAssignSubject'])->name('assign.subject.details');

Route::get('assign/subject/delete/{id}', [AssignSubjectController::class, 'DeleteAssignSubject'])->name('assign.subject.delete');

// Assign Class Teacher Routes 
Route::get('assign/class/teacher/view', [AssignClassTeacherController::class, 'ViewAssignTeacher'])->name('assign.class.teacher.view');
Route::get('assign/class/teacher/add', [AssignClassTeacherController::class, 'AddAssignTeacher'])->name('assign.class.teacher.add');
Route::post('assign/class/teacher/store', [AssignClassTeacherController::class, 'StoreAssignTeacher'])->name('assign.class.teacher.store');
Route::get('assign/class/teacher/edit/{id}', [AssignClassTeacherController::class, 'EditAssignTeacher'])->name('assign.class.teacher.edit');
Route::post('assign/class/teacher/update/{id}', [AssignClassTeacherController::class, 'UpdateAssignTeacher'])->name('assign.class.teacher.update');
Route::get('assign/class/teacher/delete/{id}', [AssignClassTeacherController::class, 'DeleteAssignTeacher'])->name('assign.class.teacher.delete');


// School Section Routes 
Route::get('school/section/view', [SchoolSectionController::class, 'SectionView'])->name('school.section.view');

Route::get('school/section/add', [SchoolSectionController::class, 'SectionAdd'])->name('school.section.add');

Route::post('school/section/store', [SchoolSectionController::class, 'SectionStore'])->name('school.section.store');

Route::get('school/section/edit/{id}', [SchoolSectionController::class, 'SectionEdit'])->name('school.section.edit');

Route::post('school/section/update/{id}', [SchoolSectionController::class, 'SectionUpdate'])->name('school.section.update');

Route::get('school/section/delete/{id}', [SchoolSectionController::class, 'SectionDelete'])->name('school.section.delete');


// Designation All Routes 

Route::get('designation/view', [DesignationController::class, 'ViewDesignation'])->name('designation.view');

Route::get('designation/add', [DesignationController::class, 'DesignationAdd'])->name('designation.add');

Route::post('designation/store', [DesignationController::class, 'DesignationStore'])->name('store.designation');

Route::get('designation/edit/{id}', [DesignationController::class, 'DesignationEdit'])->name('designation.edit');

Route::post('designation/update/{id}', [DesignationController::class, 'DesignationUpdate'])->name('update.designation');

Route::get('designation/delete/{id}', [DesignationController::class, 'DesignationDelete'])->name('designation.delete');


}); 


/// Student Registration Routes  
Route::prefix('students')->middleware('permission:view_students')->group(function(){

Route::get('/reg/view', [StudentRegController::class, 'StudentRegView'])->name('student.registration.view');
Route::get('/reg/live-search', [StudentRegController::class, 'StudentRegLiveSearch'])->name('student.registration.live-search');

Route::get('/reg/Add', [StudentRegController::class, 'StudentRegAdd'])->name('student.registration.add');

Route::post('/reg/store', [StudentRegController::class, 'StudentRegStore'])->name('store.student.registration');
 
Route::get('/year/class/wise', [StudentRegController::class, 'StudentClassYearWise'])->name('student.year.class.wise');

Route::get('/reg/edit/{student_id}', [StudentRegController::class, 'StudentRegEdit'])->name('student.registration.edit');

Route::post('/reg/update/{student_id}', [StudentRegController::class, 'StudentRegUpdate'])->name('update.student.registration');

Route::get('/reg/promotion/{student_id}', [StudentRegController::class, 'StudentRegPromotion'])->name('student.registration.promotion');

Route::post('/reg/update/promotion/{student_id}', [StudentRegController::class, 'StudentUpdatePromotion'])->name('promotion.student.registration');

Route::get('/reg/details/{student_id}', [StudentRegController::class, 'StudentRegDetails'])->name('student.registration.details');

// Import/Export
Route::get('/reg/export', [StudentRegController::class, 'StudentExport'])->name('student.registration.export');
Route::get('/reg/import-template', [StudentRegController::class, 'StudentImportTemplate'])->name('student.registration.import-template');
Route::post('/reg/import', [StudentRegController::class, 'StudentImport'])->name('student.registration.import');

// Student Roll Generate Routes 
Route::get('/roll/generate/view', [StudentRollController::class, 'StudentRollView'])->name('roll.generate.view');

Route::get('/reg/getstudents', [StudentRollController::class, 'GetStudents'])->name('student.registration.getstudents');

Route::post('/roll/generate/store', [StudentRollController::class, 'StudentRollStore'])->name('roll.generate.store');

// Registration Fee Routes 
Route::get('/reg/fee/view', [RegistrationFeeController::class, 'RegFeeView'])->name('registration.fee.view');

Route::get('/reg/fee/classwisedata', [RegistrationFeeController::class, 'RegFeeClassData'])->name('student.registration.fee.classwise.get');

Route::get('/reg/fee/payslip', [RegistrationFeeController::class, 'RegFeePayslip'])->name('student.registration.fee.payslip');


// Monthly Fee Routes 
Route::get('/monthly/fee/view', [MonthlyFeeController::class, 'MonthlyFeeView'])->name('monthly.fee.view');

Route::get('/monthly/fee/classwisedata', [MonthlyFeeController::class, 'MonthlyFeeClassData'])->name('student.monthly.fee.classwise.get');

Route::get('/monthly/fee/payslip', [MonthlyFeeController::class, 'MonthlyFeePayslip'])->name('student.monthly.fee.payslip');

// Exam Fee Routes 
Route::get('/exam/fee/view', [ExamFeeController::class, 'ExamFeeView'])->name('exam.fee.view');

Route::get('/exam/fee/classwisedata', [ExamFeeController::class, 'ExamFeeClassData'])->name('student.exam.fee.classwise.get');

Route::get('/exam/fee/payslip', [ExamFeeController::class, 'ExamFeePayslip'])->name('student.exam.fee.payslip');



// Group Promotion Routes
Route::get('/promotion/group/view', [StudentRegController::class, 'GroupPromotionView'])->name('student.promotion.group.view');
Route::get('/promotion/group/getstudents', [StudentRegController::class, 'GroupPromotionGetStudents'])->name('student.promotion.group.getstudents');
Route::post('/promotion/group/store', [StudentRegController::class, 'GroupPromotionStore'])->name('student.promotion.group.store');

Route::get('/reg/delete/{assign_id}', [StudentRegController::class, 'StudentRegDelete'])->name('student.registration.delete');

}); 

// Student Attendance Routes
Route::prefix('student/attendance')->group(function(){
    Route::get('/view', [App\Http\Controllers\Backend\Student\StudentAttendanceController::class, 'AttendanceView'])->name('student.attendance.view');
    Route::get('/add', [App\Http\Controllers\Backend\Student\StudentAttendanceController::class, 'AttendanceAdd'])->name('student.attendance.add');
    Route::get('/getstudents', [App\Http\Controllers\Backend\Student\StudentAttendanceController::class, 'GetStudents'])->name('student.attendance.getstudents');
    Route::post('/store', [App\Http\Controllers\Backend\Student\StudentAttendanceController::class, 'AttendanceStore'])->name('student.attendance.store');
    Route::get('/edit/{date}/{class_id}/{section_id?}', [App\Http\Controllers\Backend\Student\StudentAttendanceController::class, 'AttendanceEdit'])->name('student.attendance.edit');
    Route::get('/details/{date}/{class_id}/{section_id?}', [App\Http\Controllers\Backend\Student\StudentAttendanceController::class, 'AttendanceDetails'])->name('student.attendance.details');
});


/// Employee Registration Routes
Route::prefix('employees')->middleware('role:Admin')->group(function(){

Route::get('reg/employee/view', [EmployeeRegController::class, 'EmployeeView'])->name('employee.registration.view');

Route::get('reg/employee/add', [EmployeeRegController::class, 'EmployeeAdd'])->name('employee.registration.add');

Route::post('reg/employee/store', [EmployeeRegController::class, 'EmployeeStore'])->name('store.employee.registration');
 
Route::get('reg/employee/edit/{id}', [EmployeeRegController::class, 'EmployeeEdit'])->name('employee.registration.edit');

Route::post('reg/employee/update/{id}', [EmployeeRegController::class, 'EmployeeUpdate'])->name('update.employee.registration');

Route::get('reg/employee/details/{id}', [EmployeeRegController::class, 'EmployeeDetails'])->name('employee.registration.details');

Route::get('reg/employee/delete/{id}', [EmployeeRegController::class, 'EmployeeDelete'])->name('employee.registration.delete');

// Employee Salary All Routes 
Route::get('salary/employee/view', [EmployeeSalaryController::class, 'SalaryView'])->name('employee.salary.view');

Route::get('salary/employee/increment/{id}', [EmployeeSalaryController::class, 'SalaryIncrement'])->name('employee.salary.increment');

Route::post('salary/employee/store/{id}', [EmployeeSalaryController::class, 'SalaryStore'])->name('update.increment.store');

Route::get('salary/employee/details/{id}', [EmployeeSalaryController::class, 'SalaryDetails'])->name('employee.salary.details');


// Employee Leave All Routes 
Route::get('leave/employee/view', [EmployeeLeaveController::class, 'LeaveView'])->name('employee.leave.view');

Route::get('leave/employee/add', [EmployeeLeaveController::class, 'LeaveAdd'])->name('employee.leave.add');

Route::post('leave/employee/store', [EmployeeLeaveController::class, 'LeaveStore'])->name('store.employee.leave');

Route::get('leave/employee/edit/{id}', [EmployeeLeaveController::class, 'LeaveEdit'])->name('employee.leave.edit');

Route::post('leave/employee/update/{id}', [EmployeeLeaveController::class, 'LeaveUpdate'])->name('update.employee.leave');

Route::get('leave/employee/delete/{id}', [EmployeeLeaveController::class, 'LeaveDelete'])->name('employee.leave.delete');


// Employee Attendance All Routes 
Route::get('attendance/employee/view', [EmployeeAttendanceController::class, 'AttendanceView'])->name('employee.attendance.view');

Route::get('attendance/employee/add', [EmployeeAttendanceController::class, 'AttendanceAdd'])->name('employee.attendance.add');

Route::post('attendance/employee/store', [EmployeeAttendanceController::class, 'AttendanceStore'])->name('store.employee.attendance');

Route::get('attendance/employee/edit/{date}', [EmployeeAttendanceController::class, 'AttendanceEdit'])->name('employee.attendance.edit');

Route::get('attendance/employee/details/{date}', [EmployeeAttendanceController::class, 'AttendanceDetails'])->name('employee.attendance.details');


// Employee Monthly Salary All Routes 
Route::get('monthly/salary/view', [MonthlySalaryController::class, 'MonthlySalaryView'])->name('employee.monthly.salary');

Route::get('monthly/salary/get', [MonthlySalaryController::class, 'MonthlySalaryGet'])->name('employee.monthly.salary.get');

Route::get('monthly/salary/payslip/{employee_id}', [MonthlySalaryController::class, 'MonthlySalaryPayslip'])->name('employee.monthly.salary.payslip');


}); 


/// Marks Management Routes  
Route::prefix('marks')->middleware('role:Admin')->group(function(){

Route::get('marks/entry/add', [MarksController::class, 'MarksAdd'])->name('marks.entry.add');

Route::post('marks/entry/store', [MarksController::class, 'MarksStore'])->name('marks.entry.store'); 

Route::get('marks/entry/edit', [MarksController::class, 'MarksEdit'])->name('marks.entry.edit'); 

Route::get('marks/getstudents/edit', [MarksController::class, 'MarksEditGetStudents'])->name('student.edit.getstudents');

Route::post('marks/entry/update', [MarksController::class, 'MarksUpdate'])->name('marks.entry.update');  

// Marks Entry Grade 

Route::get('marks/grade/view', [GradeController::class, 'MarksGradeView'])->name('marks.entry.grade');

Route::get('marks/grade/add', [GradeController::class, 'MarksGradeAdd'])->name('marks.grade.add');

Route::post('marks/grade/store', [GradeController::class, 'MarksGradeStore'])->name('store.marks.grade');

Route::get('marks/grade/edit/{id}', [GradeController::class, 'MarksGradeEdit'])->name('marks.grade.edit');

Route::post('marks/grade/update/{id}', [GradeController::class, 'MarksGradeUpdate'])->name('update.marks.grade');

}); 

Route::middleware(['auth', 'permission:view_results|view-results|upload_results|enter_marks', 'academic.context'])->prefix('academic')->group(function () {
    Route::get('settings/session-term', [AcademicSessionTermController::class, 'edit'])->name('academic.settings.edit');
    Route::post('settings/session-term', [AcademicSessionTermController::class, 'update'])->name('academic.settings.update');
    Route::get('config', [AcademicConfigController::class, 'index'])->name('academic.config.index');
    Route::post('config/teacher-assignment', [AcademicConfigController::class, 'storeTeacherAssignment'])->name('academic.config.assignment.store');
    Route::post('config/class-marking', [AcademicConfigController::class, 'storeClassMarkingSetting'])->name('academic.config.marking.store');
    Route::get('config/class-marking/edit/{id}', [AcademicConfigController::class, 'editClassMarkingSetting'])->name('academic.config.marking.edit');
    Route::post('config/class-marking/update/{id}', [AcademicConfigController::class, 'updateClassMarkingSetting'])->name('academic.config.marking.update');
    Route::get('config/class-marking/delete/{id}', [AcademicConfigController::class, 'destroyClassMarkingSetting'])->name('academic.config.marking.delete');
    Route::get('config/class-marking/toggle/{id}', [AcademicConfigController::class, 'toggleActiveClassMarkingSetting'])->name('academic.config.marking.toggle');
    Route::post('config/assessment/areas/store', [AcademicConfigController::class, 'storeAssessmentAreas'])->name('academic.config.assessment.areas.store');


    Route::get('marks/entry', [StructuredMarksController::class, 'create'])->name('academic.marks.entry');
    Route::get('marks/classes', [StructuredMarksController::class, 'getClassesBySection'])->name('academic.marks.classes');
    Route::get('marks/sections', [StructuredMarksController::class, 'getSectionsByClass'])->name('academic.marks.sections');
    Route::get('marks/subjects', [StructuredMarksController::class, 'getAssignedSubjects'])->name('academic.marks.subjects');
    Route::get('marks/context', [StructuredMarksController::class, 'loadEntryContext'])->name('academic.marks.context');
    Route::post('marks/store', [StructuredMarksController::class, 'store'])->name('academic.marks.store');
    Route::get('marks/export-excel', [StructuredMarksController::class, 'exportExcel'])->name('academic.marks.export');
    Route::post('marks/import-excel', [StructuredMarksController::class, 'importExcel'])->name('academic.marks.import');

    Route::get('results', [StructuredMarksController::class, 'results'])->name('academic.results.index');

    // Student Assessment Routes
    Route::get('assessment', [App\Http\Controllers\Backend\Academic\ClassTeacherAssessmentController::class, 'index'])->name('academic.assessment.index');
    Route::post('assessment/load', [App\Http\Controllers\Backend\Academic\ClassTeacherAssessmentController::class, 'loadStudents'])->name('academic.assessment.load');
    Route::post('assessment/store', [App\Http\Controllers\Backend\Academic\ClassTeacherAssessmentController::class, 'store'])->name('academic.assessment.store');
});

Route::middleware(['auth', 'role:Admin,Teacher', 'academic.context'])->prefix('academic')->group(function () {
    Route::get('cbt', [\App\Http\Controllers\Backend\Academic\QuizController::class, 'index'])->name('academic.cbt.index');
    Route::get('cbt/create', [\App\Http\Controllers\Backend\Academic\QuizController::class, 'create'])->name('academic.cbt.create');
    Route::get('cbt/options', [\App\Http\Controllers\Backend\Academic\QuizController::class, 'formOptions'])->name('academic.cbt.options');
    Route::post('cbt/store', [\App\Http\Controllers\Backend\Academic\QuizController::class, 'store'])->name('academic.cbt.store');
    Route::get('cbt/{quiz}/edit', [\App\Http\Controllers\Backend\Academic\QuizController::class, 'edit'])->name('academic.cbt.edit');
    Route::post('cbt/{quiz}/update', [\App\Http\Controllers\Backend\Academic\QuizController::class, 'update'])->name('academic.cbt.update');
    Route::get('cbt/{quiz}', [\App\Http\Controllers\Backend\Academic\QuizController::class, 'show'])->name('academic.cbt.show');
    Route::post('cbt/{quiz}/question', [\App\Http\Controllers\Backend\Academic\QuizController::class, 'addQuestion'])->name('academic.cbt.addQuestion');
    Route::get('cbt/question/{question}/edit', [\App\Http\Controllers\Backend\Academic\QuizController::class, 'editQuestion'])->name('academic.cbt.editQuestion');
    Route::post('cbt/question/{question}/update', [\App\Http\Controllers\Backend\Academic\QuizController::class, 'updateQuestion'])->name('academic.cbt.updateQuestion');
    Route::delete('cbt/question/{question}', [\App\Http\Controllers\Backend\Academic\QuizController::class, 'deleteQuestion'])->name('academic.cbt.deleteQuestion');

    Route::post('cbt/{quiz}/passage', [\App\Http\Controllers\Backend\Academic\QuizController::class, 'addPassage'])->name('academic.cbt.addPassage');
    Route::get('cbt/passage/{passage}/edit', [\App\Http\Controllers\Backend\Academic\QuizController::class, 'editPassage'])->name('academic.cbt.editPassage');
    Route::post('cbt/passage/{passage}/update', [\App\Http\Controllers\Backend\Academic\QuizController::class, 'updatePassage'])->name('academic.cbt.updatePassage');
    Route::delete('cbt/passage/{passage}', [\App\Http\Controllers\Backend\Academic\QuizController::class, 'deletePassage'])->name('academic.cbt.deletePassage');
    Route::post('cbt/attempt/{attempt}/retake', [\App\Http\Controllers\Backend\Academic\QuizController::class, 'allowRetake'])->name('academic.cbt.allowRetake');
    Route::post('cbt/{quiz}/status', [\App\Http\Controllers\Backend\Academic\QuizController::class, 'updateStatus'])->name('academic.cbt.updateStatus');
    Route::get('cbt/{quiz}/import', [\App\Http\Controllers\Backend\Academic\QuizController::class, 'import'])->name('academic.cbt.import');
    Route::post('cbt/{quiz}/import', [\App\Http\Controllers\Backend\Academic\QuizController::class, 'processImport'])->name('academic.cbt.processImport');
    Route::delete('cbt/{quiz}', [\App\Http\Controllers\Backend\Academic\QuizController::class, 'destroy'])->name('academic.cbt.destroy');
});

// Homework & Note Routes
Route::middleware(['auth'])->prefix('homework')->group(function(){
    Route::get('/view', [HomeworkController::class, 'HomeworkView'])->name('homework.view');
    Route::get('/add', [HomeworkController::class, 'HomeworkAdd'])->name('homework.add');
    Route::post('/store', [HomeworkController::class, 'HomeworkStore'])->name('homework.store');
    Route::get('/edit/{id}', [HomeworkController::class, 'HomeworkEdit'])->name('homework.edit');
    Route::post('/update/{id}', [HomeworkController::class, 'HomeworkUpdate'])->name('homework.update');
    Route::get('/delete/{id}', [HomeworkController::class, 'HomeworkDelete'])->name('homework.delete');
    Route::get('/download/{id}', [HomeworkController::class, 'HomeworkDownload'])->name('homework.download');

    // Submission Routes
    Route::post('/submission/store', [SubmissionController::class, 'SubmissionStore'])->name('homework.submission.store');
    Route::get('/submission/view/{homework_id}', [SubmissionController::class, 'SubmissionView'])->name('homework.submission.view');
    Route::get('/submission/download/{id}', [SubmissionController::class, 'SubmissionDownload'])->name('homework.submission.download');
});

Route::middleware(['auth'])->prefix('class-gallery')->group(function () {
    Route::get('/', [\App\Http\Controllers\Backend\Academic\ClassGalleryController::class, 'index'])->name('class.gallery.index');
    Route::get('/{class}', [\App\Http\Controllers\Backend\Academic\ClassGalleryController::class, 'show'])->name('class.gallery.show');
    Route::post('/{class}', [\App\Http\Controllers\Backend\Academic\ClassGalleryController::class, 'store'])->name('class.gallery.store');
    Route::delete('/photo/{photo}', [\App\Http\Controllers\Backend\Academic\ClassGalleryController::class, 'destroy'])->name('class.gallery.destroy');
});

Route::middleware(['auth'])->prefix('library')->group(function () {
    Route::get('/', [\App\Http\Controllers\Backend\Academic\LibraryResourceController::class, 'index'])->name('library.index');
    Route::get('/create', [\App\Http\Controllers\Backend\Academic\LibraryResourceController::class, 'create'])->name('library.create');
    Route::post('/', [\App\Http\Controllers\Backend\Academic\LibraryResourceController::class, 'store'])->name('library.store');
    Route::get('/{resource}/download', [\App\Http\Controllers\Backend\Academic\LibraryResourceController::class, 'download'])->name('library.download');
    Route::delete('/{resource}', [\App\Http\Controllers\Backend\Academic\LibraryResourceController::class, 'destroy'])->name('library.destroy');
});

// FamaiisStudyHub Platform Routes
Route::middleware(['auth'])->prefix('famaiis-study-hub')->name('learnhub.')->group(function () {
    Route::get('/', [LearnHubController::class, 'index'])->name('index');
    Route::post('/subjects', [LearnHubController::class, 'storeSubject'])->name('subject.store');
    Route::delete('/subjects/{id}', [LearnHubController::class, 'destroySubject'])->name('subject.destroy');
    Route::get('/subjects/{id}/manage', [LearnHubController::class, 'manageSubject'])->name('manage');
    Route::post('/subjects/{subjectId}/weeks', [LearnHubController::class, 'storeWeek'])->name('week.store');
    Route::post('/subjects/{subjectId}/weeks/{weekId}/lessons', [LearnHubController::class, 'storeLesson'])->name('lesson.store');
    Route::post('/subjects/{subjectId}/lessons/{lessonId}/generate-quiz', [LearnHubController::class, 'generateQuiz'])->name('quiz.generate');
    Route::get('/student/subjects/{id}', [LearnHubController::class, 'studentSubject'])->name('student.subject');
    Route::get('/lessons/{id}', [LearnHubController::class, 'showLesson'])->name('lesson');
    Route::get('/lessons/{id}/quiz', [LearnHubController::class, 'showQuiz'])->name('quiz');
    Route::get('/lessons/{id}/quiz-game', [LearnHubController::class, 'showQuizGame'])->name('quiz.game');
    Route::post('/lessons/{id}/quiz', [LearnHubController::class, 'submitQuiz'])->name('quiz.submit');
    Route::get('/lessons/{id}/chat', [LearnHubController::class, 'showChat'])->name('chat');
    Route::post('/lessons/{id}/chat', [LearnHubController::class, 'sendChat'])->name('chat.send');
    Route::post('/subjects/{subjectId}/live-sessions', [LearnHubController::class, 'storeLiveSession'])->name('live.store');
    Route::post('/subjects/{subjectId}/live-sessions/{sessionId}/start', [LearnHubController::class, 'startLiveSession'])->name('live.start');
    Route::post('/subjects/{subjectId}/live-sessions/{sessionId}/end', [LearnHubController::class, 'endLiveSession'])->name('live.end');
    Route::get('/live/{sessionId}/join', [LearnHubController::class, 'joinLiveSession'])->name('live.join');
});
 
Route::get('marks/getsubject', [DefaultController::class, 'GetSubject'])->name('marks.getsubject');

Route::get('student/marks/getstudents', [DefaultController::class, 'GetStudents'])->name('student.marks.getstudents');

Route::post('academic/section/switch', [DefaultController::class, 'SwitchSection'])->name('academic.section.switch');

Route::post('language/switch', function (\Illuminate\Http\Request $request) {
    $request->validate(['language' => 'required|in:en,fr,ar,es,sw,ha,yo,ig']);
    auth()->user()->update(['language' => $request->language]);
    return back();
})->name('language.switch');

Route::post('notifications/clear', function () {
    auth()->user()->unreadNotifications->markAsRead();

    return back()->with([
        'message' => 'Notifications cleared successfully',
        'alert-type' => 'success',
    ]);
})->name('notifications.clear');





/// Account Management Routes  
Route::prefix('accounts')->middleware('permission:view_fees')->group(function(){

Route::get('student/fee/view', [StudentFeeController::class, 'StudentFeeView'])->name('student.fee.view');

Route::get('student/fee/add', [StudentFeeController::class, 'StudentFeeAdd'])->name('student.fee.add');

Route::get('student/fee/getstudent', [StudentFeeController::class, 'StudentFeeGetStudent'])->name('account.fee.getstudent'); 

Route::post('student/fee/store', [StudentFeeController::class, 'StudentFeeStore'])->name('account.fee.store'); 

// Employee Salary Routes
Route::get('account/salary/view', [AccountSalaryController::class, 'AccountSalaryView'])->name('account.salary.view');

Route::get('account/salary/add', [AccountSalaryController::class, 'AccountSalaryAdd'])->name('account.salary.add');

Route::get('account/salary/getemployee', [AccountSalaryController::class, 'AccountSalaryGetEmployee'])->name('account.salary.getemployee');

Route::post('account/salary/store', [AccountSalaryController::class, 'AccountSalaryStore'])->name('account.salary.store');

// Other Cost Rotues 

Route::get('other/cost/view', [OtherCostController::class, 'OtherCostView'])->name('other.cost.view');

Route::get('other/cost/add', [OtherCostController::class, 'OtherCostAdd'])->name('other.cost.add');

Route::post('other/cost/store', [OtherCostController::class, 'OtherCostStore'])->name('store.other.cost');

Route::get('other/cost/edit/{id}', [OtherCostController::class, 'OtherCostEdit'])->name('edit.other.cost');

Route::post('other/cost/update/{id}', [OtherCostController::class, 'OtherCostUpdate'])->name('update.other.cost');

}); 

Route::get('reports/marksheet/generate/get', [MarkSheetController::class, 'MarkSheetGet'])->name('report.marksheet.get');




/// Report Management All Routes  
Route::prefix('reports')->middleware('permission:view_reports')->group(function(){

Route::get('monthly/profit/view', [ProfiteController::class, 'MonthlyProfitView'])->name('monthly.profit.view');

Route::get('monthly/profit/datewais', [ProfiteController::class, 'MonthlyProfitDatewais'])->name('report.profit.datewais.get');

Route::get('monthly/profit/pdf', [ProfiteController::class, 'MonthlyProfitPdf'])->name('report.profit.pdf');

// MarkSheet Generate Staff View
Route::get('marksheet/generate/view', [MarkSheetController::class, 'MarkSheetView'])->name('marksheet.generate.view');


// Attendance Report Routes 
Route::get('attendance/report/view', [AttenReportController::class, 'AttenReportView'])->name('attendance.report.view');

Route::get('report/attendance/get', [AttenReportController::class, 'AttenReportGet'])->name('report.attendance.get');

// Student Result Report Routes 
Route::get('student/result/view', [ResultReportController::class, 'ResultView'])->name('student.result.view');
Route::get('student/result/get', [ResultReportController::class, 'ResultGet'])->name('report.student.result.get');

// Broadsheet Routes
Route::get('broadsheet/view', [\App\Http\Controllers\Backend\Report\BroadsheetController::class, 'BroadsheetView'])->name('broadsheet.view');
Route::get('broadsheet/full/get', [\App\Http\Controllers\Backend\Report\BroadsheetController::class, 'BroadsheetFullGet'])->name('broadsheet.full.get');
Route::get('broadsheet/subject', [\App\Http\Controllers\Backend\Report\BroadsheetController::class, 'BroadsheetSubjectGet'])->name('broadsheet.subject.get');
Route::get('broadsheet/compare', [\App\Http\Controllers\Backend\Report\BroadsheetController::class, 'BroadsheetCompareGet'])->name('broadsheet.compare.get');
Route::get('broadsheet/export', [\App\Http\Controllers\Backend\Report\BroadsheetController::class, 'BroadsheetExportCSV'])->name('broadsheet.export.csv');

// Student ID Card Routes 
Route::get('student/idcard/view', [ResultReportController::class, 'IdcardView'])->name('student.idcard.view');

Route::get('student/idcard/get', [ResultReportController::class, 'IdcardGet'])->name('report.student.idcard.get');

}); 

/// Activity Reports Routes
Route::middleware(['auth'])->prefix('activity-reports')->group(function(){
    // Teacher Routes
    Route::middleware('role:Admin,Teacher,Staff')->group(function(){
        Route::get('/teacher/view', [ReportController::class, 'teacherIndex'])->name('teacher.report.index');
        Route::get('/teacher/add', [ReportController::class, 'teacherCreate'])->name('teacher.report.add');
        Route::post('/teacher/store', [ReportController::class, 'teacherStore'])->name('teacher.report.store');
        Route::get('/teacher/get-subjects', [ReportController::class, 'getTeacherSubjects'])->name('teacher.report.getSubjects');
        Route::get('/teacher/get-students', [ReportController::class, 'getTeacherStudents'])->name('teacher.report.getStudents');
    });

    // Parent Routes
    Route::middleware('role:Parent')->group(function(){
        Route::get('/parent/view', [ReportController::class, 'parentIndex'])->name('parent.report.index');
        Route::post('/parent/seen/{id}', [ReportController::class, 'markAsSeen'])->name('parent.report.seen');
    });

    // Admin Routes
    Route::middleware('role:Admin')->group(function(){
        Route::get('/admin/view', [ReportController::class, 'adminIndex'])->name('admin.report.index');
        Route::get('/admin/delete/{id}', [ReportController::class, 'destroy'])->name('admin.report.delete');
    });
});

/// TinyMCE Upload Routes
Route::middleware(['auth'])->prefix('tinymce')->group(function(){
    Route::post('/upload-image', [App\Http\Controllers\TinyMCEUploadController::class, 'uploadImage'])->name('tinymce.upload.image');
});

/// Shop Routes
Route::prefix('shop')->group(function(){
    // Product Management (Admin/Accountant)
    Route::middleware('role:Admin,Accountant')->group(function () {
        Route::resource('products', App\Http\Controllers\Shop\ProductController::class);
    });

    // Buyer Routes
    Route::middleware('role:Admin,Accountant,Teacher,Staff,Student,Parent')->group(function () {
        Route::get('/view', [App\Http\Controllers\Shop\ShopController::class, 'index'])->name('shop.index');
        Route::get('/product/{product}', [App\Http\Controllers\Shop\ShopController::class, 'show'])->name('shop.show');
        Route::get('/cart', [App\Http\Controllers\Shop\ShopController::class, 'cart'])->name('shop.cart');

        // Order Routes
        Route::get('/orders', [App\Http\Controllers\Shop\OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [App\Http\Controllers\Shop\OrderController::class, 'show'])->name('orders.show');
        Route::post('/orders/store', [App\Http\Controllers\Shop\OrderController::class, 'store'])->name('orders.store');
        Route::get('/orders/{order}/invoice', [App\Http\Controllers\Shop\OrderController::class, 'invoice'])->name('orders.invoice');
        Route::get('/payment/callback', [App\Http\Controllers\Shop\OrderController::class, 'paymentCallback'])->name('shop.payment.callback');
    });

    Route::middleware('role:Admin,Accountant')->group(function () {
        Route::post('/orders/{order}/status', [App\Http\Controllers\Shop\OrderController::class, 'updateStatus'])->name('orders.updateStatus');
    });
});

// School Fees Setup (Admin/Accountant)
Route::middleware('role:Admin,Accountant')->prefix('fees')->group(function () {
    Route::get('/', [FeeController::class, 'index'])->name('fees.index');
    Route::get('/create', [FeeController::class, 'create'])->name('fees.create');
    Route::post('/store', [FeeController::class, 'store'])->name('fees.store');
    Route::get('/edit/{fee}', [FeeController::class, 'edit'])->name('fees.edit');
    Route::post('/update/{fee}', [FeeController::class, 'update'])->name('fees.update');
    Route::post('/delete/{fee}', [FeeController::class, 'destroy'])->name('fees.delete');
});

// Student/Parent Fee Payment
Route::middleware('role:Student,Parent')->prefix('student/fees')->group(function () {
    Route::get('/dashboard', [PaymentController::class, 'studentDashboard'])->name('payment.student.dashboard');
    Route::get('/pay/{fee}', [PaymentController::class, 'initializePayment'])->name('payment.initialize');
    Route::post('/pay/{fee}', [PaymentController::class, 'initializePayment'])->name('payment.initialize.post');
    Route::get('/receipt/{payment}', [PaymentController::class, 'receipt'])->name('payment.receipt');
});

// Admin/Accountant Payment Monitoring
Route::middleware('role:Admin,Accountant')->prefix('admin/payments')->group(function () {
    Route::get('/', [PaymentController::class, 'adminPayments'])->name('payments.admin.index');
    Route::get('/export', [PaymentController::class, 'exportReport'])->name('payments.admin.export');
});


 

// Wallet Management Routes
Route::prefix('wallet')->group(function(){
    Route::get('/view', [App\Http\Controllers\Backend\Wallet\WalletController::class, 'WalletView'])->name('wallet.view');
    Route::get('/history', [App\Http\Controllers\Backend\Wallet\WalletController::class, 'WalletHistory'])->name('wallet.history');
    Route::get('/pay-fees', [App\Http\Controllers\Backend\Wallet\WalletController::class, 'PayFeesView'])->name('wallet.pay_fees');
    Route::post('/pay-fees/store', [App\Http\Controllers\Backend\Wallet\WalletController::class, 'PayFeesStore'])->name('wallet.pay_fees.store');
    Route::post('/fund', [App\Http\Controllers\Backend\Wallet\WalletFundingController::class, 'initialize'])->name('wallet.fund.store');
    Route::get('/fund/callback', [App\Http\Controllers\Backend\Wallet\WalletFundingController::class, 'callback'])->name('wallet.fund.callback');
    Route::get('/get-student-fees/{student_id}', [App\Http\Controllers\Backend\Wallet\WalletController::class, 'GetStudentFees'])->name('wallet.get_student_fees');
    
    // Admin Wallet Routes
    Route::middleware('role:Admin')->prefix('admin')->group(function(){
        Route::get('/manage', [App\Http\Controllers\Backend\Wallet\AdminWalletController::class, 'ManageAll'])->name('wallet.admin.manage');
        Route::post('/credit', [App\Http\Controllers\Backend\Wallet\AdminWalletController::class, 'WalletCredit'])->name('wallet.admin.credit');
        Route::post('/debit', [App\Http\Controllers\Backend\Wallet\AdminWalletController::class, 'WalletDebit'])->name('wallet.admin.debit');
    });
});

}); // End Middleare Auth Route 

});  // Prevent Back Middleare

Route::get('/payment/callback', [PaymentController::class, 'paymentCallback'])->name('payment.callback');

Route::middleware(['auth', 'role:Parent', 'academic.context'])->prefix('parent')->group(function () {
Route::get('dashboard', [ParentDashboardController::class, 'index'])->name('parent.dashboard');
Route::get('results', [ParentDashboardController::class, 'results'])->name('parent.results');
Route::get('fees', [ParentDashboardController::class, 'fees'])->name('parent.fees');
Route::get('shop', [ParentDashboardController::class, 'shop'])->name('parent.shop');
Route::get('academic/dashboard', [ParentDashboardController::class, 'index'])->name('parent.academic.dashboard');
});

// CBT Routes (Student)
Route::middleware(['auth', 'role:Student', 'academic.context'])->prefix('student')->group(function () {
    Route::get('cbt', [\App\Http\Controllers\Student\CbtController::class, 'index'])->name('student.cbt.index');
    Route::get('cbt/{quiz}/take', [\App\Http\Controllers\Student\CbtController::class, 'take'])->name('student.cbt.take');
    Route::get('cbt/result/{attempt}', [\App\Http\Controllers\Student\CbtController::class, 'result'])->name('student.cbt.result');
    Route::get('cbt/result/{attempt}/download', [\App\Http\Controllers\Student\CbtController::class, 'download'])->name('student.cbt.result.download');
});

Route::middleware(['auth', 'role:Admin,Teacher'])->prefix('ai')->group(function () {
    Route::get('tools', [AIController::class, 'tools'])->name('ai.tools');
    Route::get('settings', [AIController::class, 'settings'])->name('ai.settings');
    Route::post('settings', [AIController::class, 'updateSettings'])->name('ai.settings.update');
    Route::post('assessment/{assessment}/comment', [AIController::class, 'generateComment'])->name('ai.assessment.comment');
    Route::post('assessment/comments/bulk', [AIController::class, 'generateBulkComments'])->middleware('role:Admin')->name('ai.assessment.comments.bulk');
    Route::post('lesson-plan', [AIController::class, 'lessonPlan'])->name('ai.lesson-plan');
    Route::post('student-insight', [AIController::class, 'insight'])->name('ai.student-insight');
    Route::post('expand-comment', [AIController::class, 'expandComment'])->name('ai.expand-comment');
});

/// Setting Routes 
Route::middleware(['auth', 'role:Admin,Accountant'])->prefix('setting')->group(function(){
 Route::get('site', [SettingController::class, 'SiteSettingView'])->name('site.setting');
 Route::post('site/update', [SettingController::class, 'SiteSettingUpdate'])->name('update.site.setting');
 Route::get('payment', [SettingController::class, 'PaymentSettingView'])->name('payment.setting');
 Route::post('payment/update', [SettingController::class, 'PaymentSettingUpdate'])->name('update.payment.setting');
});

// Chat Route
Route::middleware(['auth'])->get('/chat', \App\Livewire\Chat\Chat::class)->name('chat.view');
Route::middleware(['auth', 'role:Admin'])->prefix('chat/connections')->name('chat.connections.')->group(function () {
    Route::get('/', [ChatConnectionController::class, 'index'])->name('index');
    Route::post('/', [ChatConnectionController::class, 'store'])->name('store');
    Route::delete('/{connection}', [ChatConnectionController::class, 'destroy'])->name('destroy');
});
