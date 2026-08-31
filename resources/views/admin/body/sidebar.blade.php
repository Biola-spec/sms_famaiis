@php
 $prefix = Request::route()->getPrefix();
 $route = Route::current()->getName();
 $user = Auth::user();
 
 // Robust case-insensitive role detection
 $is_admin = $user->hasRole('Admin') || $user->hasRole('Super Admin');
 $is_teacher = $user->hasRole('Teacher') || $user->hasRole('Staff');
 $is_parent = $user->hasRole('Parent');
 $is_student = $user->hasRole('Student');
 $is_accountant = $user->hasRole('Accountant');
@endphp


 <aside class="main-sidebar">
    <!-- sidebar-->
    <section class="sidebar" style="overflow-y: auto;">	
		
        <div class="user-profile">
			<div class="ulogo">
				 <a href="{{ url('dashboard') }}">
				  <!-- logo for regular state and mobile devices -->
					 <div class="d-flex align-items-center justify-content-center">					 	
						  <img src="{{ (!empty($setting->logo))? url($setting->logo) : url('upload/logo/no_image.jpg') }}" alt="" style="width: 30px;">
						  <h3><b>{{ $setting->school_name ?? 'Easy' }}</b></h3>
					 </div>
				</a>
			</div>
        </div>
      
      <!-- sidebar menu-->
      <ul class="sidebar-menu" data-widget="tree">  
		  
		<li class="{{ ($route == 'dashboard' || $route == 'parent.dashboard')?'active':'' }}" >
          <a href="{{ $is_parent ? route('parent.dashboard') : route('dashboard') }}">
            <i data-feather="pie-chart"></i>
			<span>
                @if($is_admin) {{ __('ui.dashboard') }} @elseif($is_teacher) {{ __('ui.teacher_dashboard') }} @elseif($is_parent) {{ __('ui.parent_dashboard') }} @else {{ __('ui.dashboard') }} @endif
            </span>
          </a>
        </li>  



        <li class="{{ ($route == 'chat.view')?'active':'' }}">
          <a href="{{ route('chat.view') }}">
            <i data-feather="message-square"></i>
            <span>{{ __('ui.messages') }}</span>
            @if(($unreadMessageCount ?? 0) > 0)
                <span class="pull-right-container">
                    <span class="label label-danger pull-right">{{ $unreadMessageCount }}</span>
                </span>
            @endif
          </a>
        </li>

        @if($is_admin)
        <li class="{{ str_starts_with($route ?? '', 'chat.connections.')?'active':'' }}">
          <a href="{{ route('chat.connections.index') }}">
            <i data-feather="link-2"></i>
            <span>Chat Connections</span>
          </a>
        </li>
        @endif

        <li class="{{ ($prefix == '/wallet')?'active':'' }}">
          <a href="{{ route('wallet.view') }}">
            <i data-feather="credit-card"></i>
            <span>{{ __('ui.my_wallet') }}</span>
          </a>
        </li>

        <li class="{{ ($route == 'event.view')?'active':'' }}">
          <a href="{{ route('event.view') }}">
            <i data-feather="calendar"></i>
            <span>Calendar</span>
          </a>
        </li>

        @if($is_admin || $is_teacher)
        <li class="{{ str_starts_with($route ?? '', 'leave.requests.')?'active':'' }}">
          <a href="{{ route('leave.requests.index') }}">
            <i data-feather="clock"></i>
            <span>{{ $is_admin ? 'Leave Requests' : 'My Leave Requests' }}</span>
          </a>
        </li>
        @endif
		


    @if($user->hasPermission('manage_users') || $user->hasPermission('assign_roles'))
        <li class="treeview {{ ($prefix == '/users' || $prefix == '/role')?'active':'' }} " >
          <a href="#">
            <i data-feather="users"></i>
            <span>{{ __('ui.user_rbac') }}</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-right pull-right"></i>
            </span>
          </a>
          <ul class="treeview-menu">
            @if($user->hasPermission('manage_users'))
            <li><a href="{{ route('users.view') }}"><i class="ti-more"></i>{{ __('ui.view_users') }}</a></li>
            @endif
            @if($user->hasPermission('assign_roles'))
            <li><a href="{{ route('role.view') }}"><i class="ti-more"></i>{{ __('ui.role_management') }}</a></li>
            @endif
          </ul>
        </li> 
    @endif
		  
        <li class="treeview {{ ($prefix == '/profile')?'active':'' }}">
          <a href="#">
            <i data-feather="grid"></i> <span>{{ __('ui.manage_profile') }}</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-right pull-right"></i>
            </span>
          </a>
          <ul class="treeview-menu">
        <li><a href="{{ route('profile.view') }}"><i class="ti-more"></i>{{ __('ui.your_profile') }}</a></li>
        <li><a href="{{ route('password.view') }}"><i class="ti-more"></i>{{ __('ui.change_password') }}</a></li>
            
          </ul>
        </li>



