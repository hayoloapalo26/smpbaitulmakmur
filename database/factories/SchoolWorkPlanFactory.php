<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\SchoolWorkPlan;
use Carbon\Carbon;

class SchoolWorkPlanFactory extends Factory
{
    protected $model = SchoolWorkPlan::class;

    public function definition(): array
    {
        return [
            'institution_id' => 2,
            'document_type' => $this->faker->randomElement(['RKT', 'RKS']),
            'drive_link' => $this->faker->url(),
            'status' => 'draft',
            'created_at' => Carbon::now()->subDays(rand(1, 30)),
            'updated_at' => Carbon::now()->subDays(rand(1, 10)),
        ];
    }
}
