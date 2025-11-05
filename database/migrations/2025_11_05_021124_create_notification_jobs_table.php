<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notification_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('channel')->index('channel');
            $table->string('recipient')->index('recipient');
            $table->text('message');
            $table->enum('status', ['PENDING', 'PROCESSING', 'RETRY', 'SUCCESS', 'FAILED'])->default('PENDING')->index('status_message');
            $table->integer('attempts')->default(0);
            $table->integer('max_attempts')->default(5);
            $table->timestamp('next_run_at')->nullable();
            $table->string('idempotency_key')->nullable()->unique();
            $table->text('last_error')->nullable();
            $table->timestamps();
            $table->timestamp('processed_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('notification_jobs');
    }
};
