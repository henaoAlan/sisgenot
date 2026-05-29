<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addColumnIfMissing('users', 'identification', fn (Blueprint $table) => $table->string('identification', 50)->nullable()->unique()->after('full_name'));
        $this->addColumnIfMissing('users', 'phone', fn (Blueprint $table) => $table->string('phone', 30)->nullable()->after('identification'));
        $this->addColumnIfMissing('users', 'profile_photo_path', fn (Blueprint $table) => $table->string('profile_photo_path')->nullable()->after('phone'));
        $this->addColumnIfMissing('users', 'auth_token', fn (Blueprint $table) => $table->string('auth_token', 12)->nullable()->after('profile_photo_path'));
        $this->addColumnIfMissing('users', 'auth_token_sent_at', fn (Blueprint $table) => $table->timestamp('auth_token_sent_at')->nullable()->after('auth_token'));
        $this->addColumnIfMissing('users', 'password_reset_token', fn (Blueprint $table) => $table->string('password_reset_token')->nullable()->after('auth_token_sent_at'));
        $this->addColumnIfMissing('users', 'password_reset_sent_at', fn (Blueprint $table) => $table->timestamp('password_reset_sent_at')->nullable()->after('password_reset_token'));
        $this->addColumnIfMissing('users', 'must_change_password', fn (Blueprint $table) => $table->boolean('must_change_password')->default(false)->after('password_reset_sent_at'));

        $this->addColumnIfMissing('courses', 'shift', fn (Blueprint $table) => $table->string('shift', 40)->nullable()->after('grade'));
        $this->addColumnIfMissing('courses', 'schedule_label', fn (Blueprint $table) => $table->string('schedule_label', 120)->nullable()->after('shift'));

        $this->addColumnIfMissing('subjects', 'course_id', fn (Blueprint $table) => $table->foreignId('course_id')
            ->nullable()
            ->after('code')
            ->constrained('courses')
            ->nullOnDelete());

        if (! Schema::hasTable('academic_activities')) {
            Schema::create('academic_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_assignment_id')->constrained('teacher_assignments')->cascadeOnDelete();
            $table->foreignId('period_id')->constrained('periods')->cascadeOnDelete();
            $table->unsignedTinyInteger('moment');
            $table->unsignedTinyInteger('activity_number');
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('due_date')->nullable();
            $table->boolean('is_recovery')->default(false);
            $table->timestamps();

            $table->unique(
                ['teacher_assignment_id', 'period_id', 'moment', 'activity_number', 'is_recovery'],
                'uq_activity_slot'
            );
            });
        }

        if (! Schema::hasTable('activity_grades')) {
            Schema::create('activity_grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_activity_id')->constrained('academic_activities')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->decimal('grade', 3, 2)->nullable();
            $table->text('feedback')->nullable();
            $table->timestamps();

            $table->unique(['academic_activity_id', 'student_id'], 'uq_activity_grade_student');
            });
        }

        if (! Schema::hasTable('activity_submissions')) {
            Schema::create('activity_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_activity_id')->constrained('academic_activities')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('file_path')->nullable();
            $table->text('comment')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->unique(['academic_activity_id', 'student_id'], 'uq_activity_submission_student');
            });
        }

        if (! Schema::hasTable('student_observations')) {
            Schema::create('student_observations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained('teachers')->nullOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->foreignId('period_id')->nullable()->constrained('periods')->nullOnDelete();
            $table->text('observation');
            $table->timestamps();
            });
        }

        if (! Schema::hasTable('schedules')) {
            Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained('teachers')->nullOnDelete();
            $table->string('day_of_week', 20);
            $table->time('starts_at');
            $table->time('ends_at');
            $table->string('classroom', 80)->nullable();
            $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('schedules');
        Schema::dropIfExists('student_observations');
        Schema::dropIfExists('activity_submissions');
        Schema::dropIfExists('activity_grades');
        Schema::dropIfExists('academic_activities');

        Schema::table('subjects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('course_id');
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn(['shift', 'schedule_label']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'identification',
                'phone',
                'profile_photo_path',
                'auth_token',
                'auth_token_sent_at',
                'password_reset_token',
                'password_reset_sent_at',
                'must_change_password',
            ]);
        });
    }

    private function addColumnIfMissing(string $tableName, string $columnName, callable $definition): void
    {
        if (Schema::hasColumn($tableName, $columnName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($definition) {
            $definition($table);
        });
    }
};