@if($is_admin || $is_accountant)
<li class="treeview {{ ($prefix == '/setups')?'active':'' }}">
          <a href="#">
            <i data-feather="credit-card"></i> <span>{{ __('ui.system_setup') }}</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-right pull-right"></i>
            </span>
          </a>
          <ul class="treeview-menu">
         <li><a href="{{ route('student.class.view') }}"><i class="ti-more"></i>{{ __('ui.student_class') }}</a></li>
         <li><a href="{{ route('school.section.view') }}"><i class="ti-more"></i>{{ __('ui.school_section') }}</a></li>
         <li><a href="{{ route('student.year.view') }}"><i class="ti-more"></i>{{ __('ui.academic_session') }}</a></li>
         <li><a href="{{ route('student.group.view') }}"><i class="ti-more"></i>{{ __('ui.student_group') }}</a></li>
         <li><a href="{{ route('school.subject.view') }}"><i class="ti-more"></i>{{ __('ui.school_subject') }}</a></li>
         <li><a href="{{ route('assign.subject.view') }}"><i class="ti-more"></i>{{ __('ui.assign_subject') }}</a></li>
         <li><a href="{{ route('assign.class.teacher.view') }}"><i class="ti-more"></i>{{ __('ui.assign_class_teacher') }}</a></li>
         <li><a href="{{ route('designation.view') }}"><i class="ti-more"></i>{{ __('ui.designation') }} </a></li>
          </ul>
        </li>
@endif


@if($user->hasPermission('view_students'))
<li class="treeview {{ ($prefix == '/students')?'active':'' }}">
          <a href="#">
             <i data-feather="hard-drive"></i></i> <span>{{ __('ui.student_management') }}</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-right pull-right"></i>
            </span>
          </a>
          <ul class="treeview-menu">
        <li class="{{ ($route == 'student.registration.view')?'active':'' }}"><a href="{{ route('student.registration.view') }}"><i class="ti-more"></i>{{ __('ui.view_students') }}</a></li>
        @if($user->hasPermission('create_student'))
        <li class="{{ ($route == 'student.registration.add')?'active':'' }}"><a href="{{ route('student.registration.add') }}"><i class="ti-more"></i>{{ __('ui.student_registration') }}</a></li>
        @endif
        <li class="{{ ($route == 'student.promotion.group.view')?'active':'' }}"><a href="{{ route('student.promotion.group.view') }}"><i class="ti-more"></i>{{ __('ui.group_promotion') }}</a></li>

        <li><a href="{{ route('roll.generate.view') }}"><i class="ti-more"></i>{{ __('ui.roll_generate') }}</a></li>
          </ul>
        </li>
@endif


