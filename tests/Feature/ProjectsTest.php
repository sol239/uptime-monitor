<?php

use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get('/projects');
    $response->assertRedirect('/login');
});

test('authenticated users can visit the projects', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get('/projects');
    $response->assertStatus(200);
});

test('not authenticated users can not visit the projects', function () {
    $response = $this->get('/projects');
    $response->assertRedirect('/login');
});
