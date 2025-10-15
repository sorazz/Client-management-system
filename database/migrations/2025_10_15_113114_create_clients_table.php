<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        
            Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->string('email')->nullable();
            $table->string('phone_number')->nullable();
            $table->boolean('is_duplicate')->default(false);
            $table->unsignedBigInteger('duplicate_of_id')->nullable();
            $table->timestamps();

            $table->index(['company_name','email','phone_number'],'clients_unique_idx');
            $table->foreign('duplicate_of_id')->references('id')->on('clients')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        
        Schema::dropIfExists('clients');
    }
};
