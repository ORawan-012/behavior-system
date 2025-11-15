<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Sessions (Laravel auth)
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        // Password reset tokens (Laravel auth)
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        // Cache tables (Laravel cache)
        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->integer('expiration');
        });

        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('owner');
            $table->integer('expiration');
        });

        // Users
        Schema::create('tb_users', function (Blueprint $table) {
            $table->bigInteger('users_id')->primary()->autoIncrement();
            $table->string('users_name_prefix', 20)->nullable();
            $table->string('users_first_name', 100)->nullable();
            $table->string('users_last_name', 100)->nullable();
            $table->string('users_email', 150)->unique()->nullable();
            $table->string('users_phone_number', 20)->nullable();
            $table->string('users_password', 255)->nullable();
            $table->enum('users_role', ['admin', 'teacher', 'student', 'guardian'])->nullable();
            $table->string('users_profile_image', 255)->nullable();
            $table->date('users_birthdate')->nullable();
            $table->enum('users_status', ['active', 'inactive', 'suspended'])->default('active');
            $table->timestamp('users_created_at')->useCurrent();
            $table->timestamp('users_updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        // Classes (no academic_year per latest schema)
        Schema::create('tb_classes', function (Blueprint $table) {
            $table->bigInteger('classes_id')->primary()->autoIncrement();
            $table->string('classes_level', 10)->nullable();
            $table->string('classes_room_number', 10)->nullable();
            $table->bigInteger('teachers_id')->nullable();
        });

        // Teachers
        Schema::create('tb_teachers', function (Blueprint $table) {
            $table->bigInteger('teachers_id')->primary()->autoIncrement();
            $table->bigInteger('users_id');
            $table->string('teachers_employee_code', 20)->nullable();
            $table->string('teachers_position', 50)->nullable();
            $table->string('teachers_department', 100)->nullable();
            $table->string('teachers_major', 100)->nullable();
            $table->boolean('teachers_is_homeroom_teacher')->nullable();
            $table->bigInteger('assigned_class_id')->nullable();

            $table->foreign('users_id')->references('users_id')->on('tb_users')->onDelete('cascade');
            $table->foreign('assigned_class_id')->references('classes_id')->on('tb_classes')->onDelete('set null');
        });

        // Students (with final status values including 'transferred')
        Schema::create('tb_students', function (Blueprint $table) {
            $table->bigInteger('students_id')->primary()->autoIncrement();
            $table->bigInteger('user_id');
            $table->string('students_student_code', 20)->nullable();
            $table->bigInteger('class_id')->nullable();
            $table->string('students_academic_year', 10)->nullable();
            $table->integer('students_current_score')->nullable();
            $table->enum('students_status', ['active', 'suspended', 'expelled', 'graduate', 'transferred'])->nullable();
            $table->enum('students_gender', ['male', 'female', 'other'])->nullable();
            $table->timestamp('students_created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('user_id')->references('users_id')->on('tb_users')->onDelete('cascade');
            $table->foreign('class_id')->references('classes_id')->on('tb_classes')->onDelete('set null');
        });

        // Guardians
        Schema::create('tb_guardians', function (Blueprint $table) {
            $table->bigInteger('guardians_id')->primary()->autoIncrement();
            $table->bigInteger('user_id');
            $table->string('guardians_relationship_to_student', 50)->nullable();
            $table->string('guardians_phone', 20)->nullable();
            $table->string('guardians_email', 150)->nullable();
            $table->string('guardians_line_id', 100)->nullable();
            $table->enum('guardians_preferred_contact_method', ['phone', 'email', 'line'])->nullable();
            $table->timestamp('guardians_created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('user_id')->references('users_id')->on('tb_users')->onDelete('cascade');
        });

        // Guardian-Student pivot
        Schema::create('tb_guardian_student', function (Blueprint $table) {
            $table->bigInteger('guardian_student_id')->primary()->autoIncrement();
            $table->bigInteger('guardian_id')->nullable();
            $table->bigInteger('student_id')->nullable();
            $table->timestamp('guardian_student_created_at')->useCurrent();

            $table->foreign('guardian_id')->references('guardians_id')->on('tb_guardians')->onDelete('cascade');
            $table->foreign('student_id')->references('students_id')->on('tb_students')->onDelete('cascade');
        });

        // Violations
        Schema::create('tb_violations', function (Blueprint $table) {
            $table->bigInteger('violations_id')->primary()->autoIncrement();
            $table->string('violations_name', 150)->nullable();
            $table->text('violations_description')->nullable();
            $table->enum('violations_category', ['light', 'medium', 'severe'])->nullable();
            $table->integer('violations_points_deducted')->nullable();
        });

        // Behavior Reports
        Schema::create('tb_behavior_reports', function (Blueprint $table) {
            $table->bigInteger('reports_id')->primary()->autoIncrement();
            $table->bigInteger('student_id');
            $table->bigInteger('teacher_id');
            $table->bigInteger('violation_id');
            $table->text('reports_description')->nullable();
            $table->string('reports_evidence_path', 255)->nullable();
            $table->timestamp('reports_report_date')->useCurrent()->useCurrentOnUpdate();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('student_id')->references('students_id')->on('tb_students')->onDelete('cascade');
            $table->foreign('teacher_id')->references('teachers_id')->on('tb_teachers')->onDelete('cascade');
            $table->foreign('violation_id')->references('violations_id')->on('tb_violations')->onDelete('cascade');
        });

        // Behavior Logs
        Schema::create('tb_behavior_logs', function (Blueprint $table) {
            $table->bigInteger('id')->primary()->autoIncrement();
            $table->bigInteger('behavior_report_id');
            $table->enum('action_type', ['create', 'update', 'delete']);
            $table->bigInteger('performed_by');
            $table->json('before_change')->nullable();
            $table->json('after_change')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('behavior_report_id')->references('reports_id')->on('tb_behavior_reports')->onDelete('cascade');
            $table->foreign('performed_by')->references('users_id')->on('tb_users')->onDelete('cascade');
        });

        // Notifications
        Schema::create('tb_notifications', function (Blueprint $table) {
            $table->bigInteger('id')->primary()->autoIncrement();
            $table->bigInteger('user_id');
            $table->string('type', 50);
            $table->string('title', 150);
            $table->text('message')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('user_id')->references('users_id')->on('tb_users')->onDelete('cascade');
        });

        // Add FK constraint for tb_classes -> tb_teachers (named as before)
        Schema::table('tb_classes', function (Blueprint $table) {
            $table->foreign('teachers_id', 'fk_class_teacher')->references('teachers_id')->on('tb_teachers')->onDelete('set null');
        });

        // Indexes consolidation
        Schema::table('tb_users', function (Blueprint $table) {
            $table->index('users_email', 'idx_users_email');
            $table->index('users_role', 'idx_users_role');
            $table->index(['users_role', 'users_status'], 'idx_users_role_status');
        });

        Schema::table('tb_students', function (Blueprint $table) {
            $table->index('user_id', 'idx_students_user_id');
            $table->index('class_id', 'idx_students_class_id');
            $table->index('students_student_code', 'idx_students_code');
            $table->index('students_status', 'idx_students_status');
            $table->index('students_current_score', 'idx_students_score');
            $table->index(['class_id', 'students_current_score'], 'idx_students_class_score');
            $table->index(['students_status', 'students_current_score'], 'idx_students_status_score');
        });

        Schema::table('tb_teachers', function (Blueprint $table) {
            $table->index('users_id', 'idx_teachers_user_id');
            $table->index('teachers_employee_code', 'idx_teachers_employee_code');
            $table->index('teachers_department', 'idx_teachers_department');
            $table->index('teachers_position', 'idx_teachers_position');
            $table->index('teachers_is_homeroom_teacher', 'idx_teachers_homeroom');
            $table->index('assigned_class_id', 'idx_teachers_assigned_class');
        });

        Schema::table('tb_classes', function (Blueprint $table) {
            $table->index('teachers_id', 'idx_classes_teacher_id');
            $table->index(['classes_level', 'classes_room_number'], 'idx_classes_level_room');
        });

        Schema::table('tb_behavior_reports', function (Blueprint $table) {
            $table->index('student_id', 'idx_reports_student_id');
            $table->index('teacher_id', 'idx_reports_teacher_id');
            $table->index('violation_id', 'idx_reports_violation_id');
            $table->index('reports_report_date', 'idx_reports_date');
            $table->index(['student_id', 'reports_report_date'], 'idx_reports_student_date');
            $table->index(['teacher_id', 'reports_report_date'], 'idx_reports_teacher_date');
            $table->index(['violation_id', 'reports_report_date'], 'idx_reports_violation_date');
            $table->index(['reports_report_date', 'student_id'], 'idx_reports_date_student');
        });

        Schema::table('tb_violations', function (Blueprint $table) {
            $table->index('violations_category', 'idx_violations_category');
            $table->index('violations_points_deducted', 'idx_violations_points');
            $table->index(['violations_category', 'violations_points_deducted'], 'idx_violations_category_points');
        });

        Schema::table('tb_guardians', function (Blueprint $table) {
            $table->index('user_id', 'idx_guardians_user_id');
            $table->index('guardians_phone', 'idx_guardians_phone');
            $table->index('guardians_email', 'idx_guardians_email');
            $table->index('guardians_preferred_contact_method', 'idx_guardians_contact_method');
        });

        Schema::table('tb_guardian_student', function (Blueprint $table) {
            $table->index('guardian_id', 'idx_guardian_student_guardian');
            $table->index('student_id', 'idx_guardian_student_student');
            $table->index(['guardian_id', 'student_id'], 'idx_guardian_student_both');
        });

        Schema::table('tb_notifications', function (Blueprint $table) {
            $table->index('user_id', 'idx_notifications_user_id');
            $table->index('type', 'idx_notifications_type');
            $table->index('read_at', 'idx_notifications_read_at');
            $table->index('created_at', 'idx_notifications_created_at');
            $table->index(['user_id', 'read_at'], 'idx_notifications_user_read');
            $table->index(['user_id', 'created_at'], 'idx_notifications_user_created');
            $table->index(['user_id', 'type', 'read_at'], 'idx_notifications_user_type_read');
        });

        // Seed stable classes (ม.1-ม.6, ห้อง 1-12)
        $classes = [];
        for ($level = 1; $level <= 6; $level++) {
            for ($room = 1; $room <= 12; $room++) {
                $classes[] = [
                    'classes_level' => 'ม.' . $level,
                    'classes_room_number' => (string) $room,
                    'teachers_id' => null
                ];
            }
        }
        DB::table('tb_classes')->insert($classes);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tb_behavior_logs');
        Schema::dropIfExists('tb_behavior_reports');
        Schema::dropIfExists('tb_notifications');
        Schema::dropIfExists('tb_guardian_student');
        Schema::dropIfExists('tb_guardians');
        Schema::dropIfExists('tb_students');
        Schema::dropIfExists('tb_teachers');
        Schema::dropIfExists('tb_violations');
        Schema::dropIfExists('tb_classes');
        Schema::dropIfExists('tb_users');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('cache');
        Schema::dropIfExists('cache_locks');
    }
};
