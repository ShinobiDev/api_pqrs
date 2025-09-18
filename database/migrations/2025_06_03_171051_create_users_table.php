<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_type_id');
            $table->unsignedBigInteger('client_id')->nullable();
            $table->string('name');
            $table->unsignedBigInteger('document_type_id');
            $table->string('document')->unique();
            $table->unsignedBigInteger('role_id');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->unsignedBigInteger('status_id');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_type_id')->references('id')->on('types');
            $table->foreign('client_id')->references('id')->on('users');
            $table->foreign('document_type_id')->references('id')->on('types');
            $table->foreign('role_id')->references('id')->on('roles');
            $table->foreign('status_id')->references('id')->on('statuses');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
