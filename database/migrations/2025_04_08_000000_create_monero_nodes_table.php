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
            $table->string('host')
                ->default('127.0.0.1');
            $table->integer('port');
            $table->string('username')
                ->nullable();
            $table->text('password')
                ->nullable();
            $table->text('daemon')
                ->nullable();
            $table->integer('pid')
                ->nullable();
            $table->timestamp('sync_at')
                ->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monero_nodes');
    }
};