@if($is_admin)
<li class="treeview {{ ($prefix == '/employees')?'active':'' }}">
          <a href="#">
            <i data-feather="package"></i> <span>{{ __('ui.employee_management') }}</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-right pull-right"></i>
            </span>
          </a>
          <ul class="treeview-menu">
        <li  class="{{ ($route == 'employee.registration.view')?'active':'' }}"><a href="{{ route('employee.registration.view') }}"><i class="ti-more"></i>{{ __('ui.employee_registration') }}</a></li>

         <li  class="{{ ($route == 'employee.salary.view')?'active':'' }}"><a href="{{ route('employee.salary.view') }}"><i class="ti-more"></i>{{ __('ui.employee_salary') }}</a></li>

         <li><a href="{{ route('employee.leave.view') }}"><i class="ti-more"></i>{{ __('ui.employee_leave') }}</a></li>
         <li><a href="{{ route('employee.attendance.view') }}"><i class="ti-more"></i>{{ __('ui.employee_attendance') }}</a></li>
          <li><a href="{{ route('employee.monthly.salary') }}"><i class="ti-more"></i>{{ __('ui.employee_monthly_salary') }}</a></li>
          </ul>
        </li>
@endif

@if($is_admin)
<li class="treeview {{ ($prefix == '/parent' && !str_contains(Request::url(), 'academic'))?'active':'' }}">
          <a href="#">
            <i data-feather="users"></i> <span>{{ __('ui.parent_management') }}</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-right pull-right"></i>
            </span>
          </a>
          <ul class="treeview-menu">
        <li  class="{{ ($route == 'parent.view')?'active':'' }}"><a href="{{ route('parent.view') }}"><i class="ti-more"></i>{{ __('ui.view_parents') }}</a></li>
         <li  class="{{ ($route == 'parent.add')?'active':'' }}"><a href="{{ route('parent.add') }}"><i class="ti-more"></i>{{ __('ui.add_parent') }}</a></li>
          </ul>
        </li>
@endif


@if(($is_admin || $is_teacher || $user->hasPermission('view_results')) && !$is_parent && !$is_student)
<li class="treeview {{ ($prefix == '/academic')?'active':'' }}">
          <a href="#">
             <i data-feather="book-open"></i> <span>{{ __('ui.academic_management') }}</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-right pull-right"></i>
            </span>
          </a>
          <ul class="treeview-menu">
        @if($is_admin)
            <li class="{{ ($route == 'academic.settings.edit')?'active':'' }}"><a href="{{ route('academic.settings.edit') }}"><i class="ti-more"></i>{{ __('ui.active_session') }}</a></li> 
            <li class="{{ ($route == 'student.year.view')?'active':'' }}"><a href="{{ route('student.year.view') }}"><i class="ti-more"></i>{{ __('ui.manage_sessions') }}</a></li>
            <li class="{{ ($route == 'academic.config.index')?'active':'' }}"><a href="{{ route('academic.config.index') }}"><i class="ti-more"></i>{{ __('ui.marking_setup') }}</a></li>
        @endif
        
        <li class="{{ ($route == 'academic.marks.entry')?'active':'' }}"><a href="{{ route('academic.marks.entry') }}"><i class="ti-more"></i>{{ __('ui.marks_entry') }}</a></li>
        <li class="{{ ($route == 'academic.results.index')?'active':'' }}"><a href="{{ route('academic.results.index') }}"><i class="ti-more"></i>{{ __('ui.view_results') }}</a></li>
        <li class="{{ ($route == 'academic.assessment.index')?'active':'' }}"><a href="{{ route('academic.assessment.index') }}"><i class="ti-more"></i>{{ __('ui.student_assessment') }}</a></li>
        
        @if($is_admin)
            <li class="{{ ($route == 'marks.entry.grade')?'active':'' }}"><a href="{{ route('marks.entry.grade') }}"><i class="ti-more"></i>{{ __('ui.marks_grade') }}</a></li> 
        @endif
        
        <li class="header nav-small-cap">{{ __('ui.cbt_assessment') }}</li>
        <li class="{{ ($route == 'academic.cbt.index')?'active':'' }}"><a href="{{ route('academic.cbt.index') }}"><i class="ti-more"></i>{{ __('ui.manage_quizzes_cbt') }}</a></li>

        <li class="header nav-small-cap">{{ __('ui.daily_attendance') }}</li>
        <li class="{{ Request::is('student/attendance*') ? 'active' : '' }}"><a href="{{ route('student.attendance.view') }}"><i class="ti-more"></i>{{ __('ui.mark_attendance') }}</a></li>
          </ul>
        </li>
