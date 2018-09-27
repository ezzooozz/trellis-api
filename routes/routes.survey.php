<?php

//***************************//
//* Token Controller Routes *//
//***************************//


use Illuminate\Support\Facades\Artisan;

$router->group([
    'prefix' => 'survey-view',
    'middleware' => ['key'],
], function () use ($router) {
    $router->post('login',                                      'TokenController@createToken');
});

$router->group([
    'prefix' => 'survey-view',
    'middleware' => ['key', 'token']
], function () use ($router) {

    $router->get('user/me',                                     'UserController@getMe');

    $router->post('condition-tag',                              'ConditionTagController@createConditionTag');
    $router->get('condition-tags',                              'ConditionTagController@getAllConditionTags');
    $router->get('condition-tags/respondent',                   'ConditionController@getAllRespondentConditionTags');


    $router->post('translation-text/{translation_text_id}',     'TranslationTextController@updateTranslatedTextById');

    $router->get('form/{form_id}',                              'FormController@getForm');

    $router->group([
        'prefix' => 'interview/{i_id}'
    ], function () use ($router) {
        $router->get('actions',                                 'InterviewDataController@getInterviewActionsByInterviewId');
        $router->get('data',                                    'InterviewDataController@getInterviewDataByInterviewId');
        $router->get('preload',                                 'PreloadController@getPreloadDataByInterviewId');
        $router->get('/',                                       'InterviewController@getInterview');
        $router->post('complete',                               'InterviewController@completeInterview');
        $router->post('actions',                                'InterviewDataController@saveInterviewActions');
        $router->post('data',                                   'InterviewDataController@updateInterviewData');
    });

    $router->get('survey/{s_id}',                               'SurveyController@getSurveyById');
    $router->post('survey/{s_id}/interview',                    'InterviewController@createInterview');
    $router->post('survey/{survey_id}/complete',                'SurveyController@completeSurvey');

    $router->get('locale/{id}',                                 'LocaleController@getLocale');

    // Study routes
    $router->get('studies',                                     'StudyController@getAllStudiesComplete');

    $router->group(['prefix' => 'study/{s_id}'], function () use ($router) {
        $router->get('respondent/{r_id}/form/{f_id}/survey',    'SurveyController@getStudySurveyByFormId');
        $router->post('respondent/{r_id}/form/{f_id}/survey',   'SurveyController@createSurvey');
        $router->get('respondents/search',                      'RespondentController@searchRespondentsByStudyId');
        $router->get('respondents',                             'RespondentController@getAllRespondentsByStudyId');
        $router->get('/',                                       'StudyController@getStudy');
        $router->get('respondent/{r_id}/surveys',               'SurveyController@getRespondentStudySurveys');
        $router->post('respondent',                             'RespondentController@createStudyRespondent');
        $router->get('forms/published',                         'FormController@getPublishedForms');
        $router->get('form/census',                             'CensusFormController@getStudyCensusForm');
        $router->get('locales',                                 'StudyController@getLocales');
    });


    // Respondent survey routes
    $router->group(['prefix' => 'respondent/{respondent_id}'], function () use ($router) {
        $router->get('/',                                       'RespondentController@getRespondentById');
        $router->get('fills',                                   'RespondentController@getRespondentFillsById');
        $router->post('name',                                   'RespondentNameController@createRespondentName');
        $router->delete('name/{respondent_name_id}',            'RespondentNameController@deleteRespondentName');
        $router->put('name/{respondent_name_id}',               'RespondentNameController@editRespondentName');
        $router->post('geo',                                    'RespondentGeoController@createRespondentGeo');
        $router->post('geo/{respondent_geo_id}/move',           'RespondentGeoController@moveRespondentGeo');
        $router->delete('geo/{respondent_geo_id}',              'RespondentGeoController@deleteRespondentGeo');
        $router->post('condition-tag/{c_id}',                   'ConditionTagController@createRespondentConditionTag');
        $router->delete('condition-tag/{condition_tag_id}',     'ConditionTagController@deleteRespondentConditionTag');
    });


    $router->post('edges',                                      'EdgeController@createEdges');
    $router->get('edges/{e_ids}',                               'EdgeController@getEdgesById');

    $router->post('rosters',                                    'RosterController@createRosterRows');
    $router->get('rosters/{r_ids}',                             'RosterController@getRostersById');
    $router->put('rosters',                                     'RosterController@editRosterRows');

    $router->put('geo',                                         'GeoController@createGeoFromModel');
    $router->get('geos/{g_ids}',                                'GeoController@getGeosById');
    $router->get('geos/parent/{parent_id}',                     'GeoController@getGeosByParentId');
    $router->get('geo/search',                                  'GeoController@searchGeos');
    $router->get('geo/{geo_id}/ancestors',                      'GeoController@getAncestorsForGeoId');
    $router->delete('geo/{geo_id}',                             'GeoController@removeGeo');
    $router->post('geo/{geo_id}/move',                          'GeoController@moveGeo');

    $router->get('geo-types',                                   'GeoTypeController@getGeoTypes');

    $router->get('photo/{p_id}',                                'PhotoController@getPhoto');

    $router->get('me/studies',                                  'UserController@getMyStudies');
});