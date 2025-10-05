<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('title')->nullable();
            $table->text('content')->default('');
            
            // Numbers
            $table->integer('internal_serial');
            $table->string('client_contract_no', 50)->unique();
            
            // Client data
            $table->string('client_name', 120)->nullable();
            $table->string('client_id_number', 20)->nullable();
            $table->string('client_phone', 20)->nullable();
            $table->string('client_address')->nullable();
            
            // Financial fields
            $table->decimal('investment_amount', 14, 2)->nullable();
            $table->decimal('capital_amount', 14, 2)->nullable();
            $table->decimal('profit_percent', 5, 2)->nullable();
            $table->integer('profit_interval_months')->nullable();
            $table->integer('withdrawal_notice_days')->nullable();
            $table->string('start_date_h')->nullable();
            $table->string('end_date_h')->nullable();
            $table->decimal('commission_percent', 5, 2)->nullable();
            $table->integer('exit_notice_days')->nullable();
            $table->decimal('penalty_amount', 10, 2)->nullable();
            
            // File paths and template
            $table->string('signature_path')->nullable();
            $table->string('template_text')->nullable();
            
            // Status and approval
            $table->string('status')->default('pending');
            $table->text('manager_note')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('contracts');
    }
};
