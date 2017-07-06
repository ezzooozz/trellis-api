<?php

namespace app\Http\Controllers;

use App\Library\DatabaseHelper;
use App\Library\FileHelper;
use App\Models\Epoch;
use App\Models\Device;
use DB;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;
use Laravel\Lumen\Routing\Controller;
use Validator;
use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\Local;
use Log;

class SyncController extends Controller
{
    public function heartbeat()
    {
        return response()->json([], Response::HTTP_OK);
    }

    public function store(Request $request, $deviceId)
    {
        $validator = Validator::make(array_merge($request->all(), [
            'id' => $deviceId
        ]), [
            'id' => 'required|string|min:36|exists:device,device_id'
        ]);

        if ($validator->fails() === true) {
            return response()->json([
                'msg' => 'Validation failed',
                'err' => $validator->errors()
            ], $validator->statusCode());
        }
    }

    public function download(Request $request, $deviceId)
    {
        $validator = Validator::make(array_merge($request->all(), [
            'id' => $deviceId
        ]), [
            'id' => 'required|string|min:14|exists:device,device_id',
            'table' => 'required|string|min:1'
        ]);

        if ($validator->fails() === true) {
            return response()->json([
                'msg' => 'Validation failed',
                'err' => $validator->errors()
            ], $validator->statusCode());
        };

        $response = [];

        $response["deviceId"] = $deviceId;
        $response["table"] = $request->input('table');

        $tableClass = str_replace(' ', '', ucwords(str_replace('_', ' ', $request->input('table'))));
        $className = "\\App\\Models\\$tableClass";
        $classModel = $className::all();

        $response["numRows"] = $classModel->count();
        $response["totalRows"] = $classModel->count();
        $response["rows"] = $classModel;

        return response()->json($response, Response::HTTP_OK);
    }

    public function upload(Request $request, $deviceId)
    {
        $validator = Validator::make(array_merge($request->all(), [
            'id' => $deviceId
        ]), [
            'id' => 'required|string|min:14|exists:device,device_id',
            'table' => 'required|string|min:1'
        ]);

        if ($validator->fails() === true) {
            return response()->json([
                'msg' => 'Validation failed',
                'err' => $validator->errors()
            ], $validator->statusCode());
        };

        DB::statement('SET FOREIGN_KEY_CHECKS = 0');
        foreach ($request->input('rows') as $row) {
            /*
            $newClassName = "\\App\\Models\\" . str_replace(' ', '', str_replace('_', '', ucwords($request->input('table'), '_')));
            $newClassName::create($row);
            */
            // Need to INSERT IGNORE to allow for resuming incomplete syncs
            $fields = implode(',', array_keys($row));
            $values = '?' . str_repeat(',?', count($row) - 1);
            $insertQuery = 'insert ignore into ' . $request->input('table') . ' (' . $fields . ') values (' . $values . ')';
            Log::debug($insertQuery);
            Log::debug(implode(", ", array_values($row)));
            DB::insert($insertQuery, array_values($row));
        }
        DB::statement('SET FOREIGN_KEY_CHECKS = 1');
        return response()->json([], Response::HTTP_OK);
    }

    public function listImages($deviceId)
    {
        //the fields are fileName:<string>, deviceId:<string>, action:<string>, length:<number>,base64:<string/base64>. Note that base64 uses no linefeeds
        $validator = Validator::make(array_merge([], [
            'id' => $deviceId
        ]), [
            'id' => 'required|string|min:14|exists:device,device_id'
        ]);

        if ($validator->fails() === true) {
            return response()->json([
                'msg' => 'Validation failed',
                'err' => $validator->errors()
            ], $validator->statusCode());
        };

        $returnArray = array();
        $adapter = new Local(storage_path() . '/respondent-photos');
        $filesystem = new Filesystem($adapter);

        $contents = $filesystem->listContents();

        foreach ($contents as $object) {
            if ($object['extension'] == "jpg") {
                $returnArray[] = array('fileName' => $object['path'], 'length' => $object['size']);
            }
        }

        return response()->json($returnArray, Response::HTTP_OK);
    }

