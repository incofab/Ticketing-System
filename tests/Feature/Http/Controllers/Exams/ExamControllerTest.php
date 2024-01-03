<?php

use App\Models\Exam;
use App\Models\ExamItem;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\postJson;
use function PHPUnit\Framework\assertNotNull;

beforeEach(function () {});

it('deletes an exam', function () {
  $exam = Exam::factory()->create();
  actingAs(User::factory()->create())
    ->deleteJson(route('api.exams.destroy', [$exam]))
    ->assertStatus(200);
  assertDatabaseMissing('exams', ['id' => $exam->id]);
});

it('stores a new exam and exam items', function () {
  $data = Exam::factory()
    ->make()
    ->toArray();
  postJson(route('api.exams.store'), [
    ...$data,
    'subject_details' => ExamItem::factory(2)
      ->make()
      ->toArray()
  ])
    // ->dump()
    ->assertStatus(200);

  $exam = Exam::where(
    'exam_no',
    Exam::platformExamNo($data['platform'], $data['exam_no'])
  )->first();
  assertNotNull($exam);
});
