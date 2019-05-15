<?php

namespace App\Http\Controllers;

use App\Models\Geo;
use App\Models\RespondentGeo;
use App\Services\RespondentService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Validator;


class RespondentGeoController extends Controller {

    /**
     * Create a respondent geo with the supplied geo id
     * @param Request $request
     * @param string $respondentId
     * @param string $geoId
     * @return \Illuminate\Http\JsonResponse
     */
    public function createRespondentGeo (Request $request, $respondentId) {
        $respondentId = urldecode($respondentId);
        $validator = Validator::make([
            'respondentId' => $respondentId,
            'geoId' => $request->get('geo_id'),
            'is_current' => $request->get('is_current'),
            'previous_respondent_geo_id' => $request->get('previous_respondent_geo_id')
        ], [
            'respondentId' => 'required|string|min:36|exists:respondent,id',
            'geoId' => 'nullable|string|min:36|exists:geo,id',
            'is_current' => 'nullable|boolean',
            'previous_respondent_geo_id' => 'nullable|string|min:36|exists:respondent_geo,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'msg' => $validator->errors()
            ], $validator->statusCode());
        }

        $respondentGeo = RespondentService::createRespondentGeo(
            $respondentId,
            $request->get('geo_id'),
            $request->get('is_current'),
            $request->get('previous_respondent_geo_id')
        );

        $geo = Geo::with('nameTranslation', 'photos', 'geoType')->find($respondentGeo->geo_id);
        $geo->pivot = [
            'id' => $respondentGeo->id,
            'is_current' => $respondentGeo->is_current,
            'previous_respondent_geo_id' => $respondentGeo->previous_respondent_geo_id,
            'deleted_at' => $respondentGeo->deleted_at,
            'notes' => $respondentGeo->notes
        ];

        return response()->json([
            'geo' => $geo
        ], Response::HTTP_OK);
    }

    /**
     * Update a single respondent location
     * @param Request $request
     * @param $respondentId
     * @param $respondentGeoId
     */
    public function editRespondentGeo (Request $request, $respondentId, $respondentGeoId) {
        $validator = Validator::make([
//            'respondent' => $respondentId,
            'respondent_geo' => $respondentGeoId,
            'is_current' => $request->get('is_current')
        ], [
//            'respondent' => 'string|exists:respondent,id',
            'is_current' => 'boolean',
            'respondent_geo' => 'string|exists:respondent_geo,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'err' => $validator->errors()
            ], $validator->statusCode());
        }

        $respondentGeo = RespondentGeo::find($respondentGeoId);
        $respondentGeo->is_current = $request->get('is_current');
        $respondentGeo->save();

        return response()->json([
            'respondent_geo' => $respondentGeo
        ]);

    }

    /**
     * Move a respondent geo to a new geo keeping the link between the two geos
     * @param Request $request
     * @param string $respondentId
     * @param string $respondentGeoId
     * @return \Illuminate\Http\JsonResponse
     */
    public function moveRespondentGeo (Request $request, $respondentId, $respondentGeoId) {
        $respondentGeoId = urldecode($respondentGeoId);
        $validator = Validator::make([
            'respondentGeoId' => $respondentGeoId,
            'new_geo_id' => $request->get('new_geo_id'),
            'is_current' => $request->get('is_current'),
            'notes' => $request->get('notes')
        ], [
            'respondentGeoId' => 'required|string|min:36|exists:respondent_geo,id',
            'new_geo_id' => 'nullable|string|min:36|exists:geo,id',
            'is_current' => 'nullable|boolean',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'msg' => $validator->errors()
            ], $validator->statusCode());
        }

        $respondentGeo = RespondentService::moveRespondentGeo($respondentGeoId, $request->get('new_geo_id'), $request->get('is_current'), $request->get('notes'));
        $geo = null;
        if (isset($respondentGeo->geo_id)) {
            $geo = Geo::with('nameTranslation', 'photos', 'parent', 'geoType')->find($respondentGeo->geo_id);
            $respondentGeo->geo = $geo;
        }

        return response()->json([
            'respondentGeo' => $respondentGeo
        ], Response::HTTP_OK);

    }

    /**
     * Soft delete a respondent geo by id
     * @param string $respondentId
     * @param string $respondentGeoId
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteRespondentGeo ($respondentId, $respondentGeoId) {

        RespondentGeo::destroy($respondentGeoId);

        return response()->json([
            'msg' => "Deleted respondent_geo, $respondentGeoId"
        ], Response::HTTP_OK);

    }

}