@endif

@if($is_teacher || $is_admin)
<li class="{{ (str_starts_with($route, 'teacher.report.'))?'active':'' }}">
    <a href="{{ route('teacher.report.index') }}">
        <i data-feather="clipboard"></i> <span>{{ __('ui.reports_activities') }}</span>
    </a>
</li>
@endif

@if($is_teacher)
<li class="{{ (str_starts_with($route ?? '', 'learnhub.') || $prefix == '/famaiis-study-hub')?'active':'' }}">
    <a href="{{ route('learnhub.index') }}">
        <i data-feather="book"></i> <span>FamaiisStudyHub</span>
    </a>
</li>
@endif

@if($is_student)
<li class="{{ (str_starts_with($route ?? '', 'learnhub.') || $prefix == '/famaiis-study-hub')?'active':'' }}">
    <a href="{{ route('learnhub.index') }}">
        <i data-feather="book"></i> <span>FamaiisStudyHub</span>
    </a>
</li>
@endif

@if($is_admin || $is_teacher || $is_student)
<li class="{{ ($prefix == '/homework')?'active':'' }}">
    <a href="{{ route('homework.view') }}">
        <i data-feather="file-text"></i> <span>{{ __('ui.home_work') }}</span>
    </a>
</li>
<li class="{{ ($prefix == '/library')?'active':'' }}">
    <a href="{{ route('library.index') }}">
        <i data-feather="folder"></i> <span>{{ __('ui.elibrary') }}</span>
    </a>
</li>
<li class="{{ ($prefix == '/class-gallery')?'active':'' }}">
    <a href="{{ route('class.gallery.index') }}">
        <i data-feather="image"></i> <span>{{ __('ui.class_gallery') }}</span>
    </a>
</li>
@endif


@if($is_admin || $is_accountant || $user->hasPermission('view_fees'))
<li class="treeview {{ ($prefix == '/fee-management' || $prefix == '/accounts')?'active':'' }}">
          <a href="#">
            <i data-feather="inbox"></i> <span>{{ __('ui.finance_fees') }}</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-right pull-right"></i>
            </span>
          </a>
          <ul class="treeview-menu">
        <li class="header nav-small-cap">{{ __('ui.dynamic_fees') }}</li>
        <li class="{{ ($route == 'fee.types')?'active':'' }}"><a href="{{ route('fee.types') }}"><i class="ti-more"></i>{{ __('ui.fee_types') }}</a></li> 
        <li class="{{ ($route == 'fee.structures')?'active':'' }}"><a href="{{ route('fee.structures') }}"><i class="ti-more"></i>{{ __('ui.fee_structures') }}</a></li> 
        <li class="{{ ($route == 'fee.assign')?'active':'' }}"><a href="{{ route('fee.assign') }}"><i class="ti-more"></i>{{ __('ui.assign_fees') }}</a></li> 
        <li class="{{ ($route == 'fee.payments')?'active':'' }}"><a href="{{ route('fee.payments') }}"><i class="ti-more"></i>{{ __('ui.manage_payments') }}</a></li> 
        <li class="{{ ($route == 'fee.report')?'active':'' }}"><a href="{{ route('fee.report') }}"><i class="ti-more"></i>{{ __('ui.fee_reports') }}</a></li> 
        
        @if($is_admin)
        <li class="{{ ($route == 'wallet.admin.manage')?'active':'' }}"><a href="{{ route('wallet.admin.manage') }}"><i class="ti-more"></i>{{ __('ui.manage_all_wallets') }}</a></li>
        @endif

        <li class="header nav-small-cap">{{ __('ui.general_accounts') }}</li>
        <li class="{{ ($route == 'account.salary.view')?'active':'' }}"><a href="{{ route('account.salary.view') }}"><i class="ti-more"></i>{{ __('ui.employee_salary') }}</a></li> 
        <li class="{{ ($route == 'other.cost.view')?'active':'' }}"><a href="{{ route('other.cost.view') }}"><i class="ti-more"></i>{{ __('ui.other_cost') }}</a></li>
          </ul>
        </li>
