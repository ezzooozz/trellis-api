<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Validator;
use App\Models\Geo;
use App\Library\TranslationHelper;
use Ramsey\Uuid\Uuid;
use DB;

class GeoController extends Controller
{
	public function getGeo(Request $request, $id) {

		$validator = Validator::make(
			['id' => $id],
			['id' => 'required|string|min:36|exists:geo,id']
		);

		if ($validator->fails() === true) {
			return response()->json([
				'msg' => 'Validation failed',
				'err' => $validator->errors()
			], $validator->statusCode());
		}

		$geoModel = Geo::find($id);

		if ($geoModel === null) {
			return response()->json([
				'msg' => 'URL resource not found'
			], Response::HTTP_OK);
		}

		return response()->json([
			'geo' => $geoModel
		], Response::HTTP_OK);
	}

	public function getAllGeos(Request $request, $localeId) {

		$validator = Validator::make(array_merge($request->all(),[
			'localeId' => $localeId
		]), [
			'localeId' => 'required|string|min:36|exists:locale,id'
		]);

		if ($validator->fails() === true) {
			return response()->json([
				'msg' => 'Validation failed',
				'err' => $validator->errors()
			], $validator->statusCode());
		}

		$geoModel = Geo::select('geo.id', 'gt.name AS type_name', 'geo.parent_id', 'geo.latitude', 'geo.longitude', 'geo.altitude', 'tt.translated_text AS name')
			->join('translation_text AS tt', 'tt.translation_id', '=', 'geo.name_translation_id')
			->join('geo_type AS gt', 'gt.id', '=', 'geo.geo_type_id')
			->where('tt.locale_id', $localeId)
			->get();

		return response()->json(
			['geos' => $geoModel],
			Response::HTTP_OK
		);
	}

	public function updateGeo(Request $request, $id) {

		$validator = Validator::make(array_merge($request->all(),[
			'id' => $id
		]), [
			'id' => 'required|string|min:36',
			'geo_type_id' => 'string|min:36',
			'parent_id' => 'string|min:36',
			'latitude' => 'integer|min:1',
			'longitude' => 'integer|min:1',
			'altitude' => 'integer|min:1',
			'name_translation_id' => 'string|min:36'
		]);

		if ($validator->fails() === true) {
			return response()->json([
				'msg' => 'Validation failed',
				'err' => $validator->errors()
			], $validator->statusCode());
		}

		$geoModel = Geo::find($id);

		if ($geoModel === null) {
			return response()->json([
				'msg' => 'URL resource not found'
			], Response::HTTP_NOT_FOUND);
		}

		$geoModel->fill->input();
		$geoModel->save();

		return response()->json([
			'msg' => Response::$statusTexts[Response::HTTP_OK]
		], Response::HTTP_OK);
	}

	public function removeGeo(Request $request, $id) {

		$validator = Validator::make(
			['id' => $id],
			['id' => 'required|string|min:36']
		);

		if ($validator->fails() === true) {
			return response()->json([
				'msg' => 'Validation failed',
				'err' => $validator->errors()
			], $validator->statusCode());
		}

		$geoModel = Geo::find($id);

		if ($geoModel === null) {
			return response()->json([
				'msg' => 'URL resource was not found'
			], Response::HTTP_NOT_FOUND);
		}

		$geoModel->delete();

		return response()->json([

		]);
	}

	public function createGeo(Request $request, $localeId) {

		$validator = Validator::make(array_merge($request->all(),[
			'localeId' => $localeId
		]), [
			'localeId' => 'required|string|min:36|exists:locale,id',
			'geo_type_id' => 'required|string|min:36|exists:geo_type,id',
			'parent_id' => 'string|min:36|exists:geo,id',
			'latitude' => 'required|string|min:1',
			'longitude' => 'required|string|min:1',
			'altitude' => 'required|string|min:1',
			'name' => 'required|string|min:1'
		]);

		if ($validator->fails() === true) {
			return response()->json([
				'msg' => 'Validation failed',
				'err' => $validator->errors()
			], $validator->statusCode());
		}

		$newGeoModel = new Geo;

		DB::transaction(function() use($request, $newGeoModel, $localeId) {
			$geoId = Uuid::uuid4();

			$geoTypeId = $request->input('geo_type_id');
			$parentId = $request->input('parent_id');
			$name = $request->input('name');
			$latitude = $request->input('latitude');
			$longitude = $request->input('longitude');
			$altitude = $request->input('altitude');

			$newGeoModel->id = $geoId;
			$newGeoModel->geo_type_id = $geoTypeId;
			$newGeoModel->parent_id = $parentId;
			$newGeoModel->latitude = $latitude;
			$newGeoModel->longitude = $longitude;
			$newGeoModel->altitude = $altitude;
			$newGeoModel->name_translation_id = TranslationHelper::createNewTranslation($name, $localeId);
			$newGeoModel->save();

		});

		return response()->json([
			'geo' => $newGeoModel
		], Response::HTTP_OK);
	}
}
