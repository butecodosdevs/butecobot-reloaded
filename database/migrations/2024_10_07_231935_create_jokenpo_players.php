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
        Schema::create('jokenpo_players', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('jokenpo_id');
            $table->unsignedBigInteger('user_id');
            $table->string('move');
            $table->decimal('amount', 10, 2);
            $table->enum('result', ['ganhou', 'perdeu', 'empate'])->nullable();
            $table->timestamps();

            $table->index('jokenpo_id');
            $table->index('user_id');

            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('jokenpo_id')->references('id')->on('jokenpo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jokenpo_players');
    }
};
