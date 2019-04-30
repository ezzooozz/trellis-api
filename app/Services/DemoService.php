<?php

namespace app\Services;

use App\Models\ConditionTag;
use App\Models\Config;
use App\Models\Locale;
use App\Models\Study;
use App\Models\StudyLocale;
use App\Models\User;
use App\Models\UserStudy;
use App\Services\RespondentService;
use App\Services\FormService;
use Log;
use Ramsey\Uuid\Uuid;

class DemoService {

    public function makeDemoUser ($username, $email, $hash, $role) {
      $user = new User;
      $user->id = Uuid::uuid4();
      $user->password = $hash;
      $user->username = $username;
      $user->email = $email;
      $user->role = $role;
      $user->save();

      $study = new Study;
      $study->id = Uuid::uuid4();
      $study->name = $user->username . ' Study';
      $study->default_locale_id = Locale::where('language_name', 'like', 'english')->first()->id;
      $study->save();

      $studyLocale = new StudyLocale;
      $studyLocale->id = Uuid::uuid4();
      $studyLocale->locale_id = $study->default_locale_id;
      $studyLocale->study_id = $study->id;
      $studyLocale->save();

      $userStudy = new UserStudy;
      $userStudy->id = Uuid::uuid4();
      $userStudy->study_id = $study->id;
      $userStudy->user_id = $user->id;
      $userStudy->save();

      // Import demo respondents and assign them to this study
      Log::debug('Adding respondents to demo user study');
      $numRespondents = RespondentService::importRespondentsFromFile(resource_path('demo/respondents.csv'), $study->id, true);
      Log::debug("Added $numRespondents respondents to this study");
      // TODO: Import respondent photos as well
      Log::debug('Adding respondent photos to demo user study');
      $numPhotos = RespondentService::importRespondentPhotos(resource_path('demo/respondent_photos.zip'), $study->id);
      Log::debug("Added $numPhotos photos to this study");
      // TODO: Import demo locations and assign them to this study

      // Load all of the forms
      $importedForm = FormService::importFormAndAddToStudy(resource_path('demo/forms/example-question-types.json'), 'Example Question Types', $study->id, 0);
      $importedForm->is_published = true;
      $importedForm->save();
    }
}