@endif

@if($is_parent)
<li class="header nav-small-cap">{{ __('ui.parent_interface') }}</li>
<li class="treeview {{ ($prefix == '/parent')?'active':'' }}">
          <a href="#">
            <i data-feather="book-open"></i> <span> {{ __('ui.parent_dashboard') }}</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-right pull-right"></i>
            </span>
          </a>
          <ul class="treeview-menu">
            <li class="{{ ($route == 'parent.dashboard' || $route == 'parent.academic.dashboard')?'active':'' }}"><a href="{{ route('parent.dashboard') }}"><i class="ti-more"></i>{{ __('ui.my_children') }}</a></li>
            <li class="{{ ($route == 'parent.results')?'active':'' }}"><a href="{{ route('parent.results') }}"><i class="ti-more"></i>{{ __('ui.results') }}</a></li>
            <li class="{{ ($route == 'parent.report.index')?'active':'' }}"><a href="{{ route('parent.report.index') }}"><i class="ti-more"></i>{{ __('ui.child_reports') }}</a></li>
            <li class="{{ ($route == 'parent.fees')?'active':'' }}"><a href="{{ route('parent.fees') }}"><i class="ti-more"></i>{{ __('ui.fees') }}</a></li>
            <li class="{{ ($route == 'parent.shop')?'active':'' }}"><a href="{{ route('parent.shop') }}"><i class="ti-more"></i>{{ __('ui.school_shop') }}</a></li>
          </ul>
</li>
@endif

@if($is_student)
<li class="header nav-small-cap">{{ __('ui.student_interface') }}</li>
<li class="treeview {{ ($prefix == '/student')?'active':'' }}">
          <a href="#">
            <i data-feather="edit"></i> <span> {{ __('ui.computer_based_test') }}</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-right pull-right"></i>
            </span>
          </a>
          <ul class="treeview-menu">
            <li class="{{ ($route == 'student.cbt.index')?'active':'' }}"><a href="{{ route('student.cbt.index') }}"><i class="ti-more"></i>Take Quiz</a></li>
          </ul>
</li>
@endif


@if($is_admin || $user->hasPermission('view_events'))
        <li class="header nav-small-cap">Event Interface</li>
        <li class="treeview {{ ($prefix == '/events')?'active':'' }}">
          <a href="#">
            <i data-feather="calendar"></i> <span> Event Management</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-right pull-right"></i>
            </span>
          </a>
          <ul class="treeview-menu">
            <li class="{{ ($route == 'event.view')?'active':'' }}"><a href="{{ route('event.view') }}"><i class="ti-more"></i>View Events</a></li>
            <li class="{{ ($route == 'event.add')?'active':'' }}"><a href="{{ route('event.add') }}"><i class="ti-more"></i>Add Event</a></li>
          </ul>
        </li>
@endif

@if($is_admin)
        <li class="{{ str_starts_with($route ?? '', 'timetable.') ? 'active' : '' }}">
          <a href="{{ route('timetable.index') }}"><i data-feather="clock"></i> <span>Subject Timetable</span></a>
        </li>
@endif


