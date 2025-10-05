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
            $table->text('content')->nullable();
            $table->string('client_name')->nullable();
            $table->string('client_id_number')->nullable();
            $table->string('client_phone')->nullable();
            $table->string('client_address')->nullable();
            $table->decimal('investment_amount', 14, 2)->nullable();
            $table->string('signature_path')->nullable();
            $table->string('status')->default('pending');
            $table->string('internal_serial')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('contracts');
    }
};