    public function syncImages(Request $request, $deviceId)
    {
        //the fields are fileName:<string>, deviceId:<string>, action:<string>, length:<number>,base64:<string/base64>. Note that base64 uses no linefeeds
        $validator = Validator::make(array_merge($request->all(), [
            'id' => $deviceId
        ]), [
            'id' => 'required|string|min:14|exists:device,device_id',
            'fileName' => 'required|string|min:1',
            'action' => 'required|string|min:1',
            'base64' => 'string'
        ]);

        if ($validator->fails() === true) {
            return response()->json([
                'msg' => 'Validation failed',
                'err' => $validator->errors()
            ], $validator->statusCode());
        };

        if ($request->input('action') == 'up') {
            // TODO: replace hard-coded directory with config / env variable
            $adapter = new Local(storage_path() . '/respondent-photos');
            $filesystem = new Filesystem($adapter);
            $data = base64_decode($request->input('base64'));
            $filesystem->put($request->input('fileName'), $data);
        } else {
            $adapter = new Local(storage_path() . '/respondent-photos');
            $filesystem = new Filesystem($adapter);
            $exists = $filesystem->has($request->input('fileName'));
            if ($exists) {
                $contents = $filesystem->read($request->input('fileName'));
                $size = $filesystem->getSize($request->input('fileName'));

                $base64 = base64_encode($contents);

                return response()->json([
                    'fileName' => $request->input('fileName'),
                    'device_id' => $deviceId,
                    'length' => $size,
                    'base64' => $base64],
                    Response::HTTP_OK);
            }
        }
    }

    public function uploadSync(Request $request, $deviceId)
    {
        $validator = Validator::make(array_merge($request->all(), [
            'id' => $deviceId
        ]), [
            'id' => 'required|string|min:14|exists:device,device_id'
        ]);

        if ($validator->fails() === true) {
            return response()->json([
                'msg' => 'Validation failed',
                'err' => $validator->errors()
            ], $validator->statusCode());
        };

        DB::transaction(function () use ($request) {
            DB::statement('SET FOREIGN_KEY_CHECKS = 0');

            $data = $request->json()->all();  // returns array of nested key-values

            foreach ($data['tables'] as $table => $value) {
                foreach ($value['rows'] as $row) {
                    $table = DatabaseHelper::escape($table);
                    $fields = implode(',', array_map(DatabaseHelper::class . '::escape', array_keys($row)));
                    $values = implode(',', array_fill(0, count($row), '?'));
                    $insertQuery = <<<EOT
insert ignore into $table (
    $fields
) values (
    $values
);
EOT
;    // use INSERT IGNORE to prevent duplicate inserts (this is generally safe since id is a UUID)   //TODO update this to use INSERT ... ON DUPLICATE KEY UPDATE with most recent wins strategy and logging previous values

                    // Log::debug($insertQuery);
                    // Log::debug(implode(", ", array_values($row)));

                    DB::insert($insertQuery, array_values($row));
                }
            }

            DB::statement('SET FOREIGN_KEY_CHECKS = 1');

            ob_start();

            Artisan::call('trellis:check:mysql');

            $result = json_decode(ob_get_clean(), true);

            if (count($result)) {
                throw new \Exception('Foreign key consistency check failed for the following tables: ' . implode(', ', array_keys($result)));
            }
        });

        $epoch = Epoch::inc();

        Device::where('device_id', $deviceId)->update([
            'epoch' => $epoch
        ]); // update device's epoch.  <epoch>.sqlite.sql.zip must exist in order to download

        return response()->json([], Response::HTTP_OK);
    }

    public function downloadSync(Request $request, $deviceId)
    {
        $validator = Validator::make(array_merge($request->all(), [
            'id' => $deviceId
        ]), [
            'id' => 'required|string|min:14|exists:device,device_id'
        ]);

        if ($validator->fails() === true) {
            return response()->json([
                'msg' => 'Validation failed',
                'err' => $validator->errors()
            ], $validator->statusCode());
        };

        Artisan::call('trellis:export:snapshot');

        app()->configure('snapshot');   // save overhead by only loading config when needed

        $snapshotPath = FileHelper::storagePath(config('snapshot.directory'));
        $files = glob("$snapshotPath/*");

        if (count($files)) {
            $files = array_combine($files, array_map("filemtime", $files));
            $newestFilePath = array_keys($files, max($files))[0];
            $newestFileName = basename($newestFilePath);
            $hex = explode('.', $newestFileName)[0];
            $snapshotEpoch = Epoch::dec($hex)*1;
            $deviceEpoch = Device::where('device_id', $deviceId)->first()->epoch;

            if ($snapshotEpoch >= $deviceEpoch) {
                return response()->download($newestFilePath);   // if snapshot epoch >= than device's epoch, then return binary file download
            }
        }

        return response()->json([], Response::HTTP_ACCEPTED);   // if no snapshot epoch >= than device's epoch, then return 202 Accepted to make client retry later
    }
}