@if($is_admin || $is_accountant || $user->hasPermission('manage-shop'))
        <li class="header nav-small-cap">School Shop</li>
        <li class="treeview {{ str_contains($prefix, 'shop') || $route == 'payment.setting' ? 'active' : '' }}">
          <a href="#">
            <i data-feather="shopping-cart"></i> <span> School Shop</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-right pull-right"></i>
            </span>
          </a>
          <ul class="treeview-menu">
            <li class="{{ ($route == 'shop.index')?'active':'' }}"><a href="{{ route('shop.index') }}"><i class="ti-more"></i>Browse Shop</a></li>
            <li class="{{ ($route == 'orders.index')?'active':'' }}"><a href="{{ route('orders.index') }}"><i class="ti-more"></i>Order History</a></li>
            @if($is_admin || $is_accountant || $user->hasPermission('manage-shop'))
                <li class="{{ ($route == 'products.index')?'active':'' }}"><a href="{{ route('products.index') }}"><i class="ti-more"></i>Manage Products</a></li>
                <li class="{{ ($route == 'orders.index')?'active':'' }}"><a href="{{ route('orders.index') }}"><i class="ti-more"></i>Manage All Orders</a></li>
                <li class="{{ ($route == 'payment.setting')?'active':'' }}"><a href="{{ route('payment.setting') }}"><i class="ti-more"></i>Bank Transfer Settings</a></li>
            @endif
          </ul>
        </li>
@endif


@if(!$is_parent && !$is_student && ($is_admin || $is_teacher || $user->hasPermission('view_reports')))
        <li class="header nav-small-cap">Report Interface</li>
		  
       <li class="treeview {{ ($prefix == '/reports')?'active':'' }}">
          <a href="#">
            <i data-feather="server"></i></i> <span> Reports Management</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-right pull-right"></i>
            </span>
          </a>
          <ul class="treeview-menu">
    @if($is_admin || $is_accountant)
        <li class="{{ ($route == 'monthly.profit.view')?'active':'' }}"><a href="{{ route('monthly.profit.view') }}"><i class="ti-more"></i>Monthly-Yearly Profit</a></li> 
    @endif
    
    <li class="{{ ($route == 'marksheet.generate.view')?'active':'' }}"><a href="{{ route('marksheet.generate.view') }}"><i class="ti-more"></i>MarkSheet Generate</a></li>
    
    <li class="{{ ($route == 'broadsheet.view')?'active':'' }}"><a href="{{ route('broadsheet.view') }}"><i class="ti-more"></i>Advanced Broadsheet</a></li>

    @if($is_admin || $is_teacher)
           <li class="{{ ($route == 'attendance.report.view')?'active':'' }}"><a href="{{ route('attendance.report.view') }}"><i class="ti-more"></i>Attendance Report</a></li>

           <li class="{{ ($route == 'student.result.view')?'active':'' }}"><a href="{{ route('student.result.view') }}"><i class="ti-more"></i>Student Result </a></li>
    @endif

    @if($user->hasPermission('manage_users'))
           <li class="{{ ($route == 'student.idcard.view')?'active':'' }}"><a href="{{ route('student.idcard.view') }}"><i class="ti-more"></i>Student ID Card </a></li>
           <li class="{{ ($route == 'admin.report.index')?'active':'' }}"><a href="{{ route('admin.report.index') }}"><i class="ti-more"></i>All Activity Reports </a></li>
    @endif
          </ul>
        </li>
@endif
		
		 
		  
		 
        
  @if($is_admin || $is_accountant)
<li class="header nav-small-cap">Settings</li>

<li class="treeview {{ ($prefix == '/setting')?'active':'' }}">
          <a href="#">
            <i data-feather="settings"></i> <span>Site Settings</span>
            <span class="pull-right-container">
              <i class="fa fa-angle-right pull-right"></i>
            </span>
          </a>
          <ul class="treeview-menu">
        <li class="{{ ($route == 'site.setting')?'active':'' }}"><a href="{{ route('site.setting') }}"><i class="ti-more"></i>General Settings</a></li>
        <li class="{{ ($route == 'payment.setting')?'active':'' }}"><a href="{{ route('payment.setting') }}"><i class="ti-more"></i>Payment & Bank Transfer</a></li>
          </ul>
        </li>
@endif


@if($is_teacher || $is_admin)
<li class="{{ str_starts_with($route ?? '', 'ai.') ? 'active' : '' }}">
  <a href="{{ route('ai.tools') }}">
    <i data-feather="cpu"></i> <span>Writing Assistant</span>
  </a>
