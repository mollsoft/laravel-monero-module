<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('monero_nodes', function (Blueprint $table) {
            $table->id();
            $table->string('name')
                ->unique();
            $table->string('title')
                ->nullable();
            $table->string('host');
            $table->unsignedInteger('port')
                ->default(38082);
            $table->string('username')
                ->nullable();
            $table->text('password')
                ->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monero_nodes');
    }
};
