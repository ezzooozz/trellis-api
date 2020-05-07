<?php

namespace App\Console\Commands;

use App\Jobs\EdgeReportJob;
use App\Jobs\FormReportJob;
use App\Jobs\GeoReportJob;
use App\Jobs\InterviewReportJob;
use App\Jobs\RespondentReportJob;
use App\Jobs\TimingReportJob;
use App\Models\Report;
use App\Models\Study;
use App\Models\Form;
use Illuminate\Console\Command;
use Illuminate\Contracts\Queue\Job;
use Laravel\Lumen\Routing\DispatchesJobs;
use DB;
use Log;
use Queue;
use Ramsey\Uuid\Uuid;

class MakeReports extends Command
{
    use DispatchesJobs, AutoDispatch {
        AutoDispatch::dispatch insteadof DispatchesJobs;
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trellis:make:reports {--skip-main} {--skip-forms} {--locale=} {--form=} {--study=*}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run each type of report once to get the latest data';

    /**
     * Execute the console command.
     *
     * To call from within code:
     *
     * ob_start();
     *
     * \Illuminate\Support\Facades\Artisan::call('trellis:check:models');
     *
     * $result = json_decode(ob_get_clean(), true);
     *
     * @return mixed
     */
    public function handle()
    {
        ini_set("memory_limit", "-1");
        set_time_limit(0);
        Queue::after(function ($connection, $job, $data) {
            Log::debug("Finished job: ", $job->id, $data);
        });

        $remainingJobIds = [];
        $studyIds = $this->option('study');
        if (count($studyIds) === 0) {
          $studyIds = Study::whereNull('deleted_at')->get()->map(function ($s) { return $s->id; });
        }
        $studyCount = count($studyIds);
        $this->info("Generating reports for $studyCount studies");

        foreach ($studyIds as $studyId) {
          $this->info("Generating reports for study $studyId");
          $this->makeStudyReports($studyId);
        }

    }

    private function makeStudyReports ($studyId) {
        $study = Study::where("id", "=", $studyId)->with("defaultLocale")->first();
        
        // if (!isset($study)) {
        //     throw Exception('Study id must be valid');
        // }

        $mainJobConstructors = [InterviewReportJob::class, EdgeReportJob::class, GeoReportJob::class, TimingReportJob::class, RespondentReportJob::class];

        if (!$this->option('skip-main')) {
            foreach ($mainJobConstructors as $constructor){
                $reportId = Uuid::uuid4();
                $reportJob = new $constructor($study->id, $reportId, new \stdClass());
                $this->info("Queued $constructor");
                $reportJob->handle();
                // $this->dispatch($reportJob);
            }
        }

        if (!$this->option('skip-forms')) {
            if ($this->option('form')) {
                $formIds = [$this->option('form')];
            } else {
                $formIds = Form::select('id')
                    ->whereIn('id', function ($q) use ($studyId) {
                        $q->select('form_master_id')
                            ->from('study_form')
                            ->where('study_id', $studyId);
                    })
                    ->whereNull('deleted_at')
                    ->where('is_published', true)
                    ->get()
                    ->map(function ($item) {
                        return $item->id;
                    });
            }

            $config = new \stdClass();
            $config->studyId = $studyId;
            $config->useChoiceNames = true;
            $config->locale = $study->defaultLocale->id;
            $config->locale = "48984fbe-84d4-11e5-ba05-0800279114ca";

            foreach ($formIds as $formId){
                $reportId = Uuid::uuid4();
                $reportJob = new FormReportJob($formId, $reportId, $config);
                $this->info("Queued FormReportJob for form, $formId");
                $reportJob->handle();
                // $this->dispatch($reportJob);
            }
        }
    }

}
