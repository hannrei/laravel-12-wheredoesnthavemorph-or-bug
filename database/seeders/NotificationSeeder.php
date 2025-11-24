<?php

namespace Database\Seeders;

use App\Models\Notification;
use App\Models\Post;
use App\Models\Video;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    public function run()
    {
        Notification::factory()->count(10)->create();

        for ($i = 0; $i < 10; $i++) {
            Notification::factory()
                ->for(Video::factory(), 'notifiable')
                ->create();
        }

        for ($i = 0; $i < 10; $i++) {
            Notification::factory()
                ->for(Post::factory(), 'notifiable')
                ->create();
        }
    }
}