</li>
@endif

    </ul>
    
    <script>
        // Set scroll position immediately during HTML parsing (before paint) to prevent visual jumps
        (function() {
            const sidebar = document.querySelector('.sidebar');
            if (sidebar) {
                const scrollPos = sessionStorage.getItem('sidebarScroll');
                if (scrollPos) {
                    sidebar.scrollTop = scrollPos;
                }
            }
        })();
    </script>
    </section>
	
	<div class="sidebar-footer">
		@if($is_admin || $is_accountant)
		<!-- item-->
		<a href="{{ route('site.setting') }}" class="link" data-toggle="tooltip" title="" data-original-title="Settings" aria-describedby="tooltip92529"><i class="ti-settings"></i></a>
		@endif
		<!-- item-->
		<a href="mailbox_inbox.html" class="link" data-toggle="tooltip" title="" data-original-title="Email"><i class="ti-email"></i></a>
		<!-- item-->
		<a href="{{ route('admin.logout') }}" class="link" data-toggle="tooltip" title="" data-original-title="Logout"><i class="ti-lock"></i></a>
	</div>
  </aside>

<style>
    /* 1. Global Scrollbar Properties */
    .sidebar::-webkit-scrollbar,
    .main-sidebar::-webkit-scrollbar {
        width: 10px !important;
        display: block !important;
    }

    .sidebar::-webkit-scrollbar-track,
    .main-sidebar::-webkit-scrollbar-track,
    .slimScrollRail {
        background: rgba(0, 0, 0, 0.1) !important;
        border-radius: 10px !important;
        display: block !important;
    }

    /* 2. DARK MODE STYLING (Dark sidebar, Purple scrollbar) */
    body.dark-skin .sidebar::-webkit-scrollbar-thumb,
    body.dark-skin .main-sidebar::-webkit-scrollbar-thumb,
    body.dark-skin .slimScrollBar {
        background: #512da8 !important; /* Theme Purple */
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 10px !important;
        opacity: 0.9 !important;
    }

    /* 3. LIGHT MODE STYLING (Purple sidebar, White scrollbar) */
    body.light-skin .sidebar::-webkit-scrollbar-thumb,
    body.light-skin .main-sidebar::-webkit-scrollbar-thumb,
    body.light-skin .slimScrollBar {
        background: rgba(255, 255, 255, 0.7) !important; /* Semi-transparent White */
        border: 1px solid rgba(0, 0, 0, 0.05);
        border-radius: 10px !important;
        opacity: 0.9 !important;
    }

    /* Firefox Support */
    body.dark-skin .sidebar, body.dark-skin .main-sidebar {
        scrollbar-width: auto !important;
        scrollbar-color: #512da8 rgba(0, 0, 0, 0.1) !important;
    }
    body.light-skin .sidebar, body.light-skin .main-sidebar {
        scrollbar-width: auto !important;
        scrollbar-color: rgba(255, 255, 255, 0.7) rgba(0, 0, 0, 0.1) !important;
    }
</style>

<script>
    // Preserve sidebar scroll position
    document.addEventListener("DOMContentLoaded", function() {
        const sidebar = document.querySelector('.sidebar');
        if (sidebar) {
            const scrollPos = sessionStorage.getItem('sidebarScroll');
            if (scrollPos) {
                sidebar.scrollTop = scrollPos;
            }
            
            // Listen for scroll events on the sidebar itself
            sidebar.addEventListener('scroll', function() {
                sessionStorage.setItem('sidebarScroll', sidebar.scrollTop);
            });

            // Also attach to window beforeunload to ensure we capture the very last position
            // This is helpful if slimScroll or another plugin handles the scrolling
            window.addEventListener('beforeunload', function() {
                sessionStorage.setItem('sidebarScroll', sidebar.scrollTop);
            });

            // Attach to all link clicks in the sidebar to save position immediately
            sidebar.querySelectorAll('a').forEach(link => {
                link.addEventListener('click', function() {
                    sessionStorage.setItem('sidebarScroll', sidebar.scrollTop);
                });
            });
        }
    });
</script>
