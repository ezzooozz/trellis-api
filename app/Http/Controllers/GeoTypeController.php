<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Validator;
use App\Models\GeoType;
use Ramsey\Uuid\Uuid;

class GeoTypeController extends Controller
{
	public function getGeoType(Request $request, $id) {

		$validator = Validator::make(
			['id' => $id],
			['id' => 'required|string|min:36|exists:geo_type,id']
		);

		if ($validator->fails() === true) {
			return response()->json([
				'msg' => 'Validation failed',
				'err' => $validator->errors()
			], $validator->statusCode());
		}

		$geoTypeModel = GeoType::find($id);

		if ($geoTypeModel === null) {
			return response()->json([
				'msg' => 'URL resource not found'
			], Response::HTTP_OK);
		}

		return response()->json([
			'geoType' => $geoTypeModel
		], Response::HTTP_OK);
	}

	public function getAllGeoTypes(Request $request) {

		$geoTypeModel = GeoType::orderBy('name', 'asc')
			->get();

		return response()->json(
			['geoTypes' => $geoTypeModel],
			Response::HTTP_OK
		);
	}

	public function getAllEligibleGeoTypesOfParentGeo(Request $request) {

			$geoTypeModel = GeoType::where('geo_type.parent_id', $request->input('geoTypeId'))
				->get();

		return response()->json([
			'geoTypes' => $geoTypeModel
		], Response::HTTP_OK);
	}

	public function updateGeoType(Request $request, $id) {

		$validator = Validator::make(array_merge($request->all(),[
			'id' => $id
		]), [
			'id' => 'required|string|min:36',
			'parent_id' => 'string|min:36',
			'name' => 'string|min:1',
			'can_enumerator_add' => 'integer|min:1|max:1',
			'can_contain_respondent' => 'integer|min:1|max:1'
		]);

		if ($validator->fails() === true) {
			return response()->json([
				'msg' => 'Validation failed',
				'err' => $validator->errors()
			], $validator->statusCode());
		}

		$geoTypeModel = GeoType::find($id);

		if ($geoTypeModel === null) {
			return response()->json([
				'msg' => 'URL resource not found'
			], Response::HTTP_NOT_FOUND);
		}

		$geoTypeModel->fill->input();
		$geoTypeModel->save();

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

		$geoTypeModel = GeoType::find($id);

		if ($geoTypeModel === null) {
			return response()->json([
				'msg' => 'URL resource was not found'
			], Response::HTTP_NOT_FOUND);
		}

		$geoTypeModel->delete();

		return response()->json([

		]);
	}

	public function createGeoType(Request $request) {

		$validator = Validator::make($request->all(), [
			'parent_id' => 'string|min:36',
			'name' => 'required|string|min:1',
			'can_enumerator_add' => 'boolean',
			'can_contain_respondent' => 'boolean'
		]);

		if ($validator->fails() === true) {
			return response()->json([
				'msg' => 'Validation failed',
				'err' => $validator->errors()
			], $validator->statusCode());
		}

		$geoTypeId = Uuid::uuid4();
		$parentId = $request->input('parent_id');
		$name = $request->input('name');
		$canEnumeratorAdd = $request->input('can_enumerator_add') === null ? false : true;
		$canContainRespondent = $request->input('can_contain_respondent') === null ? false : true;

		$newGeoTypeModel = new GeoType;

		$newGeoTypeModel->id = $geoTypeId;
		$newGeoTypeModel->parent_id = $parentId;
		$newGeoTypeModel->name = $name;
		$newGeoTypeModel->can_enumerator_add = $canEnumeratorAdd;
		$newGeoTypeModel->can_contain_respondent = $canContainRespondent;

		$newGeoTypeModel->save();

		return response()->json([
			'geoType' => $newGeoTypeModel
		], Response::HTTP_OK);
	}
}
