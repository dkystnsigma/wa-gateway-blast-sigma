<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIncomingMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('incoming_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->onDelete('cascade');
            $table->string('sender');
            $table->text('message_content');
            $table->string('message_type')->default('text');
            $table->timestamp('timestamp');
            $table->boolean('is_read')->default(false);
            $table->json('raw_data')->nullable();
            $table->timestamps();
            
            $table->index(['device_id', 'timestamp']);
            $table->index(['sender', 'created_at']);
            $table->index(['is_read']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('incoming_messages');
    }
}
