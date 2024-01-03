<?php

namespace App\Jobs;

use App\Models\Exam;
use Http;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UploadExamResultJob implements ShouldQueue
{
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  /**
   * Create a new job instance.
   */
  public function __construct(private Exam $exam)
  {
    $exam->load('examItems');
  }

  /**
   * Execute the job.
   */
  public function handle(): void
  {
    $url = config("services.platform.upload-exam.{$this->exam->platform}");
    $res = Http::post($url, [
      ...$this->exam->toArray(),
      'exam_no' => $this->exam->getOriginalExamNo()
    ]);
    if (!$res->ok()) {
      info('UploadExamResultJob: Error uploading ' . $this->exam->exam_no);
    }
    $res->json();
  }
}
